<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING REFRESH BUG ===\n\n";

// Get current active session
$sql = "SELECT session_id, table_id, status, confirmed_by_counter FROM qr_sessions WHERE status = 'active' ORDER BY created_at DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session ID: " . $session['session_id'] . "\n";
    echo "Table ID: " . $session['table_id'] . "\n";
    echo "Session Status: " . $session['status'] . "\n";
    echo "Confirmed by Counter: " . ($session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n\n";
    
    // Get ALL orders for this session with their history
    $orders_sql = "SELECT order_id, menu_item_id, quantity, status, subtotal, created_at, confirmed_at FROM qr_orders WHERE session_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($orders_sql);
    $stmt->bind_param('i', $session['session_id']);
    $stmt->execute();
    $orders = $stmt->get_result();
    $stmt->close();
    
    echo "--- ALL ORDERS IN SESSION ---\n";
    $status_counts = [];
    while ($order = $orders->fetch_assoc()) {
        echo "Order ID: {$order['order_id']}, Menu Item: {$order['menu_item_id']}, Qty: {$order['quantity']}, Status: {$order['status']}, Created: {$order['created_at']}, Confirmed: {$order['confirmed_at']}\n";
        $status_counts[$order['status']] = ($status_counts[$order['status']] ?? 0) + 1;
    }
    
    echo "\n--- STATUS SUMMARY ---\n";
    foreach ($status_counts as $status => $count) {
        echo "{$status}: {$count} orders\n";
    }
    
    // Check if there are any pending orders that should exist
    $pending_count = $status_counts['pending'] ?? 0;
    echo "\nPending orders: {$pending_count}\n";
    
    if ($pending_count == 0 && count($status_counts) > 0) {
        echo "\n🚨 ALERT: NO PENDING ORDERS FOUND!\n";
        echo "This suggests orders were automatically confirmed on page load!\n";
        
        // Check if there's any logic that auto-confirms orders
        echo "\n--- CHECKING FOR AUTO-CONFIRMATION LOGIC ---\n";
        
        // Check if there are any recent updates to orders
        $recent_updates_sql = "SELECT order_id, status, confirmed_at FROM qr_orders WHERE session_id = ? AND confirmed_at IS NOT NULL ORDER BY confirmed_at DESC LIMIT 5";
        $stmt = $conn->prepare($recent_updates_sql);
        $stmt->bind_param('i', $session['session_id']);
        $stmt->execute();
        $recent_updates = $stmt->get_result();
        $stmt->close();
        
        echo "Recent confirmations:\n";
        while ($update = $recent_updates->fetch_assoc()) {
            echo "- Order {$update['order_id']}: confirmed at {$update['confirmed_at']}\n";
        }
    }
    
} else {
    echo "No active sessions found\n";
}

$conn->close();
?>





