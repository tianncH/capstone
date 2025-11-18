<?php
require_once 'admin/includes/db_connection.php';

// Output to file instead of browser
$output = "";

$output .= "=== DEEP SESSION ANALYSIS ===\n\n";

// 1. Check all QR sessions
$output .= "1. ALL QR SESSIONS:\n";
$sessions_sql = "SELECT session_id, table_id, status, confirmed_by_counter, created_at, expires_at FROM qr_sessions ORDER BY created_at DESC LIMIT 5";
$sessions_result = $conn->query($sessions_sql);

if ($sessions_result && $sessions_result->num_rows > 0) {
    while ($session = $sessions_result->fetch_assoc()) {
        $confirmed = $session['confirmed_by_counter'] ? 'YES' : 'NO';
        $output .= "Session {$session['session_id']}: Table {$session['table_id']}, Status: {$session['status']}, Confirmed: {$confirmed}, Created: {$session['created_at']}\n";
    }
} else {
    $output .= "No QR sessions found!\n";
}

$output .= "\n";

// 2. Check Table 1 specifically
$output .= "2. TABLE 1 SESSION STATUS:\n";
$table_1_sql = "SELECT * FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1";
$table_1_result = $conn->query($table_1_sql);

if ($table_1_result && $table_1_result->num_rows > 0) {
    $table_1_session = $table_1_result->fetch_assoc();
    $output .= "Found active session for Table 1:\n";
    $output .= "- Session ID: {$table_1_session['session_id']}\n";
    $output .= "- Status: {$table_1_session['status']}\n";
    $output .= "- Confirmed by Counter: " . ($table_1_session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
    $output .= "- Created: {$table_1_session['created_at']}\n";
    $output .= "- Expires: {$table_1_session['expires_at']}\n";
} else {
    $output .= "No active session found for Table 1!\n";
}

$output .= "\n";

// 3. Check what customer page query would return
$output .= "3. CUSTOMER PAGE QUERY TEST:\n";
$customer_query = "SELECT * FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1";
$customer_result = $conn->query($customer_query);

if ($customer_result && $customer_result->num_rows > 0) {
    $customer_session = $customer_result->fetch_assoc();
    $output .= "Customer page WOULD find session:\n";
    $output .= "- Session ID: {$customer_session['session_id']}\n";
    $output .= "- Confirmed: " . ($customer_session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
} else {
    $output .= "Customer page would NOT find any session!\n";
}

$output .= "\n";

// 4. Check counter dashboard queries
$output .= "4. COUNTER DASHBOARD QUERIES:\n";

// Unconfirmed sessions query
$unconfirmed_sql = "SELECT COUNT(*) as count FROM qr_sessions WHERE status = 'active' AND confirmed_by_counter = FALSE";
$unconfirmed_result = $conn->query($unconfirmed_sql);
$unconfirmed_count = $unconfirmed_result->fetch_assoc()['count'];
$output .= "Unconfirmed sessions: {$unconfirmed_count}\n";

// Confirmed sessions query
$confirmed_sql = "SELECT COUNT(*) as count FROM qr_sessions WHERE status = 'active' AND confirmed_by_counter = TRUE";
$confirmed_result = $conn->query($confirmed_sql);
$confirmed_count = $confirmed_result->fetch_assoc()['count'];
$output .= "Confirmed sessions: {$confirmed_count}\n";

$output .= "\n";

// 5. Check notifications
$output .= "5. QR SESSION NOTIFICATIONS:\n";
$notifications_sql = "SELECT notification_id, session_id, notification_type, status, message FROM qr_session_notifications ORDER BY created_at DESC LIMIT 3";
$notifications_result = $conn->query($notifications_sql);

if ($notifications_result && $notifications_result->num_rows > 0) {
    while ($notification = $notifications_result->fetch_assoc()) {
        $output .= "Notification {$notification['notification_id']}: Session {$notification['session_id']}, Type: {$notification['notification_type']}, Status: {$notification['status']}\n";
    }
} else {
    $output .= "No notifications found!\n";
}

// Write to file
file_put_contents('session_analysis_results.txt', $output);

echo "Analysis complete! Results saved to session_analysis_results.txt\n";

$conn->close();
?>






