<?php
require_once 'admin/includes/db_connection.php';

echo "=== TESTING KITCHEN QR ORDERS ===\n\n";

// Simulate the kitchen system query
echo "1. SIMULATING KITCHEN SYSTEM QUERY:\n";
$today = date('Y-m-d');

$sql_orders = "SELECT o.*, os.name as status_name, t.table_number, ts.session_id
               FROM orders o 
               LEFT JOIN tables t ON o.table_id = t.table_id 
               JOIN order_statuses os ON o.status_id = os.status_id 
               LEFT JOIN table_sessions ts ON o.session_id = ts.session_id
               WHERE DATE(o.created_at) = ? 
               AND (o.status_id = 3 OR o.status_id = 4) 
               ORDER BY o.created_at ASC";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("s", $today);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
$stmt_orders->close();

$orders = [];

while ($order = $result_orders->fetch_assoc()) {
    echo "Order ID: {$order['order_id']}\n";
    echo "Queue Number: {$order['queue_number']}\n";
    echo "Table: {$order['table_number']}\n";
    echo "Status: {$order['status_name']}\n";
    
    // Try regular order items first
    $sql_items = "SELECT oi.*, mi.name as item_name, iv.name as variation_name 
                  FROM order_items oi 
                  JOIN menu_items mi ON oi.item_id = mi.item_id 
                  LEFT JOIN item_variations iv ON oi.variation_id = iv.variation_id 
                  WHERE oi.order_id = ?";
    
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $order['order_id']);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    $stmt_items->close();
    
    echo "Regular order items: {$result_items->num_rows}\n";
    
    // If no regular order items found, check for QR orders
    if ($result_items->num_rows == 0) {
        echo "No regular order items, checking QR orders...\n";
        
        $sql_qr_items = "SELECT qo.*, mi.name as item_name, 'QR Order' as variation_name
                         FROM qr_orders qo 
                         JOIN menu_items mi ON qo.menu_item_id = mi.item_id 
                         WHERE qo.session_id = (SELECT session_id FROM qr_sessions WHERE table_id = ? AND status = 'active' LIMIT 1)";
        
        $stmt_qr_items = $conn->prepare($sql_qr_items);
        $stmt_qr_items->bind_param("i", $order['table_id']);
        $stmt_qr_items->execute();
        $result_items = $stmt_qr_items->get_result();
        $stmt_qr_items->close();
        
        echo "QR order items: {$result_items->num_rows}\n";
    }
    
    // Display items
    if ($result_items->num_rows > 0) {
        echo "Items for kitchen:\n";
        while ($item = $result_items->fetch_assoc()) {
            echo "- {$item['item_name']}: {$item['quantity']} x {$item['unit_price']}\n";
            if (isset($item['variation_name']) && $item['variation_name'] != 'QR Order') {
                echo "  Variation: {$item['variation_name']}\n";
            }
        }
    } else {
        echo "❌ NO ITEMS FOUND FOR KITCHEN!\n";
    }
    
    echo "\n";
}

echo "=== TEST COMPLETED ===\n";

$conn->close();
?>





