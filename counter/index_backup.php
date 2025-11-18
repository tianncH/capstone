<?php
// Start session for authentication
session_start();

// AGGRESSIVE SECURITY CHECK - Force logout and redirect if not properly authenticated
if (!isset($_SESSION["counter_loggedin"]) || 
    $_SESSION["counter_loggedin"] !== true || 
    !isset($_SESSION["counter_user_id"]) || 
    !isset($_SESSION["counter_username"])) {
    
    // Clear ALL session data
    $_SESSION = array();
    session_unset();
    session_destroy();
    
    // Force redirect to login page
    header("Location: counter_login.php?error=access_denied");
    exit();
}

// Verify session is still valid (check if user still exists and is active)
if (isset($_SESSION["counter_user_id"])) {
    require_once 'includes/db_connection.php';
    
    $check_user_sql = "SELECT counter_id, username, is_active FROM counter_users WHERE counter_id = ? AND is_active = 1";
    $check_stmt = $conn->prepare($check_user_sql);
    $check_stmt->bind_param("i", $_SESSION["counter_user_id"]);
    $check_stmt->execute();
    $user_result = $check_stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        // User no longer exists or is inactive - force logout
        session_unset();
        session_destroy();
        header("Location: counter_login.php?error=session_expired");
        exit();
    }
    
    $check_stmt->close();
} else {
    // No user ID in session - force logout
    session_unset();
    session_destroy();
    header("Location: counter_login.php?error=invalid_session");
    exit();
}

require_once 'includes/db_connection.php';

// Simple, clean counter system - no blinking, no shaking!
$today = date('Y-m-d');

// Get daily sales summary (including pending orders for counter display)
$daily_sales_sql = "SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(total_amount), 0) as total_sales
    FROM orders 
    WHERE DATE(created_at) = ? 
    AND status_id IN (2, 4, 5)";

$stmt = $conn->prepare($daily_sales_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$daily_sales = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get cash float status
$cash_float_sql = "SELECT * FROM cash_float_sessions WHERE shift_date = ? AND assigned_to = 1 AND status = 'active'";
$stmt = $conn->prepare($cash_float_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$cash_float_session = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get current cash on hand
$current_cash = 0;
if ($cash_float_session) {
    $cash_sql = "SELECT cash_on_hand FROM cash_float_transactions WHERE session_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($cash_sql);
    $stmt->bind_param("i", $cash_float_session['session_id']);
    $stmt->execute();
    $cash_result = $stmt->get_result();
    if ($cash_row = $cash_result->fetch_assoc()) {
        $current_cash = $cash_row['cash_on_hand'];
            }
            $stmt->close();
}

        // Fetch bill request notifications
        $bill_notifications = [];
        try {
            $notification_stmt = $conn->prepare("SELECT tsn.*, t.table_number 
                                                FROM table_session_notifications tsn 
                                                JOIN table_sessions ts ON tsn.session_id = ts.session_id 
                                                JOIN tables t ON ts.table_id = t.table_id 
                                                WHERE tsn.notification_type = 'bill_request' 
                                                AND tsn.status = 'pending' 
                                                ORDER BY tsn.created_at DESC");
            $notification_stmt->execute();
            $notification_result = $notification_stmt->get_result();
            while ($notification = $notification_result->fetch_assoc()) {
                $bill_notifications[] = $notification;
            }
            $notification_stmt->close();
        } catch (Exception $e) {
            // If there are no table sessions or notifications, just continue with empty array
            $bill_notifications = [];
        }

        // Fetch QR session confirmation requests
        $qr_sessions = [];
        try {
            $qr_stmt = $conn->prepare("SELECT qs.*, t.table_number, t.qr_code 
                                      FROM qr_sessions qs 
                                      JOIN tables t ON qs.table_id = t.table_id 
                                      WHERE qs.status = 'active' 
                                      AND qs.confirmed_by_counter = FALSE 
                                      ORDER BY qs.created_at DESC");
            $qr_stmt->execute();
            $qr_result = $qr_stmt->get_result();
            while ($qr_session = $qr_result->fetch_assoc()) {
                $qr_sessions[] = $qr_session;
            }
            $qr_stmt->close();
        } catch (Exception $e) {
            // If there are no QR sessions, just continue with empty array
            $qr_sessions = [];
        }

        // Fetch orders that need kitchen validation (status = 2, paid but not sent to kitchen)
        $kitchen_validation_orders = [];
        try {
            $validation_stmt = $conn->prepare("SELECT o.*, os.name as status_name, t.table_number 
                                              FROM orders o 
                                              LEFT JOIN tables t ON o.table_id = t.table_id 
                                              JOIN order_statuses os ON o.status_id = os.status_id 
                                              WHERE o.status_id = 2 
                                              AND DATE(o.created_at) = ? 
                                              ORDER BY o.created_at ASC");
            $validation_stmt->bind_param("s", $today);
            $validation_stmt->execute();
            $validation_result = $validation_stmt->get_result();
            while ($order = $validation_result->fetch_assoc()) {
                $kitchen_validation_orders[] = $order;
            }
            $validation_stmt->close();
        } catch (Exception $e) {
            $kitchen_validation_orders = [];
        }


// Get orders for today (excluding cancelled orders)
$orders_sql = "SELECT o.*, os.name as status_name, t.table_number 
               FROM orders o 
               LEFT JOIN tables t ON o.table_id = t.table_id 
               JOIN order_statuses os ON o.status_id = os.status_id 
               WHERE DATE(o.created_at) = ? 
               AND o.status_id != 6
               ORDER BY o.status_id ASC, o.created_at DESC";

$stmt = $conn->prepare($orders_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$orders = $stmt->get_result();
            $stmt->close();
            
// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $order_id = intval($_POST['order_id']);
    
    if ($_POST['action'] === 'mark_paid') {
        // Simple payment processing
        $update_sql = "UPDATE orders SET status_id = 2, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
        $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $order_id);
            
        if ($stmt->execute()) {
            // Update daily sales
            $today = date('Y-m-d');
            $check_sql = "SELECT daily_sales_id FROM daily_sales WHERE date = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $today);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing
                $update_daily_sql = "UPDATE daily_sales SET total_orders = total_orders + 1, total_sales = total_sales + (SELECT total_amount FROM orders WHERE order_id = ?), updated_at = CURRENT_TIMESTAMP WHERE date = ?";
                $update_daily_stmt = $conn->prepare($update_daily_sql);
                $update_daily_stmt->bind_param("is", $order_id, $today);
                $update_daily_stmt->execute();
                $update_daily_stmt->close();
            } else {
                // Create new
                $insert_daily_sql = "INSERT INTO daily_sales (date, total_orders, total_sales) SELECT ?, 1, total_amount FROM orders WHERE order_id = ?";
                $insert_daily_stmt = $conn->prepare($insert_daily_sql);
                $insert_daily_stmt->bind_param("si", $today, $order_id);
                $insert_daily_stmt->execute();
                $insert_daily_stmt->close();
            }
            
            $check_stmt->close();
            $success_message = "Order marked as paid successfully!";
        }
        $stmt->close();
    }
    
    if ($_POST['action'] === 'mark_completed') {
        $order_id = intval($_POST['order_id']);
        $update_sql = "UPDATE orders SET status_id = 5, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute()) {
            $success_message = "Order marked as completed!";
        }
        $stmt->close();
    }
    
    if ($_POST['action'] === 'cancel_order') {
        $order_id = intval($_POST['order_id']);
        $cancel_sql = "UPDATE orders SET status_id = 6, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
        $stmt = $conn->prepare($cancel_sql);
        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute()) {
            $success_message = "Order cancelled successfully!";
        }
        $stmt->close();
    }
    
        if ($_POST['action'] === 'acknowledge_bill_request') {
            $notification_id = intval($_POST['notification_id']);
            $acknowledge_sql = "UPDATE table_session_notifications SET status = 'acknowledged', acknowledged_at = NOW() WHERE notification_id = ?";
            $stmt = $conn->prepare($acknowledge_sql);
            $stmt->bind_param("i", $notification_id);
            
            if ($stmt->execute()) {
                $success_message = "Bill request acknowledged!";
            }
            $stmt->close();
        }
        
        if ($_POST['action'] === 'send_to_kitchen') {
            $order_id = intval($_POST['order_id']);
            
            // Update order status to preparing (status_id = 3)
            $update_sql = "UPDATE orders SET status_id = 3, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $order_id);
            
            if ($stmt->execute()) {
                // Add to order status history
                $history_sql = "INSERT INTO order_status_history (order_id, status_id, notes) 
                                VALUES (?, 3, 'Order sent to kitchen by counter staff')";
                $history_stmt = $conn->prepare($history_sql);
                $history_stmt->bind_param("i", $order_id);
                $history_stmt->execute();
                $history_stmt->close();
                
                $success_message = "Order sent to kitchen successfully!";
            } else {
                $error_message = "Error sending order to kitchen.";
            }
            $stmt->close();
        }
        
        if ($_POST['action'] === 'hold_order') {
            $order_id = intval($_POST['order_id']);
            
            // Add a note to hold the order
            $hold_sql = "UPDATE orders SET notes = CONCAT(COALESCE(notes, ''), '\n[HOLD] Order held by counter staff at " . date('Y-m-d H:i:s') . "') WHERE order_id = ?";
            $stmt = $conn->prepare($hold_sql);
            $stmt->bind_param("i", $order_id);
            
            if ($stmt->execute()) {
                $success_message = "Order held successfully!";
            } else {
                $error_message = "Error holding order.";
            }
            $stmt->close();
        }
        
    if ($_POST['action'] === 'confirm_order') {
        $order_id = intval($_POST['order_id']);
        
        // Update order status to preparing (status_id = 3)
        $update_sql = "UPDATE orders SET status_id = 3, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute()) {
            // Add to order status history
            $history_sql = "INSERT INTO order_status_history (order_id, status_id, notes) 
                            VALUES (?, 3, 'Order confirmed by counter - ready for kitchen preparation')";
            $history_stmt = $conn->prepare($history_sql);
            $history_stmt->bind_param("i", $order_id);
            $history_stmt->execute();
            $history_stmt->close();
            
            $success_message = "Order confirmed and sent to kitchen!";
        } else {
            $error_message = "Error confirming order.";
        }
        $stmt->close();
    }
    
    if ($_POST['action'] === 'mark_completed') {
        $order_id = intval($_POST['order_id']);
        
        // Update order status to completed (status_id = 5)
        $update_sql = "UPDATE orders SET status_id = 5, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute()) {
            // Add to order status history
            $history_sql = "INSERT INTO order_status_history (order_id, status_id, notes) 
                            VALUES (?, 5, 'Order completed - picked up by customer')";
            $history_stmt = $conn->prepare($history_sql);
            $history_stmt->bind_param("i", $order_id);
            $history_stmt->execute();
            $history_stmt->close();
            
            $success_message = "Order marked as completed!";
        } else {
            $error_message = "Error marking order as completed.";
        }
        $stmt->close();
    }
    
    // Handle QR session confirmation
    if ($_POST['action'] === 'confirm_qr_session') {
        $session_id = intval($_POST['session_id']);
        
        // Update QR session as confirmed by counter
        $confirm_sql = "UPDATE qr_sessions SET confirmed_by_counter = TRUE, confirmed_at = NOW(), confirmed_by = 1 WHERE session_id = ?";
        $stmt = $conn->prepare($confirm_sql);
        $stmt->bind_param("i", $session_id);
        
        if ($stmt->execute()) {
            // Update any pending notifications for this session
            $notif_sql = "UPDATE qr_session_notifications SET status = 'acknowledged', acknowledged_at = NOW() WHERE session_id = ? AND notification_type = 'new_session'";
            $notif_stmt = $conn->prepare($notif_sql);
            $notif_stmt->bind_param("i", $session_id);
            $notif_stmt->execute();
            $notif_stmt->close();
            
            $success_message = "QR session confirmed successfully!";
        } else {
            $error_message = "Error confirming QR session.";
        }
        $stmt->close();
    }
            
            // Redirect to prevent form resubmission
    header("Location: index.php?success=" . urlencode($success_message ?? 'Action completed'));
            exit;
        }
?>

        <?php
$page_title = "Orders Management";
require_once 'includes/header_clean.php';
?>
    <div class="container-fluid">
        <!-- Header -->
        <div class="header">
            <h1><i class="bi bi-clipboard-check"></i> Orders Management</h1>
            <p class="mb-0">Simple and stable order management</p>
            </div>
        
        <!-- Success Message -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>
        
        <!-- Daily Stats -->
            <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <h5><i class="bi bi-calendar"></i> Today's Date</h5>
                    <h3><?= date('M j, Y') ?></h3>
                </div>
                </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h5><i class="bi bi-receipt"></i> Total Orders</h5>
                    <h3><?= $daily_sales['total_orders'] ?></h3>
            </div>
        </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h5><i class="bi bi-currency-dollar"></i> Total Sales</h5>
                    <h3>₱<?= number_format($daily_sales['total_sales'], 2) ?></h3>
                    </div>
                            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h5><i class="bi bi-cash-coin"></i> Cash Float</h5>
                    <h3><?= $cash_float_session ? '₱' . number_format($current_cash, 2) : 'Not Set' ?></h3>
                    <small><?= $cash_float_session ? 'Active' : 'Inactive' ?></small>
                            </div>
                                </div>
                            </div>
        
        <!-- Active Orders by Table -->
        <?php 
        // Use only kitchen validation orders (ready orders are handled in main orders section)
        $all_active_orders = $kitchen_validation_orders;
        
        // Group orders by table
        $table_groups = [];
        foreach ($all_active_orders as $order) {
            $table_num = $order['table_number'] ?? 'Takeout';
            if (!isset($table_groups[$table_num])) {
                $table_groups[$table_num] = [];
            }
            $table_groups[$table_num][] = $order;
        }
        
        if (!empty($table_groups)): ?>
        <div class="header">
            <h3><i class="bi bi-egg-fried text-primary"></i> Kitchen Validation Required</h3>
            <p class="text-muted">Orders paid and ready to send to kitchen</p>
        </div>
        
        <div class="row mb-4">
            <?php foreach ($table_groups as $table_number => $table_orders): ?>
            <div class="col-md-6 mb-4">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-table"></i> Table <?= $table_number ?>
                            <span class="badge bg-light text-dark ms-2"><?= count($table_orders) ?> Orders</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($table_orders as $order): ?>
                        <div class="order-item mb-3 p-3 border rounded">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="badge bg-warning me-2">Ready for Kitchen</span>
                                        <strong>Order #<?= $order['queue_number'] ?></strong>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?= date('h:i A', strtotime($order['created_at'])) ?> • 
                                        <i class="bi bi-currency-dollar"></i> ₱<?= number_format($order['total_amount'], 2) ?>
                                    </small>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="d-grid gap-2">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="send_to_kitchen">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('Send this order to kitchen?')">
                                                <i class="bi bi-send"></i> Send to Kitchen
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="hold_order">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                                                <i class="bi bi-pause"></i> Hold
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        
        <!-- Bill Request Notifications -->
        <?php if (!empty($bill_notifications)): ?>
        <div class="header">
            <h3><i class="bi bi-bell-fill text-warning"></i> Bill Requests</h3>
        </div>
        
        <div class="row mb-4">
            <?php foreach ($bill_notifications as $notification): ?>
            <div class="col-md-6 mb-3">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="bi bi-table"></i> Table <?= $notification['table_number'] ?> 
                            <span class="badge bg-dark ms-2">Payment Request</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><?= htmlspecialchars($notification['message']) ?></p>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> <?= date('h:i A', strtotime($notification['created_at'])) ?>
                        </small>
                        <div class="mt-3">
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal<?= $notification['notification_id'] ?>">
                                <i class="bi bi-cash-coin"></i> Process Payment
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="acknowledge_bill_request">
                                <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-check-circle"></i> Acknowledge
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- QR Session Confirmations -->
        <?php if (!empty($qr_sessions)): ?>
        <div class="header">
            <h3><i class="bi bi-qr-code text-primary"></i> QR Session Confirmations</h3>
        </div>
        
        <div class="row mb-4">
            <?php foreach ($qr_sessions as $qr_session): ?>
            <div class="col-md-6 mb-3">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-table"></i> Table <?= $qr_session['table_number'] ?> 
                            <span class="badge bg-light text-primary ms-2">QR Session</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>QR Code:</strong> <?= htmlspecialchars($qr_session['qr_code']) ?><br>
                            <strong>Session:</strong> <?= substr($qr_session['session_token'], 0, 8) ?>...<br>
                            <strong>Device:</strong> <?= substr($qr_session['device_fingerprint'], 0, 12) ?>...
                        </p>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> Started: <?= date('h:i A', strtotime($qr_session['created_at'])) ?>
                        </small>
                        <div class="mt-3">
                            <div class="d-grid gap-2">
                                <a href="qr_order_details.php?qr_session_id=<?= $qr_session['session_id'] ?>" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-eye"></i> View Details
                                </a>
                                <?php if (!$qr_session['confirmed_by_counter']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="confirm_qr_session">
                                    <input type="hidden" name="session_id" value="<?= $qr_session['session_id'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle"></i> Confirm Session
                                    </button>
                                </form>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                        onclick="window.open('../ordering/secure_qr_menu.php?qr=<?= urlencode($qr_session['qr_code']) ?>', '_blank')">
                                    <i class="bi bi-eye"></i> View Customer Menu
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Ready Orders -->
        
        <!-- Orders -->
        <div class="header">
            <h3><i class="bi bi-list-ul"></i> Today's Orders</h3>
                                </div>
                                                
        <?php if ($orders->num_rows > 0): ?>
                                            <?php
            // Group orders by table
            $orders->data_seek(0); // Reset pointer
            $table_orders = [];
            while ($order = $orders->fetch_assoc()) {
                $table_key = $order['table_number'] ?? 'Takeout';
                if (!isset($table_orders[$table_key])) {
                    $table_orders[$table_key] = [];
                }
                $table_orders[$table_key][] = $order;
            }
            ?>
            
            <?php foreach ($table_orders as $table_number => $table_order_list): ?>
                                                <?php
                $total_orders = count($table_order_list);
                // Only include paid orders in total amount (status 2, 4, 5)
                $paid_orders_for_total = array_filter($table_order_list, function($order) { 
                    return in_array($order['status_id'], [2, 4, 5]); 
                });
                $total_amount = array_sum(array_column($paid_orders_for_total, 'total_amount'));
                $pending_orders = array_filter($table_order_list, function($order) { return $order['status_id'] == 1; });
                $paid_orders = array_filter($table_order_list, function($order) { return $order['status_id'] == 2; });
                $pending_count = count($pending_orders);
                $paid_count = count($paid_orders);
                ?>
                
                <div class="table-group-card mb-4">
                    <div class="table-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-1">
                                    <i class="bi bi-table"></i> Table <?= $table_number ?>
                                </h4>
                                <div class="table-stats">
                                    <span class="badge bg-primary me-2"><?= $total_orders ?> Orders</span>
                                    <span class="badge bg-success me-2">₱<?= number_format($total_amount, 2) ?> Total</span>
                                    <?php if ($pending_count > 0): ?>
                                        <span class="badge bg-warning me-2"><?= $pending_count ?> Pending Payment</span>
                                    <?php endif; ?>
                                    <?php if ($paid_count > 0): ?>
                                        <span class="badge bg-info"><?= $paid_count ?> Paid</span>
                                    <?php endif; ?>
                                        </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#tableOrders<?= $table_number ?>" aria-expanded="false">
                                    <i class="bi bi-chevron-down"></i> View Orders (<?= $total_orders ?>)
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                    <div class="collapse" id="tableOrders<?= $table_number ?>">
                        <div class="table-orders-list">
                            <?php foreach ($table_order_list as $order): ?>
                                <div class="order-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="badge <?= $order['status_id'] == 1 ? 'bg-warning' : ($order['status_id'] == 2 ? 'bg-info' : ($order['status_id'] == 3 ? 'bg-primary' : ($order['status_id'] == 4 ? 'bg-success' : 'bg-secondary'))) ?> me-2">
                                                    <?= $order['status_id'] == 1 ? 'Pending Payment' : $order['status_name'] ?>
                                                </span>
                                                <strong>Order #<?= $order['queue_number'] ?></strong>
                                        </div>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> <?= date('h:i A', strtotime($order['created_at'])) ?> • 
                                                <i class="bi bi-currency-dollar"></i> ₱<?= number_format($order['total_amount'], 2) ?>
                                            </small>
                                            </div>
                                        <div class="col-md-4 text-end">
                                            <div class="d-grid gap-2">
                                                <!-- View Details Button - Always Available -->
                                                <a href="order_details.php?order_id=<?= $order['order_id'] ?>" class="btn btn-outline-info btn-sm">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>
                                                
                                                <?php if ($order['status_id'] == 1): // Pending ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="confirm_order">
                                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirm this order for kitchen preparation?')">
                                                            <i class="bi bi-check-circle"></i> Confirm Order
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="cancel_order">
                                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Cancel this order?')">
                                                            <i class="bi bi-x-circle"></i> Cancel
                                                        </button>
                                                    </form>
                                                <?php elseif ($order['status_id'] == 4): // Ready ?>
                                                    <div class="text-center">
                                                        <span class="badge bg-success">Ready for Payment</span>
                                                        <small class="text-muted d-block mt-1">Waiting for customer to request bill</small>
                                                    </div>
                                                <?php elseif ($order['status_id'] == 2): // Paid ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="mark_completed">
                                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                        <button type="submit" class="btn btn-outline-success btn-sm" onclick="return confirm('Mark as completed?')">
                                                            <i class="bi bi-check-circle"></i> Complete
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">No action needed</span>
                                                <?php endif; ?>
                                            </div>
                                                </div>
                                            </div>
                                                </div>
                            <?php endforeach; ?>
                                            </div>
                                        </div>
                                        </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="order-card text-center">
                <h5><i class="bi bi-inbox"></i> No orders for today</h5>
                <p class="text-muted">Orders will appear here when customers place them.</p>
                                    </div>
        <?php endif; ?>
        
        <!-- Payment Modals -->
                <?php
        // Reset orders pointer for modals
        $orders->data_seek(0);
        while ($order = $orders->fetch_assoc()): 
            if ($order['status_id'] == 1): // Show modals only for pending orders
        ?>
            <!-- Payment Modal for Order <?= $order['order_id'] ?> -->
            <div class="modal fade" id="paymentModal<?= $order['order_id'] ?>" tabindex="-1" aria-labelledby="paymentModalLabel<?= $order['order_id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="paymentModalLabel<?= $order['order_id'] ?>">
                                <i class="bi bi-cash-coin"></i> Process Cash Payment
                                    </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="bi bi-receipt"></i> Order Details</h6>
                                    <div class="card payment-detail-card">
                                <div class="card-body">
                                            <p><strong>Order #:</strong> <?= $order['queue_number'] ?></p>
                                            <p><strong>Table:</strong> <?= $order['table_number'] ?? 'Takeout' ?></p>
                                            <p><strong>Time:</strong> <?= date('h:i A', strtotime($order['created_at'])) ?></p>
                                            <hr>
                                            <div class="text-center">
                                                <h4 class="text-success"><strong>Total Amount: ₱<?= number_format($order['total_amount'], 2) ?></strong></h4>
                                                </div>
                                            </div>
                                            </div>
                                    </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-calculator"></i> Payment Details</h6>
                                    <div class="card payment-detail-card">
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="cashReceived<?= $order['order_id'] ?>" class="form-label">
                                                    <i class="bi bi-currency-dollar"></i> Cash Received
                                                </label>
                                                <div class="input-group input-group-lg">
                                                    <span class="input-group-text">₱</span>
                                                    <input type="number" class="form-control form-control-lg" id="cashReceived<?= $order['order_id'] ?>" 
                                                           step="0.01" min="<?= $order['total_amount'] ?>" 
                                                           value="<?= $order['total_amount'] ?>" 
                                                           onchange="calculateChange(<?= $order['order_id'] ?>, <?= $order['total_amount'] ?>)"
                                                           placeholder="Enter amount received">
                                </div>
                            </div>
                                                <div class="mb-3">
                                                <label class="form-label">
                                                    <i class="bi bi-arrow-right-circle"></i> Change to Give
                                                </label>
                                                <div class="change-display change-positive" id="changeDisplay<?= $order['order_id'] ?>">
                                                    ₱0.00
            </div>
                                </div>
                                            <div class="mb-3">
                                                <label class="form-label">Quick Amounts</label>
                                                <div class="btn-group w-100" role="group">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmount(<?= $order['order_id'] ?>, <?= $order['total_amount'] ?>, 0)">
                                                        Exact (₱<?= number_format($order['total_amount'], 2) ?>)
                                    </button>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmount(<?= $order['order_id'] ?>, <?= $order['total_amount'] ?>, 1)">
                                                        +₱1
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmount(<?= $order['order_id'] ?>, <?= $order['total_amount'] ?>, 5)">
                                                        +₱5
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmount(<?= $order['order_id'] ?>, <?= $order['total_amount'] ?>, 10)">
                                                        +₱10
                                        </button>
                                    </div>
                                </div>
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i> 
                                                <small>Cash received must be at least the order total (₱<?= number_format($order['total_amount'], 2) ?>)</small>
                            </div>
                                            </div>
                                                </div>
                                                </div>
                                    </div>
                                            </div>
                                            <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle"></i> Cancel
                                    </button>
                            <button type="button" class="btn btn-success" onclick="processCashPayment(<?= $order['order_id'] ?>, <?= $order['total_amount'] ?>, this)">
                                <i class="bi bi-check-circle"></i> Confirm Payment
                            </button>
                                            </div>
                                    </div>
                                </div>
                            </div>
                            <?php
            endif;
        endwhile; 
        ?>
        
        <!-- Navigation -->
        <div class="header text-center">
            <a href="cash_float.php" class="btn btn-primary">
                <i class="bi bi-cash-coin"></i> Cash Float Status
            </a>
            <a href="daily_sales.php" class="btn btn-primary">
                <i class="bi bi-graph-up"></i> Daily Sales
            </a>
            <button type="button" class="btn btn-secondary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
                                    </button>
                                    </div>
                                    </div>

<script>
        function calculateChange(orderId, totalAmount) {
            const cashReceived = parseFloat(document.getElementById('cashReceived' + orderId).value) || 0;
            const change = cashReceived - totalAmount;
            
            const changeDisplay = document.getElementById('changeDisplay' + orderId);
            const changeValue = change >= 0 ? change.toFixed(2) : '0.00';
            changeDisplay.textContent = '₱' + changeValue;
            
            // Update display styling based on change amount
            changeDisplay.className = 'change-display';
            if (change > 0) {
                changeDisplay.classList.add('change-positive');
            } else if (change < 0) {
                changeDisplay.classList.add('change-negative');
            } else {
                // Exact amount - neutral styling
                changeDisplay.style.background = 'linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%)';
                changeDisplay.style.color = '#495057';
                changeDisplay.style.border = '2px solid #6c757d';
            }
        }
        
        function setQuickAmount(orderId, totalAmount, extraAmount) {
            const quickAmount = totalAmount + extraAmount;
            document.getElementById('cashReceived' + orderId).value = quickAmount.toFixed(2);
            calculateChange(orderId, totalAmount);
        }
        
        function processCashPayment(orderId, totalAmount, buttonElement) {
    const cashReceivedInput = document.getElementById('cashReceived' + orderId);
    const cashReceived = parseFloat(cashReceivedInput.value.replace(/,/g, ''));
    
    if (!cashReceived || cashReceived < totalAmount) {
        alert('Please enter a valid cash amount that covers the order total.');
        return;
    }
    
            const button = buttonElement;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    button.disabled = true;
    
    fetch('cash_payment_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=process_cash_payment&order_id=${orderId}&cash_received=${cashReceived}`
    })
        .then(response => response.json())
        .then(data => {
        if (data.success) {
                    alert(`✅ Payment processed successfully!\n\n` +
                          `Order: ${data.queue_number}\n` +
                          `Total: ₱${data.total_amount.toLocaleString('en-US', {minimumFractionDigits: 2})}\n` +
                          `Cash Received: ₱${data.cash_received.toLocaleString('en-US', {minimumFractionDigits: 2})}\n` +
                          `Change: ₱${data.change.toLocaleString('en-US', {minimumFractionDigits: 2})}\n\n` +
                          `Cash Float Updated: ₱${data.cash_on_hand.toLocaleString('en-US', {minimumFractionDigits: 2})}`);
                    
                    const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal' + orderId));
            modal.hide();
                location.reload();
        } else {
                    alert('❌ Error: ' + data.message);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
                alert('❌ An error occurred while processing the payment.');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function processBillPayment(notificationId, sessionId, totalAmount, buttonElement) {
    const cashReceivedInput = document.getElementById('cashReceived' + notificationId);
    const cashReceived = parseFloat(cashReceivedInput.value.replace(/,/g, ''));
    
    if (!cashReceived || cashReceived < totalAmount) {
        alert('Please enter a valid cash amount that covers the bill total.');
        return;
    }
    
    const button = buttonElement;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    button.disabled = true;
    
    fetch('cash_payment_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=process_bill_payment&notification_id=${notificationId}&session_id=${sessionId}&total_amount=${totalAmount}&cash_received=${cashReceived}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`✅ Bill payment processed successfully!\n\n` +
                  `Table: ${data.table_number}\n` +
                  `Total: ₱${data.total_amount.toLocaleString('en-US', {minimumFractionDigits: 2})}\n` +
                  `Cash Received: ₱${data.cash_received.toLocaleString('en-US', {minimumFractionDigits: 2})}\n` +
                  `Change Given: ₱${data.change.toLocaleString('en-US', {minimumFractionDigits: 2})}\n\n` +
                  `Cash Float Updated: ₱${data.cash_on_hand.toLocaleString('en-US', {minimumFractionDigits: 2})}\n\n` +
                  `✅ Table session closed - ready for new customers!`);
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal' + notificationId));
            modal.hide();
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ An error occurred while processing the payment.');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>

<!-- Bill Request Payment Modals -->
<?php foreach ($bill_notifications as $notification): ?>
<?php
// Extract total amount from the notification message
preg_match('/₱([\d,]+\.?\d*)/', $notification['message'], $matches);
$total_amount = isset($matches[1]) ? str_replace(',', '', $matches[1]) : '0.00';
?>
<!-- Payment Modal for Bill Request <?= $notification['notification_id'] ?> -->
<div class="modal fade" id="paymentModal<?= $notification['notification_id'] ?>" tabindex="-1" aria-labelledby="paymentModalLabel<?= $notification['notification_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="paymentModalLabel<?= $notification['notification_id'] ?>">
                    <i class="bi bi-cash-coin"></i> Process Table Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="bi bi-receipt"></i> Payment Details</h6>
                        <div class="card payment-detail-card">
                            <div class="card-body">
                                <p><strong>Table:</strong> <?= $notification['table_number'] ?></p>
                                <p><strong>Request Time:</strong> <?= date('h:i A', strtotime($notification['created_at'])) ?></p>
                                <p><strong>Message:</strong> <?= htmlspecialchars($notification['message']) ?></p>
                                <hr>
                                <div class="text-center">
                                    <h4 class="text-success"><strong>Total Amount: ₱<?= number_format($total_amount, 2) ?></strong></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-calculator"></i> Cash Transaction</h6>
                        <div class="card payment-detail-card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="cashReceived<?= $notification['notification_id'] ?>" class="form-label">
                                        <i class="bi bi-currency-dollar"></i> Cash Received from Customer
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control form-control-lg" id="cashReceived<?= $notification['notification_id'] ?>" 
                                               step="0.01" min="<?= $total_amount ?>" 
                                               value="<?= $total_amount ?>" 
                                               onchange="calculateChange(<?= $notification['notification_id'] ?>, <?= $total_amount ?>)"
                                               placeholder="Enter amount received">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-arrow-right-circle"></i> Change to Give Customer
                                    </label>
                                    <div class="change-display change-positive" id="changeDisplay<?= $notification['notification_id'] ?>">
                                        ₱0.00
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Quick Amounts</label>
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmount(<?= $notification['notification_id'] ?>, <?= $total_amount ?>, 0)">
                                            Exact (₱<?= number_format($total_amount, 2) ?>)
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmount(<?= $notification['notification_id'] ?>, <?= $total_amount ?>, 1)">
                                            +₱1
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmount(<?= $notification['notification_id'] ?>, <?= $total_amount ?>, 5)">
                                            +₱5
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmount(<?= $notification['notification_id'] ?>, <?= $total_amount ?>, 10)">
                                            +₱10
                                        </button>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> 
                                    <small>Cash received must be at least the bill total (₱<?= number_format($total_amount, 2) ?>)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" onclick="processBillPayment(<?= $notification['notification_id'] ?>, <?= $notification['session_id'] ?>, <?= $total_amount ?>, this)">
                    <i class="bi bi-check-circle"></i> Complete Payment
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php require_once 'includes/footer_clean.php'; ?>