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
require_once '../admin/includes/discount_functions.php';

// Simple, clean counter system - no blinking, no shaking!
$today = date('Y-m-d');

// Get daily sales summary (including pending orders for counter display)
$daily_sales_sql = "SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(total_amount), 0) as total_sales
    FROM orders 
    WHERE DATE(created_at) = ? 
    AND status_id = 2";

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

        // Fetch bill request notifications (regular table sessions)
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

        // Fetch QR session bill request notifications
        $qr_bill_notifications = [];
        try {
            $qr_notification_stmt = $conn->prepare("SELECT qsn.*, t.table_number 
                                                   FROM qr_session_notifications qsn 
                                                   JOIN qr_sessions qs ON qsn.session_id = qs.session_id 
                                                   JOIN tables t ON qs.table_id = t.table_id 
                                                   WHERE qsn.notification_type = 'bill_request' 
                                                   AND qsn.status = 'pending' 
                                                   ORDER BY qsn.created_at DESC");
            $qr_notification_stmt->execute();
            $qr_notification_result = $qr_notification_stmt->get_result();
            while ($notification = $qr_notification_result->fetch_assoc()) {
                $qr_bill_notifications[] = $notification;
            }
            $qr_notification_stmt->close();
        } catch (Exception $e) {
            // If there are no QR sessions or notifications, just continue with empty array
            $qr_bill_notifications = [];
        }

        // Fetch QR session confirmation requests (unconfirmed)
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

        // Fetch confirmed QR sessions
        $confirmed_qr_sessions = [];
        try {
            $confirmed_stmt = $conn->prepare("SELECT qs.*, t.table_number, t.qr_code 
                                             FROM qr_sessions qs 
                                             JOIN tables t ON qs.table_id = t.table_id 
                                             WHERE qs.status = 'active' 
                                             AND qs.confirmed_by_counter = TRUE 
                                             ORDER BY qs.created_at DESC");
            $confirmed_stmt->execute();
            $confirmed_result = $confirmed_stmt->get_result();
            while ($confirmed_session = $confirmed_result->fetch_assoc()) {
                $confirmed_qr_sessions[] = $confirmed_session;
            }
            $confirmed_stmt->close();
        } catch (Exception $e) {
            // If there are no confirmed QR sessions, just continue with empty array
            $confirmed_qr_sessions = [];
        }

        // Fetch orders that need kitchen validation (status = 2, paid but not sent to kitchen)
        // EXCLUDE QR orders that have already been served (they are done after payment)
        $kitchen_validation_orders = [];
        try {
            $validation_stmt = $conn->prepare("SELECT o.*, os.name as status_name, t.table_number 
                                              FROM orders o 
                                              LEFT JOIN tables t ON o.table_id = t.table_id 
                                              JOIN order_statuses os ON o.status_id = os.status_id 
                                              WHERE o.status_id = 2 
                                              AND DATE(o.created_at) = ? 
                                              AND o.order_id NOT IN (
                                                  SELECT DISTINCT o2.order_id 
                                                  FROM orders o2 
                                                  JOIN order_status_history osh ON o2.order_id = osh.order_id 
                                                  WHERE osh.status_id = 5 
                                                  AND o2.status_id = 2
                                              )
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
$orders_sql = "SELECT o.*, os.name as status_name, t.table_number,
                      o.discount_type, o.discount_percentage, o.discount_amount, o.original_amount
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
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if ($_POST['action'] === 'apply_discount') {
        $order_id = intval($_POST['order_id']);
        $discount_type = $_POST['discount_type'];
        
        // Initialize discount manager
        $discountManager = new DiscountManager($conn);
        
        // Apply discount to order
        $result = $discountManager->applyDiscountToOrder($order_id, $discount_type, $_SESSION['counter_user_id']);
        
        if ($result['success']) {
            $discount_result = $result['discount_result'];
            $success_message = "Discount applied successfully! Original: ₱" . number_format($discount_result['original_amount'], 2) . 
                             ", Discount: ₱" . number_format($discount_result['discount_amount'], 2) . 
                             ", Final: ₱" . number_format($discount_result['final_amount'], 2);
        } else {
            $error_message = "Failed to apply discount: " . $result['error'];
        }
    }
    
    if ($_POST['action'] === 'mark_paid') {
        // Check if cash float is active before processing payment
        if (!$cash_float_session || $cash_float_session['status'] !== 'active') {
            $error_message = "Cannot process payment: Cash float is not set or inactive. Please contact admin to set up cash float.";
        } else {
            $discount_type = $_POST['discount_type'] ?? 'none';
            $original_amount = floatval($_POST['original_amount'] ?? 0);
            $final_amount = floatval($_POST['final_amount'] ?? 0);
            
            // Update order with discount information
            if ($discount_type !== 'none') {
                $discount_amount = $original_amount - $final_amount;
                $update_sql = "UPDATE orders SET 
                                status_id = 2, 
                                discount_type = ?, 
                                discount_percentage = ?, 
                                discount_amount = ?, 
                                original_amount = ?, 
                                total_amount = ?, 
                                discount_notes = ?, 
                                updated_at = CURRENT_TIMESTAMP 
                                WHERE order_id = ?";
                $stmt = $conn->prepare($update_sql);
                $notes = ucfirst(str_replace('_', ' ', $discount_type)) . " discount applied";
                $discount_percentage = 20.00; // Senior Citizen/PWD discount
                $stmt->bind_param("sddddsi", $discount_type, $discount_percentage, $discount_amount, $original_amount, $final_amount, $notes, $order_id);
            } else {
                $update_sql = "UPDATE orders SET status_id = 2, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("i", $order_id);
            }
            
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
            
            // Create cash float transaction for regular order payment
            // Get order total amount
            $order_total_sql = "SELECT total_amount FROM orders WHERE order_id = ?";
            $order_total_stmt = $conn->prepare($order_total_sql);
            $order_total_stmt->bind_param("i", $order_id);
            $order_total_stmt->execute();
            $order_total_result = $order_total_stmt->get_result();
            $order_total = $order_total_result->fetch_assoc()['total_amount'];
            $order_total_stmt->close();
            
            // Calculate new cash balance (assume exact payment for regular orders)
            $current_cash_sql = "SELECT COALESCE(cash_on_hand, 0) as current_cash FROM cash_float_transactions WHERE session_id = ? ORDER BY created_at DESC LIMIT 1";
            $current_cash_stmt = $conn->prepare($current_cash_sql);
            $current_cash_stmt->bind_param("i", $cash_float_session['session_id']);
            $current_cash_stmt->execute();
            $current_cash_result = $current_cash_stmt->get_result();
            $current_cash = $current_cash_result->fetch_assoc()['current_cash'] ?? 0;
            $current_cash_stmt->close();
            
            // For regular orders, assume exact payment (no change)
            $new_cash_balance = $current_cash + $order_total;
            
            // Create cash float transaction
            $transaction_sql = "INSERT INTO cash_float_transactions (session_id, transaction_type, amount, customer_payment, change_given, cash_on_hand, notes, created_by, created_at) VALUES (?, 'sale', ?, ?, 0, ?, ?, ?, NOW())";
            $transaction_stmt = $conn->prepare($transaction_sql);
            
            // Create detailed notes with discount information
            if ($discount_type !== 'none') {
                $discount_amount = $original_amount - $final_amount;
                $discount_label = ucfirst(str_replace('_', ' ', $discount_type));
                $notes = "Regular Order Payment - Order ID: {$order_id} - Original: ₱{$original_amount}, {$discount_label} Discount: ₱{$discount_amount}, Final: ₱{$final_amount}";
            } else {
                $notes = "Regular Order Payment - Order ID: {$order_id} - Amount: ₱{$order_total}";
            }
            
            $transaction_stmt->bind_param("idddsi", $cash_float_session['session_id'], $order_total, $order_total, $new_cash_balance, $notes, $_SESSION['counter_user_id']);
            $transaction_stmt->execute();
            $transaction_stmt->close();
            
            // Create success message with discount information
            if ($discount_type !== 'none') {
                $discount_amount = $original_amount - $final_amount;
                $discount_label = ucfirst(str_replace('_', ' ', $discount_type));
                $success_message = "Order marked as paid successfully! Original: ₱{$original_amount}, {$discount_label} Discount: ₱{$discount_amount}, Final: ₱{$final_amount}";
            } else {
                $success_message = "Order marked as paid successfully!";
            }
            
            // Store order ID for automatic receipt printing
            $_SESSION['print_receipt_order_id'] = $order_id;
            }
            $stmt->close();
        }
    }
    
    // Removed mark_completed action - In fine dining, kitchen handles serving (status 5)
    // Counter only handles payment (status 2) and sending to kitchen (status 3)
    // This prevents confusion and ensures proper workflow
    
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

        if ($_POST['action'] === 'acknowledge_qr_bill_request') {
            $notification_id = intval($_POST['notification_id']);
            $acknowledge_sql = "UPDATE qr_session_notifications SET status = 'acknowledged', acknowledged_at = NOW() WHERE notification_id = ?";
            $stmt = $conn->prepare($acknowledge_sql);
            $stmt->bind_param("i", $notification_id);
            
            if ($stmt->execute()) {
                $success_message = "QR Bill request acknowledged!";
            }
            $stmt->close();
        }

        if ($_POST['action'] === 'apply_qr_discount') {
            $qr_order_id = intval($_POST['qr_order_id']);
            $discount_type = $_POST['discount_type'];
            
            // Initialize discount manager
            $discountManager = new DiscountManager($conn);
            
            // Apply discount to QR order
            $result = $discountManager->applyDiscountToQROrder($qr_order_id, $discount_type, $_SESSION['counter_user_id']);
            
            if ($result['success']) {
                $discount_result = $result['discount_result'];
                $success_message = "QR Order discount applied successfully! Original: ₱" . number_format($discount_result['original_amount'], 2) . 
                                 ", Discount: ₱" . number_format($discount_result['discount_amount'], 2) . 
                                 ", Final: ₱" . number_format($discount_result['final_amount'], 2);
            } else {
                $error_message = "Failed to apply QR discount: " . $result['error'];
            }
        }

        if ($_POST['action'] === 'process_qr_payment') {
            // Check if cash float is active before processing payment
            if (!$cash_float_session || $cash_float_session['status'] !== 'active') {
                $error_message = "Cannot process payment: Cash float is not set or inactive. Please contact admin to set up cash float.";
            } else {
                $notification_id = intval($_POST['notification_id']);
                $table_id = intval($_POST['table_id']);
                $total_amount = floatval($_POST['total_amount']); // This is now the final amount after discount
                $amount_received = floatval($_POST['amount_received']);
                $change = $amount_received - $total_amount;
                $discount_type = $_POST['discount_type'] ?? 'none';
                
                // Get original amount from QR orders to calculate discount
                $original_amount = $total_amount;
                if ($discount_type !== 'none') {
                    // Calculate original amount: final_amount / 0.80 (since 20% discount)
                    $original_amount = $total_amount / 0.80;
                }
                
                // Validate payment amount
                if ($amount_received < $total_amount) {
                    $error_message = "Payment rejected: Customer provided ₱" . number_format($amount_received, 2) . " but final amount is ₱" . number_format($total_amount, 2) . ". Insufficient payment!";
                } else {
            
            // Update QR session status to paid
            $update_session_sql = "UPDATE qr_sessions SET status = 'paid', closed_at = NOW() WHERE table_id = ? AND status = 'locked'";
            $stmt = $conn->prepare($update_session_sql);
            $stmt->bind_param("i", $table_id);
            $stmt->execute();
            $stmt->close();
            
            // Update notification status
            $update_notification_sql = "UPDATE qr_session_notifications SET status = 'acknowledged', acknowledged_at = NOW() WHERE notification_id = ?";
            $stmt = $conn->prepare($update_notification_sql);
            $stmt->bind_param("i", $notification_id);
            $stmt->execute();
            $stmt->close();
            
            // CRITICAL: Update orders table status to paid (status_id = 2) for sales recording
            $update_orders_sql = "UPDATE orders SET status_id = 2, updated_at = CURRENT_TIMESTAMP WHERE table_id = ? AND status_id IN (1, 3, 4, 5)";
            $stmt = $conn->prepare($update_orders_sql);
            $stmt->bind_param("i", $table_id);
            $stmt->execute();
            $stmt->close();
            
            // Update order amounts with discount information if applicable
            if ($discount_type !== 'none') {
                $discount_amount = $original_amount - $total_amount;
                $discount_label = ucfirst(str_replace('_', ' ', $discount_type));
                $update_order_amounts_sql = "UPDATE orders SET 
                                            discount_type = ?, 
                                            discount_percentage = ?, 
                                            discount_amount = ?, 
                                            original_amount = ?, 
                                            total_amount = ?, 
                                            discount_notes = ? 
                                            WHERE table_id = ? AND status_id = 2";
                $stmt = $conn->prepare($update_order_amounts_sql);
                $notes = "{$discount_label} discount applied during payment";
                $discount_percentage = 20.00; // Senior Citizen/PWD discount
                $stmt->bind_param("sddddsi", $discount_type, $discount_percentage, $discount_amount, $original_amount, $total_amount, $notes, $table_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Calculate new cash balance
            $current_cash_sql = "SELECT COALESCE(cash_on_hand, 0) as current_cash FROM cash_float_transactions WHERE session_id = ? ORDER BY created_at DESC LIMIT 1";
            $current_cash_stmt = $conn->prepare($current_cash_sql);
            $current_cash_stmt->bind_param("i", $cash_float_session['session_id']);
            $current_cash_stmt->execute();
            $current_cash_result = $current_cash_stmt->get_result();
            $current_cash = $current_cash_result->fetch_assoc()['current_cash'] ?? 0;
            $current_cash_stmt->close();
            
            // Calculate new cash balance: current + received - change
            $new_cash_balance = $current_cash + $amount_received - $change;
            
            // Create cash float transaction with proper cash tracking
            $transaction_sql = "INSERT INTO cash_float_transactions (session_id, transaction_type, amount, customer_payment, change_given, cash_on_hand, notes, created_by, created_at) VALUES (?, 'sale', ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($transaction_sql);
            
            // Create detailed notes with discount information
            if ($discount_type !== 'none') {
                $discount_amount = $original_amount - $total_amount;
                $discount_label = ucfirst(str_replace('_', ' ', $discount_type));
                $notes = "QR Payment - Table {$table_id} - Original: ₱{$original_amount}, {$discount_label} Discount: ₱{$discount_amount}, Final: ₱{$total_amount}, Received: ₱{$amount_received}, Change: ₱{$change}";
            } else {
                $notes = "QR Payment - Table {$table_id} - Amount: ₱{$total_amount}, Received: ₱{$amount_received}, Change: ₱{$change}";
            }
            
            $stmt->bind_param("iddddsi", $cash_float_session['session_id'], $total_amount, $amount_received, $change, $new_cash_balance, $notes, $_SESSION['counter_user_id']);
            $stmt->execute();
            $stmt->close();
            
            // Create success message with discount information
            if ($discount_type !== 'none') {
                $discount_amount = $original_amount - $total_amount;
                $discount_label = ucfirst(str_replace('_', ' ', $discount_type));
                $success_message = "QR Payment processed successfully! Original: ₱{$original_amount}, {$discount_label} Discount: ₱{$discount_amount}, Final: ₱{$total_amount}, Change: ₱{$change}";
            } else {
                $success_message = "QR Payment processed successfully! Amount: ₱{$total_amount}, Change: ₱{$change}";
            }
            
            // Store order ID for automatic receipt printing
            $_SESSION['print_receipt_order_id'] = $order_id;
                }
            }
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
    
    // Removed duplicate mark_completed action
    // In fine dining, kitchen staff marks orders as served (status 5)
    // Counter staff only handles payment processing and kitchen coordination
    
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
    
    // Handle QR session closure
    if ($_POST['action'] === 'close_qr_session') {
        $session_id = intval($_POST['session_id']);
        
        // Update QR session as closed
        $close_sql = "UPDATE qr_sessions SET status = 'closed', closed_at = NOW() WHERE session_id = ?";
        $stmt = $conn->prepare($close_sql);
        $stmt->bind_param("i", $session_id);
        
        if ($stmt->execute()) {
            $success_message = "QR session closed successfully!";
        } else {
            $error_message = "Error closing QR session.";
        }
        $stmt->close();
    }
            
    // Redirect to prevent form resubmission
    if (isset($error_message)) {
        header("Location: index.php?error=" . urlencode($error_message));
    } else {
        // Store success message in session to avoid URL encoding issues with HTML
        $_SESSION['success_message'] = $success_message ?? 'Action completed';
        header("Location: index.php");
    }
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
        
        <!-- Success Message from Session -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <?php 
            $print_order_id = $_SESSION['print_receipt_order_id'] ?? null;
            unset($_SESSION['success_message']); 
            unset($_SESSION['print_receipt_order_id']);
            ?>
            
            <!-- Payment Success Modal -->
            <div class="modal fade show" id="paymentSuccessModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Payment Completed
                            </h5>
                        </div>
                        <div class="modal-body text-center">
                            <div class="mb-3">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                            </div>
                            <h6 class="mb-3"><?= $_SESSION['success_message'] ?? 'Payment processed successfully!' ?></h6>
                            <?php if ($print_order_id): ?>
                                <p class="text-muted mb-4">Order reference: #<?= $print_order_id ?></p>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-success btn-lg" onclick="window.open('print_receipt.php?order_id=<?= $print_order_id ?>', '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes')">
                                        <i class="bi bi-printer me-2"></i>Print Receipt
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="closePaymentModal()">
                                        Close
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Error Message -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error']) ?>
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
        // TEMPORARILY DISABLED: Kitchen Validation section
        // This section was showing QR orders as "ready to send to kitchen" after payment
        // For QR flow: orders are served FIRST, then paid - no need to send to kitchen after payment
        // TODO: Re-enable when manual counter ordering is added back
        $all_active_orders = []; // Empty array to hide this section
        
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
                                        <?php if ($order['discount_amount'] > 0): ?>
                                            <span class="badge bg-success ms-1">
                                                <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $order['discount_type'])) ?> (₱<?= number_format($order['discount_amount'], 2) ?> off)
                                            </span>
                                        <?php endif; ?>
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

        <!-- QR Bill Request Notifications -->
        <?php if (!empty($qr_bill_notifications)): ?>
        <div class="header">
            <h3><i class="bi bi-qr-code text-warning"></i> QR Bill Requests</h3>
        </div>

        <div class="row mb-4">
            <?php foreach ($qr_bill_notifications as $notification): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="bi bi-table"></i> Table <?= $notification['table_number'] ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><?= htmlspecialchars($notification['message']) ?></p>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> <?= date('h:i A', strtotime($notification['created_at'])) ?>
                        </small>
                        <div class="mt-3">
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#qrPaymentModal<?= $notification['notification_id'] ?>">
                                <i class="bi bi-cash-coin"></i> Process Payment
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="acknowledge_qr_bill_request">
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

        <!-- QR Payment Modals -->
        <?php foreach ($qr_bill_notifications as $notification): ?>
        <?php
        // Extract total amount from notification message
        preg_match('/Total: ₱([\d,]+\.?\d*)/', $notification['message'], $matches);
        $total_amount = isset($matches[1]) ? str_replace(',', '', $matches[1]) : '0.00';
        ?>
        <!-- QR Payment Modal for Bill Request <?= $notification['notification_id'] ?> -->
        <div class="modal fade" id="qrPaymentModal<?= $notification['notification_id'] ?>" tabindex="-1" aria-labelledby="qrPaymentModalLabel<?= $notification['notification_id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="qrPaymentModalLabel<?= $notification['notification_id'] ?>">
                            <i class="bi bi-cash-coin"></i> Process QR Payment
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
                                            <?php 
                                            // Check if this notification has discount information
                                            $notification_data = json_decode($notification['data'] ?? '{}', true);
                                            $has_discount = isset($notification_data['discount_type']) && $notification_data['discount_type'] !== 'none';
                                            ?>
                                            <?php if ($has_discount): ?>
                                                <div class="mb-2">
                                                    <small class="text-muted">Original: ₱<?= number_format($notification_data['original_amount'] ?? $total_amount, 2) ?></small><br>
                                                    <small class="text-success">
                                                        <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $notification_data['discount_type'])) ?> Discount: -₱<?= number_format($notification_data['discount_amount'] ?? 0, 2) ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            <h4 class="text-success"><strong>Total Amount: ₱<?= number_format($total_amount, 2) ?></strong></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="bi bi-calculator"></i> Cash Transaction</h6>
                                
                                <!-- Philippine Discount Options -->
                                <div class="mb-3">
                                    <label class="form-label"><i class="bi bi-percent"></i> Philippine Discounts</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="discount_type<?= $notification['notification_id'] ?>" 
                                               id="noDiscount<?= $notification['notification_id'] ?>" value="none" checked>
                                        <label class="btn btn-outline-secondary" for="noDiscount<?= $notification['notification_id'] ?>">
                                            No Discount
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="discount_type<?= $notification['notification_id'] ?>" 
                                               id="seniorCitizen<?= $notification['notification_id'] ?>" value="senior_citizen">
                                        <label class="btn btn-outline-warning" for="seniorCitizen<?= $notification['notification_id'] ?>">
                                            Senior Citizen (20%)
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="discount_type<?= $notification['notification_id'] ?>" 
                                               id="pwd<?= $notification['notification_id'] ?>" value="pwd">
                                        <label class="btn btn-outline-info" for="pwd<?= $notification['notification_id'] ?>">
                                            PWD (20%)
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Amount Display -->
                                <div class="mb-3">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <small class="text-muted">Original</small>
                                                    <div class="fw-bold" id="originalAmount<?= $notification['notification_id'] ?>">₱<?= number_format($total_amount, 2) ?></div>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted">Discount</small>
                                                    <div class="fw-bold text-warning" id="discountAmount<?= $notification['notification_id'] ?>">₱0.00</div>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted">Final</small>
                                                    <div class="fw-bold text-success" id="finalAmount<?= $notification['notification_id'] ?>">₱<?= number_format($total_amount, 2) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <form method="POST" id="qrPaymentForm<?= $notification['notification_id'] ?>">
                                    <input type="hidden" name="action" value="process_qr_payment">
                                    <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                                    <input type="hidden" name="table_id" value="<?= $notification['table_number'] ?>">
                                    <input type="hidden" name="total_amount" id="totalAmount<?= $notification['notification_id'] ?>" value="<?= $total_amount ?>">
                                    <input type="hidden" name="discount_type" id="discountType<?= $notification['notification_id'] ?>" value="none">
                                    
                                    <div class="mb-3">
                                        <label for="amountReceived<?= $notification['notification_id'] ?>" class="form-label">Amount Received (₱)</label>
                                        <input type="number" class="form-control" id="amountReceived<?= $notification['notification_id'] ?>" 
                                               name="amount_received" step="0.01" min="0" required 
                                               oninput="calculateQRChangeWithDiscount(<?= $notification['notification_id'] ?>)">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="change<?= $notification['notification_id'] ?>" class="form-label">Change (₱)</label>
                                        <input type="number" class="form-control" id="change<?= $notification['notification_id'] ?>" 
                                               name="change" step="0.01" min="0" readonly>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success" onclick="return validateQRPaymentWithDiscount(<?= $notification['notification_id'] ?>)">
                                            <i class="bi bi-check-circle"></i> Complete Payment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- QR Session Confirmations -->
        <?php if (!empty($qr_sessions)): ?>
        <div class="header">
            <h3><i class="bi bi-qr-code text-primary"></i> QR Session Confirmations</h3>
        </div>
        
        <div class="row mb-4">
            <?php foreach ($qr_sessions as $qr_session): ?>
            <div class="col-md-6 mb-3" data-cy="counter-queue-row">
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
                                    <button type="submit" class="btn btn-success btn-sm" data-cy="counter-confirm">
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

        <!-- Confirmed QR Sessions -->
        <?php if (!empty($confirmed_qr_sessions)): ?>
        <div class="header">
            <h3><i class="bi bi-check-circle text-success"></i> Confirmed QR Sessions</h3>
        </div>
        
        <div class="row mb-4">
            <?php foreach ($confirmed_qr_sessions as $confirmed_session): ?>
            <div class="col-md-6 mb-3">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-table"></i> Table <?= $confirmed_session['table_number'] ?> 
                            <span class="badge bg-light text-success ms-2">Confirmed</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>QR Code:</strong> <?= htmlspecialchars($confirmed_session['qr_code']) ?><br>
                            <strong>Session:</strong> <?= substr($confirmed_session['session_token'], 0, 8) ?>...<br>
                            <strong>Device:</strong> <?= substr($confirmed_session['device_fingerprint'], 0, 12) ?>...
                        </p>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> Started: <?= date('h:i A', strtotime($confirmed_session['created_at'])) ?><br>
                            <i class="bi bi-check-circle"></i> Confirmed: <?= date('h:i A', strtotime($confirmed_session['confirmed_at'])) ?>
                        </small>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group w-100" role="group">
                            <a href="qr_order_details.php?session_id=<?= $confirmed_session['session_id'] ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View Orders
                            </a>
                            <a href="../ordering/secure_qr_menu.php?qr=<?= urlencode($confirmed_session['qr_code']) ?>" 
                               class="btn btn-sm btn-outline-info" target="_blank">
                                <i class="bi bi-eye"></i> View Customer Menu
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="closeQRSession(<?= $confirmed_session['session_id'] ?>)">
                                <i class="bi bi-x-circle"></i> Close Session
                            </button>
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
                                <div class="order-item" data-cy="counter-order-row" data-order-id="<?= $order['order_id'] ?>">
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
                                                <?php if ($order['discount_amount'] > 0): ?>
                                                    <span class="badge bg-success ms-1">
                                                        <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $order['discount_type'])) ?> (₱<?= number_format($order['discount_amount'], 2) ?> off)
                                                    </span>
                                                <?php endif; ?>
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
                                                    <div class="text-center">
                                                        <span class="badge bg-success">Payment Complete</span>
                                                        <small class="text-muted d-block mt-1">Order finished - customer has paid</small>
                                                        <a href="print_receipt.php?order_id=<?= $order['order_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                            <i class="bi bi-printer"></i> Print Receipt
                                                        </a>
                                                    </div>
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
                                                <?php if ($order['discount_amount'] > 0): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted">Original: ₱<?= number_format($order['original_amount'], 2) ?></small><br>
                                                        <small class="text-success">
                                                            <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $order['discount_type'])) ?> Discount: -₱<?= number_format($order['discount_amount'], 2) ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                <h4 class="text-success"><strong>Total Amount: ₱<?= number_format($order['total_amount'], 2) ?></strong></h4>
                                                </div>
                                            </div>
                                            </div>
                                    </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-calculator"></i> Payment Details</h6>
                                    
                                    <!-- Philippine Discount Options -->
                                    <div class="mb-3">
                                        <label class="form-label"><i class="bi bi-percent"></i> Philippine Discounts</label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="discount_type<?= $order['order_id'] ?>" 
                                                   id="noDiscount<?= $order['order_id'] ?>" value="none" checked>
                                            <label class="btn btn-outline-secondary" for="noDiscount<?= $order['order_id'] ?>">
                                                No Discount
                                            </label>
                                            
                                            <input type="radio" class="btn-check" name="discount_type<?= $order['order_id'] ?>" 
                                                   id="seniorCitizen<?= $order['order_id'] ?>" value="senior_citizen">
                                            <label class="btn btn-outline-warning" for="seniorCitizen<?= $order['order_id'] ?>">
                                                Senior Citizen (20%)
                                            </label>
                                            
                                            <input type="radio" class="btn-check" name="discount_type<?= $order['order_id'] ?>" 
                                                   id="pwd<?= $order['order_id'] ?>" value="pwd">
                                            <label class="btn btn-outline-info" for="pwd<?= $order['order_id'] ?>">
                                                PWD (20%)
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Amount Display -->
                                    <div class="mb-3">
                                        <div class="card">
                                            <div class="card-body p-2">
                                                <div class="row text-center">
                                                    <div class="col-4">
                                                        <small class="text-muted">Original</small>
                                                        <div class="fw-bold" id="originalAmount<?= $order['order_id'] ?>">₱<?= number_format($order['total_amount'], 2) ?></div>
                                                    </div>
                                                    <div class="col-4">
                                                        <small class="text-muted">Discount</small>
                                                        <div class="fw-bold text-warning" id="discountAmount<?= $order['order_id'] ?>">₱0.00</div>
                                                    </div>
                                                    <div class="col-4">
                                                        <small class="text-muted">Final</small>
                                                        <div class="fw-bold text-success" id="finalAmount<?= $order['order_id'] ?>">₱<?= number_format($order['total_amount'], 2) ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
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
                                                           onchange="calculateChangeWithDiscount(<?= $order['order_id'] ?>)"
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
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmountWithDiscount(<?= $order['order_id'] ?>, 0)">
                                                        Exact
                                    </button>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmountWithDiscount(<?= $order['order_id'] ?>, 1)">
                                                        +₱1
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmountWithDiscount(<?= $order['order_id'] ?>, 5)">
                                                        +₱5
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setQuickAmountWithDiscount(<?= $order['order_id'] ?>, 10)">
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
                            <button type="button" class="btn btn-success" onclick="processCashPaymentWithDiscount(<?= $order['order_id'] ?>, this)">
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
// Close payment success modal
function closePaymentModal() {
    const modal = document.getElementById('paymentSuccessModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        // Remove the modal from DOM
        modal.remove();
    }
}
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

        function calculateChange(notificationId, totalAmount) {
            const amountReceived = parseFloat(document.getElementById('amountReceived' + notificationId).value) || 0;
            const change = amountReceived - totalAmount;
            
            const changeInput = document.getElementById('change' + notificationId);
            const changeValue = change >= 0 ? change.toFixed(2) : '0.00';
            changeInput.value = changeValue;
        }
        
        function validateQRPayment(notificationId, totalAmount) {
            const amountReceived = parseFloat(document.getElementById('amountReceived' + notificationId).value) || 0;
            
            if (amountReceived < totalAmount) {
                alert('❌ Payment rejected: Customer provided ₱' + amountReceived.toFixed(2) + ' but bill is ₱' + totalAmount.toFixed(2) + '. Insufficient payment!');
                return false;
            }
            
            return true;
        }
        
        // QR discount calculation functions
        function calculateQRChangeWithDiscount(notificationId) {
            console.log('QR Change calculation called for notification:', notificationId);
            const originalAmount = parseFloat(document.getElementById('originalAmount' + notificationId).textContent.replace('₱', '').replace(',', '')) || 0;
            const discountType = document.querySelector('input[name="discount_type' + notificationId + '"]:checked').value;
            const amountReceived = parseFloat(document.getElementById('amountReceived' + notificationId).value) || 0;
            
            console.log('Original:', originalAmount, 'Discount:', discountType, 'Received:', amountReceived);
            
            // Calculate discount
            let discountAmount = 0;
            let finalAmount = originalAmount;
            
            if (discountType === 'senior_citizen' || discountType === 'pwd') {
                discountAmount = originalAmount * 0.20; // 20% discount
                finalAmount = originalAmount - discountAmount;
            }
            
            // Update display
            document.getElementById('discountAmount' + notificationId).textContent = '₱' + discountAmount.toFixed(2);
            document.getElementById('finalAmount' + notificationId).textContent = '₱' + finalAmount.toFixed(2);
            document.getElementById('totalAmount' + notificationId).value = finalAmount;
            document.getElementById('discountType' + notificationId).value = discountType;
            
            // Calculate change
            const change = amountReceived - finalAmount;
            const changeInput = document.getElementById('change' + notificationId);
            const changeValue = change >= 0 ? change.toFixed(2) : '0.00';
            console.log('Change calculation:', amountReceived, '-', finalAmount, '=', change, 'Setting to:', changeValue);
            if (changeInput) {
                changeInput.value = changeValue;
                console.log('Change input updated successfully');
            } else {
                console.error('Change input element not found:', 'change' + notificationId);
            }
        }
        
        function validateQRPaymentWithDiscount(notificationId) {
            const originalAmount = parseFloat(document.getElementById('originalAmount' + notificationId).textContent.replace('₱', '').replace(',', '')) || 0;
            const discountType = document.querySelector('input[name="discount_type' + notificationId + '"]:checked').value;
            const amountReceived = parseFloat(document.getElementById('amountReceived' + notificationId).value) || 0;
            
            // Calculate final amount
            let finalAmount = originalAmount;
            if (discountType === 'senior_citizen' || discountType === 'pwd') {
                finalAmount = originalAmount * 0.80; // 20% discount
            }
            
            if (amountReceived < finalAmount) {
                alert('❌ Payment rejected: Customer provided ₱' + amountReceived.toFixed(2) + ' but final amount is ₱' + finalAmount.toFixed(2) + '. Insufficient payment!');
                return false;
            }
            return true;
        }
        
        // Add event listeners for discount radio buttons
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to all discount radio buttons
            document.querySelectorAll('input[name^="discount_type"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    const id = this.name.replace('discount_type', '');
                    
                    // Check if this is a QR notification (has 'change' input) or regular order (has 'changeDisplay')
                    const changeInput = document.getElementById('change' + id);
                    const changeDisplay = document.getElementById('changeDisplay' + id);
                    
                    if (changeInput) {
                        // This is a QR notification - use QR function
                        calculateQRChangeWithDiscount(id);
                    } else if (changeDisplay) {
                        // This is a regular order - use regular order function
                        calculateChangeWithDiscount(id);
                    }
                });
            });
        });
        
        // Regular order discount functions
        function calculateChangeWithDiscount(orderId) {
            const originalAmount = parseFloat(document.getElementById('originalAmount' + orderId).textContent.replace('₱', '').replace(',', '')) || 0;
            const discountType = document.querySelector('input[name="discount_type' + orderId + '"]:checked').value;
            const cashReceived = parseFloat(document.getElementById('cashReceived' + orderId).value) || 0;
            
            // Calculate discount
            let discountAmount = 0;
            let finalAmount = originalAmount;
            
            if (discountType === 'senior_citizen' || discountType === 'pwd') {
                discountAmount = originalAmount * 0.20; // 20% discount
                finalAmount = originalAmount - discountAmount;
            }
            
            // Update display
            document.getElementById('discountAmount' + orderId).textContent = '₱' + discountAmount.toFixed(2);
            document.getElementById('finalAmount' + orderId).textContent = '₱' + finalAmount.toFixed(2);
            
            // Calculate change
            const change = cashReceived - finalAmount;
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
                changeDisplay.classList.add('change-zero');
            }
        }
        
        function setQuickAmountWithDiscount(orderId, extraAmount) {
            const originalAmount = parseFloat(document.getElementById('originalAmount' + orderId).textContent.replace('₱', '').replace(',', '')) || 0;
            const discountType = document.querySelector('input[name="discount_type' + orderId + '"]:checked').value;
            
            // Calculate final amount
            let finalAmount = originalAmount;
            if (discountType === 'senior_citizen' || discountType === 'pwd') {
                finalAmount = originalAmount * 0.80; // 20% discount
            }
            
            const quickAmount = finalAmount + extraAmount;
            document.getElementById('cashReceived' + orderId).value = quickAmount.toFixed(2);
            calculateChangeWithDiscount(orderId);
        }
        
        function processCashPaymentWithDiscount(orderId, button) {
            const originalAmount = parseFloat(document.getElementById('originalAmount' + orderId).textContent.replace('₱', '').replace(',', '')) || 0;
            const discountType = document.querySelector('input[name="discount_type' + orderId + '"]:checked').value;
            const cashReceived = parseFloat(document.getElementById('cashReceived' + orderId).value) || 0;
            
            // Calculate final amount
            let finalAmount = originalAmount;
            if (discountType === 'senior_citizen' || discountType === 'pwd') {
                finalAmount = originalAmount * 0.80; // 20% discount
            }
            
            if (cashReceived < finalAmount) {
                alert('❌ Payment rejected: Customer provided ₱' + cashReceived.toFixed(2) + ' but final amount is ₱' + finalAmount.toFixed(2) + '. Insufficient payment!');
                return false;
            }
            
            // Disable button to prevent double submission
            button.disabled = true;
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // Add form fields
            const actionField = document.createElement('input');
            actionField.type = 'hidden';
            actionField.name = 'action';
            actionField.value = 'mark_paid';
            form.appendChild(actionField);
            
            const orderIdField = document.createElement('input');
            orderIdField.type = 'hidden';
            orderIdField.name = 'order_id';
            orderIdField.value = orderId;
            form.appendChild(orderIdField);
            
            const discountTypeField = document.createElement('input');
            discountTypeField.type = 'hidden';
            discountTypeField.name = 'discount_type';
            discountTypeField.value = discountType;
            form.appendChild(discountTypeField);
            
            const originalAmountField = document.createElement('input');
            originalAmountField.type = 'hidden';
            originalAmountField.name = 'original_amount';
            originalAmountField.value = originalAmount;
            form.appendChild(originalAmountField);
            
            const finalAmountField = document.createElement('input');
            finalAmountField.type = 'hidden';
            finalAmountField.name = 'final_amount';
            finalAmountField.value = finalAmount;
            form.appendChild(finalAmountField);
            
            document.body.appendChild(form);
            form.submit();
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
                          `Cash Float Updated: ₱${data.cash_on_hand.toLocaleString('en-US', {minimumFractionDigits: 2})}\n\n` +
                          `🖨️ Receipt will open in new window for printing.`);
                    
                    // Open receipt in new window
                    window.open(`print_receipt.php?order_id=${orderId}`, '_blank');
                    
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

function closeQRSession(sessionId) {
    if (confirm('Are you sure you want to close this QR session? This will end the customer\'s ordering session.')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="close_qr_session">
            <input type="hidden" name="session_id" value="${sessionId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
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