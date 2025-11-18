<?php
require_once 'includes/db_connection.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$session_id = intval($_POST['session_id'] ?? 0);
$session_token = $_POST['session_token'] ?? '';
$device_fingerprint = $_POST['device_fingerprint'] ?? '';

// Validate session security
if (!$session_id || !$session_token || !$device_fingerprint) {
    echo json_encode(['success' => false, 'message' => 'Invalid session parameters']);
    exit;
}

// Verify session security (accept both 'active' and 'locked' status)
$session_sql = "SELECT * FROM qr_sessions WHERE session_id = ? AND session_token = ? AND device_fingerprint = ? AND status IN ('active', 'locked') AND (expires_at IS NULL OR expires_at > NOW())";
$session_stmt = $conn->prepare($session_sql);
$session_stmt->bind_param('iss', $session_id, $session_token, $device_fingerprint);
$session_stmt->execute();
$session = $session_stmt->get_result()->fetch_assoc();
$session_stmt->close();

if (!$session) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired session']);
    exit;
}

try {
    switch ($action) {
        case 'add_order':
            $item_id = intval($_POST['item_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if (!$item_id) {
                throw new Exception('Invalid item ID');
            }
            
            // Check if session is confirmed by counter
            if (!$session['confirmed_by_counter']) {
                throw new Exception('Session not confirmed by counter yet');
            }
            
            // Get item details
            $item_sql = "SELECT * FROM menu_items WHERE item_id = ? AND is_available = 1";
            $item_stmt = $conn->prepare($item_sql);
            $item_stmt->bind_param('i', $item_id);
            $item_stmt->execute();
            $item = $item_stmt->get_result()->fetch_assoc();
            $item_stmt->close();
            
            if (!$item) {
                throw new Exception('Item not found or unavailable');
            }
            
            // Check if item already exists in session
            $existing_sql = "SELECT * FROM qr_orders WHERE session_id = ? AND menu_item_id = ? AND status = 'pending'";
            $existing_stmt = $conn->prepare($existing_sql);
            $existing_stmt->bind_param('ii', $session_id, $item_id);
            $existing_stmt->execute();
            $existing = $existing_stmt->get_result()->fetch_assoc();
            $existing_stmt->close();
            
            if ($existing) {
                // Update existing order quantity
                $new_quantity = $existing['quantity'] + $quantity;
                $new_subtotal = $new_quantity * $item['price'];
                $time_limit = (new DateTime('now', new DateTimeZone('Asia/Manila')))->add(new DateInterval('PT10M'))->format('Y-m-d H:i:s'); // 10-minute cancellation window
                
                $update_sql = "UPDATE qr_orders SET quantity = ?, subtotal = ?, time_limit_expires = ? WHERE order_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('idsi', $new_quantity, $new_subtotal, $time_limit, $existing['order_id']);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                // Add new order
                $subtotal = $quantity * $item['price'];
                $time_limit = (new DateTime('now', new DateTimeZone('Asia/Manila')))->add(new DateInterval('PT10M'))->format('Y-m-d H:i:s'); // 10-minute cancellation window
                
                $insert_sql = "INSERT INTO qr_orders (session_id, menu_item_id, quantity, unit_price, subtotal, time_limit_expires) VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param('iiidds', $session_id, $item_id, $quantity, $item['price'], $subtotal, $time_limit);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
            
            // Update session activity
            updateSessionActivity($session_id, $conn);
            
            echo json_encode(['success' => true, 'message' => 'Order added successfully']);
            break;
            
        case 'update_quantity':
            $order_id = intval($_POST['order_id'] ?? 0);
            $change = intval($_POST['change'] ?? 0);
            
            if (!$order_id || !$change) {
                throw new Exception('Invalid parameters');
            }
            
            // Get current order
            $order_sql = "SELECT * FROM qr_orders WHERE order_id = ? AND session_id = ? AND status = 'pending'";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param('ii', $order_id, $session_id);
            $order_stmt->execute();
            $order = $order_stmt->get_result()->fetch_assoc();
            $order_stmt->close();
            
            if (!$order) {
                throw new Exception('Order not found or cannot be modified');
            }
            
            $new_quantity = $order['quantity'] + $change;
            
            if ($new_quantity <= 0) {
                // Remove order
                $delete_sql = "DELETE FROM qr_orders WHERE order_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param('i', $order_id);
                $delete_stmt->execute();
                $delete_stmt->close();
            } else {
                // Update quantity
                $new_subtotal = $new_quantity * $order['unit_price'];
                $time_limit = (new DateTime('now', new DateTimeZone('Asia/Manila')))->add(new DateInterval('PT10M'))->format('Y-m-d H:i:s'); // 10-minute cancellation window
                
                $update_sql = "UPDATE qr_orders SET quantity = ?, subtotal = ?, time_limit_expires = ? WHERE order_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('idsi', $new_quantity, $new_subtotal, $time_limit, $order_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            // Update session activity
            updateSessionActivity($session_id, $conn);
            
            echo json_encode(['success' => true, 'message' => 'Quantity updated successfully']);
            break;
            
        case 'cancel_order':
            $order_id = intval($_POST['order_id'] ?? 0);
            
            if (!$order_id) {
                throw new Exception('Invalid order ID');
            }
            
            // Get current order
            $order_sql = "SELECT * FROM qr_orders WHERE order_id = ? AND session_id = ? AND status = 'pending'";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param('ii', $order_id, $session_id);
            $order_stmt->execute();
            $order = $order_stmt->get_result()->fetch_assoc();
            $order_stmt->close();
            
            if (!$order) {
                throw new Exception('Order not found or cannot be cancelled');
            }
            
            // Check time limit
            if (strtotime($order['time_limit_expires']) < time()) {
                throw new Exception('Cancellation time limit expired. Please contact staff for assistance.');
            }
            
            // Cancel the order
            $cancel_sql = "UPDATE qr_orders SET status = 'cancelled', cancelled_at = NOW() WHERE order_id = ?";
            $cancel_stmt = $conn->prepare($cancel_sql);
            $cancel_stmt->bind_param('i', $order_id);
            $cancel_stmt->execute();
            $cancel_stmt->close();
            
            // Create notification
            $notification_sql = "INSERT INTO qr_session_notifications (session_id, notification_type, message, data) VALUES (?, 'cancellation_request', ?, ?)";
            $notification_stmt = $conn->prepare($notification_sql);
            $message = "Order cancelled by customer - Table {$session['table_id']}";
            $data = json_encode(['order_id' => $order_id, 'item_name' => 'Unknown', 'reason' => 'Customer cancellation']);
            $notification_stmt->bind_param('iss', $session_id, $message, $data);
            $notification_stmt->execute();
            $notification_stmt->close();
            
            // Update session activity
            updateSessionActivity($session_id, $conn);
            
            echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
            break;
            
        case 'send_to_kitchen':
            // Update all pending orders to confirmed
            $update_sql = "UPDATE qr_orders SET status = 'confirmed', confirmed_at = NOW() WHERE session_id = ? AND status = 'pending'";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('i', $session_id);
            $update_stmt->execute();
            $affected_rows = $update_stmt->affected_rows;
            $update_stmt->close();
            
            // Check if there are any confirmed orders (either newly confirmed or already confirmed)
            $confirmed_count_sql = "SELECT COUNT(*) as count FROM qr_orders WHERE session_id = ? AND status = 'confirmed'";
            $confirmed_stmt = $conn->prepare($confirmed_count_sql);
            $confirmed_stmt->bind_param('i', $session_id);
            $confirmed_stmt->execute();
            $confirmed_count = $confirmed_stmt->get_result()->fetch_assoc()['count'];
            $confirmed_stmt->close();
            
            if ($confirmed_count > 0) {
                // Get table information for notification
                $table_sql = "SELECT t.table_number FROM qr_sessions qs JOIN tables t ON qs.table_id = t.table_id WHERE qs.session_id = ?";
                $table_stmt = $conn->prepare($table_sql);
                $table_stmt->bind_param('i', $session_id);
                $table_stmt->execute();
                $table_info = $table_stmt->get_result()->fetch_assoc();
                $table_stmt->close();
                
                $table_number = $table_info['table_number'] ?? 'Unknown';
                
                // Create notification for kitchen
                $notification_sql = "INSERT INTO qr_session_notifications (session_id, notification_type, message, data) VALUES (?, 'order_update', ?, ?)";
                $notification_stmt = $conn->prepare($notification_sql);
                $message = "Orders sent to kitchen from Table {$table_number} - {$confirmed_count} items";
                $data = json_encode(['orders_count' => $confirmed_count, 'table_id' => $session['table_id']]);
                $notification_stmt->bind_param('iss', $session_id, $message, $data);
                $notification_stmt->execute();
                $notification_stmt->close();
                
                // Create orders in main orders table for kitchen tracking
                try {
                    createKitchenOrders($session_id, $conn);
                } catch (Exception $e) {
                    // Log the error but don't fail the entire operation
                    error_log("Failed to create kitchen orders: " . $e->getMessage());
                }
            }
            
            // Update session activity
            updateSessionActivity($session_id, $conn);
            
            echo json_encode(['success' => true, 'message' => 'Orders sent to kitchen successfully']);
            break;
            
        case 'request_bill':
            // Lock the session to prevent further ordering
            $lock_sql = "UPDATE qr_sessions SET status = 'locked' WHERE session_id = ?";
            $lock_stmt = $conn->prepare($lock_sql);
            $lock_stmt->bind_param('i', $session_id);
            $lock_stmt->execute();
            $lock_stmt->close();
            
            // Create notification for counter
            $notification_sql = "INSERT INTO qr_session_notifications (session_id, notification_type, message, data) VALUES (?, 'bill_request', ?, ?)";
            $notification_stmt = $conn->prepare($notification_sql);
            $total_amount = getSessionTotal($session_id, $conn);
            $message = "Table {$session['table_id']} requesting bill - Total: {$total_amount}";
            $data = json_encode(['table_id' => $session['table_id'], 'total_amount' => $total_amount]);
            $notification_stmt->bind_param('iss', $session_id, $message, $data);
            $notification_stmt->execute();
            $notification_stmt->close();
            
            // Update session activity
            updateSessionActivity($session_id, $conn);
            
            echo json_encode(['success' => true, 'message' => 'Bill request sent to counter']);
            break;
            
        case 'get_orders':
            // Get session orders
            $orders_sql = "SELECT qo.*, mi.name as item_name, mi.description 
                          FROM qr_orders qo 
                          JOIN menu_items mi ON qo.menu_item_id = mi.item_id 
                          WHERE qo.session_id = ? 
                          ORDER BY qo.created_at DESC";
            $orders_stmt = $conn->prepare($orders_sql);
            $orders_stmt->bind_param('i', $session_id);
            $orders_stmt->execute();
            $orders = $orders_stmt->get_result();
            $orders_stmt->close();
            
            $orders_html = '';
            $total_items = 0;
            $total_amount = 0;
            
            if ($orders->num_rows > 0) {
                while ($order = $orders->fetch_assoc()) {
                    if ($order['status'] != 'cancelled') {
                        $total_items += $order['quantity'];
                        $total_amount += $order['subtotal'];
                    }
                    
                    $status_class = $order['status'] == 'pending' ? 'warning' : 
                                   ($order['status'] == 'confirmed' ? 'info' : 
                                   ($order['status'] == 'preparing' ? 'primary' : 
                                   ($order['status'] == 'ready' ? 'success' : 
                                   ($order['status'] == 'served' ? 'secondary' : 'danger'))));
                    
                    $can_cancel = $order['status'] == 'pending' && strtotime($order['time_limit_expires']) > time();
                    $time_limit_display = $can_cancel ? 
                        '<div class="time-limit-warning"><small><i class="bi bi-clock"></i> Can cancel until ' . (new DateTime($order['time_limit_expires'], new DateTimeZone('Asia/Manila')))->format('g:i A') . '</small></div>' : '';
                    
                    $cancel_button = $can_cancel ? 
                        '<button class="btn btn-outline-danger btn-sm mt-1" onclick="cancelOrder(' . $order['order_id'] . ')"><i class="bi bi-x"></i> Cancel</button>' : '';
                    
                    $orders_html .= '
                        <div class="order-item p-3" data-order-id="' . $order['order_id'] . '">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">' . htmlspecialchars($order['item_name']) . '</h6>
                                    <small class="text-muted">₱' . number_format($order['unit_price'], 2, '.', ',') . ' each</small>
                                    <div class="mt-1">
                                        <span class="order-status badge bg-' . $status_class . '">' . ucfirst($order['status']) . '</span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="d-flex align-items-center mb-1">
                                        <button class="btn btn-outline-secondary btn-sm" 
                                                onclick="updateQuantity(' . $order['order_id'] . ', -1)"
                                                ' . ($order['status'] != 'pending' ? 'disabled' : '') . '>
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <span class="mx-2 fw-bold">' . $order['quantity'] . '</span>
                                        <button class="btn btn-outline-secondary btn-sm" 
                                                onclick="updateQuantity(' . $order['order_id'] . ', 1)"
                                                ' . ($order['status'] != 'pending' ? 'disabled' : '') . '>
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <div class="text-primary fw-bold">
                                        ₱' . number_format($order['subtotal'], 2, '.', ',') . '
                                    </div>
                                    ' . $cancel_button . '
                                </div>
                            </div>
                            ' . $time_limit_display . '
                        </div>';
                }
            } else {
                $orders_html = '
                    <div class="text-center py-4">
                        <i class="bi bi-cart-x text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No orders yet</p>
                        <small>Add items from the menu to get started</small>
                    </div>';
            }
            
            echo json_encode([
                'success' => true,
                'orders_html' => $orders_html,
                'total_items' => $total_items,
                'total_amount' => number_format($total_amount, 2, '.', ',')
            ]);
            break;
            
        case 'check_session':
            echo json_encode([
                'success' => true,
                'confirmed' => $session['confirmed_by_counter'],
                'status' => $session['status'],
                'expires_at' => $session['expires_at']
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    // Log the full error for debugging
    error_log("QR API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

function updateSessionActivity($session_id, $conn) {
    $update_sql = "UPDATE qr_sessions SET last_activity = NOW() WHERE session_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('i', $session_id);
    $update_stmt->execute();
    $update_stmt->close();
}

function getSessionTotal($session_id, $conn) {
    $total_sql = "SELECT SUM(subtotal) as total FROM qr_orders WHERE session_id = ? AND status != 'cancelled'";
    $total_stmt = $conn->prepare($total_sql);
    $total_stmt->bind_param('i', $session_id);
    $total_stmt->execute();
    $result = $total_stmt->get_result()->fetch_assoc();
    $total_stmt->close();
    
    return '₱' . number_format($result['total'] ?? 0, 2, '.', ',');
}

function createKitchenOrders($session_id, $conn) {
    // Get session details
    $session_sql = "SELECT ts.*, t.table_number FROM qr_sessions ts JOIN tables t ON ts.table_id = t.table_id WHERE ts.session_id = ?";
    $session_stmt = $conn->prepare($session_sql);
    $session_stmt->bind_param('i', $session_id);
    $session_stmt->execute();
    $session = $session_stmt->get_result()->fetch_assoc();
    $session_stmt->close();
    
    // Get confirmed orders
    $orders_sql = "SELECT * FROM qr_orders WHERE session_id = ? AND status = 'confirmed'";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param('i', $session_id);
    $orders_stmt->execute();
    $orders = $orders_stmt->get_result();
    $orders_stmt->close();
    
    if ($orders->num_rows > 0) {
        // Create main order record
        $order_sql = "INSERT INTO orders (table_id, session_id, status_id, queue_number, total_amount, notes, created_at) 
                      VALUES (?, ?, 1, ?, ?, 'QR session order', NOW())";
        $order_stmt = $conn->prepare($order_sql);
        $queue_number = 'QR' . $session['table_number'] . '-' . date('His');
        $total_amount = 0;
        
        // Calculate total
        while ($order = $orders->fetch_assoc()) {
            $total_amount += $order['subtotal'];
        }
        $orders->data_seek(0); // Reset pointer
        
        $order_stmt->bind_param('iisd', $session['table_id'], $session['session_id'], $queue_number, $total_amount);
        $order_stmt->execute();
        $order_id = $conn->insert_id;
        $order_stmt->close();
        
        // Create order items
        while ($order = $orders->fetch_assoc()) {
            $order_item_sql = "INSERT INTO order_items (order_id, item_id, quantity, subtotal, notes) 
                               VALUES (?, ?, ?, ?, ?)";
            $order_item_stmt = $conn->prepare($order_item_sql);
            $notes = 'From QR session';
            $order_item_stmt->bind_param('iiids', $order_id, $order['menu_item_id'], $order['quantity'], $order['subtotal'], $notes);
            $order_item_stmt->execute();
            $order_item_stmt->close();
        }
    }
}
?>
