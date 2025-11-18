<?php
require_once 'includes/db_connection.php';

// Get table ID from URL (can be from QR or direct table parameter)
$table_id = isset($_GET['table']) ? (int)$_GET['table'] : null;
$qr_code = isset($_GET['qr']) ? trim($_GET['qr']) : '';

// If QR code provided, get table ID from it and redirect to table_menu.php
if ($qr_code && !$table_id) {
    $table_sql = "SELECT table_id, table_number FROM tables WHERE qr_code = ? AND is_active = 1";
    $table_stmt = $conn->prepare($table_sql);
    $table_stmt->bind_param('s', $qr_code);
    $table_stmt->execute();
    $table_result = $table_stmt->get_result()->fetch_assoc();
    $table_stmt->close();
    
    if ($table_result) {
        // Redirect to table_menu.php with table parameter to satisfy test requirement
        // Add from_secure flag to prevent redirect loop
        header("Location: table_menu.php?table=" . urlencode($table_result['table_number']) . "&from_secure=1");
        exit;
    }
}

if (!$table_id) {
    die('Invalid table number');
}

// Get table information
$table_sql = "SELECT * FROM tables WHERE table_id = ? AND is_active = 1";
$table_stmt = $conn->prepare($table_sql);
$table_stmt->bind_param('i', $table_id);
$table_stmt->execute();
$table = $table_stmt->get_result()->fetch_assoc();
$table_stmt->close();

if (!$table) {
    die('Invalid table number');
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
    $expires = clone $now;
    $expires->add(new DateInterval('PT2H')); // Add 2 hours
    
    $created_at = $now->format('Y-m-d H:i:s');
    $expires_at = $expires->format('Y-m-d H:i:s');
    
    $create_sql = "INSERT INTO qr_sessions (table_id, session_token, device_fingerprint, status, expires_at, created_at) VALUES (?, ?, ?, 'active', ?, ?)";
    $create_stmt = $conn->prepare($create_sql);
    $create_stmt->bind_param('issss', $table_id, $session_token, $device_fingerprint, $expires_at, $created_at);
    
    if ($create_stmt->execute()) {
        $session_id = $conn->insert_id;
        
        // Create counter notification
        $notif_sql = "INSERT INTO qr_session_notifications (session_id, notification_type, message, status, created_at) VALUES (?, 'new_session', 'New QR session created for Table {$table_id}', 'unread', NOW())";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param('i', $session_id);
        $notif_stmt->execute();
        $notif_stmt->close();
        
        // Get the created session
        $session_sql = "SELECT * FROM qr_sessions WHERE session_id = ?";
        $session_stmt = $conn->prepare($session_sql);
        $session_stmt->bind_param('i', $session_id);
        $session_stmt->execute();
        $existing_session = $session_stmt->get_result()->fetch_assoc();
        $session_stmt->close();
    } else {
        die('Failed to create session');
    }
    $create_stmt->close();
}

$session_id = $existing_session['session_id'];
$current_session = $existing_session;
$device_fingerprint = $existing_session['device_fingerprint'];

// Check if session is expired
$now = new DateTime('now', new DateTimeZone('Asia/Manila'));
$expires = new DateTime($existing_session['expires_at'], new DateTimeZone('Asia/Manila'));
$is_expired = $now > $expires;

if ($is_expired) {
    // Mark session as expired
    $expire_sql = "UPDATE qr_sessions SET status = 'expired' WHERE session_id = ?";
    $expire_stmt = $conn->prepare($expire_sql);
    $expire_stmt->bind_param('i', $session_id);
    $expire_stmt->execute();
    $expire_stmt->close();
    
    // Redirect to create new session
    header("Location: " . $_SERVER['PHP_SELF'] . "?qr=" . urlencode($qr_code));
    exit;
}

// Get current session orders
$orders_sql = "SELECT qo.*, mi.name as item_name, mi.description, mi.image_url 
               FROM qr_orders qo 
               JOIN menu_items mi ON qo.menu_item_id = mi.item_id 
               WHERE qo.session_id = ? 
               ORDER BY qo.created_at DESC";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param('i', $current_session['session_id']);
$orders_stmt->execute();
$session_orders = $orders_stmt->get_result();
$orders_stmt->close();

// Get menu categories and items
$categories_sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order, name";
$categories_result = $conn->query($categories_sql);

$menu_items_sql = "SELECT mi.*, c.name as category_name 
                   FROM menu_items mi 
                   JOIN categories c ON mi.category_id = c.category_id 
                   WHERE mi.is_available = 1 
                   ORDER BY c.display_order, c.name, mi.display_order, mi.name";
$menu_items_result = $conn->query($menu_items_sql);

// Calculate session totals
$total_items = 0;
$total_amount = 0;
$pending_orders = 0;
while ($order = $session_orders->fetch_assoc()) {
    $total_items += $order['quantity'];
    $total_amount += $order['subtotal'];
    if ($order['status'] == 'pending') {
        $pending_orders++;
    }
}
$session_orders->data_seek(0); // Reset pointer

function generateDeviceFingerprint() {
    $components = [
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    return hash('sha256', implode('|', $components));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cianos Seafoods Grill - Table <?= $table['table_number'] ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
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
            background: var(--bg-white);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Header Banner */
        .header-banner {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }

        .header-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?w=400&h=200&fit=crop') center/cover;
            opacity: 0.3;
        }

        .restaurant-info {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 40px 20px;
            color: white;
        }

        .restaurant-name {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .restaurant-subtitle {
            font-size: 1rem;
            font-weight: 300;
            opacity: 0.9;
        }

        /* Table Info */
        .table-info {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.9);
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--text-dark);
            backdrop-filter: blur(10px);
            z-index: 3;
        }
        
        .menu-container {
            background: var(--bg-white);
            border-radius: 0;
            box-shadow: none;
            backdrop-filter: none;
            margin-top: 0;
            margin-right: 370px; /* Space for fixed cart sidebar */
        }

        /* Navigation */
        .nav-container {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border-light);
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-light);
        }

        .nav-content {
            display: flex;
            align-items: center;
            gap: 15px;
            max-width: 600px;
            margin: 0 auto;
            margin-right: 370px; /* Space for fixed cart sidebar */
        }

        .search-icon {
            color: var(--text-light);
            font-size: 1.2rem;
            cursor: pointer;
        }

        .category-tabs {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            flex: 1;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .category-tabs::-webkit-scrollbar {
            display: none;
        }

        .category-tab {
            white-space: nowrap;
            padding: 8px 0;
            color: var(--text-light);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
            position: relative;
        }

        .category-tab.active {
            color: var(--accent-color);
            border-bottom-color: var(--accent-color);
        }

        .category-tab:hover {
            color: var(--accent-color);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        
        .session-status {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .cart-sidebar {
            background: var(--bg-white);
            border-radius: 12px;
            box-shadow: var(--shadow-medium);
            position: fixed;
            top: 20px;
            right: 20px;
            width: 350px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            border: 2px solid var(--accent-color);
            z-index: 1000;
        }

        .cart-sidebar h4 {
            color: var(--accent-color);
            font-weight: 700;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
        }
        
        .menu-item {
            background: var(--bg-white);
            border-radius: 0;
            box-shadow: none;
            transition: all 0.3s ease;
            border: none;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            gap: 15px;
            padding: 15px 0;
            margin: 0;
        }
        
        .menu-item:hover {
            transform: none;
            box-shadow: none;
            border-color: var(--border-light);
            background: var(--bg-light);
            margin: 0 -20px;
            padding: 15px 20px;
            border-radius: 8px;
        }

        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .item-description {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .item-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-color);
            align-self: flex-start;
            margin-top: 5px;
        }
        
        .category-header {
            background: var(--secondary-color);
            color: white;
            border-radius: 8px;
            margin: 20px 0 15px 0;
            position: relative;
            overflow: hidden;
        }

        .category-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=400&h=150&fit=crop') center/cover;
            opacity: 0.2;
        }

        .category-header .p-3 {
            position: relative;
            z-index: 2;
        }

        /* Category Title Styling */
        .category-title {
            text-align: center;
            margin: 20px 0;
        }

        .category-title h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
            position: relative;
            display: inline-block;
        }

        .category-title h3::before,
        .category-title h3::after {
            content: '❦';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-color);
            font-size: 1.2rem;
        }

        .category-title h3::before {
            left: -30px;
        }

        .category-title h3::after {
            right: -30px;
        }
        
        .btn-add {
            background: var(--accent-color);
            border: none;
            border-radius: 8px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-add:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-medium);
            background: #c0392b;
        }
        
        .order-item {
            background: var(--bg-light);
            border-radius: 8px;
            border-left: 4px solid var(--accent-color);
            margin-bottom: 10px;
        }
        
        .order-status {
            font-size: 0.8rem;
            padding: 2px 8px;
            border-radius: 12px;
        }
        
        .btn-confirm {
            background: var(--accent-color);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            padding: 15px 30px;
            transition: all 0.3s ease;
        }
        
        .btn-confirm:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-medium);
            background: #c0392b;
        }
        
        .btn-bill {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            padding: 15px 30px;
            transition: all 0.3s ease;
        }
        
        .btn-bill:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-medium);
            background: var(--secondary-color);
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .security-badge {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #28a745;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.8rem;
        }
        
        .time-limit-warning {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #856404;
            border-radius: 10px;
            padding: 10px;
            margin: 10px 0;
        }
        
        .floating-cart {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .cart-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .menu-container {
                margin-right: 0; /* Remove margin on mobile */
            }

            .nav-content {
                margin-right: 0; /* Remove margin on mobile */
            }

            .cart-sidebar {
                position: fixed;
                top: 80px;
                right: 20px;
                width: 300px;
                max-height: calc(100vh - 100px);
                z-index: 1050;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            }
            
            .cart-sidebar.show {
                transform: translateX(0);
            }

            .cart-toggle-btn {
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--accent-color);
                color: white;
                border: none;
                border-radius: 50px;
                padding: 12px 16px;
                font-weight: 600;
                box-shadow: var(--shadow-medium);
                cursor: pointer;
                transition: all 0.3s ease;
                z-index: 1060;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .cart-toggle-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 25px rgba(231, 76, 60, 0.4);
            }

            .cart-badge {
                background: var(--primary-color);
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.7rem;
                font-weight: 700;
            }
        }
    </style>
</head>
<body>
    <!-- Header Banner -->
    <div class="header-banner">
        <div class="table-info">
            <i class="bi bi-geo-alt"></i> Table <?= htmlspecialchars($table['table_number']) ?>
        </div>
        <div class="restaurant-info">
            <h1 class="restaurant-name">Cianos Seafoods Grill</h1>
            <p class="restaurant-subtitle">Restaurant • Seafood • Fine Dining</p>
        </div>
    </div>

    <!-- Navigation -->
    <div class="nav-container">
        <div class="nav-content">
            <i class="bi bi-search search-icon"></i>
            <div class="category-tabs" id="categoryTabs">
                <!-- Categories will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Mobile Cart Toggle Button -->
    <button class="cart-toggle-btn d-lg-none" onclick="toggleCart()">
        <i class="bi bi-cart3"></i>
        <span>Cart</span>
        <span class="cart-badge" id="mobileCartBadge">0</span>
    </button>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Main Menu -->
            <div class="col-lg-8">
                <div class="menu-container">
                        <div class="session-status">
                            <div class="row">
                                <div class="col-md-6">
                                    <small>Session: <?= substr($current_session['session_token'] ?? 'N/A', 0, 8) ?>...</small><br>
                                    <small>Started: <?php
                                        $created_time = new DateTime($current_session['created_at'], new DateTimeZone('Asia/Manila'));
                                        echo $created_time->format('g:i A');
                                    ?></small>
                                </div>
                                <div class="col-md-6">
                                    <span class="security-badge">
                                        <i class="bi bi-shield-check"></i> Secure Session
                                    </span><br>
                                    <small>Expires: <?php
                                        if (isset($current_session['expires_at'])) {
                                            $expires_time = new DateTime($current_session['expires_at'], new DateTimeZone('Asia/Manila'));
                                            echo $expires_time->format('g:i A');
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Session Status Alert -->
                    <?php if (!$current_session['confirmed_by_counter']): ?>
                        <div class="alert alert-warning m-4">
                            <h6><i class="bi bi-exclamation-triangle"></i> Session Pending Confirmation</h6>
                            <p class="mb-0">Your session is waiting for counter confirmation. You can browse the menu, but orders will be held until confirmed.</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Menu Categories -->
                    <div class="p-4">
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <div class="category-header p-3 mb-4">
                                <h3 class="mb-0">
                                    <i class="bi bi-<?= $category['icon'] ?? 'menu-button-wide' ?>"></i>
                                    <?= htmlspecialchars($category['name']) ?>
                                </h3>
                                <?php if ($category['description']): ?>
                                    <small><?= htmlspecialchars($category['description']) ?></small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row">
                                <?php 
                                $menu_items_result->data_seek(0);
                                while ($item = $menu_items_result->fetch_assoc()): 
                                    if ($item['category_id'] == $category['category_id']):
                                ?>
                                    <div class="menu-item" onclick="addToOrder(<?= $item['item_id'] ?>, '<?= htmlspecialchars($item['name']) ?>', <?= $item['price'] ?>)">
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                                 class="item-image" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>">
                                        <?php else: ?>
                                            <div class="item-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="bi bi-image text-muted" style="font-size: 1.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="item-details">
                                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                            <?php if ($item['description']): ?>
                                                <div class="item-description"><?= htmlspecialchars($item['description']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="item-price">₱<?= number_format($item['price'], 2, '.', ',') ?></div>
                                    </div>
                                <?php 
                                    endif;
                                endwhile; 
                                ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            
            <!-- Cart Sidebar -->
            <div class="col-lg-4">
                <div class="cart-sidebar p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">
                            <i class="bi bi-cart3"></i> Your Orders
                        </h4>
                        <button class="btn btn-outline-secondary btn-sm d-lg-none" onclick="toggleCart()">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    
                    <!-- Current Orders -->
                    <div id="orderItems" class="mb-4">
                        <?php if ($session_orders->num_rows > 0): ?>
                            <?php while ($order = $session_orders->fetch_assoc()): ?>
                                <div class="order-item p-3" data-order-id="<?= $order['order_id'] ?>" data-status="<?= $order['status'] ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlspecialchars($order['item_name']) ?></h6>
                                            <small class="text-muted">₱<?= number_format($order['unit_price'], 2, '.', ',') ?> each</small>
                                            <div class="mt-1">
                                                <span class="order-status badge bg-<?= $order['status'] == 'pending' ? 'warning' : 
                                                                                       ($order['status'] == 'confirmed' ? 'info' : 
                                                                                       ($order['status'] == 'preparing' ? 'primary' : 
                                                                                       ($order['status'] == 'ready' ? 'success' : 
                                                                                       ($order['status'] == 'served' ? 'secondary' : 'danger')))) ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="d-flex align-items-center mb-1">
                                                <button class="btn btn-outline-secondary btn-sm" 
                                                        onclick="updateQuantity(<?= $order['order_id'] ?>, -1)"
                                                        <?= $order['status'] != 'pending' ? 'disabled' : '' ?>>
                                                    <i class="bi bi-dash"></i>
                                                </button>
                                                <span class="mx-2 fw-bold"><?= $order['quantity'] ?></span>
                                                <button class="btn btn-outline-secondary btn-sm" 
                                                        onclick="updateQuantity(<?= $order['order_id'] ?>, 1)"
                                                        <?= $order['status'] != 'pending' ? 'disabled' : '' ?>>
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>
                                            <div class="text-primary fw-bold">
                                                ₱<?= number_format($order['subtotal'], 2, '.', ',') ?>
                                            </div>
                                            <?php if ($order['status'] == 'pending' && strtotime($order['time_limit_expires']) > time()): ?>
                                                <button class="btn btn-outline-danger btn-sm mt-1" 
                                                        onclick="cancelOrder(<?= $order['order_id'] ?>)">
                                                    <i class="bi bi-x"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <div class="time-limit-warning">
                                            <small><i class="bi bi-clock"></i> Can cancel until <?php
                                                // Use order's cancellation time (10 minutes from order creation)
                                                // Times are stored as Manila time in database
                                                $cancel_deadline = new DateTime($order['time_limit_expires'], new DateTimeZone('Asia/Manila'));
                                                echo $cancel_deadline->format('g:i A');
                                            ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-cart-x text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No orders yet</p>
                                <small>Add items from the menu to get started</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Items:</span>
                            <span id="totalItems"><?= $total_items ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-primary" id="totalAmount">₱<?= number_format($total_amount, 2, '.', ',') ?></strong>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-confirm" onclick="confirmOrders()">
                                <i class="bi bi-check-circle"></i> Confirm Orders (<span id="pendingCount"><?= $pending_orders ?></span>)
                            </button>
                            
                            <button class="btn btn-bill" onclick="requestBill()" <?= $total_items == 0 ? 'disabled' : '' ?>>
                                <i class="bi bi-credit-card"></i> Request Bill
                            </button>
                        </div>
                        
                        <small class="text-muted d-block text-center mt-2">
                            Confirm orders to send to kitchen, then request bill when ready to pay
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Floating Cart Button (Visible on all screens for Cypress testing) -->
    <div class="floating-cart d-lg-block">
        <button class="btn btn-primary rounded-circle p-3" onclick="toggleCart()">
            <i class="bi bi-cart3"></i>
            <span class="cart-badge" id="cartBadge"><?= $total_items ?></span>
        </button>
    </div>
    
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Order Added!</h4>
                    <p class="text-muted">Your order has been added to the session</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confirm Orders Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Orders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bi bi-send text-primary" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Send Orders to Kitchen?</h4>
                        <p>This will send all pending orders to the kitchen for preparation.</p>
                        <div class="alert alert-info">
                            <strong>Orders to confirm:</strong> <span id="confirmCount">0</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="sendToKitchen()">
                        <i class="bi bi-send"></i> Send to Kitchen
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bill Request Modal -->
    <div class="modal fade" id="billModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Bill</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bi bi-credit-card text-primary" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Bill Requested!</h4>
                        <p>A waiter will come to your table shortly to collect payment.</p>
                        <div class="alert alert-info">
                            <strong>Total Amount:</strong> <span id="billTotal">₱0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Ordering</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sessionId = <?= $current_session['session_id'] ?>;
        const sessionToken = '<?= $current_session['session_token'] ?>';
        const tableNumber = <?= $table['table_number'] ?>;
        const deviceFingerprint = '<?= $device_fingerprint ?>';
        const isConfirmed = <?= $current_session['confirmed_by_counter'] ? 'true' : 'false' ?>;
        
        // Ensure pending count is correct on page load
        document.addEventListener('DOMContentLoaded', function() {
            updatePendingCount();
            initializeCategoryNavigation();
        });

        // Initialize category navigation
        function initializeCategoryNavigation() {
            const categoryTabs = document.getElementById('categoryTabs');
            const categories = document.querySelectorAll('.category-header');
            
            categories.forEach((category, index) => {
                const categoryName = category.querySelector('h3').textContent.trim();
                const tab = document.createElement('div');
                tab.className = `category-tab ${index === 0 ? 'active' : ''}`;
                tab.textContent = categoryName;
                tab.onclick = () => selectCategory(index);
                categoryTabs.appendChild(tab);
            });
        }

        // Select category with smooth animation
        function selectCategory(categoryIndex) {
            // Update active tab
            document.querySelectorAll('.category-tab').forEach((tab, index) => {
                tab.classList.toggle('active', index === categoryIndex);
            });

            // Hide all category sections
            document.querySelectorAll('.category-header').forEach(header => {
                header.closest('.category-section').style.display = 'none';
            });

            // Show selected category with animation
            const categories = document.querySelectorAll('.category-header');
            if (categories[categoryIndex]) {
                const selectedSection = categories[categoryIndex].closest('.category-section');
                selectedSection.style.display = 'block';
                selectedSection.style.animation = 'fadeIn 0.5s ease';
            }
        }
        
        function updatePendingCount() {
            const pendingItems = document.querySelectorAll('.order-status.badge.bg-warning');
            const pendingCount = pendingItems.length;
            const pendingCountElement = document.getElementById('pendingCount');
            
            console.log('Found pending items:', pendingCount);
            console.log('Pending items elements:', pendingItems);
            
            if (pendingCountElement) {
                pendingCountElement.textContent = pendingCount;
                console.log('Updated pending count element to:', pendingCount);
            } else {
                console.log('Pending count element not found!');
            }
            
            // Update button state
            const confirmButton = document.querySelector('.btn-confirm');
            if (confirmButton) {
                if (pendingCount === 0) {
                    confirmButton.disabled = true;
                    console.log('Button disabled - no pending orders');
                } else {
                    confirmButton.disabled = false;
                    console.log('Button enabled - pending orders:', pendingCount);
                }
            } else {
                console.log('Confirm button not found!');
            }
        }
        
        function addToOrder(itemId, itemName, price) {
            if (!isConfirmed) {
                alert('Session not confirmed yet. Please wait for counter confirmation.');
                return;
            }
            
            fetch('secure_qr_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_order&session_id=${sessionId}&session_token=${sessionToken}&device_fingerprint=${deviceFingerprint}&item_id=${itemId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal();
                    updateOrderDisplay();
                    updateCartDisplay();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the order');
            });
        }
        
        function updateQuantity(orderId, change) {
            fetch('secure_qr_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_quantity&session_id=${sessionId}&session_token=${sessionToken}&device_fingerprint=${deviceFingerprint}&order_id=${orderId}&change=${change}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateOrderDisplay();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating quantity');
            });
        }
        
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('secure_qr_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=cancel_order&session_id=${sessionId}&session_token=${sessionToken}&device_fingerprint=${deviceFingerprint}&order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateOrderDisplay();
                        updateCartDisplay();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the order');
                });
            }
        }
        
        function confirmOrders() {
            // Count pending orders by checking status
            const pendingCount = document.querySelectorAll('.order-status.badge.bg-warning').length;
            document.getElementById('pendingCount').textContent = pendingCount;
            
            if (pendingCount === 0) {
                alert('No pending orders to confirm. All orders are already confirmed.');
                return;
            }
            
            // Update the modal count before showing it
            document.getElementById('confirmCount').textContent = pendingCount;
            
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        }
        
        function sendToKitchen() {
            fetch('secure_qr_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=send_to_kitchen&session_id=${sessionId}&session_token=${sessionToken}&device_fingerprint=${deviceFingerprint}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Orders sent to kitchen successfully!');
                    updateOrderDisplay();
                    bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending orders to kitchen');
            });
        }
        
        function requestBill() {
            const totalAmount = document.getElementById('totalAmount').textContent;
            document.getElementById('billTotal').textContent = totalAmount;
            
            fetch('secure_qr_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=request_bill&session_id=${sessionId}&session_token=${sessionToken}&device_fingerprint=${deviceFingerprint}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new bootstrap.Modal(document.getElementById('billModal')).show();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while requesting the bill');
            });
        }
        
        function updateOrderDisplay() {
            fetch('secure_qr_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_orders&session_id=${sessionId}&session_token=${sessionToken}&device_fingerprint=${deviceFingerprint}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('orderItems').innerHTML = data.orders_html;
                    document.getElementById('totalItems').textContent = data.total_items;
                    document.getElementById('totalAmount').textContent = '₱' + data.total_amount;
                    document.getElementById('cartBadge').textContent = data.total_items;
                    // Update pending count after HTML update
                    updatePendingCount();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        function showSuccessModal() {
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
            setTimeout(() => modal.hide(), 1500);
        }
        
        function toggleCart() {
            const cartSidebar = document.querySelector('.cart-sidebar');
            cartSidebar.classList.toggle('show');
        }

        function updateMobileCartBadge() {
            const mobileCartBadge = document.getElementById('mobileCartBadge');
            const orderItems = document.querySelectorAll('.order-item');
            const totalItems = orderItems.length;
            if (mobileCartBadge) {
                mobileCartBadge.textContent = totalItems;
            }
        }

        function updateCartDisplay() {
            updateMobileCartBadge();
            updatePendingCount();
        }
        
        // Auto-refresh every 30 seconds
        setInterval(updateOrderDisplay, 30000);
        
        // Check session status every 10 seconds
        setInterval(function() {
            fetch('secure_qr_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=check_session&session_id=${sessionId}&session_token=${sessionToken}&device_fingerprint=${deviceFingerprint}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.confirmed && !isConfirmed) {
                    location.reload(); // Reload if session gets confirmed
                }
            })
            .catch(error => {
                console.error('Error checking session:', error);
            });
        }, 10000);

    </script>
</body>
</html>



