<?php
// Orders Management Page for Counter Staff
require_once 'includes/db_connection.php';

// Start session for authentication
session_start();

// Check if the user is logged in
if (!isset($_SESSION["counter_loggedin"]) || $_SESSION["counter_loggedin"] !== true) {
    header("location: counter_login.php");
    exit;
}

$today = date('Y-m-d');
$success_message = '';
$error_message = '';

// Handle order verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_order') {
    $order_id = intval($_POST['order_id']);
    
    // Update order status to verified (status_id = 2 means paid/verified)
    $update_sql = "UPDATE orders SET status_id = 2, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        $success_message = "Order verified successfully.";
    } else {
        $error_message = "Error verifying order: " . $conn->error;
    }
    $stmt->close();
}

// Get all orders for today
$orders_sql = "SELECT o.*, os.name as status_name, t.table_number 
              FROM orders o 
              LEFT JOIN tables t ON o.table_id = t.table_id 
              JOIN order_statuses os ON o.status_id = os.status_id 
              WHERE DATE(o.created_at) = ? 
              ORDER BY o.created_at DESC";
$stmt = $conn->prepare($orders_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$orders_result = $stmt->get_result();
$stmt->close();

require_once 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Orders Management</h1>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Today's Orders</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Table</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result->num_rows > 0): ?>
                            <?php while ($order = $orders_result->fetch_assoc()): ?>
                                <tr data-cy="counter-order-row">
                                    <td><?= htmlspecialchars($order['queue_number']) ?></td>
                                    <td><?= $order['table_number'] ? htmlspecialchars($order['table_number']) : '-' ?></td>
                                    <td>
                                        <span class="badge bg-<?= $order['status_id'] == 2 ? 'success' : 'warning' ?>">
                                            <?= htmlspecialchars($order['status_name']) ?>
                                        </span>
                                    </td>
                                    <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                    <td><?= date('h:i A', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <?php if ($order['status_id'] != 2): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="verify_order">
                                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-primary" data-cy="counter-verify">
                                                    Verify
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-success">Verified</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No orders found for today.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>


