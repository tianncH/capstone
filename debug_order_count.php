<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING ORDER COUNT MISMATCH ===\n\n";

// Check all orders for session 6
echo "1. ALL ORDERS FOR SESSION 6:\n";
$result = $conn->query("SELECT qo.*, mi.name as item_name FROM qr_orders qo LEFT JOIN menu_items mi ON qo.menu_item_id = mi.item_id WHERE qo.session_id = 6 ORDER BY qo.order_id");
if ($result) {
    echo "Total orders: {$result->num_rows}\n";
    $total_items = 0;
    $total_amount = 0;
    
    while ($row = $result->fetch_assoc()) {
        echo "- Order ID: {$row['order_id']}, Item: {$row['item_name']}, Qty: {$row['quantity']}, Status: {$row['status']}, Price: {$row['unit_price']}, Subtotal: {$row['subtotal']}\n";
        $total_items += $row['quantity'];
        $total_amount += $row['subtotal'];
    }
    
    echo "\nSummary:\n";
    echo "Total Orders: {$result->num_rows}\n";
    echo "Total Items: {$total_items}\n";
    echo "Total Amount: {$total_amount}\n";
} else {
    echo "❌ Error querying orders: " . $conn->error . "\n";
}

echo "\n";

// Check pending orders specifically
echo "2. PENDING ORDERS ONLY:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM qr_orders WHERE session_id = 6 AND status = 'pending'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Pending orders: {$row['count']}\n";
} else {
    echo "❌ Error querying pending orders: " . $conn->error . "\n";
}

echo "\n";

// Check confirmed orders specifically
echo "3. CONFIRMED ORDERS ONLY:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM qr_orders WHERE session_id = 6 AND status = 'confirmed'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Confirmed orders: {$row['count']}\n";
} else {
    echo "❌ Error querying confirmed orders: " . $conn->error . "\n";
}

echo "\n";

// Check what the JavaScript should be counting
echo "4. JAVASCRIPT COUNTING LOGIC:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM qr_orders WHERE session_id = 6 AND status = 'pending'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "JavaScript should show: 'Confirm Orders ({$row['count']})'\n";
} else {
    echo "❌ Error in JavaScript counting logic\n";
}

$conn->close();
?>





