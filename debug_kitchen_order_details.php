<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING KITCHEN ORDER DETAILS ===\n\n";

// Check the order that kitchen is seeing
echo "1. ORDER #QR1-133940 DETAILS:\n";
$result = $conn->query("SELECT * FROM orders WHERE queue_number = 'QR1-133940'");
if ($result && $result->num_rows > 0) {
    $order = $result->fetch_assoc();
    echo "Order ID: {$order['order_id']}\n";
    echo "Queue Number: {$order['queue_number']}\n";
    echo "Table ID: {$order['table_id']}\n";
    echo "Status ID: {$order['status_id']}\n";
    echo "Total Amount: {$order['total_amount']}\n";
    echo "Notes: {$order['notes']}\n";
    echo "Created At: {$order['created_at']}\n";
} else {
    echo "❌ Order not found\n";
    exit;
}

echo "\n";

// Check order items for this order
echo "2. ORDER ITEMS FOR ORDER #{$order['order_id']}:\n";
$result = $conn->query("SELECT oi.*, mi.name as item_name, mi.description as item_description
                        FROM order_items oi 
                        LEFT JOIN menu_items mi ON oi.item_id = mi.item_id 
                        WHERE oi.order_id = {$order['order_id']}");
if ($result) {
    echo "Items count: {$result->num_rows}\n";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['item_name']}: {$row['quantity']} x {$row['unit_price']} = {$row['subtotal']}\n";
            echo "  Description: {$row['item_description']}\n";
            if ($row['notes']) {
                echo "  Notes: {$row['notes']}\n";
            }
        }
    } else {
        echo "❌ NO ORDER ITEMS FOUND!\n";
    }
} else {
    echo "❌ Error querying order items: " . $conn->error . "\n";
}

echo "\n";

// Check if this is a QR order that should have items in qr_orders table
echo "3. CHECKING QR ORDERS:\n";
$result = $conn->query("SELECT qo.*, mi.name as item_name 
                        FROM qr_orders qo 
                        LEFT JOIN menu_items mi ON qo.menu_item_id = mi.item_id 
                        WHERE qo.session_id = 6");
if ($result) {
    echo "QR Orders count: {$result->num_rows}\n";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['item_name']}: {$row['quantity']} x {$row['unit_price']} = {$row['subtotal']}\n";
            echo "  Status: {$row['status']}\n";
        }
    } else {
        echo "❌ NO QR ORDERS FOUND!\n";
    }
} else {
    echo "❌ Error querying QR orders: " . $conn->error . "\n";
}

echo "\n";

// Check the kitchen system query
echo "4. WHAT KITCHEN SYSTEM SHOULD BE SHOWING:\n";
echo "The kitchen should display:\n";
echo "- Order ID: {$order['order_id']}\n";
echo "- Queue Number: {$order['queue_number']}\n";
echo "- Table: {$order['table_id']}\n";
echo "- Items: [LIST OF FOOD ITEMS WITH QUANTITIES]\n";
echo "- Total: {$order['total_amount']}\n";

$conn->close();
?>





