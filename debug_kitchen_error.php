<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING KITCHEN ORDER ERROR ===\n\n";

// Check current orders for session 6
echo "1. CURRENT ORDERS FOR SESSION 6:\n";
$result = $conn->query("SELECT qo.*, mi.name as item_name FROM qr_orders qo LEFT JOIN menu_items mi ON qo.menu_item_id = mi.item_id WHERE qo.session_id = 6 ORDER BY qo.order_id");
if ($result) {
    echo "Orders count: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Order ID: {$row['order_id']}, Item: {$row['item_name']}, Qty: {$row['quantity']}, Status: {$row['status']}, Price: {$row['unit_price']}\n";
    }
} else {
    echo "❌ Error querying orders: " . $conn->error . "\n";
}

echo "\n";

// Check what happens when we try to send orders to kitchen
echo "2. SIMULATING SEND TO KITCHEN:\n";
$session_id = 6;
$session_token = '9b9d12d5dd83d7c02f165859e425439f';
$device_fingerprint = 'abe3453cf6dac6ba237dcfd0e62a5dcd';

// Check session validity
$session_sql = "SELECT * FROM qr_sessions WHERE session_id = ? AND session_token = ? AND device_fingerprint = ? AND status = 'active'";
$session_stmt = $conn->prepare($session_sql);
$session_stmt->bind_param('iss', $session_id, $session_token, $device_fingerprint);
$session_stmt->execute();
$session = $session_stmt->get_result()->fetch_assoc();
$session_stmt->close();

if (!$session) {
    echo "❌ Session validation failed\n";
    exit;
}

echo "✅ Session validation passed\n";

// Check pending orders
$orders_sql = "SELECT * FROM qr_orders WHERE session_id = ? AND status = 'pending'";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param('i', $session_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$orders_stmt->close();

$pending_orders = [];
while ($row = $orders_result->fetch_assoc()) {
    $pending_orders[] = $row;
}

echo "Pending orders count: " . count($pending_orders) . "\n";

if (empty($pending_orders)) {
    echo "❌ No pending orders to send\n";
    exit;
}

// Try to update orders to 'confirmed' status
echo "3. UPDATING ORDERS TO CONFIRMED:\n";
$update_sql = "UPDATE qr_orders SET status = 'confirmed', confirmed_at = NOW() WHERE session_id = ? AND status = 'pending'";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param('i', $session_id);

if ($update_stmt->execute()) {
    $affected_rows = $update_stmt->affected_rows;
    echo "✅ Successfully updated {$affected_rows} orders to confirmed status\n";
} else {
    echo "❌ Failed to update orders: " . $update_stmt->error . "\n";
}
$update_stmt->close();

echo "\n";

// Check if there's a kitchen system to send orders to
echo "4. CHECKING KITCHEN SYSTEM:\n";
$kitchen_files = ['kitchen/index.php', 'kitchen/orders.php', 'kitchen/order_queue.php'];
foreach ($kitchen_files as $file) {
    if (file_exists($file)) {
        echo "✅ Kitchen file exists: {$file}\n";
    } else {
        echo "❌ Kitchen file missing: {$file}\n";
    }
}

$conn->close();
?>





