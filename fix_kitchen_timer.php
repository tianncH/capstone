<?php
require_once 'admin/includes/db_connection.php';

echo "=== FIXING KITCHEN TIMER ===\n\n";

// Check the current order
$result = $conn->query("SELECT * FROM orders WHERE queue_number = 'QR1-133940'");
if ($result && $result->num_rows > 0) {
    $order = $result->fetch_assoc();
    echo "Order: {$order['queue_number']}\n";
    echo "Created: {$order['created_at']}\n";
    echo "Updated: {$order['updated_at']}\n";
    
    // The issue might be that we're using 'created_at' but we should use 'updated_at' for status changes
    // Let's check when the order was last updated to "preparing" status
    
    $result = $conn->query("SELECT * FROM order_status_history WHERE order_id = {$order['order_id']} AND status_id = 3 ORDER BY created_at DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $status_history = $result->fetch_assoc();
        echo "Last status change to preparing: {$status_history['created_at']}\n";
        
        // Calculate time since status change
        $status_change_time = strtotime($status_history['created_at']);
        $current_time = time();
        $time_elapsed = round(($current_time - $status_change_time) / 60);
        echo "Time since status change: {$time_elapsed} minutes\n";
    } else {
        echo "No status history found\n";
    }
}

echo "\n";

// The real issue might be that the kitchen system is using 'created_at' instead of when the order was last updated
// Let's check what the kitchen system should be using

echo "KITCHEN SYSTEM SHOULD USE:\n";
echo "1. For new orders: 'created_at' (when order was first created)\n";
echo "2. For preparing orders: 'updated_at' (when order was last updated to preparing)\n";
echo "3. For ready orders: 'updated_at' (when order was marked as ready)\n";

$conn->close();
?>





