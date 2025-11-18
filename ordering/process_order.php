<?php
require_once 'includes/db_connection.php';

header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (!isset($input['table_id']) || !isset($input['items']) || !is_array($input['items'])) {
        throw new Exception('Missing required fields');
    }
    
    $table_id = intval($input['table_id']);
    $total_amount = floatval($input['total_amount']);
    $notes = isset($input['notes']) ? trim($input['notes']) : '';
    $items = $input['items'];
    
    if (empty($items)) {
        throw new Exception('No items in order');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Generate order number
        $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Create order
        $order_sql = "INSERT INTO orders (table_id, queue_number, status_id, total_amount, notes, created_at, updated_at) 
                      VALUES (?, ?, 1, ?, ?, NOW(), NOW())";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param('isds', $table_id, $order_number, $total_amount, $notes);
        
        if (!$order_stmt->execute()) {
            throw new Exception('Failed to create order: ' . $order_stmt->error);
        }
        
        $order_id = $conn->insert_id;
        $order_stmt->close();
        
        // Add order items
        foreach ($items as $item) {
            $item_id = intval($item['id']);
            $quantity = intval($item['quantity']);
            $unit_price = floatval($item['price']);
            $subtotal = $unit_price * $quantity;
            
            // Verify menu item exists and is available
            $menu_sql = "SELECT * FROM menu_items WHERE item_id = ? AND is_available = 1";
            $menu_stmt = $conn->prepare($menu_sql);
            $menu_stmt->bind_param('i', $item_id);
            $menu_stmt->execute();
            $menu_item = $menu_stmt->get_result()->fetch_assoc();
            $menu_stmt->close();
            
            if (!$menu_item) {
                throw new Exception('Menu item not found or unavailable: ' . $item['name']);
            }
            
            // Insert order item
            $item_sql = "INSERT INTO order_items (order_id, item_id, quantity, unit_price, subtotal, created_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())";
            $item_stmt = $conn->prepare($item_sql);
            $item_stmt->bind_param('iiidd', $order_id, $item_id, $quantity, $unit_price, $subtotal);
            
            if (!$item_stmt->execute()) {
                throw new Exception('Failed to add item to order: ' . $item_stmt->error);
            }
            $item_stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'queue_number' => $order_number,
            'order_id' => $order_id,
            'message' => 'Order placed successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>