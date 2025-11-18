<?php
require_once 'admin/includes/db_connection.php';

echo "=== TESTING DEVICE FINGERPRINT ===\n\n";

// Simulate the same logic as in secure_qr_menu.php
$table_id = 1;
$session_sql = "SELECT * FROM qr_sessions WHERE table_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1";
$session_stmt = $conn->prepare($session_sql);
$session_stmt->bind_param('i', $table_id);
$session_stmt->execute();
$existing_session = $session_stmt->get_result()->fetch_assoc();
$session_stmt->close();

if ($existing_session) {
    echo "✅ Session found:\n";
    echo "Session ID: {$existing_session['session_id']}\n";
    echo "Session Token: {$existing_session['session_token']}\n";
    echo "Device Fingerprint: {$existing_session['device_fingerprint']}\n";
    echo "Confirmed by Counter: " . ($existing_session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
    
    // Test JavaScript variable generation
    echo "\nJavaScript variables that would be generated:\n";
    echo "const sessionId = {$existing_session['session_id']};\n";
    echo "const sessionToken = '{$existing_session['session_token']}';\n";
    echo "const deviceFingerprint = '{$existing_session['device_fingerprint']}';\n";
    echo "const isConfirmed = " . ($existing_session['confirmed_by_counter'] ? 'true' : 'false') . ";\n";
    
    // Test AJAX request body
    echo "\nAJAX request body that would be sent:\n";
    echo "action=add_order&session_id={$existing_session['session_id']}&session_token={$existing_session['session_token']}&device_fingerprint={$existing_session['device_fingerprint']}&item_id=2&quantity=1\n";
    
} else {
    echo "❌ No active session found\n";
}

$conn->close();
?>





