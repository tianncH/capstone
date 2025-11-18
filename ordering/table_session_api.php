<?php
require_once 'includes/db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_to_kitchen':
            $session_id = intval($_POST['session_id'] ?? 0);
            $cart_data = $_POST['cart_data'] ?? '';
            
            // Debug logging
            error_log("API Debug - Session ID: " . $session_id);
            error_log("API Debug - Cart Data: " . $cart_data);
            error_log("API Debug - POST data: " . print_r($_POST, true));
            
            if (!$session_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid session ID: ' . $session_id]);
                exit;
            }
            
            try {
                $conn->begin_transaction();
                
                // Parse cart data
                $cart_items = json_decode($cart_data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid cart data format: ' . json_last_error_msg());
                }
                
                if (!$cart_items || !is_array($cart_items)) {
                    throw new Exception('Cart is empty or invalid. Please add items to your cart before sending to kitchen.');
                }
                
                if (empty($cart_items)) {
                    throw new Exception('Your cart is empty! Please add items to your order before sending to kitchen.');
                }
                
                // Get session details from table_sessions (not qr_sessions)
                $session_sql = "SELECT ts.*, t.table_number FROM table_sessions ts JOIN tables t ON ts.table_id = t.table_id WHERE ts.session_id = ?";
                $session_stmt = $conn->prepare($session_sql);
                $session_stmt->bind_param("i", $session_id);
                $session_stmt->execute();
                $session = $session_stmt->get_result()->fetch_assoc();
                $session_stmt->close();
                
                if (!$session) {
                    throw new Exception('Table session not found');
                }
                
                // Calculate total
                $total_amount = 0;
                foreach ($cart_items as $item) {
                    $total_amount += floatval($item['total']);
                }
                
                        // Create main order - KEEP AS PENDING until bill is requested
                        $order_sql = "INSERT INTO orders (table_id, session_id, status_id, queue_number, total_amount, notes, created_at) VALUES (?, ?, 1, ?, ?, 'Table session order - Pending payment', NOW())";
                        $order_stmt = $conn->prepare($order_sql);
                        $queue_number = 'T' . $session['table_number'] . '-' . date('His');
                        $order_stmt->bind_param("iisd", $session['table_id'], $session['session_id'], $queue_number, $total_amount);
                        $order_stmt->execute();
                        $order_id = $conn->insert_id;
                        $order_stmt->close();
                
                // Create order items
                foreach ($cart_items as $item) {
                    $item_id = intval($item['id']);
                    $quantity = intval($item['quantity']);
                    $price = floatval($item['price']);
                    $subtotal = $price * $quantity;
                    
                    $item_sql = "INSERT INTO order_items (order_id, item_id, quantity, unit_price, subtotal, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                    $item_stmt = $conn->prepare($item_sql);
                    $item_stmt->bind_param("iiidd", $order_id, $item_id, $quantity, $price, $subtotal);
                    $item_stmt->execute();
                    $item_stmt->close();
                }
                
                // No QR orders to update for table_sessions
                
                $conn->commit();
                
            echo json_encode([
                'success' => true, 
                            'message' => 'Order sent to kitchen successfully! Order #' . $queue_number . ' (Pending payment)',
                            'order_id' => $order_id,
                            'queue_number' => $queue_number
                        ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
                case 'get_order_history':
                    $session_id = intval($_POST['session_id'] ?? 0);
                    
                    if (!$session_id) {
                        echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
                        exit;
                    }
                    
                    try {
                        // Get all orders for this table session
                        $orders_sql = "SELECT o.*, os.name as status_name 
                                      FROM orders o 
                                      JOIN order_statuses os ON o.status_id = os.status_id 
                                      WHERE o.session_id = ? 
                                      ORDER BY o.created_at DESC";
                        $orders_stmt = $conn->prepare($orders_sql);
                        $orders_stmt->bind_param("i", $session_id);
                        $orders_stmt->execute();
                        $orders_result = $orders_stmt->get_result();
                        $orders_stmt->close();
                        
                        $orders = [];
                        $total_bill = 0;
                        
                        while ($order = $orders_result->fetch_assoc()) {
                            // Get order items
                            $items_sql = "SELECT oi.*, mi.name as item_name 
                                         FROM order_items oi 
                                         JOIN menu_items mi ON oi.item_id = mi.item_id 
                                         WHERE oi.order_id = ?";
                            $items_stmt = $conn->prepare($items_sql);
                            $items_stmt->bind_param("i", $order['order_id']);
                            $items_stmt->execute();
                            $items_result = $items_stmt->get_result();
                            $items_stmt->close();
                            
                            $order_items = [];
                            while ($item = $items_result->fetch_assoc()) {
                                $order_items[] = $item;
                            }
                            
                            $order['items'] = $order_items;
                            $orders[] = $order;
                            $total_bill += floatval($order['total_amount']);
                        }
                        
                 // Get session status
                 $session_status_sql = "SELECT status FROM table_sessions WHERE session_id = ?";
                 $session_status_stmt = $conn->prepare($session_status_sql);
                 $session_status_stmt->bind_param("i", $session_id);
                 $session_status_stmt->execute();
                 $session_status_result = $session_status_stmt->get_result()->fetch_assoc();
                 $session_status_stmt->close();
                 
                 echo json_encode([
                     'success' => true,
                     'orders' => $orders,
                     'total_bill' => $total_bill,
                     'session_id' => $session_id,
                     'session_status' => $session_status_result['status'] ?? 'active'
                 ]);
                        
                    } catch (Exception $e) {
                        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                    }
            break;
            
                case 'request_bill':
            $session_id = intval($_POST['session_id'] ?? 0);
            $total_amount = floatval($_POST['total_amount'] ?? 0);
            $order_count = intval($_POST['order_count'] ?? 0);
            
            if (!$session_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
                exit;
            }
            
            try {
                // Get session details
                $session_sql = "SELECT ts.*, t.table_number FROM table_sessions ts 
                               JOIN tables t ON ts.table_id = t.table_id 
                               WHERE ts.session_id = ?";
                $session_stmt = $conn->prepare($session_sql);
                $session_stmt->bind_param("i", $session_id);
                $session_stmt->execute();
                $session_result = $session_stmt->get_result();
                $session_stmt->close();
                
                if ($session_result->num_rows === 0) {
                    throw new Exception('Table session not found');
                }
                
                $session = $session_result->fetch_assoc();
                
                // Create a bill request notification for the counter
                $notification_sql = "INSERT INTO table_session_notifications 
                                    (session_id, notification_type, message, created_at) 
                                    VALUES (?, 'bill_request', ?, NOW())";
                $notification_stmt = $conn->prepare($notification_sql);
                $message = "Table {$session['table_number']} requesting bill - ₱{$total_amount} ({$order_count} orders)";
                $notification_stmt->bind_param("is", $session_id, $message);
                $notification_stmt->execute();
                $notification_stmt->close();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Bill request sent to counter',
                    'total_amount' => $total_amount,
                    'order_count' => $order_count,
                    'table_number' => $session['table_number']
                ]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_current_session':
            $table_id = intval($_POST['table_id'] ?? 0);
            
            if (!$table_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid table ID']);
                exit;
            }
            
            try {
                // Get or create active session for this table
                $session_sql = "SELECT * FROM table_sessions WHERE table_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1";
                $session_stmt = $conn->prepare($session_sql);
                $session_stmt->bind_param('i', $table_id);
                $session_stmt->execute();
                $current_session = $session_stmt->get_result()->fetch_assoc();
                $session_stmt->close();
                
                // If no active session, return error (don't create new one)
                if (!$current_session) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No active session found for this table'
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'session_id' => $current_session['session_id'],
                        'new_session' => false
                    ]);
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        case 'process_bill_payment':
            // This action should NOT be called from customer side anymore
            // All payment processing should be done by counter staff
            echo json_encode([
                'success' => false, 
                'message' => 'Payment processing is now handled by counter staff. Please wait for assistance.'
            ]);
            break;
                    
                case 'add_item':
                case 'update_quantity':
                case 'get_cart':
                case 'request_bill':
                    echo json_encode(['success' => true, 'message' => 'Feature coming soon!']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>