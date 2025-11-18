<?php
require_once 'admin/includes/db_connection.php';

echo "=== CREATING FRESH SESSION FOR TABLE 1 ===\n\n";

// Close all existing sessions for Table 1
$close_sql = "UPDATE qr_sessions SET status = 'expired', closed_at = NOW() WHERE table_id = 1 AND status = 'active'";
if ($conn->query($close_sql)) {
    echo "✅ Closed existing sessions for Table 1\n";
} else {
    echo "❌ Error closing sessions: " . $conn->error . "\n";
}

// Create new session
$session_token = bin2hex(random_bytes(16));
$device_fingerprint = md5('test_device_' . time());
$now = new DateTime('now', new DateTimeZone('Asia/Manila'));
$expires = clone $now;
$expires->add(new DateInterval('PT2H')); // Add 2 hours

$created_at = $now->format('Y-m-d H:i:s');
$expires_at = $expires->format('Y-m-d H:i:s');

$create_sql = "INSERT INTO qr_sessions (table_id, session_token, device_fingerprint, status, expires_at, created_at) VALUES (1, ?, ?, 'active', ?, ?)";
$stmt = $conn->prepare($create_sql);
$stmt->bind_param('ssss', $session_token, $device_fingerprint, $expires_at, $created_at);

if ($stmt->execute()) {
    $session_id = $conn->insert_id;
    echo "✅ Created new session ID: {$session_id}\n";
    echo "Session Token: {$session_token}\n";
    echo "Created At: {$created_at}\n";
    echo "Expires At: {$expires_at}\n";
    
    // Create counter notification
    $notif_sql = "INSERT INTO qr_session_notifications (session_id, notification_type, message, status, created_at) VALUES (?, 'new_session', 'New QR session created for Table 1', 'unread', NOW())";
    $notif_stmt = $conn->prepare($notif_sql);
    $notif_stmt->bind_param('i', $session_id);
    $notif_stmt->execute();
    $notif_stmt->close();
    
    echo "✅ Created counter notification\n";
} else {
    echo "❌ Error creating session: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>






