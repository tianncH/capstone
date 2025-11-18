<?php
require_once 'admin/includes/db_connection.php';

echo "=== TIMEZONE & BUTTON TREASURE HUNT ===\n\n";

// 1. Check current timezone settings
echo "1. TIMEZONE ANALYSIS:\n";
echo "PHP Default Timezone: " . date_default_timezone_get() . "\n";
echo "PHP Current Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Current Time (12h): " . date('g:i A') . "\n";
echo "PHP Timestamp: " . time() . "\n\n";

// 2. Check MySQL timezone
echo "2. MYSQL TIMEZONE:\n";
$result = $conn->query("SELECT @@global.time_zone, @@session.time_zone, NOW() as mysql_time");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Global Timezone: {$row['@@global.time_zone']}\n";
    echo "Session Timezone: {$row['@@session.time_zone']}\n";
    echo "MySQL Current Time: {$row['mysql_time']}\n\n";
}

// 3. Check Table 1 session times
echo "3. TABLE 1 SESSION TIMES:\n";
$result = $conn->query("SELECT session_id, created_at, expires_at, confirmed_at FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session ID: {$session['session_id']}\n";
    echo "Created At: {$session['created_at']}\n";
    echo "Expires At: {$session['expires_at']}\n";
    echo "Confirmed At: {$session['confirmed_at']}\n";
    
    // Convert to Philippines time
    $created_ph = new DateTime($session['created_at'], new DateTimeZone('UTC'));
    $created_ph->setTimezone(new DateTimeZone('Asia/Manila'));
    echo "Created (PH Time): " . $created_ph->format('Y-m-d H:i:s') . "\n";
    
    $expires_ph = new DateTime($session['expires_at'], new DateTimeZone('UTC'));
    $expires_ph->setTimezone(new DateTimeZone('Asia/Manila'));
    echo "Expires (PH Time): " . $expires_ph->format('Y-m-d H:i:s') . "\n";
} else {
    echo "No active session found for Table 1\n";
}

echo "\n";

// 4. Check if session is confirmed
echo "4. SESSION CONFIRMATION STATUS:\n";
$result = $conn->query("SELECT session_id, confirmed_by_counter, status FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session ID: {$session['session_id']}\n";
    echo "Confirmed by Counter: " . ($session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
    echo "Status: {$session['status']}\n";
    
    if ($session['confirmed_by_counter']) {
        echo "✅ Session is confirmed - Add buttons should work!\n";
    } else {
        echo "❌ Session not confirmed - Add buttons should be disabled!\n";
    }
} else {
    echo "No active session found\n";
}

echo "\n";

// 5. Check JavaScript in customer page
echo "5. CHECKING CUSTOMER PAGE JAVASCRIPT:\n";
$customer_file = 'ordering/secure_qr_menu.php';
if (file_exists($customer_file)) {
    $content = file_get_contents($customer_file);
    
    // Look for isConfirmed variable
    if (preg_match('/isConfirmed.*=.*(true|false)/i', $content, $matches)) {
        echo "Found isConfirmed: " . $matches[1] . "\n";
    } else {
        echo "❌ isConfirmed variable not found!\n";
    }
    
    // Look for addToOrder function
    if (strpos($content, 'function addToOrder') !== false) {
        echo "✅ addToOrder function found\n";
    } else {
        echo "❌ addToOrder function not found!\n";
    }
    
    // Look for button click handlers
    if (strpos($content, 'onclick="addToOrder') !== false) {
        echo "✅ Button click handlers found\n";
    } else {
        echo "❌ Button click handlers not found!\n";
    }
} else {
    echo "❌ Customer page file not found!\n";
}

$conn->close();
?>






