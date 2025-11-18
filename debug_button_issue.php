<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING BUTTON ISSUE ===\n\n";

// Check current session status
echo "1. CURRENT SESSION STATUS:\n";
$result = $conn->query("SELECT session_id, table_id, status, confirmed_by_counter, confirmed_at, created_at, expires_at FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session ID: {$session['session_id']}\n";
    echo "Table ID: {$session['table_id']}\n";
    echo "Status: {$session['status']}\n";
    echo "Confirmed by Counter: " . ($session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
    echo "Confirmed At: {$session['confirmed_at']}\n";
    echo "Created At: {$session['created_at']}\n";
    echo "Expires At: {$session['expires_at']}\n";
    
    // Check if session is expired
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $expires = new DateTime($session['expires_at'], new DateTimeZone('Asia/Manila'));
    $is_expired = $now > $expires;
    echo "Is Expired: " . ($is_expired ? 'YES' : 'NO') . "\n";
    
} else {
    echo "❌ No active session found for Table 1\n";
}

echo "\n";

// Check what the JavaScript variables should be
echo "2. JAVASCRIPT VARIABLES:\n";
if (isset($session)) {
    echo "sessionToken: {$session['session_token']}\n";
    echo "tableNumber: {$session['table_id']}\n";
    echo "isConfirmed: " . ($session['confirmed_by_counter'] ? 'true' : 'false') . "\n";
}

echo "\n";

// Check if there are any existing orders
echo "3. EXISTING ORDERS:\n";
$result = $conn->query("SELECT * FROM qr_orders WHERE session_id = {$session['session_id']}");
if ($result) {
    echo "Orders count: {$result->num_rows}\n";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "- Order ID: {$row['order_id']}, Item: {$row['menu_item_id']}, Qty: {$row['quantity']}, Status: {$row['status']}\n";
        }
    }
} else {
    echo "❌ Error querying orders: " . $conn->error . "\n";
}

echo "\n";

// Check if session needs to be confirmed
echo "4. SESSION CONFIRMATION CHECK:\n";
if (isset($session)) {
    if (!$session['confirmed_by_counter']) {
        echo "❌ Session is NOT confirmed - buttons should be disabled\n";
        echo "🔧 Need to confirm session in counter dashboard\n";
    } else {
        echo "✅ Session is confirmed - buttons should work\n";
    }
}

$conn->close();
?>





