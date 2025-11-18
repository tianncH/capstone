<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING CONFIRM ORDERS BUG ===\n\n";

// Check current session
echo "1. CURRENT SESSION:\n";
$result = $conn->query("SELECT * FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session ID: {$session['session_id']}\n";
    echo "Table ID: {$session['table_id']}\n";
    echo "Status: {$session['status']}\n";
    echo "Created: {$session['created_at']}\n";
    echo "Confirmed: " . ($session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
    
    $session_id = $session['session_id'];
} else {
    echo "❌ No active session found\n";
    exit;
}

echo "\n";

// Check QR orders for this session
echo "2. QR ORDERS FOR SESSION {$session_id}:\n";
$result = $conn->query("SELECT * FROM qr_orders WHERE session_id = {$session_id} ORDER BY created_at");
if ($result) {
    echo "Total QR orders: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Order ID: {$row['order_id']}\n";
        echo "  Menu Item ID: {$row['menu_item_id']}\n";
        echo "  Quantity: {$row['quantity']}\n";
        echo "  Status: {$row['status']}\n";
        echo "  Created: {$row['created_at']}\n";
        echo "\n";
    }
} else {
    echo "❌ Error querying QR orders: " . $conn->error . "\n";
}

echo "\n";

// Check what the JavaScript should be counting
echo "3. JAVASCRIPT COUNTING LOGIC:\n";
echo "The 'Confirm Orders' button should count orders with status = 'pending'\n";

$result = $conn->query("SELECT COUNT(*) as count FROM qr_orders WHERE session_id = {$session_id} AND status = 'pending'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Pending orders count: {$row['count']}\n";
    
    if ($row['count'] > 0) {
        echo "✅ Should show: 'Confirm Orders ({$row['count']})'\n";
    } else {
        echo "❌ Shows: 'Confirm Orders (0)' - This is the bug!\n";
    }
} else {
    echo "❌ Error counting pending orders: " . $conn->error . "\n";
}

echo "\n";

// Check all order statuses
echo "4. ALL ORDER STATUSES:\n";
$result = $conn->query("SELECT status, COUNT(*) as count FROM qr_orders WHERE session_id = {$session_id} GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['status']}: {$row['count']} orders\n";
    }
} else {
    echo "❌ Error querying order statuses: " . $conn->error . "\n";
}

$conn->close();
?>





