<?php
require_once 'includes/db_connection.php';

// Get table ID from URL (can be from QR or direct table parameter)
$table_id = isset($_GET['table']) ? (int)$_GET['table'] : null;
$qr_code = isset($_GET['qr']) ? trim($_GET['qr']) : '';
$test_mode = isset($_GET['test']) ? true : false;

// Test mode - use table 1 for testing
if ($test_mode && !$table_id) {
    $table_id = 1;
}

// If QR code provided, get table ID from it
if ($qr_code && !$table_id) {
    $table_sql = "SELECT table_id FROM tables WHERE qr_code = ? AND is_active = 1";
    $table_stmt = $conn->prepare($table_sql);
    $table_stmt->bind_param('s', $qr_code);
    $table_stmt->execute();
    $table_result = $table_stmt->get_result()->fetch_assoc();
    $table_stmt->close();
    
    if ($table_result) {
        $table_id = $table_result['table_id'];
    }
}

if (!$table_id) {
    if ($test_mode) {
        // In test mode, create a dummy table if none exists
        $table_id = 1;
    } else {
        die('Invalid table or QR code. Add ?test=1 to URL for testing mode.');
    }
}

// Get table information
$table_sql = "SELECT * FROM tables WHERE table_id = ? AND is_active = 1";
$table_stmt = $conn->prepare($table_sql);
$table_stmt->bind_param('i', $table_id);
$table_stmt->execute();
$table = $table_stmt->get_result()->fetch_assoc();
$table_stmt->close();

if (!$table) {
    die('Table not found or inactive');
}

// Check if session exists, if not create one
$session_sql = "SELECT * FROM qr_sessions WHERE table_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1";
$session_stmt = $conn->prepare($session_sql);
$session_stmt->bind_param('i', $table_id);
$session_stmt->execute();
$existing_session = $session_stmt->get_result()->fetch_assoc();
$session_stmt->close();

if (!$existing_session) {
    // Create new session only if no active session exists
    $session_token = bin2hex(random_bytes(16));
    $device_fingerprint = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    
    $insert_sql = "INSERT INTO qr_sessions (table_id, session_token, device_fingerprint, status, created_at) VALUES (?, ?, ?, 'active', ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param('isss', $table_id, $session_token, $device_fingerprint, $now->format('Y-m-d H:i:s'));
    $insert_stmt->execute();
    $session_id = $conn->insert_id;
    $insert_stmt->close();
} else {
    $session_id = $existing_session['session_id'];
    $session_token = $existing_session['session_token'];
}

// Get menu categories with items
$categories_sql = "SELECT c.*, 
                   COUNT(mi.item_id) as item_count
                   FROM categories c 
                   LEFT JOIN menu_items mi ON c.category_id = mi.category_id AND mi.is_available = 1
                   WHERE c.is_active = 1 
                   GROUP BY c.category_id 
                   ORDER BY c.sort_order, c.name";
$categories_result = $conn->query($categories_sql);

// Get all menu items with variations
$items_sql = "SELECT mi.*, c.name as category_name, c.category_id,
              GROUP_CONCAT(
                  CONCAT(mv.variation_name, '|', mv.price_adjustment, '|', mv.is_default) 
                  ORDER BY mv.is_default DESC, mv.variation_name 
                  SEPARATOR '||'
              ) as variations
              FROM menu_items mi
              JOIN categories c ON mi.category_id = c.category_id
              LEFT JOIN menu_variations mv ON mi.item_id = mv.menu_item_id
              WHERE mi.is_available = 1 AND c.is_active = 1
              GROUP BY mi.item_id
              ORDER BY c.sort_order, c.name, mi.sort_order, mi.name";
$items_result = $conn->query($items_sql);

// Organize items by category
$items_by_category = [];
while ($item = $items_result->fetch_assoc()) {
    $category_id = $item['category_id'];
    if (!isset($items_by_category[$category_id])) {
        $items_by_category[$category_id] = [];
    }
    
    // Parse variations
    $variations = [];
    if ($item['variations']) {
        $variation_pairs = explode('||', $item['variations']);
        foreach ($variation_pairs as $pair) {
            $parts = explode('|', $pair);
            if (count($parts) >= 3) {
                $variations[] = [
                    'name' => $parts[0],
                    'price_adjustment' => floatval($parts[1]),
                    'is_default' => $parts[2] == '1'
                ];
            }
        }
    }
    $item['parsed_variations'] = $variations;
    $items_by_category[$category_id][] = $item;
}

// Get current cart items
$cart_sql = "SELECT qoi.*, mi.name as item_name, mi.base_price, mi.image_url,
             mv.variation_name, mv.price_adjustment
             FROM qr_order_items qoi
             JOIN menu_items mi ON qoi.menu_item_id = mi.item_id
             LEFT JOIN menu_variations mv ON qoi.variation_id = mv.variation_id
             WHERE qoi.session_id = ?";
$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param('i', $session_id);
$cart_stmt->execute();
$cart_items = $cart_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cart_stmt->close();

// Calculate cart total
$cart_total = 0;
foreach ($cart_items as $item) {
    $item_price = $item['base_price'] + ($item['price_adjustment'] ?? 0);
    $cart_total += $item_price * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cianos Fine Dining - Table <?= htmlspecialchars($table['table_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #e74c3c;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --bg-light: #f8f9fa;
            --bg-white: #ffffff;
            --border-light: #e9ecef;
            --shadow-light: 0 2px 10px rgba(0,0,0,0.08);
            --shadow-medium: 0 4px 20px rgba(0,0,0,0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border-light);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-light);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }

        .table-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .cart-toggle {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .cart-toggle:hover {
            background: #c0392b;
            transform: translateY(-1px);
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Main Layout */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }

        /* Categories Sidebar */
        .categories-sidebar {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .categories-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent-color);
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            cursor: pointer;
            border-bottom: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }

        .category-item:hover {
            color: var(--accent-color);
        }

        .category-item.active {
            color: var(--accent-color);
            font-weight: 600;
        }

        .category-name {
            font-size: 0.95rem;
        }

        .category-arrow {
            transition: transform 0.3s ease;
            color: var(--text-light);
        }

        .category-item.active .category-arrow {
            transform: rotate(90deg);
            color: var(--accent-color);
        }

        .category-count {
            background: var(--bg-light);
            color: var(--text-light);
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        /* Menu Content */
        .menu-content {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
        }

        .category-section {
            display: none;
        }

        .category-section.active {
            display: block;
        }

        .category-header {
            margin-bottom: 1.5rem;
        }

        .category-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .category-description {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* Carousel */
        .carousel-container {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
        }

        .carousel-wrapper {
            display: flex;
            transition: transform 0.3s ease;
            gap: 1rem;
        }

        .carousel-item {
            min-width: 280px;
            background: var(--bg-white);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
        }

        .carousel-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .item-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: var(--bg-light);
        }

        .item-content {
            padding: 1rem;
        }

        .item-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .item-description {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }

        .item-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 0.75rem;
        }

        .variation-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border-light);
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            background: var(--bg-white);
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .quantity-btn {
            background: var(--bg-light);
            border: 1px solid var(--border-light);
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }

        .quantity-display {
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }

        .add-to-cart-btn {
            width: 100%;
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: #c0392b;
            transform: translateY(-1px);
        }

        .add-to-cart-btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            transform: none;
        }

        /* Carousel Navigation */
        .carousel-nav {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .nav-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--border-light);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-dot.active {
            background: var(--accent-color);
        }

        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--bg-white);
            border: 1px solid var(--border-light);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
            z-index: 10;
        }

        .carousel-arrow:hover {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }

        .carousel-arrow.left {
            left: -20px;
        }

        .carousel-arrow.right {
            right: -20px;
        }

        /* Cart Sidebar */
        .cart-sidebar {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent-color);
        }

        .cart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .cart-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--text-light);
            cursor: pointer;
        }

        .cart-items {
            max-height: 400px;
            overflow-y: auto;
        }

        .cart-item {
            display: flex;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-light);
        }

        .cart-item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            background: var(--bg-light);
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .cart-item-variation {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .cart-item-price {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--accent-color);
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cart-quantity-btn {
            background: var(--bg-light);
            border: 1px solid var(--border-light);
            width: 24px;
            height: 24px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .cart-remove-btn {
            background: var(--accent-color);
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .cart-total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--border-light);
        }

        .cart-total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .cart-total-label {
            font-weight: 600;
            color: var(--primary-color);
        }

        .cart-total-amount {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--accent-color);
        }

        .checkout-btn {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .checkout-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-1px);
        }

        .checkout-btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            transform: none;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 1rem;
            }

            .categories-sidebar {
                order: 2;
                position: static;
            }

            .menu-content {
                order: 1;
            }

            .cart-sidebar {
                position: fixed;
                top: 0;
                right: -100%;
                width: 100%;
                height: 100vh;
                z-index: 2000;
                transition: right 0.3s ease;
                overflow-y: auto;
            }

            .cart-sidebar.open {
                right: 0;
            }

            .carousel-item {
                min-width: 250px;
            }

            .carousel-arrow {
                display: none;
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .category-section.active {
            animation: slideIn 0.3s ease;
        }

        /* Empty State */
        .empty-cart {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
        }

        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--border-light);
        }

        .empty-menu {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
        }

        .empty-menu i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--border-light);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <a href="#" class="logo">Cianos Fine Dining</a>
            <div class="table-info">
                <i class="bi bi-geo-alt"></i>
                <span>Table <?= htmlspecialchars($table['table_number']) ?></span>
                <?php if ($test_mode): ?>
                    <span class="badge bg-warning text-dark ms-2">TEST MODE</span>
                <?php endif; ?>
            </div>
            <button class="cart-toggle" onclick="toggleCart()">
                <i class="bi bi-cart3"></i>
                Cart
                <span class="cart-badge" id="cartBadge"><?= count($cart_items) ?></span>
            </button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Categories Sidebar -->
        <div class="categories-sidebar">
            <h3 class="categories-title">View Menu</h3>
            <?php while ($category = $categories_result->fetch_assoc()): ?>
                <div class="category-item" onclick="selectCategory(<?= $category['category_id'] ?>)">
                    <span class="category-name"><?= htmlspecialchars($category['name']) ?></span>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="category-count"><?= $category['item_count'] ?></span>
                        <i class="bi bi-chevron-right category-arrow"></i>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Menu Content -->
        <div class="menu-content">
            <?php foreach ($items_by_category as $category_id => $items): ?>
                <div class="category-section" id="category-<?= $category_id ?>">
                    <div class="category-header">
                        <h2 class="category-title"><?= htmlspecialchars($items[0]['category_name']) ?></h2>
                        <p class="category-description">Discover our carefully crafted <?= strtolower($items[0]['category_name']) ?> selection</p>
                    </div>
                    
                    <div class="carousel-container">
                        <div class="carousel-wrapper" id="carousel-<?= $category_id ?>">
                            <?php foreach ($items as $item): ?>
                                <div class="carousel-item">
                                    <img src="<?= htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/280x180?text=No+Image') ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>" 
                                         class="item-image">
                                    <div class="item-content">
                                        <h4 class="item-name"><?= htmlspecialchars($item['name']) ?></h4>
                                        <p class="item-description"><?= htmlspecialchars($item['description']) ?></p>
                                        <div class="item-price">₱<?= number_format($item['base_price'], 2) ?></div>
                                        
                                        <?php if (!empty($item['parsed_variations'])): ?>
                                            <select class="variation-select" id="variation-<?= $item['item_id'] ?>">
                                                <?php foreach ($item['parsed_variations'] as $variation): ?>
                                                    <option value="<?= $variation['name'] ?>" 
                                                            data-price="<?= $item['base_price'] + $variation['price_adjustment'] ?>"
                                                            <?= $variation['is_default'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($variation['name']) ?>
                                                        <?php if ($variation['price_adjustment'] != 0): ?>
                                                            (<?= $variation['price_adjustment'] > 0 ? '+' : '' ?>₱<?= number_format($variation['price_adjustment'], 2) ?>)
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                        
                                        <div class="quantity-controls">
                                            <button class="quantity-btn" onclick="changeQuantity(<?= $item['item_id'] ?>, -1)">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <span class="quantity-display" id="qty-<?= $item['item_id'] ?>">1</span>
                                            <button class="quantity-btn" onclick="changeQuantity(<?= $item['item_id'] ?>, 1)">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                        
                                        <button class="add-to-cart-btn" onclick="addToCart(<?= $item['item_id'] ?>)">
                                            <i class="bi bi-cart-plus"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Carousel Navigation -->
                        <div class="carousel-nav" id="nav-<?= $category_id ?>">
                            <!-- Dots will be generated by JavaScript -->
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Cart Sidebar -->
        <div class="cart-sidebar" id="cartSidebar">
            <div class="cart-header">
                <h3 class="cart-title">Your Order</h3>
                <button class="cart-close" onclick="toggleCart()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            
            <div class="cart-items" id="cartItems">
                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <i class="bi bi-cart-x"></i>
                        <p>Your cart is empty</p>
                        <small>Add some delicious items to get started!</small>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" id="cart-item-<?= $item['order_item_id'] ?>">
                            <img src="<?= htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/50x50?text=No+Image') ?>" 
                                 alt="<?= htmlspecialchars($item['item_name']) ?>" 
                                 class="cart-item-image">
                            <div class="cart-item-details">
                                <div class="cart-item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <?php if ($item['variation_name']): ?>
                                    <div class="cart-item-variation"><?= htmlspecialchars($item['variation_name']) ?></div>
                                <?php endif; ?>
                                <div class="cart-item-price">₱<?= number_format(($item['base_price'] + ($item['price_adjustment'] ?? 0)) * $item['quantity'], 2) ?></div>
                            </div>
                            <div class="cart-item-controls">
                                <button class="cart-quantity-btn" onclick="updateCartQuantity(<?= $item['order_item_id'] ?>, <?= $item['quantity'] - 1 ?>)">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <span><?= $item['quantity'] ?></span>
                                <button class="cart-quantity-btn" onclick="updateCartQuantity(<?= $item['order_item_id'] ?>, <?= $item['quantity'] + 1 ?>)">
                                    <i class="bi bi-plus"></i>
                                </button>
                                <button class="cart-remove-btn" onclick="removeFromCart(<?= $item['order_item_id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($cart_items)): ?>
                <div class="cart-total">
                    <div class="cart-total-row">
                        <span class="cart-total-label">Total:</span>
                        <span class="cart-total-amount">₱<?= number_format($cart_total, 2) ?></span>
                    </div>
                    <button class="checkout-btn" onclick="checkout()">
                        <i class="bi bi-credit-card"></i> Request Bill
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentCategory = null;
        let cartItems = <?= json_encode($cart_items) ?>;
        let sessionId = <?= $session_id ?>;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Select first category by default
            const firstCategory = document.querySelector('.category-item');
            if (firstCategory) {
                firstCategory.click();
            }
            
            // Initialize carousels
            initializeCarousels();
        });

        function selectCategory(categoryId) {
            // Update active category in sidebar
            document.querySelectorAll('.category-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            // Show selected category content
            document.querySelectorAll('.category-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById('category-' + categoryId).classList.add('active');

            currentCategory = categoryId;
        }

        function initializeCarousels() {
            document.querySelectorAll('.carousel-container').forEach(container => {
                const wrapper = container.querySelector('.carousel-wrapper');
                const items = wrapper.querySelectorAll('.carousel-item');
                const nav = container.querySelector('.carousel-nav');
                
                if (items.length > 0) {
                    // Create navigation dots
                    nav.innerHTML = '';
                    const totalPages = Math.ceil(items.length / getItemsPerPage());
                    
                    for (let i = 0; i < totalPages; i++) {
                        const dot = document.createElement('div');
                        dot.className = 'nav-dot';
                        if (i === 0) dot.classList.add('active');
                        dot.onclick = () => goToPage(container, i);
                        nav.appendChild(dot);
                    }
                }
            });
        }

        function getItemsPerPage() {
            return window.innerWidth <= 768 ? 1 : 3;
        }

        function goToPage(container, page) {
            const wrapper = container.querySelector('.carousel-wrapper');
            const itemsPerPage = getItemsPerPage();
            const translateX = -page * (280 + 16) * itemsPerPage; // 280px item width + 16px gap
            
            wrapper.style.transform = `translateX(${translateX}px)`;
            
            // Update active dot
            container.querySelectorAll('.nav-dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === page);
            });
        }

        function changeQuantity(itemId, change) {
            const display = document.getElementById('qty-' + itemId);
            let quantity = parseInt(display.textContent) + change;
            quantity = Math.max(1, quantity);
            display.textContent = quantity;
        }

        function addToCart(itemId) {
            const quantity = parseInt(document.getElementById('qty-' + itemId).textContent);
            const variationSelect = document.getElementById('variation-' + itemId);
            const variation = variationSelect ? variationSelect.value : null;
            
            // Add to cart via AJAX
            fetch('secure_qr_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add_to_cart',
                    session_id: sessionId,
                    menu_item_id: itemId,
                    quantity: quantity,
                    variation: variation
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartDisplay();
                    showNotification('Item added to cart!', 'success');
                } else {
                    showNotification(data.message || 'Error adding item to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error adding item to cart', 'error');
            });
        }

        function updateCartQuantity(itemId, newQuantity) {
            if (newQuantity <= 0) {
                removeFromCart(itemId);
                return;
            }

            fetch('secure_qr_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_cart_quantity',
                    order_item_id: itemId,
                    quantity: newQuantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartDisplay();
                } else {
                    showNotification(data.message || 'Error updating cart', 'error');
                }
            });
        }

        function removeFromCart(itemId) {
            fetch('secure_qr_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove_from_cart',
                    order_item_id: itemId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartDisplay();
                    showNotification('Item removed from cart', 'success');
                } else {
                    showNotification(data.message || 'Error removing item', 'error');
                }
            });
        }

        function updateCartDisplay() {
            // Reload cart items
            fetch('secure_qr_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_cart_items',
                    session_id: sessionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('cartItems').innerHTML = data.html;
                    document.getElementById('cartBadge').textContent = data.count;
                }
            });
        }

        function toggleCart() {
            const cartSidebar = document.getElementById('cartSidebar');
            cartSidebar.classList.toggle('open');
        }

        function checkout() {
            if (cartItems.length === 0) {
                showNotification('Your cart is empty', 'error');
                return;
            }

            // Redirect to checkout or show checkout modal
            window.location.href = 'bill_out.php?session_id=' + sessionId;
        }

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            initializeCarousels();
        });
    </script>
</body>
</html>
