<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUG SESSION VARIABLES ===\n\n";

// Get the current session for Table 1
$session_sql = "SELECT * FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1";
$session_result = $conn->query($session_sql);

if ($session_result && $session_result->num_rows > 0) {
    $current_session = $session_result->fetch_assoc();
    
    echo "CURRENT SESSION DATA:\n";
    echo "Session ID: {$current_session['session_id']}\n";
    echo "Table ID: {$current_session['table_id']}\n";
    echo "Status: {$current_session['status']}\n";
    echo "Confirmed by Counter: " . ($current_session['confirmed_by_counter'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Confirmed by Counter (raw): '{$current_session['confirmed_by_counter']}'\n";
    echo "Created At: {$current_session['created_at']}\n";
    echo "Expires At: {$current_session['expires_at']}\n";
    echo "Confirmed At: {$current_session['confirmed_at']}\n\n";
    
    echo "JAVASCRIPT VARIABLE VALUES:\n";
    echo "isConfirmed = " . ($current_session['confirmed_by_counter'] ? 'true' : 'false') . "\n";
    echo "Button disabled = " . (!$current_session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n\n";
    
    echo "TIMEZONE CONVERSION:\n";
    $created_ph = new DateTime($current_session['created_at'], new DateTimeZone('UTC'));
    $created_ph->setTimezone(new DateTimeZone('Asia/Manila'));
    echo "Created (UTC): {$current_session['created_at']}\n";
    echo "Created (PH): " . $created_ph->format('Y-m-d H:i:s') . "\n";
    echo "Created (PH 12h): " . $created_ph->format('g:i A') . "\n\n";
    
    $expires_ph = new DateTime($current_session['expires_at'], new DateTimeZone('UTC'));
    $expires_ph->setTimezone(new DateTimeZone('Asia/Manila'));
    echo "Expires (UTC): {$current_session['expires_at']}\n";
    echo "Expires (PH): " . $expires_ph->format('Y-m-d H:i:s') . "\n";
    echo "Expires (PH 12h): " . $expires_ph->format('g:i A') . "\n\n";
    
    // Check if session is expired
    $now = new DateTime();
    $expires = new DateTime($current_session['expires_at']);
    $is_expired = $now > $expires;
    echo "SESSION EXPIRATION CHECK:\n";
    echo "Current Time: " . $now->format('Y-m-d H:i:s') . "\n";
    echo "Expires Time: " . $expires->format('Y-m-d H:i:s') . "\n";
    echo "Is Expired: " . ($is_expired ? 'YES' : 'NO') . "\n";
    
} else {
    echo "No active session found for Table 1\n";
}

$conn->close();
?>






