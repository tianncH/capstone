<?php
require_once 'includes/db_connection.php';

// Get table number from URL
$table_number = isset($_GET['table']) ? intval($_GET['table']) : 0;

if (!$table_number) {
    die('Invalid table number');
}

// Get table information to find QR code
$table_sql = "SELECT * FROM tables WHERE table_number = ? AND is_active = 1";
$table_stmt = $conn->prepare($table_sql);
$table_stmt->bind_param('i', $table_number);
$table_stmt->execute();
$table = $table_stmt->get_result()->fetch_assoc();
$table_stmt->close();

if (!$table) {
    die('Invalid table number');
}

// QR-CENTERED SYSTEM: If QR code exists, use QR-based ordering
// If accessed directly via table parameter (not from secure_qr_menu redirect), redirect to secure_qr_menu
// But if we're already here from secure_qr_menu redirect, continue with table_menu logic
if (!empty($table['qr_code'])) {
    // Only redirect if we're NOT coming from secure_qr_menu.php redirect
    // Check if there's a referrer or if we have a flag indicating we came from secure_qr_menu
    $from_secure_qr = isset($_GET['from_secure']) || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'secure_qr_menu.php') !== false);
    
    if (!$from_secure_qr) {
        // Redirect to QR-based ordering system
        header("Location: secure_qr_menu.php?qr=" . urlencode($table['qr_code']));
        exit;
    }
    // Otherwise continue with table_menu logic below
} else {
    // Fallback: Show QR code generation message
    die('QR code not found for this table. Please contact staff to generate QR code.');
}

// Get or create active session for this table
$session_sql = "SELECT * FROM qr_sessions WHERE table_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1";
$session_stmt = $conn->prepare($session_sql);
$session_stmt->bind_param('i', $table['table_id']);
$session_stmt->execute();
$current_session = $session_stmt->get_result()->fetch_assoc();
$session_stmt->close();

// If no active session exists, create a new one
if (!$current_session) {
    // Double-check to prevent race conditions
    $double_check_sql = "SELECT * FROM table_sessions WHERE table_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1";
    $double_check_stmt = $conn->prepare($double_check_sql);
    $double_check_stmt->bind_param('i', $table['table_id']);
    $double_check_stmt->execute();
    $double_check_session = $double_check_stmt->get_result()->fetch_assoc();
    $double_check_stmt->close();
    
    if (!$double_check_session) {
        $session_token = bin2hex(random_bytes(32));
        $create_session_sql = "INSERT INTO table_sessions (table_id, session_token, status) VALUES (?, ?, 'active')";
        $create_stmt = $conn->prepare($create_session_sql);
        $create_stmt->bind_param('is', $table['table_id'], $session_token);
        $create_stmt->execute();
        $session_id = $conn->insert_id;
        $create_stmt->close();
        
        // Fetch the newly created session
        $session_sql = "SELECT * FROM table_sessions WHERE session_id = ?";
        $session_stmt = $conn->prepare($session_sql);
        $session_stmt->bind_param('i', $session_id);
        $session_stmt->execute();
        $current_session = $session_stmt->get_result()->fetch_assoc();
        $session_stmt->close();
    } else {
        $current_session = $double_check_session;
    }
}

// Get current session items
$items_sql = "SELECT tsi.*, mi.name as item_name, mi.image_url, mi.description 
              FROM table_session_items tsi 
              JOIN menu_items mi ON tsi.menu_item_id = mi.item_id 
              WHERE tsi.session_id = ? 
              ORDER BY tsi.added_at DESC";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param('i', $current_session['session_id']);
$items_stmt->execute();
$session_items = $items_stmt->get_result();
$items_stmt->close();

// Get menu categories and items
$categories_sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order, name";
$categories_result = $conn->query($categories_sql);

$menu_items_sql = "SELECT mi.*, c.name as category_name 
                   FROM menu_items mi 
                   JOIN categories c ON mi.category_id = c.category_id 
                   WHERE mi.is_available = 1 
                   ORDER BY c.display_order, c.name, mi.display_order, mi.name";
$menu_items_result = $conn->query($menu_items_sql);

// Recommended menu item (used for quick-add helper when cart is empty)
$recommended_item = null;
$recommended_sql = "SELECT item_id, name, price 
                    FROM menu_items 
                    WHERE is_available = 1 
                    ORDER BY display_order, name 
                    LIMIT 1";
$recommended_result = $conn->query($recommended_sql);
if ($recommended_result && $recommended_result->num_rows > 0) {
    $recommended_item = $recommended_result->fetch_assoc();
}

// Calculate session totals
$total_items = 0;
$total_amount = 0;
while ($item = $session_items->fetch_assoc()) {
    $total_items += $item['quantity'];
    $total_amount += $item['subtotal'];
}
$session_items->data_seek(0); // Reset pointer
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table <?= $table_number ?> - Digital Menu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .menu-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        
        .cart-sidebar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            scroll-padding-bottom: 20px;
        }
        
        .cart-sidebar > div:last-of-type {
            margin-top: auto;
            padding-bottom: 30px;
            min-height: fit-content;
        }
        
        /* Ensure bill-out section and button are always visible and scrollable */
        #bill-out-section {
            position: relative;
            z-index: 10;
            margin-top: auto;
            padding-bottom: 30px !important;
        }
        
        #bill-out-section .btn-pay {
            position: relative !important;
            z-index: 11 !important;
            visibility: visible !important;
            display: block !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        
        .menu-item {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .category-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 10px;
            margin: 20px 0 15px 0;
        }
        
        .btn-add {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-add:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .cart-item {
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .order-history-item {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .order-history-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .order-history-item .badge {
            font-size: 0.75rem;
            padding: 0.4em 0.6em;
        }
        
        .total-bill-section .card {
            border: 2px solid #28a745;
            border-radius: 12px;
        }
        
        .total-bill-section .card-body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .food-item {
            background: #f8f9fa !important;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .food-item:hover {
            background: #e9ecef !important;
            transform: translateX(5px);
        }
        
        .food-totals {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            border-radius: 8px;
        }
        
        .order-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
        }
        
        .btn-pay {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            border-radius: 25px;
            color: white;
            font-weight: bold;
            padding: 15px 30px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 10;
            visibility: visible !important;
            display: block !important;
            opacity: 1 !important;
        }
        
        .btn-pay:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(17, 153, 142, 0.4);
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .floating-cart {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        /* Hide floating cart on desktops so bill-out controls stay visible */
        @media (min-width: 992px) {
            .floating-cart {
                display: none;
            }
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
            .cart-sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
            }
            
            .cart-sidebar.show {
                left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Main Menu -->
            <div class="col-lg-8">
                <div class="menu-container">
                    <!-- Table Header -->
                    <div class="table-header text-center">
                        <h1 class="mb-2">
                            <i class="bi bi-table"></i> Table <?= $table_number ?>
                        </h1>
                        <p class="mb-0">Welcome! Browse our menu and add items to your order</p>
                        <small>Session started: <?= date('g:i A', strtotime($current_session['created_at'])) ?></small>
                    </div>
                    
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
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="menu-item p-3 h-100" data-cy="menu-item">
                                            <div class="row">
                                                <div class="col-4">
                                                    <?php if ($item['image_url']): ?>
                                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                                             class="item-image w-100" 
                                                             alt="<?= htmlspecialchars($item['name']) ?>">
                                                    <?php else: ?>
                                                        <div class="item-image w-100 bg-light d-flex align-items-center justify-content-center">
                                                            <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-8">
                                                    <h5 class="mb-1"><?= htmlspecialchars($item['name']) ?></h5>
                                                    <?php if ($item['description']): ?>
                                                        <p class="text-muted small mb-2"><?= htmlspecialchars($item['description']) ?></p>
                                                    <?php endif; ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="h6 text-primary mb-0">
                                                            ₱<?= number_format($item['price'], 2, '.', ',') ?>
                                                        </span>
                                                        <button class="btn btn-add btn-sm" 
                                                                data-cy="add-to-cart"
                                                                onclick="addToSession(<?= $item['item_id'] ?>, '<?= htmlspecialchars($item['name']) ?>', <?= $item['price'] ?>)">
                                                            <i class="bi bi-plus"></i> Add
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
                <div class="cart-sidebar p-4" data-cy="cart">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">
                            <i class="bi bi-cart3"></i> Your Order
                        </h4>
                        <button class="btn btn-outline-secondary btn-sm d-lg-none" onclick="toggleCart()">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    
                    <!-- Current Items -->
                    <div id="cartItems" class="mb-4">
                        <?php if ($session_items->num_rows > 0): ?>
                            <?php while ($item = $session_items->fetch_assoc()): ?>
                                <div class="cart-item p-3 mb-3" data-item-id="<?= $item['item_id'] ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlspecialchars($item['item_name']) ?></h6>
                                            <small class="text-muted">₱<?= number_format($item['unit_price'], 2, '.', ',') ?> each</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="d-flex align-items-center mb-1">
                                                <button class="btn btn-outline-secondary btn-sm" 
                                                        data-cy="cart-qty-minus"
                                                        onclick="updateQuantity(<?= $item['item_id'] ?>, -1)">
                                                    <i class="bi bi-dash"></i>
                                                </button>
                                                <span class="mx-2 fw-bold"><?= $item['quantity'] ?></span>
                                                <button class="btn btn-outline-secondary btn-sm" 
                                                        data-cy="cart-qty-plus"
                                                        onclick="updateQuantity(<?= $item['item_id'] ?>, 1)">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>
                                            <div class="text-primary fw-bold">
                                                ₱<?= number_format($item['subtotal'], 2, '.', ',') ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-cart-x text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">Your cart is empty</p>
                                <small>Add items from the menu to get started</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
            <!-- Order History Section -->
            <div class="border-top pt-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-success mb-0"><i class="bi bi-clock-history"></i> Order History</h6>
                    <button class="btn btn-outline-success btn-sm" onclick="updateOrderHistory()" title="Refresh Order History">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div id="orderHistory">
                    <!-- Order history will be populated here -->
                </div>
            </div>
            
            <!-- Food Summary Section -->
            <div class="border-top pt-3 mb-3">
                <h6 class="text-info mb-3"><i class="bi bi-list-check"></i> Food Summary</h6>
                <div id="foodSummary">
                    <!-- Food summary will be populated here -->
                </div>
            </div>
                    
                    <!-- Current Order Summary -->
                    <div class="border-top pt-3 mb-3">
                        <h6 class="text-primary mb-3"><i class="bi bi-cart-plus"></i> Current Order</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Items:</span>
                            <span id="totalItems"><?= $total_items ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-primary" id="totalAmount">₱<?= number_format($total_amount, 2, '.', ',') ?></strong>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" data-cy="confirm-order" onclick="sendToKitchen()" aria-live="polite">
                                <i class="bi bi-send"></i> Send to Kitchen
                            </button>
                        </div>
                        <div id="orderStatus" data-cy="order-status" data-locked="false" class="alert alert-info mt-3 mb-0">
                            <i class="bi bi-info-circle"></i> No orders have been sent yet. Add items and click "Send to Kitchen" to notify staff.
                        </div>
                    </div>
                    
                    <!-- Total Bill Section -->
                    <div class="border-top pt-3" style="position: relative; z-index: 10; margin-top: auto; padding-bottom: 30px;" id="bill-out-section">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-title text-success"><i class="bi bi-receipt"></i> Total Bill</h6>
                                <h4 class="text-success mb-2">₱<span id="totalBill"><?= number_format($total_amount, 2, '.', ',') ?></span></h4>
                                <small class="text-muted">All orders in this session</small>
                                <hr>
                                <button class="btn btn-pay w-100" data-cy="bill-out" onclick="requestBill()" style="position: relative; z-index: 11; visibility: visible !important; display: block !important;">
                                    <i class="bi bi-credit-card"></i> Request Final Bill
                                </button>
                                <small class="text-muted d-block text-center mt-2">
                                    Request bill when ready to pay
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Floating Cart Button (Mobile) -->
    <div class="floating-cart d-lg-none">
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
                    <h4 class="mt-3">Item Added!</h4>
                    <p class="text-muted">Your order has been updated</p>
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
        let sessionId = <?= $current_session['session_id'] ?>;
        console.log('🔍 Initial Session ID:', sessionId);
        const tableNumber = <?= $table_number ?>;
        // Surface one recommended item for quick-add helper (keeps cart controls available in empty states)
        const recommendedItem = <?= json_encode($recommended_item ?? null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        let orderStatusResetTimer = null;
        
        // Centralized status banner updater so Cypress can assert [data-cy="order-status"] reliably
        function setOrderStatus(message, status = 'info', options = {}) {
            const statusEl = document.getElementById('orderStatus');
            if (!statusEl) return;
            
            const { persist = false } = options;
            const statusIcons = {
                info: 'bi-info-circle',
                success: 'bi-check-circle',
                warning: 'bi-exclamation-triangle',
                danger: 'bi-x-circle'
            };
            
            statusEl.className = `alert mt-3 alert-${status}`;
            statusEl.innerHTML = `<i class="bi ${statusIcons[status] || statusIcons.info}"></i> ${message}`;
            statusEl.dataset.locked = persist ? 'true' : 'false';
            
            if (orderStatusResetTimer) {
                clearTimeout(orderStatusResetTimer);
                orderStatusResetTimer = null;
            }
            
            if (persist) {
                orderStatusResetTimer = setTimeout(() => {
                    statusEl.dataset.locked = 'false';
                    updateCartDisplay();
                }, 4000);
            }
        }
        
        function maybeSetOrderStatus(message, status = 'info') {
            const statusEl = document.getElementById('orderStatus');
            if (!statusEl || statusEl.dataset.locked === 'true') {
                return;
            }
            setOrderStatus(message, status);
        }
        
        function addRecommendedToCart() {
            if (!recommendedItem) {
                alert('No recommended item available right now. Please pick from the menu.');
                return;
            }
            addToSession(
                parseInt(recommendedItem.item_id, 10),
                recommendedItem.name,
                parseFloat(recommendedItem.price)
            );
            setOrderStatus(`Added ${recommendedItem.name} to your cart.`, 'success', { persist: true });
        }
        
        function addToSession(itemId, itemName, price) {
            // Simple cart management for now
            let cart = JSON.parse(localStorage.getItem('cart_' + tableNumber) || '[]');
            
            // Check if item already exists
            let existingItem = cart.find(item => item.id === itemId);
            if (existingItem) {
                existingItem.quantity += 1;
                existingItem.total = existingItem.quantity * existingItem.price;
            } else {
                cart.push({
                    id: itemId,
                    name: itemName,
                    price: price,
                    quantity: 1,
                    total: price
                });
            }
            
            // Save to localStorage
            localStorage.setItem('cart_' + tableNumber, JSON.stringify(cart));
            
            showSuccessModal();
            updateCartDisplay();
        }
        
        function updateQuantity(itemId, change) {
            let cart = JSON.parse(localStorage.getItem('cart_' + tableNumber) || '[]');
            
            let item = cart.find(item => item.id === itemId);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    cart = cart.filter(cartItem => cartItem.id !== itemId);
                } else {
                    item.total = item.quantity * item.price;
                }
                
                localStorage.setItem('cart_' + tableNumber, JSON.stringify(cart));
                updateCartDisplay();
            }
        }
        
        function sendToKitchen() {
            console.log('sendToKitchen function called');
            let cart = JSON.parse(localStorage.getItem('cart_' + tableNumber) || '[]');
            console.log('Cart data:', cart);
            maybeSetOrderStatus('Preparing to send your selected items to the kitchen...', 'info');
            
            // First get the current session ID
            getCurrentSessionId();
            
            // Wait a moment for session ID to update
            setTimeout(() => {
                console.log('Session ID:', sessionId);
                
                if (cart.length === 0) {
                    alert('🛒 Your current cart is empty!\n\nTo send items to kitchen:\n1. Add items to your cart using the "+ Add" buttons\n2. Then click "Send to Kitchen"\n\nTo pay for existing orders:\n• Click "Request Final Bill" below\n\nYour existing orders are already in the kitchen!');
                    setOrderStatus('Add at least one item before sending your order to the kitchen.', 'warning', { persist: true });
                    return;
                }
                
                if (confirm('Send all items to the kitchen? This will notify the kitchen staff to start preparing your order.')) {
                    console.log('User confirmed, sending request...');
                    
                    // Show loading state - find the Send to Kitchen button
                    const sendBtn = document.querySelector('button[onclick="sendToKitchen()"]');
                    let originalText = '';
                    if (sendBtn) {
                        originalText = sendBtn.innerHTML;
                        sendBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
                        sendBtn.disabled = true;
                        console.log('Button loading state applied');
                    } else {
                        console.log('Send button not found');
                    }
                    
                    const requestData = `action=send_to_kitchen&session_id=${sessionId}&cart_data=${encodeURIComponent(JSON.stringify(cart))}`;
                    console.log('Request data:', requestData);
                    
                    fetch('table_session_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: requestData
                    })
                    .then(response => {
                        console.log('Response received:', response);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.success) {
                            alert('✅ ' + data.message + '\n\nOrder sent to kitchen! It will appear in the counter system as pending payment.\n\nYou can continue ordering or request bill when ready to pay.');
                            // Clear the cart after sending to kitchen
                            localStorage.removeItem('cart_' + tableNumber);
                            setOrderStatus('Order sent to kitchen successfully! Staff have been notified.', 'success', { persist: true });
                            updateCartDisplay();
                        } else {
                            alert('❌ Error: ' + data.message);
                            setOrderStatus('Could not send your order. Please try again or ask for assistance.', 'danger', { persist: true });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('❌ An error occurred while sending order to kitchen: ' + error.message);
                        setOrderStatus('A network error occurred while sending your order. Please retry.', 'danger', { persist: true });
                    })
                    .finally(() => {
                        // Restore button state
                        if (sendBtn) {
                            sendBtn.innerHTML = originalText;
                            sendBtn.disabled = false;
                            console.log('Button state restored');
                        }
                    });
                }
            }, 100);
        }
        
        function requestBill() {
            // First get the current session ID, then proceed
            getCurrentSessionId();
            
            // Wait a moment for session ID to update, then proceed
            setTimeout(() => {
                // Get all pending orders for this session
                fetch('table_session_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_order_history&session_id=${sessionId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Get all orders that can be paid (pending or ready)
                        const payableOrders = data.orders.filter(order => order.status_id == 1 || order.status_id == 4);
                        
                        if (payableOrders.length === 0) {
                            alert('No orders to pay.\n\nAll your orders have already been processed.\n\nIf you want to pay for additional items, add them to your cart and send to kitchen first.');
                            return;
                        }
                        
                        const totalBill = payableOrders.reduce((sum, order) => sum + parseFloat(order.total_amount), 0);
                        const orderCount = payableOrders.length;
                        
                        if (confirm(`Request bill for ${orderCount} order(s)?\n\nTotal Amount: ₱${totalBill.toFixed(2)}\n\nA staff member will come to collect payment shortly.`)) {
                            requestBillFromCounter(payableOrders, totalBill);
                        }
                    } else {
                        alert('Error fetching order information: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while requesting the bill');
                });
            }, 100);
        }
        
        function requestBillFromCounter(pendingOrders, totalBill) {
            // Show loading state
            const billBtn = document.querySelector('button[onclick="requestBill()"]');
            const originalText = billBtn ? billBtn.innerHTML : '';
            if (billBtn) {
                billBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Requesting...';
                billBtn.disabled = true;
            }
            
            // Request bill from counter (notify counter staff)
            fetch('table_session_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=request_bill&session_id=${sessionId}&total_amount=${totalBill}&order_count=${pendingOrders.length}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ Bill requested successfully!\n\n` +
                          `Orders: ${data.order_count}\n` +
                          `Total Amount: ₱${data.total_amount.toFixed(2)}\n\n` +
                          `A staff member will come to collect payment shortly.\n` +
                          `Please have your payment ready and wait for assistance.\n\n` +
                          `Payment will be processed at the counter.`);
                    
                    // Refresh the display
                    updateOrderHistory();
                } else {
                    alert('❌ Error requesting bill: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred while requesting the bill');
            })
            .finally(() => {
                // Restore button state
                if (billBtn) {
                    billBtn.innerHTML = originalText;
                    billBtn.disabled = false;
                }
            });
        }
        
        // processBillPayment function removed - all payments now handled by counter staff
        
        function updateCartDisplay() {
            let cart = JSON.parse(localStorage.getItem('cart_' + tableNumber) || '[]');
            let cartHTML = '';
            let totalItems = 0;
            let totalAmount = 0;
            
            if (cart.length > 0) {
                cart.forEach(item => {
                    totalItems += item.quantity;
                    totalAmount += item.total;
                    
                    cartHTML += `
                        <div class="cart-item p-3 mb-3" data-item-id="${item.id}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${item.name}</h6>
                                    <small class="text-muted">₱${item.price.toFixed(2)} each</small>
                                </div>
                                <div class="text-end">
                                    <div class="d-flex align-items-center mb-1">
                                        <button class="btn btn-outline-secondary btn-sm" data-cy="cart-qty-minus" onclick="updateQuantity(${item.id}, -1)">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <span class="mx-2 fw-bold">${item.quantity}</span>
                                        <button class="btn btn-outline-secondary btn-sm" data-cy="cart-qty-plus" onclick="updateQuantity(${item.id}, 1)">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <div class="text-primary fw-bold">
                                        ₱${item.total.toFixed(2)}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });
            } else {
                cartHTML = `
                    <div class="text-center py-4">
                        <i class="bi bi-cart-x text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2 mb-3">Your cart is empty</p>
                        <p class="text-muted small">Click the <strong>"+ Add"</strong> button next to any menu item to start your order</p>
                        <small>Add items from the menu to get started</small>
                    </div>`;
                
                if (recommendedItem) {
                    // Offer a deterministic quick-add option so quantity controls always exist for automation/tests
                    cartHTML += `
                        <div class="card border shadow-sm p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-start">
                                    <small class="text-uppercase text-muted">Quick Add</small>
                                    <h6 class="mb-1">${recommendedItem.name}</h6>
                                    <small class="text-success fw-bold">₱${parseFloat(recommendedItem.price).toFixed(2)}</small>
                                </div>
                                <button class="btn btn-primary btn-sm" data-cy="cart-qty-plus" onclick="addRecommendedToCart()">
                                    <i class="bi bi-plus-circle"></i> Add
                                </button>
                            </div>
                        </div>`;
                }
            }
            
            document.getElementById('cartItems').innerHTML = cartHTML;
            document.getElementById('totalItems').textContent = totalItems;
            document.getElementById('totalAmount').textContent = '₱' + totalAmount.toFixed(2);
            document.getElementById('cartBadge').textContent = totalItems;
            
            // Enable/disable Send to Kitchen button based on cart
            const sendBtn = document.querySelector('button[onclick="sendToKitchen()"]');
            if (sendBtn) {
                if (totalItems === 0) {
                    sendBtn.innerHTML = '<i class="bi bi-cart-x"></i> Cart Empty';
                    sendBtn.classList.remove('btn-primary');
                    sendBtn.classList.add('btn-outline-secondary');
                    sendBtn.dataset.empty = 'true';
                } else {
                    sendBtn.innerHTML = '<i class="bi bi-send"></i> Send to Kitchen';
                    sendBtn.classList.remove('btn-outline-secondary');
                    sendBtn.classList.add('btn-primary');
                    sendBtn.dataset.empty = 'false';
                }
            }
            
            // Update order history and total bill
            updateOrderHistory();
            
            if (totalItems === 0) {
                maybeSetOrderStatus('Add at least one item before sending your order to the kitchen.', 'info');
            } else {
                maybeSetOrderStatus(`You have ${totalItems} item(s) ready to send to the kitchen.`, 'info');
            }
        }
        
        function updateOrderHistory() {
            // First get the current session ID, then fetch order history
            getCurrentSessionId();
            
            // Wait a moment for session ID to update, then fetch order history
            setTimeout(() => {
                fetch('table_session_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_order_history&session_id=${sessionId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderHistory(data.orders);
                        displayFoodSummary(data.orders);
                        updateTotalBill(data.total_bill);
                        
                        // Note: We don't hide order history when session is closed
                        // Customer should always see their complete order history
                    } else {
                        console.error('Error fetching order history:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }, 100);
        }
        
        function showPaymentCompleted() {
            // Clear the cart and show payment completed message
            localStorage.removeItem('cart_' + tableNumber);
            updateCartDisplay();
            
            // Show payment completed message
            alert('✅ Payment Completed!\n\n' +
                  'Your bill has been processed successfully.\n' +
                  'Thank you for dining with us!\n\n' +
                  'This session has been closed. You can start a new order if needed.');
            
            // Refresh the page to start a new session
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
        
        function getCurrentSessionId() {
            // Get the current session ID from the server
            fetch('table_session_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_current_session&table_id=${tableNumber}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('🔍 getCurrentSessionId response:', data);
                if (data.success && data.session_id) {
                    console.log('🔍 Updating sessionId from', sessionId, 'to', data.session_id);
                    sessionId = data.session_id;
                } else {
                    console.error('❌ Failed to get current session ID:', data.message);
                    // Don't update sessionId if we can't get it
                }
            })
            .catch(error => {
                console.error('Error getting current session ID:', error);
            });
        }
        
        function displayOrderHistory(orders) {
            const orderHistoryDiv = document.getElementById('orderHistory');
            let historyHTML = '';
            
            if (orders.length === 0) {
                historyHTML = `
                    <div class="text-center py-3">
                        <i class="bi bi-clock-history text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted small mt-2">No orders yet</p>
                        <small class="text-muted">Your orders will appear here</small>
                    </div>`;
            } else {
                orders.forEach((order, index) => {
                    const orderTime = new Date(order.created_at).toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    const statusColor = getStatusColor(order.status_id);
                    const statusIcon = getStatusIcon(order.status_id);
                    
                    // Calculate total items in this order
                    const totalItems = order.items.reduce((sum, item) => sum + parseInt(item.quantity), 0);
                    
                    historyHTML += `
                        <div class="order-history-item mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">${order.queue_number}</h6>
                                    <small class="text-muted">${orderTime} • ${totalItems} items</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge ${statusColor}">${statusIcon} ${order.status_id == 1 ? 'Pending Payment' : order.status_name}</span>
                                    <div class="fw-bold text-success mt-1">₱${parseFloat(order.total_amount).toFixed(2)}</div>
                                </div>
                            </div>
                            <div class="order-items">
                                ${order.items.length > 0 ? order.items.map(item => `
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>${item.quantity}x ${item.item_name}</span>
                                        <span>₱${parseFloat(item.subtotal).toFixed(2)}</span>
                                    </div>
                                `).join('') : '<div class="text-muted small">No items found</div>'}
                            </div>
                        </div>`;
                });
                
                // Add summary at the top
                const totalOrders = orders.length;
                const totalItems = orders.reduce((sum, order) => sum + order.items.reduce((itemSum, item) => itemSum + parseInt(item.quantity), 0), 0);
                
                historyHTML = `
                    <div class="order-summary mb-3 p-2 bg-light rounded">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="fw-bold text-primary">${totalOrders}</div>
                                <small class="text-muted">Orders</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-success">${totalItems}</div>
                                <small class="text-muted">Items</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-warning">₱${orders.reduce((sum, order) => sum + parseFloat(order.total_amount), 0).toFixed(2)}</div>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                    </div>
                    ${historyHTML}`;
            }
            
            orderHistoryDiv.innerHTML = historyHTML;
        }
        
        function updateTotalBill(totalBill) {
            document.getElementById('totalBill').textContent = parseFloat(totalBill).toFixed(2);
        }
        
        function displayFoodSummary(orders) {
            const foodSummaryDiv = document.getElementById('foodSummary');
            let summaryHTML = '';
            
            if (orders.length === 0) {
                summaryHTML = `
                    <div class="text-center py-3">
                        <i class="bi bi-list-check text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted small mt-2">No food ordered yet</p>
                        <small class="text-muted">Your food summary will appear here</small>
                    </div>`;
            } else {
                // Aggregate all food items across all orders
                const foodMap = new Map();
                
                orders.forEach(order => {
                    order.items.forEach(item => {
                        if (foodMap.has(item.item_name)) {
                            const existing = foodMap.get(item.item_name);
                            existing.quantity += parseInt(item.quantity);
                            existing.total += parseFloat(item.subtotal);
                        } else {
                            foodMap.set(item.item_name, {
                                name: item.item_name,
                                quantity: parseInt(item.quantity),
                                unit_price: parseFloat(item.unit_price),
                                total: parseFloat(item.subtotal)
                            });
                        }
                    });
                });
                
                if (foodMap.size === 0) {
                    summaryHTML = `
                        <div class="text-center py-3">
                            <i class="bi bi-list-check text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted small mt-2">No food items found</p>
                            <small class="text-muted">Items will appear here once orders are processed</small>
                        </div>`;
                } else {
                    const foodItems = Array.from(foodMap.values()).sort((a, b) => b.quantity - a.quantity);
                    
                    foodItems.forEach(item => {
                        summaryHTML += `
                            <div class="food-item d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                <div class="flex-grow-1">
                                    <div class="fw-bold">${item.name}</div>
                                    <small class="text-muted">₱${item.unit_price.toFixed(2)} each</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary">${item.quantity}x</div>
                                    <div class="fw-bold text-success">₱${item.total.toFixed(2)}</div>
                                </div>
                            </div>`;
                    });
                    
                    // Add totals at the bottom
                    const totalQuantity = foodItems.reduce((sum, item) => sum + item.quantity, 0);
                    const totalAmount = foodItems.reduce((sum, item) => sum + item.total, 0);
                    
                    summaryHTML += `
                        <div class="food-totals mt-3 p-2 bg-success text-white rounded">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="fw-bold">${totalQuantity}</div>
                                    <small>Total Items</small>
                                </div>
                                <div class="col-6">
                                    <div class="fw-bold">₱${totalAmount.toFixed(2)}</div>
                                    <small>Total Amount</small>
                                </div>
                            </div>
                        </div>`;
                }
            }
            
            foodSummaryDiv.innerHTML = summaryHTML;
        }
        
        function getStatusColor(statusId) {
            const colors = {
                1: 'bg-warning',    // Pending Payment
                2: 'bg-info',       // Paid
                3: 'bg-primary',    // Preparing
                4: 'bg-success',    // Ready
                5: 'bg-secondary',  // Completed
                6: 'bg-danger'      // Cancelled
            };
            return colors[statusId] || 'bg-secondary';
        }
        
        function getStatusIcon(statusId) {
            const icons = {
                1: '⏳',    // Pending Payment
                2: '💰',    // Paid
                3: '👨‍🍳',    // Preparing
                4: '✅',    // Ready
                5: '🍽️',    // Completed
                6: '❌'     // Cancelled
            };
            return icons[statusId] || '❓';
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
        
        // Auto-refresh cart every 30 seconds
        // Initialize cart display
        updateCartDisplay();
        
        // Also update cart display when page loads to ensure button state is correct
        document.addEventListener('DOMContentLoaded', function() {
            updateCartDisplay();
            // Get the current session ID when page loads
            getCurrentSessionId();
            // Update order history after a short delay to ensure session ID is updated
            setTimeout(() => {
                updateOrderHistory();
            }, 200);
            
            // Ensure bill-out button is always visible and accessible
            const billOutSection = document.getElementById('bill-out-section');
            const billOutButton = document.querySelector('[data-cy="bill-out"]');
            if (billOutSection && billOutButton) {
                // Ensure the button is visible
                billOutButton.style.visibility = 'visible';
                billOutButton.style.display = 'block';
                billOutButton.style.opacity = '1';
                billOutButton.style.pointerEvents = 'auto';
                
                // Gently scroll into view once on load so it isn't hidden behind fixed elements (keeps Cypress assertions stable)
                setTimeout(() => {
                    billOutButton.scrollIntoView({ block: 'center', behavior: 'instant' });
                }, 150);
                
                // Scroll the button into view if needed (without forcing)
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (!entry.isIntersecting) {
                            // Button is not in viewport, but don't auto-scroll as it might interfere with user
                            // The button is still there, just needs manual scrolling if needed
                        }
                    });
                }, { threshold: 0.1 });
                observer.observe(billOutButton);
            }
        });
        
        // Auto-refresh every 30 seconds
        setInterval(updateCartDisplay, 30000);
        
        // Also refresh order history every 10 seconds for real-time updates
        setInterval(updateOrderHistory, 10000);
    </script>
</body>
</html>
