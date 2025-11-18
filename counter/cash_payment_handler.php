<?php
require_once 'includes/db_connection.php';

// This file handles cash payment processing and automatically updates cash float

function processCashPayment($order_id, $cash_received, $conn) {
    try {
        // Get order details
        $order_sql = "SELECT total_amount FROM orders WHERE order_id = ?";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order = $order_stmt->get_result()->fetch_assoc();
        $order_stmt->close();
        
        if (!$order) {
            throw new Exception('Order not found.');
        }
        
        $total_amount = $order['total_amount'];
        $change = $cash_received - $total_amount;
        
        if ($change < 0) {
            throw new Exception('Insufficient cash received.');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Update order status to paid
        $update_sql = "UPDATE orders SET status_id = 2, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $order_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to update order status: ' . $conn->error);
        }
        $update_stmt->close();
        
        // Add to order status history
        $history_sql = "INSERT INTO order_status_history (order_id, status_id, notes) 
                        VALUES (?, 2, 'Order marked as paid - Cash payment processed')";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->bind_param("i", $order_id);
        
        if (!$history_stmt->execute()) {
            throw new Exception('Failed to add status history: ' . $conn->error);
        }
        $history_stmt->close();
        
        // Update daily sales
        $today = date('Y-m-d');
        $sql_check_daily = "SELECT daily_sales_id FROM daily_sales WHERE date = ?";
        $stmt_check = $conn->prepare($sql_check_daily);
        $stmt_check->bind_param("s", $today);
        $stmt_check->execute();
        $result_daily = $stmt_check->get_result();
        $stmt_check->close();
        
        if ($result_daily->num_rows > 0) {
            // Update existing record
            $sql_update_daily = "UPDATE daily_sales 
                                SET total_orders = total_orders + 1, 
                                    total_sales = total_sales + ?,
                                    updated_at = CURRENT_TIMESTAMP 
                                WHERE date = ?";
            
            $stmt_update = $conn->prepare($sql_update_daily);
            $stmt_update->bind_param("ds", $total_amount, $today);
            
            if (!$stmt_update->execute()) {
                throw new Exception('Error updating daily sales: ' . $conn->error);
            }
            $stmt_update->close();
        } else {
            // Create new record
            $sql_insert_daily = "INSERT INTO daily_sales (date, total_orders, total_sales) 
                                VALUES (?, 1, ?)";
            
            $stmt_insert = $conn->prepare($sql_insert_daily);
            $stmt_insert->bind_param("sd", $today, $total_amount);
            
            if (!$stmt_insert->execute()) {
                throw new Exception('Error creating daily sales: ' . $conn->error);
            }
            $stmt_insert->close();
        }
        
        // Update cash float (assume Counter #1 for now)
        $counter_id = 1;
        $session_sql = "SELECT session_id FROM cash_float_sessions 
                        WHERE shift_date = ? AND assigned_to = ? AND status = 'active'";
        $session_stmt = $conn->prepare($session_sql);
        $session_stmt->bind_param("si", $today, $counter_id);
        $session_stmt->execute();
        $session_result = $session_stmt->get_result();
        $session = $session_result->fetch_assoc();
        $session_stmt->close();
        
        // Validate cash float session exists before processing payment
        if (!$session) {
            // Rollback the transaction since we can't update cash float
            $conn->rollback();
            throw new Exception('No active cash float session found. Please contact your administrator to set up a cash float before processing payments.');
        }
        
        if ($session) {
            // Get current cash on hand
            $cash_sql = "SELECT cash_on_hand FROM cash_float_transactions 
                         WHERE session_id = ? 
                         ORDER BY created_at DESC 
                         LIMIT 1";
            $cash_stmt = $conn->prepare($cash_sql);
            $cash_stmt->bind_param("i", $session['session_id']);
            $cash_stmt->execute();
            $cash_result = $cash_stmt->get_result();
            $current_cash = $cash_result->fetch_assoc()['cash_on_hand'] ?? 0;
            $cash_stmt->close();
            
            // Calculate new cash on hand (add cash received, subtract change given)
            $new_cash = $current_cash + $cash_received - $change;
            
            // Create sale transaction
            $sale_sql = "INSERT INTO cash_float_transactions 
                        (session_id, transaction_type, amount, cash_on_hand, notes, created_by, shift_date) 
                        VALUES (?, 'sale', ?, ?, ?, ?, ?)";
            $sale_stmt = $conn->prepare($sale_sql);
            $sale_notes = "Cash sale - Received: ₱" . number_format($cash_received, 2) . 
                          ", Change: ₱" . number_format($change, 2);
            $sale_stmt->bind_param("iddiss", $session['session_id'], $total_amount, $new_cash, $sale_notes, $counter_id, $today);
            
            if (!$sale_stmt->execute()) {
                throw new Exception('Failed to record cash sale: ' . $sale_stmt->error);
            }
            $sale_stmt->close();
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'total_amount' => $total_amount,
            'cash_received' => $cash_received,
            'change' => $change,
            'cash_on_hand' => $new_cash,
            'message' => 'Payment processed successfully'
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Function to process bill payment from notification
function processBillPayment($notification_id, $session_id, $total_amount, $cash_received, $conn) {
    try {
        $conn->begin_transaction();
        
        // Validate cash float session
        $today = date('Y-m-d');
        $float_sql = "SELECT session_id FROM cash_float_sessions WHERE shift_date = ? AND assigned_to = 1 AND status = 'active'";
        $float_stmt = $conn->prepare($float_sql);
        $float_stmt->bind_param("s", $today);
        $float_stmt->execute();
        $float_result = $float_stmt->get_result();
        $float_stmt->close();
        
        if ($float_result->num_rows === 0) {
            throw new Exception('No active cash float session. Please contact administrator to set up cash float.');
        }
        
        $cash_float_session = $float_result->fetch_assoc();
        
        // Get all payable orders for this session (pending or ready)
        $orders_sql = "SELECT o.* FROM orders o 
                       WHERE o.session_id = ? AND (o.status_id = 1 OR o.status_id = 4)";
        $orders_stmt = $conn->prepare($orders_sql);
        $orders_stmt->bind_param("i", $session_id);
        $orders_stmt->execute();
        $orders_result = $orders_stmt->get_result();
        $orders_stmt->close();
        
        $orders_processed = 0;
        $actual_total = 0;
        
        // Process each payable order
        while ($order = $orders_result->fetch_assoc()) {
            // Update order status to paid (status_id = 2) for proper sales recording
            $update_sql = "UPDATE orders SET status_id = 2, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $order['order_id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Add to order status history
            $history_sql = "INSERT INTO order_status_history (order_id, status_id, notes) 
                            VALUES (?, 2, 'Order paid - Payment processed via bill request')";
            $history_stmt = $conn->prepare($history_sql);
            $history_stmt->bind_param("i", $order['order_id']);
            $history_stmt->execute();
            $history_stmt->close();
            
            $orders_processed++;
            $actual_total += floatval($order['total_amount']);
        }
        
        // Update daily sales
        $check_sql = "SELECT daily_sales_id FROM daily_sales WHERE date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $today);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_stmt->close();
        
        if ($check_result->num_rows > 0) {
            // Update existing record
            $update_daily_sql = "UPDATE daily_sales 
                                SET total_orders = total_orders + ?, 
                                    total_sales = total_sales + ?,
                                    updated_at = CURRENT_TIMESTAMP 
                                WHERE date = ?";
            $update_daily_stmt = $conn->prepare($update_daily_sql);
            $update_daily_stmt->bind_param("ids", $orders_processed, $actual_total, $today);
            $update_daily_stmt->execute();
            $update_daily_stmt->close();
        } else {
            // Create new record
            $insert_daily_sql = "INSERT INTO daily_sales (date, total_orders, total_sales) 
                                VALUES (?, ?, ?)";
            $insert_daily_stmt = $conn->prepare($insert_daily_sql);
            $insert_daily_stmt->bind_param("sid", $today, $orders_processed, $actual_total);
            $insert_daily_stmt->execute();
            $insert_daily_stmt->close();
        }
        
        // Update cash float
        $counter_id = 1;
        
        // Get current cash on hand
        $cash_sql = "SELECT cash_on_hand FROM cash_float_transactions 
                     WHERE session_id = ? 
                     ORDER BY created_at DESC 
                     LIMIT 1";
        $cash_stmt = $conn->prepare($cash_sql);
        $cash_stmt->bind_param("i", $cash_float_session['session_id']);
        $cash_stmt->execute();
        $cash_result = $cash_stmt->get_result();
        $current_cash = $cash_result->fetch_assoc()['cash_on_hand'] ?? 0;
        $cash_stmt->close();
        
        // Calculate change
        $change = $cash_received - $actual_total;
        
        // Update cash float (add the payment received)
        $new_cash = $current_cash + $actual_total;
        
        // Create payment transaction
        $trans_sql = "INSERT INTO cash_float_transactions 
                     (session_id, transaction_type, amount, cash_on_hand, notes, created_by, shift_date) 
                     VALUES (?, 'sale', ?, ?, ?, ?, ?)";
        $trans_stmt = $conn->prepare($trans_sql);
        $trans_notes = "Table session payment - " . $orders_processed . " orders paid via bill request. Cash received: ₱" . number_format($cash_received, 2) . ", Change given: ₱" . number_format($change, 2);
        $trans_stmt->bind_param("iddiss", $cash_float_session['session_id'], $actual_total, $new_cash, $trans_notes, $counter_id, $today);
        $trans_stmt->execute();
        $trans_stmt->close();
        
        // Mark notification as acknowledged
        $notif_sql = "UPDATE table_session_notifications SET status = 'acknowledged', acknowledged_at = NOW() WHERE notification_id = ?";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("i", $notification_id);
        $notif_stmt->execute();
        $notif_stmt->close();
        
        // Get table number for response
        $table_sql = "SELECT t.table_number FROM table_sessions ts 
                     JOIN tables t ON ts.table_id = t.table_id 
                     WHERE ts.session_id = ?";
        $table_stmt = $conn->prepare($table_sql);
        $table_stmt->bind_param("i", $session_id);
        $table_stmt->execute();
        $table_result = $table_stmt->get_result()->fetch_assoc();
        $table_stmt->close();
        
        // Close the table session after payment completion
        $close_session_sql = "UPDATE table_sessions SET status = 'closed', closed_at = NOW() WHERE session_id = ?";
        $close_session_stmt = $conn->prepare($close_session_sql);
        $close_session_stmt->bind_param("i", $session_id);
        $close_session_stmt->execute();
        $close_session_stmt->close();
        
        // Create a new active session for the table immediately (only if no active session exists)
        $table_sql = "SELECT table_id FROM table_sessions WHERE session_id = ?";
        $table_stmt = $conn->prepare($table_sql);
        $table_stmt->bind_param("i", $session_id);
        $table_stmt->execute();
        $table_result = $table_stmt->get_result()->fetch_assoc();
        $table_id = $table_result['table_id'];
        $table_stmt->close();
        
        // Check if there's already an active session for this table
        $check_active_sql = "SELECT session_id FROM table_sessions WHERE table_id = ? AND status = 'active' LIMIT 1";
        $check_stmt = $conn->prepare($check_active_sql);
        $check_stmt->bind_param('i', $table_id);
        $check_stmt->execute();
        $existing_active = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        
        // Only create new session if no active session exists
        if (!$existing_active) {
            $new_session_token = bin2hex(random_bytes(32));
            $new_session_sql = "INSERT INTO table_sessions (table_id, session_token, status) VALUES (?, ?, 'active')";
            $new_session_stmt = $conn->prepare($new_session_sql);
            $new_session_stmt->bind_param('is', $table_id, $new_session_token);
            $new_session_stmt->execute();
            $new_session_stmt->close();
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Bill payment processed successfully',
            'orders_processed' => $orders_processed,
            'total_amount' => $actual_total,
            'cash_received' => $cash_received,
            'change' => $change,
            'cash_on_hand' => $new_cash,
            'table_number' => $table_result['table_number'] ?? 'Unknown'
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'process_bill_payment') {
        $notification_id = intval($_POST['notification_id']);
        $session_id = intval($_POST['session_id']);
        $total_amount = floatval($_POST['total_amount']);
        $cash_received = floatval(str_replace(',', '', $_POST['cash_received']));
        
        $result = processBillPayment($notification_id, $session_id, $total_amount, $cash_received, $conn);
        echo json_encode($result);
        exit;
    }
    
    if ($_POST['action'] === 'process_cash_payment') {
        $order_id = intval($_POST['order_id']);
        $cash_received = floatval(str_replace(',', '', $_POST['cash_received']));
        
        // Get order details for response
        $order_sql = "SELECT queue_number FROM orders WHERE order_id = ?";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result()->fetch_assoc();
        $order_stmt->close();
        
        $result = processCashPayment($order_id, $cash_received, $conn);
        
        // Add queue number to response
        if ($result['success'] && $order_result) {
            $result['queue_number'] = $order_result['queue_number'];
        }
        
        echo json_encode($result);
        exit;
    }
}
?>