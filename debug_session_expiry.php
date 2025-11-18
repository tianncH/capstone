<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING SESSION EXPIRY LOGIC ===\n\n";

// Check current session details
echo "1. CURRENT SESSION DETAILS:\n";
$result = $conn->query("SELECT * FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
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
    
} else {
    echo "❌ No active session found for Table 1\n";
}

echo "\n";

// Check what the secure_qr_api.php is doing for session validation
echo "2. SESSION VALIDATION LOGIC:\n";
echo "The secure_qr_api.php should be checking:\n";
echo "- Session exists and is active\n";
echo "- Session token matches\n";
echo "- Device fingerprint matches\n";
echo "- Session is not expired\n";

echo "\n";

// Check if there are any orders for this session
echo "3. ORDERS FOR THIS SESSION:\n";
if (isset($session)) {
    $result = $conn->query("SELECT * FROM qr_orders WHERE session_id = {$session['session_id']}");
    if ($result) {
        echo "QR Orders count: {$result->num_rows}\n";
        while ($row = $result->fetch_assoc()) {
            echo "- Order ID: {$row['order_id']}, Status: {$row['status']}, Created: {$row['created_at']}\n";
        }
    }
    
    // Check if there's a corresponding order in the orders table
    $result = $conn->query("SELECT * FROM orders WHERE table_id = {$session['table_id']} ORDER BY created_at DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();
        echo "Regular Order: {$order['queue_number']}, Status ID: {$order['status_id']}, Created: {$order['created_at']}\n";
    } else {
        echo "❌ No regular order found for this table\n";
    }
}

$conn->close();
?>





