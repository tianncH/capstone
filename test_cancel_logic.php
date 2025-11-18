<?php
require_once 'admin/includes/db_connection.php';

echo "=== TESTING CANCEL LOGIC ===\n\n";

// Get session data
$result = $conn->query("SELECT expires_at FROM qr_sessions WHERE session_id = 6");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    
    echo "Session data:\n";
    echo "Expires at (DB): {$session['expires_at']}\n";
    
    // Test the new logic
    $cancel_deadline = new DateTime($session['expires_at'], new DateTimeZone('Asia/Manila'));
    echo "Can cancel until: " . $cancel_deadline->format('g:i A') . "\n";
    
    // Check current time
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    echo "Current time: " . $now->format('g:i A') . "\n";
    
    // Check if cancellation is still possible
    $can_cancel = $now < $cancel_deadline;
    echo "Can still cancel: " . ($can_cancel ? 'YES' : 'NO') . "\n";
    
    if ($can_cancel) {
        $time_left = $cancel_deadline->diff($now);
        echo "Time left to cancel: {$time_left->h} hours {$time_left->i} minutes\n";
    }
    
} else {
    echo "No session found\n";
}

$conn->close();
?>





