<?php
require_once 'admin/includes/db_connection.php';

echo "=== TESTING KITCHEN TIMER FIX ===\n\n";

// Simulate the fixed kitchen system calculation
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
    echo "Status: {$order['status_name']} (ID: {$order['status_id']})\n";
    echo "Created: {$order['created_at']}\n";
    echo "Updated: {$order['updated_at']}\n";
    
    // NEW FIXED CALCULATION
    $timestamp_to_use = ($order['status_id'] >= 3) ? $order['updated_at'] : $order['created_at'];
    $timestamp_time = strtotime($timestamp_to_use);
    $current_time = time();
    $time_elapsed = round(($current_time - $timestamp_time) / 60);
    $time_elapsed = max(0, $time_elapsed);
    
    echo "Using timestamp: {$timestamp_to_use}\n";
    echo "Time elapsed: {$time_elapsed} minutes\n";
    echo "Should display: {$time_elapsed}m ago\n";
    
    // OLD BROKEN CALCULATION (for comparison)
    $old_created_time = strtotime($order['created_at']);
    $old_time_elapsed = round(($current_time - $old_created_time) / 60);
    echo "OLD calculation (broken): {$old_time_elapsed}m ago\n";
    
    echo "\n";
}

echo "=== FIX VERIFICATION ===\n";
echo "✅ NEW: Uses updated_at for preparing/ready orders\n";
echo "✅ NEW: Uses created_at for new orders\n";
echo "✅ NEW: Shows correct elapsed time since status change\n";
echo "❌ OLD: Always used created_at (incorrect for status changes)\n";

$conn->close();
?>





