<?php
require_once '../admin/includes/db_connection.php';

header('Content-Type: application/json');

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    // Get order details
    $order_sql = "SELECT o.*, t.table_name, t.table_number 
                  FROM orders o 
                  JOIN tables t ON o.table_id = t.table_id 
                  WHERE o.order_id = ?";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param('i', $order_id);
    $order_stmt->execute();
    $order = $order_stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Get order items with status
    $items_sql = "SELECT oi.*, mi.item_name 
                  FROM order_items oi 
                  JOIN menu_items mi ON oi.menu_item_id = mi.menu_item_id 
                  WHERE oi.order_id = ? 
                  ORDER BY oi.created_at";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get recent notifications
    $notifications_sql = "SELECT * FROM order_notifications 
                          WHERE order_id = ? 
                          ORDER BY created_at DESC 
                          LIMIT 5";
    $notifications_stmt = $conn->prepare($notifications_sql);
    $notifications_stmt->bind_param('i', $order_id);
    $notifications_stmt->execute();
    $notifications = $notifications_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Check if order has been updated recently (within last 30 seconds)
    $last_update = strtotime($order['updated_at']);
    $current_time = time();
    $updated = ($current_time - $last_update) < 30;
    
    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'order' => [
            'order_id' => $order['order_id'],
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'total_amount' => $order['total_amount'],
            'is_billed_out' => $order['is_billed_out'],
            'updated_at' => $order['updated_at']
        ],
        'items' => $order_items,
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
