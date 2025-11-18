<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

$order_id = intval($_GET['order_id'] ?? $_GET['id'] ?? 0);

if (!$order_id) {
    echo '<div class="alert alert-danger">Invalid order ID provided.</div>';
    exit;
}

// Get order details including discount fields
$order_sql = "SELECT o.*, os.name as status_name, t.table_number,
                     o.discount_type, o.discount_percentage, o.discount_amount, o.original_amount
              FROM orders o 
              LEFT JOIN tables t ON o.table_id = t.table_id 
              JOIN order_statuses os ON o.status_id = os.status_id 
              WHERE o.order_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param('i', $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();
$order_stmt->close();

if (!$order) {
    echo '<div class="alert alert-danger">Order not found.</div>';
    exit;
}

// Get order items
$items_sql = "SELECT oi.*, mi.name as item_name, iv.name as variation_name 
              FROM order_items oi 
              JOIN menu_items mi ON oi.item_id = mi.item_id 
              LEFT JOIN item_variations iv ON oi.variation_id = iv.variation_id 
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result();
$items_stmt->close();
?>

<div class="order-details">
    <div class="order-details-container">
        <!-- Back Button - Upper Left -->
        <div class="back-button-container">
            <a href="index.php" class="btn btn-primary back-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 12H5M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>

        <!-- Order Header -->
    <div class="order-header-info">
        <div class="row">
            <div class="col-md-6">
                <h4>Order #<?= htmlspecialchars($order['queue_number']) ?></h4>
                <p class="text-muted">Table <?= $order['table_number'] ?? 'Takeout' ?> • <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></p>
            </div>
            <div class="col-md-6 text-end">
                <span class="badge bg-<?= $order['status_name'] === 'pending' ? 'warning' : 
                                       ($order['status_name'] === 'paid' ? 'info' : 
                                       ($order['status_name'] === 'preparing' ? 'primary' : 
                                       ($order['status_name'] === 'ready' ? 'success' : 
                                       ($order['status_name'] === 'completed' ? 'secondary' : 'danger')))) ?> fs-6">
                    <?= ucfirst($order['status_name']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="order-items-section">
        <h5>Order Items</h5>
        <?php if ($items->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Variation</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $items->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                                </td>
                                <td>
                                    <?= $item['variation_name'] ? htmlspecialchars($item['variation_name']) : '-' ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?= $item['quantity'] ?></span>
                                </td>
                                <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                                <td><strong>₱<?= number_format($item['subtotal'], 2) ?></strong></td>
                            </tr>
                            
                            <?php
                            // Get add-ons for this item
                            $addons_sql = "SELECT oia.*, ia.name as addon_name 
                                          FROM order_item_addons oia 
                                          JOIN item_addons ia ON oia.addon_id = ia.addon_id 
                                          WHERE oia.order_item_id = ?";
                            $addons_stmt = $conn->prepare($addons_sql);
                            $addons_stmt->bind_param('i', $item['order_item_id']);
                            $addons_stmt->execute();
                            $addons = $addons_stmt->get_result();
                            $addons_stmt->close();
                            
                            if ($addons->num_rows > 0):
                            ?>
                                <tr class="addon-row">
                                    <td colspan="5">
                                        <div class="addons-list">
                                            <strong>Add-ons:</strong>
                                            <?php while ($addon = $addons->fetch_assoc()): ?>
                                                <span class="badge bg-light text-dark me-1">
                                                    + <?= htmlspecialchars($addon['addon_name']) ?> (₱<?= number_format($addon['addon_price'], 2) ?>)
                                                </span>
                                            <?php endwhile; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <?php if ($order['discount_amount'] > 0): ?>
                        <tr>
                            <th colspan="4">Original Amount:</th>
                            <th>₱<?= number_format($order['original_amount'] ?? $order['total_amount'] + $order['discount_amount'], 2) ?></th>
                        </tr>
                        <tr class="table-warning">
                            <th colspan="4">
                                <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $order['discount_type'])) ?> Discount:
                            </th>
                            <th class="text-success">-₱<?= number_format($order['discount_amount'], 2) ?></th>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-success">
                            <th colspan="4">Total Amount:</th>
                            <th>₱<?= number_format($order['total_amount'], 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No items found for this order.</div>
        <?php endif; ?>
    </div>

    <!-- Order Notes -->
    <?php if (!empty($order['notes'])): ?>
        <div class="order-notes-section">
            <h5>Notes</h5>
            <div class="alert alert-light">
                <?= nl2br(htmlspecialchars($order['notes'])) ?>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>

<style>
.order-details {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.order-details-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 30px;
    margin: 20px auto;
    max-width: 800px;
    width: 100%;
    position: relative;
}

.back-button-container {
    position: absolute;
    top: -15px;
    left: -15px;
    z-index: 10;
}

.back-btn {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #2c3e50;
    border: 1px solid #34495e;
    color: #ecf0f1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    transition: all 0.2s ease;
    position: relative;
}

.back-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    background: #34495e;
    color: #ffffff;
}

@media (max-width: 768px) {
    .order-details {
        padding: 10px;
    }
    
    .order-details-container {
        padding: 20px;
        margin: 10px auto;
    }
    
    .back-button-container {
        top: -10px;
        left: -10px;
    }
    
    .back-btn {
        width: 45px;
        height: 45px;
        font-size: 1.1rem;
    }
}

.order-header-info {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.order-items-section {
    margin-bottom: 20px;
}

.order-items-section h5 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 600;
}

.addon-row {
    background-color: #f8f9fa;
}

.addons-list {
    padding-left: 20px;
}

.order-notes-section h5 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 600;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #2c3e50;
}

.table-success th {
    background-color: #d4edda !important;
    color: #155724 !important;
}
</style>