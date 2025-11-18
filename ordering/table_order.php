<?php
require_once '../admin/includes/db_connection.php';

// Get table information
$table_number = $_GET['table'] ?? '';
$table = null;
$current_order = null;
$order_items = [];

if ($table_number) {
    // Get table details
    $table_sql = "SELECT * FROM tables WHERE table_number = ? AND is_active = 1";
    $table_stmt = $conn->prepare($table_sql);
    $table_stmt->bind_param('s', $table_number);
    $table_stmt->execute();
    $table = $table_stmt->get_result()->fetch_assoc();
    
    if ($table) {
        // Get current active order for this table
        $order_sql = "SELECT o.* FROM orders o 
                      JOIN order_statuses s ON o.status_id = s.status_id 
                      WHERE o.table_id = ? AND s.name NOT IN ('completed', 'cancelled') 
                      ORDER BY o.created_at DESC LIMIT 1";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param('i', $table['table_id']);
        $order_stmt->execute();
        $current_order = $order_stmt->get_result()->fetch_assoc();
        
        if ($current_order) {
            // Get order items
            $items_sql = "SELECT oi.*, mi.name as item_name, mi.description, mi.image_url 
                         FROM order_items oi 
                         JOIN menu_items mi ON oi.item_id = mi.item_id 
                         WHERE oi.order_id = ? 
                         ORDER BY oi.created_at";
            $items_stmt = $conn->prepare($items_sql);
            $items_stmt->bind_param('i', $current_order['order_id']);
            $items_stmt->execute();
            $order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Get menu categories and items
$categories_sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order, name";
$categories_result = $conn->query($categories_sql);

$menu_sql = "SELECT mi.*, c.name as category_name 
             FROM menu_items mi 
             JOIN categories c ON mi.category_id = c.category_id 
             WHERE mi.is_available = 1 AND c.is_active = 1 
             ORDER BY c.display_order, mi.display_order, mi.name";
$menu_result = $conn->query($menu_sql);

// Organize menu items by category
$menu_by_category = [];
while ($item = $menu_result->fetch_assoc()) {
    $category = $item['category_name'];
    if (!isset($menu_by_category[$category])) {
        $menu_by_category[$category] = [];
    }
    $menu_by_category[$category][] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order for Table <?= $table ? htmlspecialchars($table['table_number']) : 'Table' ?> - Restaurant</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 1200px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 30px;
            text-align: center;
        }
        
        .table-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .menu-section {
            padding: 30px;
        }
        
        .category-tabs {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 30px;
        }
        
        .category-tab {
            padding: 15px 25px;
            border: none;
            background: none;
            color: #6c757d;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .category-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .category-tab:hover {
            color: #667eea;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .menu-item {
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            background: white;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .menu-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .menu-item-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .menu-item-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .menu-item-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .menu-item-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .quantity-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .quantity-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 8px;
        }
        
        .add-to-cart-btn {
            width: 100%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .cart-section {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 3px solid #667eea;
            padding: 20px;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .cart-items {
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 15px;
        }
        
        .cart-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item-name {
            flex: 1;
            font-weight: 500;
        }
        
        .cart-item-quantity {
            margin: 0 15px;
            font-weight: bold;
        }
        
        .cart-item-price {
            font-weight: bold;
            color: #28a745;
        }
        
        .cart-total {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .cart-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-cart {
            flex: 1;
            padding: 12px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-bill-out {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            color: white;
        }
        
        .btn-bill-out:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        .current-order-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-info {
            flex: 1;
        }
        
        .order-item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .order-item-status {
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-preparing {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-ready {
            background: #d4edda;
            color: #155724;
        }
        
        .status-served {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>
    <?php if (!$table): ?>
        <div class="container">
            <div class="main-container">
                <div class="header-section">
                    <h1 class="display-4 mb-3">
                        <i class="bi bi-exclamation-triangle"></i> Invalid Table
                    </h1>
                    <p class="lead">The table you're trying to access is not available.</p>
                    <a href="../ordering/index.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-arrow-left"></i> Back to Ordering
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="container-fluid">
            <div class="main-container">
                <!-- Header Section -->
                <div class="header-section">
                    <h1 class="display-4 mb-3">
                        <i class="bi bi-table"></i> Order for Table <?= htmlspecialchars($table['table_number']) ?>
                    </h1>
                    <p class="lead">Welcome! Browse our menu and place your order</p>
                    
                    <div class="table-info">
                        <div class="row">
                            <div class="col-md-4">
                                <h5><i class="bi bi-geo-alt"></i> Table <?= htmlspecialchars($table['table_number']) ?></h5>
                            </div>
                            <div class="col-md-4">
                            </div>
                            <div class="col-md-4">
                                <h5><i class="bi bi-hash"></i> Table <?= htmlspecialchars($table['table_number']) ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Current Order Section -->
                <?php if ($current_order && !empty($order_items)): ?>
                    <div class="current-order-section">
                        <h4 class="mb-3">
                            <i class="bi bi-cart-check"></i> Current Order #<?= $current_order['queue_number'] ?>
                        </h4>
                        <div class="row">
                            <div class="col-md-8">
                                <?php foreach ($order_items as $item): ?>
                                    <div class="order-item">
                                        <div class="order-item-info">
                                            <div class="order-item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                        </div>
                                        <div class="order-item-quantity">Qty: <?= $item['quantity'] ?></div>
                                        <div class="order-item-price">$<?= number_format($item['subtotal'], 2) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="col-md-4">
                                <div class="text-end">
                                    <h5>Order Total: $<?= number_format($current_order['total_amount'], 2) ?></h5>
                                    <p class="text-muted">Status: <span class="badge bg-warning">
                                        Active Order
                                    </span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Menu Section -->
                <div class="menu-section">
                    <!-- Category Tabs -->
                    <div class="category-tabs">
                        <button class="category-tab active" data-category="all">
                            <i class="bi bi-grid"></i> All Items
                        </button>
                        <?php foreach ($menu_by_category as $category => $items): ?>
                            <button class="category-tab" data-category="<?= strtolower(str_replace(' ', '-', $category)) ?>">
                                <i class="bi bi-tag"></i> <?= htmlspecialchars($category) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Menu Items -->
                    <div class="menu-grid">
                        <?php foreach ($menu_by_category as $category => $items): ?>
                            <?php foreach ($items as $item): ?>
                                <div class="menu-item" data-category="<?= strtolower(str_replace(' ', '-', $category)) ?>">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="menu-item-image">
                                    <?php else: ?>
                                        <div class="menu-item-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="menu-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="menu-item-description"><?= htmlspecialchars($item['description']) ?></div>
                                    <div class="menu-item-price">$<?= number_format($item['price'], 2) ?></div>
                                    
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="changeQuantity(<?= $item['item_id'] ?>, -1)">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="number" class="quantity-input" id="qty-<?= $item['item_id'] ?>" value="1" min="1" max="10">
                                        <button class="quantity-btn" onclick="changeQuantity(<?= $item['item_id'] ?>, 1)">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    
                                    <button class="add-to-cart-btn" onclick="addToCart(<?= $item['item_id'] ?>, '<?= htmlspecialchars($item['name']) ?>', <?= $item['price'] ?>)">
                                        <i class="bi bi-cart-plus"></i> Add to Order
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cart Section -->
        <div class="cart-section" id="cartSection" style="display: none;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <i class="bi bi-cart"></i> Your Order
                            <span class="badge bg-primary ms-2" id="cartItemCount">0</span>
                        </h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="cart-total">
                            Total: $<span id="cartTotal">0.00</span>
                        </div>
                    </div>
                </div>
                
                <div class="cart-items" id="cartItems">
                    <!-- Cart items will be populated here -->
                </div>
                
                <div class="cart-actions">
                    <button class="btn btn-cart btn-checkout" onclick="submitOrder()">
                        <i class="bi bi-check-circle"></i> Submit Order
                    </button>
                    <?php if ($current_order): ?>
                        <button class="btn btn-cart btn-bill-out" onclick="billOut()">
                            <i class="bi bi-receipt"></i> Bill Out
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Cart management
let cart = {};
let currentTableId = <?= $table ? $table['table_id'] : 'null' ?>;
let currentOrderId = <?= $current_order ? $current_order['order_id'] : 'null' ?>;

// Category filtering
document.querySelectorAll('.category-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const category = this.dataset.category;
        
        // Update active tab
        document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Filter menu items
        document.querySelectorAll('.menu-item').forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

function changeQuantity(itemId, change) {
    const input = document.getElementById(`qty-${itemId}`);
    const newValue = parseInt(input.value) + change;
    if (newValue >= 1 && newValue <= 10) {
        input.value = newValue;
    }
}

function addToCart(itemId, itemName, price) {
    const quantity = parseInt(document.getElementById(`qty-${itemId}`).value);
    
    if (cart[itemId]) {
        cart[itemId].quantity += quantity;
    } else {
        cart[itemId] = {
            name: itemName,
            price: price,
            quantity: quantity
        };
    }
    
    updateCartDisplay();
    showCart();
}

function removeFromCart(itemId) {
    delete cart[itemId];
    updateCartDisplay();
    
    if (Object.keys(cart).length === 0) {
        hideCart();
    }
}

function updateCartDisplay() {
    const cartItems = document.getElementById('cartItems');
    const cartItemCount = document.getElementById('cartItemCount');
    const cartTotal = document.getElementById('cartTotal');
    
    let total = 0;
    let itemCount = 0;
    let itemsHtml = '';
    
    for (const [itemId, item] of Object.entries(cart)) {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        itemCount += item.quantity;
        
        itemsHtml += `
            <div class="cart-item">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-quantity">${item.quantity}</div>
                <div class="cart-item-price">$${itemTotal.toFixed(2)}</div>
                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${itemId})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
    }
    
    cartItems.innerHTML = itemsHtml;
    cartItemCount.textContent = itemCount;
    cartTotal.textContent = total.toFixed(2);
}

function showCart() {
    document.getElementById('cartSection').style.display = 'block';
    document.body.style.paddingBottom = '200px';
}

function hideCart() {
    document.getElementById('cartSection').style.display = 'none';
    document.body.style.paddingBottom = '0';
}

function submitOrder() {
    if (Object.keys(cart).length === 0) {
        alert('Your cart is empty!');
        return;
    }
    
    const orderData = {
        table_id: currentTableId,
        items: cart,
        existing_order_id: currentOrderId
    };
    
    fetch('process_table_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order submitted successfully!');
            cart = {};
            updateCartDisplay();
            hideCart();
            location.reload(); // Refresh to show updated order
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting your order.');
    });
}

function billOut() {
    if (currentOrderId) {
        window.location.href = `bill_out.php?order_id=${currentOrderId}`;
    }
}

// Auto-refresh order status every 30 seconds
setInterval(function() {
    if (currentOrderId) {
        fetch(`get_order_status.php?order_id=${currentOrderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.updated) {
                    location.reload();
                }
            })
            .catch(error => console.error('Status check error:', error));
    }
}, 30000);
</script>

<?php $conn->close(); ?>
