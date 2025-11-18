<?php
// Order Details Viewer for Counter Staff
require_once 'includes/db_connection.php';

// Initialize the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if(!isset($_SESSION["counter_loggedin"]) || $_SESSION["counter_loggedin"] !== true){
    header("location: counter_login.php");
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("location: index.php");
    exit;
}

$order_id = intval($_GET['order_id']);

// Get order details with all related information including discount fields
$sql_order = "SELECT o.*, os.name as status_name, t.table_number,
                     o.total_amount as original_total,
                     o.discount_type, o.discount_percentage, o.discount_amount, o.original_amount
              FROM orders o 
              LEFT JOIN tables t ON o.table_id = t.table_id 
              JOIN order_statuses os ON o.status_id = os.status_id 
              WHERE o.order_id = ?";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();
$order = $result_order->fetch_assoc();
$stmt_order->close();

if (!$order) {
    header("location: index.php");
    exit;
}

// Get order items with variations and add-ons
$sql_items = "SELECT oi.*, mi.name as item_name, mi.description as item_description,
                     iv.name as variation_name, iv.price_adjustment as variation_price_adj
              FROM order_items oi 
              JOIN menu_items mi ON oi.item_id = mi.item_id 
              LEFT JOIN item_variations iv ON oi.variation_id = iv.variation_id 
              WHERE oi.order_id = ?
              ORDER BY oi.order_item_id";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$stmt_items->close();

// If no regular order items found, check for QR orders
if ($result_items->num_rows == 0) {
    // Try to find QR orders for this order
    $sql_qr_items = "SELECT qo.*, mi.name as item_name, mi.description as item_description, 'QR Order' as variation_name, 0 as variation_price_adj
                     FROM qr_orders qo 
                     JOIN menu_items mi ON qo.menu_item_id = mi.item_id 
                     WHERE qo.session_id = (SELECT session_id FROM qr_sessions WHERE table_id = ? AND status IN ('active', 'locked') LIMIT 1)";
    
    $stmt_qr_items = $conn->prepare($sql_qr_items);
    $stmt_qr_items->bind_param("i", $order['table_id']);
    $stmt_qr_items->execute();
    $result_items = $stmt_qr_items->get_result();
    $stmt_qr_items->close();
}

$items = [];
$subtotal = 0;

while ($item = $result_items->fetch_assoc()) {
    // Get add-ons for this item (only for regular order items, not QR orders)
    $addons = [];
    $addon_total = 0;
    
    if (isset($item['order_item_id'])) {
        // Regular order item - get add-ons
        $sql_addons = "SELECT oia.*, ia.name as addon_name, ia.price as addon_price
                       FROM order_item_addons oia 
                       JOIN item_addons ia ON oia.addon_id = ia.addon_id 
                       WHERE oia.order_item_id = ?
                       ORDER BY ia.name";
        $stmt_addons = $conn->prepare($sql_addons);
        $stmt_addons->bind_param("i", $item['order_item_id']);
        $stmt_addons->execute();
        $result_addons = $stmt_addons->get_result();
        
        while ($addon = $result_addons->fetch_assoc()) {
            $addons[] = $addon;
            $addon_total += $addon['addon_price'];
        }
        $stmt_addons->close();
    }
    // QR orders don't have add-ons, so skip add-on processing
    
    $item['addons'] = $addons;
    $item['addon_total'] = $addon_total;
    $items[] = $item;
    $subtotal += $item['subtotal'];
}

// Get payment information if available (optional - may not exist yet)
$payment_info = null;
try {
    $sql_payment = "SELECT ct.*, cu.username as cashier_name
                    FROM cash_float_transactions ct 
                    LEFT JOIN counter_users cu ON ct.created_by = cu.counter_id
                    WHERE ct.transaction_type = 'sale' AND ct.notes LIKE CONCAT('%Order ', ?, '%')
                    ORDER BY ct.created_at DESC LIMIT 1";
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bind_param("i", $order_id);
    $stmt_payment->execute();
    $result_payment = $stmt_payment->get_result();
    $payment_info = $result_payment->fetch_assoc();
    $stmt_payment->close();
} catch (Exception $e) {
    // Payment info not available yet - this is normal for new orders
    $payment_info = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Counter System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .order-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .order-info-row:last-child {
            border-bottom: none;
        }
        .item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        .item-header {
            background: #f8f9fa;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
        }
        .item-line {
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
        }
        .item-details {
            padding: 10px 15px;
            background: #f8f9fa;
            font-size: 0.9em;
            color: #6c757d;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8em;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-paid { background: #cce5ff; color: #004085; }
        .status-completed { background: #e2e3e5; color: #383d41; }
        .btn-back {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
        }
        .total-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="bi bi-receipt"></i> Order Details</h2>
                    <p class="mb-0">Complete order information for counter staff</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Back to Counter
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Order Information -->
        <div class="order-card">
            <div class="order-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-1">
                            <i class="bi bi-receipt"></i> Order #<?= $order['queue_number'] ?>
                        </h3>
                        <p class="mb-0">
                            <?php if ($order['table_number']): ?>
                                <i class="bi bi-table"></i> Table <?= $order['table_number'] ?>
                            <?php else: ?>
                                <i class="bi bi-bag"></i> Takeout Order
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="status-badge status-<?= strtolower($order['status_name']) ?>">
                            <?= $order['status_name'] ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="p-4">
                <div class="order-info-row">
                    <span><strong>Order ID:</strong></span>
                    <span><?= $order['order_id'] ?></span>
                </div>
                <div class="order-info-row">
                    <span><strong>Order Time:</strong></span>
                    <span><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="order-info-row">
                    <span><strong>Status:</strong></span>
                    <span class="status-badge status-<?= strtolower($order['status_name']) ?>">
                        <?= $order['status_name'] ?>
                    </span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                <div class="order-info-row">
                    <span><strong>Discount:</strong></span>
                    <span class="badge bg-success">
                        <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $order['discount_type'])) ?> (₱<?= number_format($order['discount_amount'], 2) ?> off)
                    </span>
                </div>
                <?php endif; ?>
                <?php if ($payment_info): ?>
                <div class="order-info-row">
                    <span><strong>Payment Method:</strong></span>
                    <span><?= ucfirst($payment_info['payment_method']) ?></span>
                </div>
                <div class="order-info-row">
                    <span><strong>Payment Amount:</strong></span>
                    <span>₱<?= number_format($payment_info['payment_amount'], 2) ?></span>
                </div>
                <div class="order-info-row">
                    <span><strong>Payment Time:</strong></span>
                    <span><?= date('M d, Y h:i A', strtotime($payment_info['created_at'])) ?></span>
                </div>
                <?php if ($payment_info['cashier_name']): ?>
                <div class="order-info-row">
                    <span><strong>Cashier:</strong></span>
                    <span><?= htmlspecialchars($payment_info['cashier_name']) ?></span>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="order-card">
            <div class="order-header">
                <h4 class="mb-0"><i class="bi bi-list-ul"></i> Order Items</h4>
            </div>
            
            <div class="p-4">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                    <div class="item">
                        <div class="item-header">
                            <span><?= htmlspecialchars($item['item_name']) ?></span>
                            <span>₱<?= number_format($item['subtotal'], 2) ?></span>
                        </div>
                        
                        <div class="item-line">
                            <span>Quantity: <?= $item['quantity'] ?> x ₱<?= number_format($item['unit_price'], 2) ?></span>
                            <span></span>
                        </div>
                        
                        <?php if ($item['variation_name']): ?>
                        <div class="item-details">
                            <strong>Variation:</strong> <?= htmlspecialchars($item['variation_name']) ?>
                            <?php if ($item['variation_price_adj'] != 0): ?>
                                (<?= $item['variation_price_adj'] > 0 ? '+' : '' ?>₱<?= number_format($item['variation_price_adj'], 2) ?>)
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($item['addons'])): ?>
                        <div class="item-details">
                            <strong>Add-ons:</strong><br>
                            <?php foreach ($item['addons'] as $addon): ?>
                                • <?= htmlspecialchars($addon['addon_name']) ?> (+₱<?= number_format($addon['addon_price'], 2) ?>)<br>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($item['item_description']): ?>
                        <div class="item-details">
                            <strong>Description:</strong> <?= htmlspecialchars($item['item_description']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #6c757d;"></i>
                        <h5 class="mt-3">No items found</h5>
                        <p class="text-muted">This order has no items.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Total -->
        <div class="total-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-1"><i class="bi bi-calculator"></i> Order Summary</h4>
                    <p class="mb-0">Total amount for this order</p>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="mt-2">
                            <small class="text-muted">Original Amount: ₱<?= number_format($order['original_amount'] ?? $order['total_amount'] + $order['discount_amount'], 2) ?></small><br>
                            <small class="text-success">
                                <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $order['discount_type'])) ?> Discount: -₱<?= number_format($order['discount_amount'], 2) ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <h2 class="mb-0">₱<?= number_format($order['total_amount'], 2) ?></h2>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <small class="text-success">
                            <i class="bi bi-check-circle"></i> Discount Applied
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary btn-lg me-3">
                <i class="bi bi-arrow-left"></i> Back to Counter
            </a>
            <?php if ($order['status_id'] == 1): // Pending Payment ?>
                <a href="index.php" class="btn btn-success btn-lg">
                    <i class="bi bi-credit-card"></i> Process Payment
                </a>
            <?php elseif ($order['status_id'] == 4): // Ready ?>
                <a href="index.php" class="btn btn-info btn-lg">
                    <i class="bi bi-eye"></i> View in Counter
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

