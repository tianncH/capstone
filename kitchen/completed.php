<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Get today's date
$today = date('Y-m-d');

// Get completed orders for today
$sql_orders = "SELECT o.*, os.name as status_name, t.table_number 
               FROM orders o 
               LEFT JOIN tables t ON o.table_id = t.table_id 
               JOIN order_statuses os ON o.status_id = os.status_id 
               WHERE DATE(o.created_at) = '$today' 
               AND (o.status_id = 4 OR o.status_id = 5) 
               ORDER BY o.updated_at DESC";
$result_orders = $conn->query($sql_orders);

// Count orders by status
$ready_count = 0;
$completed_count = 0;

if ($result_orders->num_rows > 0) {
    while ($order = $result_orders->fetch_assoc()) {
        if ($order['status_id'] == 4) {
            $ready_count++;
        } elseif ($order['status_id'] == 5) {
            $completed_count++;
        }
    }
}

// Reset result pointer
if ($result_orders->num_rows > 0) {
    $result_orders->data_seek(0);
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Completed Orders</h2>
            <div>
                <span class="badge bg-success fs-6 me-2">Ready: <?= $ready_count ?></span>
                <span class="badge bg-secondary fs-6">Completed: <?= $completed_count ?></span>
            </div>
        </div>
        
        <ul class="nav nav-tabs mb-4" id="orderTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="ready-tab" data-bs-toggle="tab" data-bs-target="#ready" type="button" role="tab" aria-controls="ready" aria-selected="true">
                    Ready for Pickup (<?= $ready_count ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab" aria-controls="completed" aria-selected="false">
                    Completed (<?= $completed_count ?>)
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="orderTabsContent">
            <!-- Ready Orders Tab -->
            <div class="tab-pane fade show active" id="ready" role="tabpanel" aria-labelledby="ready-tab">
                <div class="kitchen-display">
                    <?php
                    if ($result_orders->num_rows > 0) {
                        $result_orders->data_seek(0); // Reset result pointer
                        $ready_found = false;
                        
                        while ($order = $result_orders->fetch_assoc()) {
                            if ($order['status_id'] == 4) { // Ready status
                                $ready_found = true;
                                ?>
                                <div class="card order-card order-ready">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            Order #<?= $order['queue_number'] ?>
                                            <?php if ($order['table_number']) { ?>
                                                <span class="badge bg-secondary">Table <?= $order['table_number'] ?></span>
                                            <?php } ?>
                                        </h5>
                                        <span class="badge bg-success">Ready for Pickup</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-3">
                                            <div class="order-time">
                                                Ordered at: <?= date('h:i A', strtotime($order['created_at'])) ?>
                                            </div>
                                            <div class="order-time">
                                                Ready at: <?= date('h:i A', strtotime($order['updated_at'])) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="order-items mb-3">
                                            <?php
                                            // Get order items
                                            $sql_items = "SELECT oi.*, mi.name as item_name, iv.name as variation_name 
                                                          FROM order_items oi 
                                                          JOIN menu_items mi ON oi.item_id = mi.item_id 
                                                          LEFT JOIN item_variations iv ON oi.variation_id = iv.variation_id 
                                                          WHERE oi.order_id = " . $order['order_id'];
                                            
                                            $result_items = $conn->query($sql_items);
                                            
                                            while ($item = $result_items->fetch_assoc()) {
                                                ?>
                                                <div class="item-row">
                                                    <div>
                                                        <span class="item-quantity"><?= $item['quantity'] ?>x</span>
                                                        <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                                                        <?php if ($item['variation_name']) { ?>
                                                            <span class="item-variation"> - <?= htmlspecialchars($item['variation_name']) ?></span>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                        
                                        <div class="alert alert-success">
                                            <i class="bi bi-check-circle"></i> This order is ready for pickup at the counter.
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        
                        if (!$ready_found) {
                            echo '<div class="col-12"><div class="alert alert-info">No orders ready for pickup at the moment.</div></div>';
                        }
                    } else {
                        echo '<div class="col-12"><div class="alert alert-info">No completed orders for today.</div></div>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Completed Orders Tab -->
            <div class="tab-pane fade" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                <div class="kitchen-display">
                    <?php
                    if ($result_orders->num_rows > 0) {
                        $result_orders->data_seek(0); // Reset result pointer
                        $completed_found = false;
                        
                        while ($order = $result_orders->fetch_assoc()) {
                            if ($order['status_id'] == 5) { // Completed status
                                $completed_found = true;
                                ?>
                                <div class="card order-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            Order #<?= $order['queue_number'] ?>
                                            <?php if ($order['table_number']) { ?>
                                                <span class="badge bg-secondary">Table <?= $order['table_number'] ?></span>
                                            <?php } ?>
                                        </h5>
                                        <span class="badge bg-secondary">Completed</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-3">
                                            <div class="order-time">
                                                Ordered at: <?= date('h:i A', strtotime($order['created_at'])) ?>
                                            </div>
                                            <div class="order-time">
                                                Completed at: <?= date('h:i A', strtotime($order['updated_at'])) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="order-items mb-3">
                                            <?php
                                            // Get order items
                                            $sql_items = "SELECT oi.*, mi.name as item_name 
                                                          FROM order_items oi 
                                                          JOIN menu_items mi ON oi.item_id = mi.item_id 
                                                          WHERE oi.order_id = " . $order['order_id'];
                                            
                                            $result_items = $conn->query($sql_items);
                                            
                                            while ($item = $result_items->fetch_assoc()) {
                                                ?>
                                                <div class="item-row">
                                                    <span class="item-quantity"><?= $item['quantity'] ?>x</span>
                                                    <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        
                        if (!$completed_found) {
                            echo '<div class="col-12"><div class="alert alert-info">No completed orders for today.</div></div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<a href="completed.php" class="btn btn-primary btn-lg rounded-circle refresh-btn" title="Refresh Orders">
    <i class="bi bi-arrow-clockwise"></i>
</a>

<?php
require_once 'includes/footer.php';
$conn->close();
?>