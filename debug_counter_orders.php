<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING COUNTER ORDERS ===\n\n";

// Check what orders the counter should be showing
echo "1. TODAY'S ORDERS (Counter Query):\n";
$today = date('Y-m-d');
$sql_orders = "SELECT o.*, os.name as status_name, t.table_number, ts.session_id
               FROM orders o 
               LEFT JOIN tables t ON o.table_id = t.table_id 
               JOIN order_statuses os ON o.status_id = os.status_id 
               LEFT JOIN table_sessions ts ON o.session_id = ts.session_id
               WHERE DATE(o.created_at) = ? 
               ORDER BY o.created_at DESC";
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
    echo "Status: {$order['status_name']} (ID: {$order['status_id']})\n";
    echo "Total: {$order['total_amount']}\n";
    echo "Created: {$order['created_at']}\n";
    echo "Session ID: " . ($order['session_id'] ?: 'None') . "\n";
    
    // Check if this order has items
    $result = $conn->query("SELECT COUNT(*) as count FROM order_items WHERE order_id = {$order['order_id']}");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Order Items: {$row['count']}\n";
    }
    
    // Check if this order has QR orders
    $result = $conn->query("SELECT COUNT(*) as count FROM qr_orders WHERE order_id = {$order['order_id']}");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "QR Orders: {$row['count']}\n";
    }
    
    echo "\n";
}

echo "\n";

// Check the specific order that should be showing
echo "2. ORDER QR1-133940 DETAILS:\n";
$result = $conn->query("SELECT * FROM orders WHERE queue_number = 'QR1-133940'");
if ($result && $result->num_rows > 0) {
    $order = $result->fetch_assoc();
    echo "Order ID: {$order['order_id']}\n";
    echo "Queue Number: {$order['queue_number']}\n";
    echo "Table ID: {$order['table_id']}\n";
    echo "Status ID: {$order['status_id']}\n";
    echo "Total Amount: {$order['total_amount']}\n";
    echo "Created: {$order['created_at']}\n";
    echo "Updated: {$order['updated_at']}\n";
    
    // Check order items
    $result = $conn->query("SELECT oi.*, mi.name as item_name 
                            FROM order_items oi 
                            LEFT JOIN menu_items mi ON oi.item_id = mi.item_id 
                            WHERE oi.order_id = {$order['order_id']}");
    if ($result) {
        echo "Order Items: {$result->num_rows}\n";
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['item_name']}: {$row['quantity']} x {$row['unit_price']}\n";
        }
    }
    
    // Check QR orders
    $result = $conn->query("SELECT qo.*, mi.name as item_name 
                            FROM qr_orders qo 
                            LEFT JOIN menu_items mi ON qo.menu_item_id = mi.item_id 
                            WHERE qo.session_id = 6");
    if ($result) {
        echo "QR Orders: {$result->num_rows}\n";
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['item_name']}: {$row['quantity']} x {$row['unit_price']}\n";
        }
    }
} else {
    echo "❌ Order QR1-133940 not found\n";
}

$conn->close();
?>





