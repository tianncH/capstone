<?php
require_once 'admin/includes/db_connection.php';

echo "=== CHECKING ORDER LINKING ===\n\n";

// Check if there's a relationship between orders and qr_orders
echo "1. CHECKING ORDER-QR_ORDER RELATIONSHIP:\n";

// Check if the order has a session_id that links to qr_sessions
$result = $conn->query("SELECT o.*, qs.session_id as qr_session_id 
                        FROM orders o 
                        LEFT JOIN qr_sessions qs ON o.session_id = qs.session_id 
                        WHERE o.order_id = 1");
if ($result && $result->num_rows > 0) {
    $order = $result->fetch_assoc();
    echo "Order ID: {$order['order_id']}\n";
    echo "Order Session ID: {$order['session_id']}\n";
    echo "QR Session ID: {$order['qr_session_id']}\n";
    
    if ($order['qr_session_id']) {
        echo "✅ Order is linked to QR session!\n";
        
        // Check QR orders for this session
        $result = $conn->query("SELECT qo.*, mi.name as item_name 
                                FROM qr_orders qo 
                                LEFT JOIN menu_items mi ON qo.menu_item_id = mi.item_id 
                                WHERE qo.session_id = {$order['qr_session_id']}");
        if ($result) {
            echo "QR Orders for this session: {$result->num_rows}\n";
            while ($row = $result->fetch_assoc()) {
                echo "- {$row['item_name']}: {$row['quantity']} x {$row['unit_price']}\n";
            }
        }
    } else {
        echo "❌ Order is NOT linked to QR session\n";
    }
} else {
    echo "❌ Order not found\n";
}

echo "\n";

// Check if we need to create order_items from qr_orders
echo "2. CHECKING IF WE NEED TO CREATE ORDER_ITEMS:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM order_items WHERE order_id = 1");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Order items in order_items table: {$row['count']}\n";
    
    if ($row['count'] == 0) {
        echo "❌ No order items found - need to create them from qr_orders\n";
        
        // Check if we can create order_items from qr_orders
        $result = $conn->query("SELECT qo.*, mi.name as item_name 
                                FROM qr_orders qo 
                                LEFT JOIN menu_items mi ON qo.menu_item_id = mi.item_id 
                                WHERE qo.session_id = 6");
        if ($result && $result->num_rows > 0) {
            echo "✅ Can create order_items from qr_orders:\n";
            while ($row = $result->fetch_assoc()) {
                echo "- {$row['item_name']}: {$row['quantity']} x {$row['unit_price']}\n";
            }
        }
    } else {
        echo "✅ Order items already exist\n";
    }
}

echo "\n";

// Check what the kitchen system should be doing
echo "3. KITCHEN SYSTEM SOLUTION:\n";
echo "The kitchen system needs to:\n";
echo "1. Check if order has items in order_items table\n";
echo "2. If not, check if order is linked to QR session\n";
echo "3. If linked to QR session, get items from qr_orders table\n";
echo "4. Display the items to kitchen staff\n";

$conn->close();
?>





