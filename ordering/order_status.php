<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Get queue number from GET parameter
$queue_number = isset($_GET['queue']) ? $conn->real_escape_string($_GET['queue']) : '';

// If no queue number provided, show form to enter it
if (empty($queue_number)) {
    ?>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Check Order Status</h5>
                </div>
                <div class="card-body">
                    <form action="order_status.php" method="get">
                        <div class="mb-3">
                            <label for="queue" class="form-label">Enter Queue Number:</label>
                            <input type="text" class="form-control" id="queue" name="queue" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Check Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    // Get order details
    $sql = "SELECT o.*, os.name as status_name 
            FROM orders o 
            JOIN order_statuses os ON o.status_id = os.status_id 
            WHERE o.queue_number = '$queue_number'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Order Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h2>Queue Number: <?= htmlspecialchars($queue_number) ?></h2>
                            <div class="alert alert-info">
                                <h4>Status: <?= htmlspecialchars($order['status_name']) ?></h4>
                            </div>
                            <p>Order placed at: <?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></p>
                        </div>
                        
                        <h5>Order Details:</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Variation</th>
                                    <th>Add-ons</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Price</th>
                                </tr>
                            </thead>
                            <tbody>
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
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= $item['variation_name'] ? htmlspecialchars($item['variation_name']) : '-' ?></td>
                                        <td>
                                            <?php
                                            // Get add-ons for this item
                                            $sql_addons = "SELECT oia.*, ia.name as addon_name 
                                                          FROM order_item_addons oia 
                                                          JOIN item_addons ia ON oia.addon_id = ia.addon_id 
                                                          WHERE oia.order_item_id = " . $item['order_item_id'];
                                            
                                            $result_addons = $conn->query($sql_addons);
                                            
                                            if ($result_addons->num_rows > 0) {
                                                $addons = [];
                                                while ($addon = $result_addons->fetch_assoc()) {
                                                    $addons[] = htmlspecialchars($addon['addon_name']);
                                                }
                                                echo implode(', ', $addons);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td class="text-end">₱<?= number_format($item['subtotal'], 2) ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <?php if (!empty($order['notes'])) { ?>
                            <div class="mt-3">
                                <h6>Special Instructions:</h6>
                                <p><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                            </div>
                        <?php } ?>
                        
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-primary">Back to Menu</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } else {
        ?>
        <div class="alert alert-danger">
            <h4>Order Not Found</h4>
            <p>No order found with queue number: <?= htmlspecialchars($queue_number) ?></p>
            <a href="order_status.php" class="btn btn-primary">Try Again</a>
        </div>
        <?php
    }
}

require_once 'includes/footer.php';
$conn->close();
?>