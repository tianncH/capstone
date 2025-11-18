<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING CANCELLATION TIME ISSUE ===\n\n";

// Check current session expiry
echo "1. SESSION EXPIRY TIME:\n";
$result = $conn->query("SELECT expires_at FROM qr_sessions WHERE session_id = 6");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session expires at (DB): {$session['expires_at']}\n";
    
    $expires_time = new DateTime($session['expires_at'], new DateTimeZone('Asia/Manila'));
    echo "Session expires at (Manila): " . $expires_time->format('g:i A') . "\n";
} else {
    echo "❌ Session not found\n";
}

echo "\n";

// Check current time
echo "2. CURRENT TIME:\n";
$now = new DateTime('now', new DateTimeZone('Asia/Manila'));
echo "Current time: " . $now->format('g:i A') . "\n";

echo "\n";

// Check what the cancellation times should be
echo "3. CANCELLATION TIMES IN ORDERS:\n";
$result = $conn->query("SELECT order_id, time_limit_expires, created_at FROM qr_orders WHERE session_id = 6");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Order ID: {$row['order_id']}\n";
        echo "Time Limit Expires: " . ($row['time_limit_expires'] ?: 'NULL') . "\n";
        echo "Created At: {$row['created_at']}\n";
        
        if ($row['time_limit_expires']) {
            $cancel_time = new DateTime($row['time_limit_expires'], new DateTimeZone('Asia/Manila'));
            echo "Can cancel until: " . $cancel_time->format('g:i A') . "\n";
        } else {
            echo "Can cancel until: NULL (should use session expiry)\n";
        }
        echo "\n";
    }
} else {
    echo "❌ No orders found\n";
}

echo "\n";

// Check what the system should be displaying
echo "4. WHAT SYSTEM SHOULD DISPLAY:\n";
$result = $conn->query("SELECT expires_at FROM qr_sessions WHERE session_id = 6");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    $expires_time = new DateTime($session['expires_at'], new DateTimeZone('Asia/Manila'));
    echo "All orders should show: 'Can cancel until " . $expires_time->format('g:i A') . "'\n";
} else {
    echo "❌ Session not found\n";
}

$conn->close();
?>





