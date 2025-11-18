<?php
require_once '../admin/includes/db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $table_id = (int)$input['table_id'];
    $items = $input['items'] ?? [];
    $existing_order_id = $input['existing_order_id'] ?? null;
    
    if (!$table_id || empty($items)) {
        throw new Exception('Table ID and items are required');
    }
    
    // Verify table exists and is active
    $table_sql = "SELECT * FROM tables WHERE table_id = ? AND is_active = 1";
    $table_stmt = $conn->prepare($table_sql);
    $table_stmt->bind_param('i', $table_id);
    $table_stmt->execute();
    $table = $table_stmt->get_result()->fetch_assoc();
    
    if (!$table) {
        throw new Exception('Table not found or inactive');
    }
    
    $conn->begin_transaction();
    
    $order_id = null;
    $order_number = '';
    
    if ($existing_order_id) {
        // Add to existing order
        $order_id = $existing_order_id;
        
        // Get existing order details
        $order_sql = "SELECT o.* FROM orders o 
                      JOIN order_statuses s ON o.status_id = s.status_id 
                      WHERE o.order_id = ? AND o.table_id = ? AND s.name NOT IN ('completed', 'cancelled')";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param('ii', $order_id, $table_id);
        $order_stmt->execute();
        $existing_order = $order_stmt->get_result()->fetch_assoc();
        
        if (!$existing_order) {
            throw new Exception('Existing order not found');
        }
        
        $order_number = $existing_order['order_number'];
    } else {
        // Create new order
        $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $insert_order_sql = "INSERT INTO orders (table_id, queue_number, status_id, total_amount) VALUES (?, ?, 1, 0)";
        $insert_order_stmt = $conn->prepare($insert_order_sql);
        $insert_order_stmt->bind_param('is', $table_id, $order_number);
        
        if (!$insert_order_stmt->execute()) {
            throw new Exception('Failed to create order: ' . $insert_order_stmt->error);
        }
        
        $order_id = $conn->insert_id;
    }
    
    // Add items to order
    $subtotal = 0;
    
    foreach ($items as $item_id => $item_data) {
        $menu_item_id = (int)$item_id;
        $quantity = (int)$item_data['quantity'];
        $unit_price = (float)$item_data['price'];
        $total_price = $unit_price * $quantity;
        
        // Verify menu item exists and is available
        $menu_sql = "SELECT * FROM menu_items WHERE item_id = ? AND is_available = 1";
        $menu_stmt = $conn->prepare($menu_sql);
        $menu_stmt->bind_param('i', $menu_item_id);
        $menu_stmt->execute();
        $menu_item = $menu_stmt->get_result()->fetch_assoc();
        
        if (!$menu_item) {
            throw new Exception('Menu item not found or unavailable: ' . $item_data['name']);
        }
        
        // Insert order item
        $item_sql = "INSERT INTO order_items (order_id, item_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)";
        $item_stmt = $conn->prepare($item_sql);
        $item_stmt->bind_param('iiidd', $order_id, $menu_item_id, $quantity, $unit_price, $total_price);
        
        if (!$item_stmt->execute()) {
            throw new Exception('Failed to add item to order: ' . $item_stmt->error);
        }
        
        $subtotal += $total_price;
    }
    
    // Calculate totals
    $tax_rate = 0.10; // 10% tax rate - you can make this configurable
    $tax_amount = $subtotal * $tax_rate;
    $total_amount = $subtotal + $tax_amount;
    
    // Update order totals
    $update_order_sql = "UPDATE orders SET subtotal = subtotal + ?, tax_amount = tax_amount + ?, total_amount = total_amount + ? WHERE order_id = ?";
    $update_order_stmt = $conn->prepare($update_order_sql);
    $update_order_stmt->bind_param('dddi', $subtotal, $tax_amount, $total_amount, $order_id);
    
    if (!$update_order_stmt->execute()) {
        throw new Exception('Failed to update order totals: ' . $update_order_stmt->error);
    }
    
    // Create notification
    $notification_sql = "INSERT INTO order_notifications (order_id, notification_type, message) VALUES (?, 'order_placed', ?)";
    $notification_stmt = $conn->prepare($notification_sql);
    $message = $existing_order_id ? 'Additional items added to order' : 'New order placed';
    $notification_stmt->bind_param('is', $order_id, $message);
    $notification_stmt->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order processed successfully',
        'order_id' => $order_id,
        'order_number' => $order_number,
        'total_amount' => $total_amount
    ]);
    
} catch (Exception $e) {
    if ($conn->in_transaction()) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
