<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING SESSION EXPIRY CONTRADICTION ===\n\n";

// Check the current session details
echo "1. CURRENT SESSION (ID: 6) DETAILS:\n";
$result = $conn->query("SELECT * FROM qr_sessions WHERE session_id = 6");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session ID: {$session['session_id']}\n";
    echo "Table ID: {$session['table_id']}\n";
    echo "Status: {$session['status']}\n";
    echo "Created At: {$session['created_at']}\n";
    echo "Expires At: {$session['expires_at']}\n";
    echo "Confirmed by Counter: " . ($session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
    
    // Check current time vs expiry time
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $expires = new DateTime($session['expires_at'], new DateTimeZone('Asia/Manila'));
    
    echo "\n2. TIME COMPARISON:\n";
    echo "Current Manila Time: " . $now->format('Y-m-d H:i:s') . "\n";
    echo "Expires Manila Time: " . $expires->format('Y-m-d H:i:s') . "\n";
    
    $is_expired = $now > $expires;
    echo "Is Session Expired: " . ($is_expired ? 'YES' : 'NO') . "\n";
    
    if ($is_expired) {
        $diff = $now->diff($expires);
        echo "Expired by: {$diff->h} hours {$diff->i} minutes\n";
    } else {
        $diff = $expires->diff($now);
        echo "Time until expiry: {$diff->h} hours {$diff->i} minutes\n";
    }
    
    // Check what the secure_qr_api.php validation would do
    echo "\n3. API VALIDATION SIMULATION:\n";
    echo "The secure_qr_api.php checks:\n";
    echo "- Session exists: " . ($session ? 'YES' : 'NO') . "\n";
    echo "- Status is 'active' or 'locked': " . (in_array($session['status'], ['active', 'locked']) ? 'YES' : 'NO') . "\n";
    echo "- Expires at is NULL or > NOW(): ";
    
    if ($session['expires_at'] === null) {
        echo "NULL (valid)\n";
    } else {
        $expires_timestamp = strtotime($session['expires_at']);
        $current_timestamp = time();
        $is_not_expired = $expires_timestamp > $current_timestamp;
        echo ($is_not_expired ? 'YES (valid)' : 'NO (expired)') . "\n";
        echo "  Expires timestamp: {$expires_timestamp}\n";
        echo "  Current timestamp: {$current_timestamp}\n";
    }
    
} else {
    echo "❌ Session ID 6 not found\n";
}

echo "\n";

// Check if there are any timezone issues
echo "4. TIMEZONE CHECK:\n";
echo "PHP default timezone: " . date_default_timezone_get() . "\n";
echo "Current PHP time: " . date('Y-m-d H:i:s') . "\n";
echo "Current PHP timestamp: " . time() . "\n";

// Check MySQL timezone
$result = $conn->query("SELECT @@session.time_zone as mysql_timezone, NOW() as mysql_now");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "MySQL timezone: {$row['mysql_timezone']}\n";
    echo "MySQL NOW(): {$row['mysql_now']}\n";
}

$conn->close();
?>





