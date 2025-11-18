<?php
require_once 'admin/includes/db_connection.php';

echo "=== TESTING TIMEZONE FIX ===\n\n";

// Get current session
$result = $conn->query("SELECT created_at, expires_at FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    
    echo "Database times:\n";
    echo "Created: {$session['created_at']}\n";
    echo "Expires: {$session['expires_at']}\n\n";
    
    echo "OLD WAY (treating as UTC then converting to Manila):\n";
    $created_old = new DateTime($session['created_at'], new DateTimeZone('UTC'));
    $created_old->setTimezone(new DateTimeZone('Asia/Manila'));
    echo "Started: " . $created_old->format('g:i A') . "\n";
    
    $expires_old = new DateTime($session['expires_at'], new DateTimeZone('UTC'));
    $expires_old->setTimezone(new DateTimeZone('Asia/Manila'));
    echo "Expires: " . $expires_old->format('g:i A') . "\n\n";
    
    echo "NEW WAY (treating as Manila time directly):\n";
    $created_new = new DateTime($session['created_at'], new DateTimeZone('Asia/Manila'));
    echo "Started: " . $created_new->format('g:i A') . "\n";
    
    $expires_new = new DateTime($session['expires_at'], new DateTimeZone('Asia/Manila'));
    echo "Expires: " . $expires_new->format('g:i A') . "\n\n";
    
    echo "Current Manila time: " . (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('g:i A') . "\n";
    
} else {
    echo "No active session found\n";
}

$conn->close();
?>





