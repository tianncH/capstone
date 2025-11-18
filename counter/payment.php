<?php
// Payment Processing Page for Counter Staff
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

// Get cash float session
$cash_float_sql = "SELECT * FROM cash_float_sessions WHERE shift_date = ? AND assigned_to = 1 AND status = 'active'";
$stmt = $conn->prepare($cash_float_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$cash_float_session = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment') {
    if (!$cash_float_session || $cash_float_session['status'] !== 'active') {
        $error_message = "Cannot process payment: Cash float is not set or inactive.";
    } else {
        $order_id = intval($_POST['order_id']);
        $discount_type = $_POST['discount_type'] ?? 'none';
        $original_amount = floatval($_POST['original_amount'] ?? 0);
        $discount_amount = floatval($_POST['discount_input'] ?? 0);
        $final_amount = $original_amount - $discount_amount;
        $payment_method = $_POST['payment_method'] ?? 'cash';
        
        if ($final_amount < 0) {
            $error_message = "Discount cannot exceed total amount.";
        } else {
            // Update order with payment and discount information
            $update_sql = "UPDATE orders SET 
                          status_id = 2, 
                          discount_type = ?, 
                          discount_amount = ?, 
                          original_amount = ?, 
                          total_amount = ?,
                          updated_at = CURRENT_TIMESTAMP 
                          WHERE order_id = ?";
            $stmt = $conn->prepare($update_sql);
            $discount_type_val = $discount_type === 'none' ? null : $discount_type;
            $discount_amount_val = $discount_amount > 0 ? $discount_amount : null;
            $stmt->bind_param("sdddi", $discount_type_val, $discount_amount_val, $original_amount, $final_amount, $order_id);
            
            if ($stmt->execute()) {
                $success_message = "Payment processed successfully. Status: Paid";
            } else {
                $error_message = "Error processing payment: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get pending orders for payment
$orders_sql = "SELECT o.*, t.table_number 
              FROM orders o 
              LEFT JOIN tables t ON o.table_id = t.table_id 
              WHERE o.status_id = 1 
              AND DATE(o.created_at) = ? 
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
        <h1 class="h2">Payment Processing</h1>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" data-cy="payment-status">
            <strong>Paid:</strong> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!$cash_float_session): ?>
        <div class="alert alert-warning">
            <strong>Warning:</strong> Cash float is not set. Please contact admin to set up cash float before processing payments.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Pending Payments</h5>
        </div>
        <div class="card-body">
            <?php if ($orders_result->num_rows > 0): ?>
                <div class="row">
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Order #<?= htmlspecialchars($order['queue_number']) ?></h6>
                                    <small>Table: <?= $order['table_number'] ? htmlspecialchars($order['table_number']) : '-' ?></small>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="process_payment">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <input type="hidden" name="original_amount" value="<?= $order['total_amount'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Original Amount</label>
                                            <input type="text" class="form-control" value="₱<?= number_format($order['total_amount'], 2) ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Discount Amount</label>
                                            <input type="number" name="discount_input" class="form-control" data-cy="discount-input" 
                                                   min="0" max="<?= $order['total_amount'] ?>" step="0.01" value="0">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Discount Type</label>
                                            <select name="discount_type" class="form-select">
                                                <option value="none">None</option>
                                                <option value="senior_citizen">Senior Citizen</option>
                                                <option value="pwd">PWD</option>
                                                <option value="staff">Staff</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Payment Method</label>
                                            <select name="payment_method" class="form-select" data-cy="payment-method">
                                                <option value="cash">Cash</option>
                                                <option value="card">Card</option>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success w-100" data-cy="confirm-payment">
                                            Process Payment
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-muted">No pending payments found for today.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>


