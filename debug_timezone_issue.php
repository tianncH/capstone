<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING TIMEZONE ISSUE ===\n\n";

// Check current session data
echo "1. CURRENT SESSION DATA:\n";
$result = $conn->query("SELECT session_id, created_at, expires_at, confirmed_by_counter FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session ID: {$session['session_id']}\n";
    echo "Created At (DB): {$session['created_at']}\n";
    echo "Expires At (DB): {$session['expires_at']}\n";
    echo "Confirmed: " . ($session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
} else {
    echo "❌ No active session found\n";
    exit;
}

echo "\n";

// Check timezone settings
echo "2. TIMEZONE SETTINGS:\n";
echo "PHP Default Timezone: " . date_default_timezone_get() . "\n";
echo "Current PHP Time: " . date('Y-m-d H:i:s') . "\n";
echo "Current PHP Time (12h): " . date('g:i A') . "\n";

// Check MySQL timezone
$result = $conn->query("SELECT @@session.time_zone as mysql_timezone, NOW() as mysql_time");
if ($result) {
    $row = $result->fetch_assoc();
    echo "MySQL Timezone: {$row['mysql_timezone']}\n";
    echo "MySQL Time: {$row['mysql_time']}\n";
}

echo "\n";

// Test timezone conversions
echo "3. TIMEZONE CONVERSION TESTS:\n";
$created_at = $session['created_at'];
$expires_at = $session['expires_at'];

echo "Original Created At: {$created_at}\n";
echo "Original Expires At: {$expires_at}\n";

// Test UTC to Asia/Manila conversion
$created_utc = new DateTime($created_at, new DateTimeZone('UTC'));
$created_manila = clone $created_utc;
$created_manila->setTimezone(new DateTimeZone('Asia/Manila'));

$expires_utc = new DateTime($expires_at, new DateTimeZone('UTC'));
$expires_manila = clone $expires_utc;
$expires_manila->setTimezone(new DateTimeZone('Asia/Manila'));

echo "Created (UTC): " . $created_utc->format('Y-m-d H:i:s') . "\n";
echo "Created (Manila): " . $created_manila->format('Y-m-d H:i:s') . "\n";
echo "Created (Manila 12h): " . $created_manila->format('g:i A') . "\n";

echo "Expires (UTC): " . $expires_utc->format('Y-m-d H:i:s') . "\n";
echo "Expires (Manila): " . $expires_manila->format('Y-m-d H:i:s') . "\n";
echo "Expires (Manila 12h): " . $expires_manila->format('g:i A') . "\n";

echo "\n";

// Check if session is expired
echo "4. SESSION EXPIRATION CHECK:\n";
$now = new DateTime('now', new DateTimeZone('Asia/Manila'));
echo "Current Manila Time: " . $now->format('Y-m-d H:i:s') . "\n";
echo "Current Manila Time (12h): " . $now->format('g:i A') . "\n";

$is_expired = $now > $expires_manila;
echo "Is Session Expired: " . ($is_expired ? 'YES' : 'NO') . "\n";

if ($is_expired) {
    $diff = $now->diff($expires_manila);
    echo "Expired by: {$diff->h} hours {$diff->i} minutes\n";
} else {
    $diff = $expires_manila->diff($now);
    echo "Time until expiry: {$diff->h} hours {$diff->i} minutes\n";
}

echo "\n";

// Check what the system should be displaying
echo "5. WHAT SYSTEM SHOULD DISPLAY:\n";
echo "Started: " . $created_manila->format('g:i A') . "\n";
echo "Expires: " . $expires_manila->format('g:i A') . "\n";

$conn->close();
?>





