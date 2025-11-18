<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING TIMER ISSUE ===\n\n";

// Check the order that's showing in kitchen
echo "1. ORDER DETAILS:\n";
$result = $conn->query("SELECT * FROM orders WHERE queue_number = 'QR1-133940'");
if ($result && $result->num_rows > 0) {
    $order = $result->fetch_assoc();
    echo "Order ID: {$order['order_id']}\n";
    echo "Queue Number: {$order['queue_number']}\n";
    echo "Created At: {$order['created_at']}\n";
    echo "Updated At: {$order['updated_at']}\n";
    echo "Status ID: {$order['status_id']}\n";
    
    // Calculate time elapsed
    $created_time = strtotime($order['created_at']);
    $current_time = time();
    $time_elapsed = round(($current_time - $created_time) / 60);
    echo "Time Elapsed (calculated): {$time_elapsed} minutes\n";
    echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
    echo "Created Time: {$order['created_at']}\n";
} else {
    echo "❌ Order not found\n";
}

echo "\n";

// Check if the order was recently updated (which would reset the timer)
echo "2. CHECKING ORDER STATUS HISTORY:\n";
$result = $conn->query("SELECT * FROM order_status_history WHERE order_id = 1 ORDER BY created_at DESC LIMIT 5");
if ($result) {
    echo "Status history entries: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Status ID: {$row['status_id']}, Created: {$row['created_at']}\n";
    }
} else {
    echo "❌ No status history found\n";
}

echo "\n";

// Check what the kitchen system is actually calculating
echo "3. KITCHEN SYSTEM CALCULATION:\n";
$today = date('Y-m-d');
$sql_orders = "SELECT o.*, os.name as status_name, t.table_number
               FROM orders o 
               LEFT JOIN tables t ON o.table_id = t.table_id 
               JOIN order_statuses os ON o.status_id = os.status_id 
               WHERE DATE(o.created_at) = ? 
               AND (o.status_id = 3 OR o.status_id = 4) 
               ORDER BY o.created_at ASC";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("s", $today);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
$stmt_orders->close();

while ($order = $result_orders->fetch_assoc()) {
    echo "Order: {$order['queue_number']}\n";
    echo "Created: {$order['created_at']}\n";
    
    // This is the same calculation as in kitchen/index.php
    $created_time = strtotime($order['created_at']);
    $current_time = time();
    $time_elapsed = round(($current_time - $created_time) / 60);
    $time_elapsed = max(0, $time_elapsed);
    
    echo "Time Elapsed (kitchen calc): {$time_elapsed} minutes\n";
    echo "Should display: {$time_elapsed}m ago\n";
}

$conn->close();
?>





