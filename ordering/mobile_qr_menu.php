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
    if ($test_mode) {
        // Create dummy table data for testing
        $table = [
            'table_id' => 1,
            'table_number' => '1',
            'table_name' => 'Table 1',
            'location' => 'Main Dining',
            'is_active' => 1
        ];
    } else {
        die('Table not found or inactive');
    }
}

// Dummy categories and menu items for visual testing
$dummy_categories = [
    [
        'category_id' => 1,
        'name' => 'Appetizers',
        'description' => 'Start your meal with our fresh appetizers',
        'icon' => 'bi-apple',
        'banner_image' => 'https://images.unsplash.com/photo-1551218808-94e220e084d2?w=400&h=200&fit=crop',
        'items' => [
            [
                'item_id' => 1,
                'name' => 'Crispy Calamari',
                'description' => 'Fresh squid rings with garlic aioli',
                'price' => 12.00,
                'image' => 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=100&h=100&fit=crop',
                'dietary' => 'Contains gluten, seafood'
            ],
            [
                'item_id' => 2,
                'name' => 'Shrimp Cocktail',
                'description' => 'Jumbo shrimp with cocktail sauce',
                'price' => 15.00,
                'image' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=100&h=100&fit=crop',
                'dietary' => 'Contains seafood'
            ]
        ]
    ],
    [
        'category_id' => 2,
        'name' => 'Main Courses',
        'description' => 'Served with rice or fries',
        'icon' => 'bi-fish',
        'banner_image' => 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=400&h=200&fit=crop',
        'items' => [
            [
                'item_id' => 3,
                'name' => 'Grilled Salmon',
                'description' => 'Atlantic salmon with lemon butter sauce',
                'price' => 24.00,
                'image' => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=100&h=100&fit=crop',
                'dietary' => 'Contains fish, dairy'
            ],
            [
                'item_id' => 4,
                'name' => 'Lobster Thermidor',
                'description' => 'Fresh lobster with creamy cheese sauce',
                'price' => 35.00,
                'image' => 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=100&h=100&fit=crop',
                'dietary' => 'Contains shellfish, dairy, gluten'
            ],
            [
                'item_id' => 5,
                'name' => 'Fish & Chips',
                'description' => 'Beer-battered cod with crispy fries',
                'price' => 18.00,
                'image' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=100&h=100&fit=crop',
                'dietary' => 'Contains fish, gluten'
            ]
        ]
    ],
    [
        'category_id' => 3,
        'name' => 'Drinks',
        'description' => 'Fresh beverages and cocktails',
        'icon' => 'bi-cup',
        'banner_image' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?w=400&h=200&fit=crop',
        'items' => [
            [
                'item_id' => 6,
                'name' => 'Fresh Lemonade',
                'description' => 'House-made with fresh lemons',
                'price' => 5.00,
                'image' => 'https://images.unsplash.com/photo-1621263764928-df1444c5e859?w=100&h=100&fit=crop',
                'dietary' => 'Vegan friendly'
            ],
            [
                'item_id' => 7,
                'name' => 'Tropical Smoothie',
                'description' => 'Mango, pineapple, and coconut',
                'price' => 8.00,
                'image' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?w=100&h=100&fit=crop',
                'dietary' => 'Contains dairy'
            ]
        ]
    ],
    [
        'category_id' => 4,
        'name' => 'Desserts',
        'description' => 'Sweet endings to your meal',
        'icon' => 'bi-cake',
        'banner_image' => 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=400&h=200&fit=crop',
        'items' => [
            [
                'item_id' => 8,
                'name' => 'Chocolate Lava Cake',
                'description' => 'Warm chocolate cake with vanilla ice cream',
                'price' => 9.00,
                'image' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=100&h=100&fit=crop',
                'dietary' => 'Contains gluten, dairy, eggs'
            ]
        ]
    ]
];

// Get current cart items (if any)
$cart_items = [];
$cart_total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cianos Seafoods Grill - Table <?= htmlspecialchars($table['table_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
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
            font-family: 'Inter', sans-serif;
            background: var(--bg-white);
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Header with Banner */
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

        /* Main Content */
        .main-content {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Category Section */
        .category-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .category-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .category-banner {
            width: 100%;
            height: 150px;
            border-radius: 12px;
            margin: 20px 0;
            overflow: hidden;
            position: relative;
        }

        .category-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .category-banner::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(0,0,0,0.3), transparent);
        }

        .category-title {
            text-align: center;
            margin: 20px 0;
        }

        .category-title h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            position: relative;
            display: inline-block;
        }

        .category-title h2::before,
        .category-title h2::after {
            content: '❦';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-color);
            font-size: 1.5rem;
        }

        .category-title h2::before {
            left: -40px;
        }

        .category-title h2::after {
            right: -40px;
        }

        .category-subtitle {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-top: 5px;
        }

        /* Menu Items */
        .menu-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }

        .menu-item:hover {
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

        .item-dietary {
            font-size: 0.8rem;
            color: var(--text-light);
            font-style: italic;
        }

        .item-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-color);
            align-self: flex-start;
            margin-top: 5px;
        }

        /* Cart Button */
        .cart-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 20px;
            font-weight: 600;
            box-shadow: var(--shadow-medium);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cart-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(231, 76, 60, 0.4);
        }

        .cart-badge {
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
        }

        /* Cart Modal */
        .cart-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            display: none;
            align-items: flex-end;
        }

        .cart-modal.show {
            display: flex;
        }

        .cart-content {
            background: var(--bg-white);
            border-radius: 20px 20px 0 0;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        .cart-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .cart-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
        }

        .cart-items {
            padding: 20px;
        }

        .cart-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-light);
        }

        .cart-item-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .cart-item-price {
            color: var(--accent-color);
            font-weight: 600;
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            background: var(--bg-light);
            border: 1px solid var(--border-light);
            width: 30px;
            height: 30px;
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

        .cart-total {
            padding: 20px;
            border-top: 2px solid var(--border-light);
            background: var(--bg-light);
        }

        .cart-total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .cart-total-label {
            font-weight: 600;
            color: var(--text-dark);
        }

        .cart-total-amount {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--accent-color);
        }

        .checkout-btn {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .checkout-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-1px);
        }

        /* Empty State */
        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }

        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--border-light);
        }

        /* Table Info */
        .table-info {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.9);
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--text-dark);
            backdrop-filter: blur(10px);
            z-index: 100;
        }

        /* Test Mode Badge */
        .test-mode {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--accent-color);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 100;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .restaurant-name {
                font-size: 2rem;
            }
            
            .category-title h2 {
                font-size: 1.5rem;
            }
            
            .category-title h2::before,
            .category-title h2::after {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Table Info -->
    <div class="table-info">
        <i class="bi bi-geo-alt"></i> Table <?= htmlspecialchars($table['table_number']) ?>
    </div>

    <!-- Test Mode Badge -->
    <?php if ($test_mode): ?>
        <div class="test-mode">TEST MODE</div>
    <?php endif; ?>

    <!-- Header Banner -->
    <div class="header-banner">
        <div class="restaurant-info">
            <h1 class="restaurant-name">Cianos Seafoods Grill</h1>
            <p class="restaurant-subtitle">Restaurant • Seafood • Fine Dining</p>
        </div>
    </div>

    <!-- Navigation -->
    <div class="nav-container">
        <div class="nav-content">
            <i class="bi bi-search search-icon"></i>
            <div class="category-tabs">
                <?php foreach ($dummy_categories as $index => $category): ?>
                    <div class="category-tab <?= $index === 0 ? 'active' : '' ?>" 
                         onclick="selectCategory(<?= $category['category_id'] ?>)">
                        <?= htmlspecialchars($category['name']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php foreach ($dummy_categories as $index => $category): ?>
            <div class="category-section <?= $index === 0 ? 'active' : '' ?>" id="category-<?= $category['category_id'] ?>">
                <!-- Category Banner -->
                <div class="category-banner">
                    <img src="<?= $category['banner_image'] ?>" alt="<?= htmlspecialchars($category['name']) ?>">
                </div>

                <!-- Category Title -->
                <div class="category-title">
                    <h2><?= htmlspecialchars($category['name']) ?></h2>
                    <p class="category-subtitle"><?= htmlspecialchars($category['description']) ?></p>
                </div>

                <!-- Menu Items -->
                <?php foreach ($category['items'] as $item): ?>
                    <div class="menu-item" onclick="addToCart(<?= $item['item_id'] ?>)">
                        <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="item-description"><?= htmlspecialchars($item['description']) ?></div>
                            <div class="item-dietary"><?= htmlspecialchars($item['dietary']) ?></div>
                        </div>
                        <div class="item-price">₱<?= number_format($item['price'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Cart Button -->
    <button class="cart-button" onclick="toggleCart()">
        <i class="bi bi-cart3"></i>
        <span>Cart</span>
        <span class="cart-badge" id="cartBadge">0</span>
    </button>

    <!-- Cart Modal -->
    <div class="cart-modal" id="cartModal">
        <div class="cart-content">
            <div class="cart-header">
                <h3 class="cart-title">Your Order</h3>
                <button class="cart-close" onclick="toggleCart()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            
            <div class="cart-items" id="cartItems">
                <div class="empty-cart">
                    <i class="bi bi-cart-x"></i>
                    <p>Your cart is empty</p>
                    <small>Add some delicious items to get started!</small>
                </div>
            </div>
            
            <div class="cart-total" id="cartTotal" style="display: none;">
                <div class="cart-total-row">
                    <span class="cart-total-label">Total:</span>
                    <span class="cart-total-amount" id="totalAmount">₱0.00</span>
                </div>
                <button class="checkout-btn" onclick="checkout()">
                    <i class="bi bi-credit-card"></i> Request Bill
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentCategory = null;
        let cartItems = [];
        let sessionId = <?= $session_id ?? 1 ?>;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Select first category by default
            const firstCategory = document.querySelector('.category-tab');
            if (firstCategory) {
                firstCategory.click();
            }
        });

        function selectCategory(categoryId) {
            // Update active tab
            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            // Show selected category content
            document.querySelectorAll('.category-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById('category-' + categoryId).classList.add('active');

            currentCategory = categoryId;
        }

        function addToCart(itemId) {
            // Find the item in dummy data
            let item = null;
            <?php foreach ($dummy_categories as $category): ?>
                <?php foreach ($category['items'] as $item): ?>
                    if (itemId === <?= $item['item_id'] ?>) {
                        item = {
                            id: <?= $item['item_id'] ?>,
                            name: '<?= addslashes($item['name']) ?>',
                            price: <?= $item['price'] ?>,
                            image: '<?= $item['image'] ?>',
                            description: '<?= addslashes($item['description']) ?>'
                        };
                    }
                <?php endforeach; ?>
            <?php endforeach; ?>

            if (item) {
                // Check if item already in cart
                const existingItem = cartItems.find(cartItem => cartItem.id === itemId);
                if (existingItem) {
                    existingItem.quantity += 1;
                } else {
                    cartItems.push({
                        ...item,
                        quantity: 1
                    });
                }
                
                updateCartDisplay();
                showNotification('Item added to cart!', 'success');
            }
        }

        function updateCartQuantity(itemId, newQuantity) {
            const item = cartItems.find(cartItem => cartItem.id === itemId);
            if (item) {
                if (newQuantity <= 0) {
                    removeFromCart(itemId);
                } else {
                    item.quantity = newQuantity;
                    updateCartDisplay();
                }
            }
        }

        function removeFromCart(itemId) {
            cartItems = cartItems.filter(item => item.id !== itemId);
            updateCartDisplay();
            showNotification('Item removed from cart', 'success');
        }

        function updateCartDisplay() {
            const cartItemsContainer = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            const cartBadge = document.getElementById('cartBadge');
            const totalAmount = document.getElementById('totalAmount');

            if (cartItems.length === 0) {
                cartItemsContainer.innerHTML = `
                    <div class="empty-cart">
                        <i class="bi bi-cart-x"></i>
                        <p>Your cart is empty</p>
                        <small>Add some delicious items to get started!</small>
                    </div>
                `;
                cartTotal.style.display = 'none';
                cartBadge.textContent = '0';
            } else {
                let total = 0;
                cartItemsContainer.innerHTML = cartItems.map(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    return `
                        <div class="cart-item">
                            <img src="${item.image}" alt="${item.name}" class="cart-item-image">
                            <div class="cart-item-details">
                                <div class="cart-item-name">${item.name}</div>
                                <div class="cart-item-price">₱${itemTotal.toFixed(2)}</div>
                            </div>
                            <div class="cart-item-controls">
                                <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <span>${item.quantity}</span>
                                <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
                
                cartTotal.style.display = 'block';
                totalAmount.textContent = `₱${total.toFixed(2)}`;
                cartBadge.textContent = cartItems.reduce((sum, item) => sum + item.quantity, 0);
            }
        }

        function toggleCart() {
            const cartModal = document.getElementById('cartModal');
            cartModal.classList.toggle('show');
        }

        function checkout() {
            if (cartItems.length === 0) {
                showNotification('Your cart is empty', 'error');
                return;
            }

            // In a real implementation, this would send the order to the server
            showNotification('Bill requested! Your order will be prepared shortly.', 'success');
            toggleCart();
        }

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
            notification.style.cssText = 'top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 300px; text-align: center;';
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

        // Close cart modal when clicking outside
        document.getElementById('cartModal').addEventListener('click', function(e) {
            if (e.target === this) {
                toggleCart();
            }
        });
    </script>
</body>
</html>


