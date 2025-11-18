<?php
require_once 'admin/includes/db_connection.php';

echo "=== TESTING COMPLETE SYSTEM FLOW ===\n\n";

// Test 1: Check current session status
echo "1. CURRENT SESSION STATUS:\n";
$result = $conn->query("SELECT session_id, table_id, status, confirmed_by_counter, created_at, expires_at FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session ID: {$session['session_id']}\n";
    echo "Status: {$session['status']}\n";
    echo "Confirmed: " . ($session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
    echo "Created: {$session['created_at']}\n";
    echo "Expires: {$session['expires_at']}\n";
    
    // Check if session is expired
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $expires = new DateTime($session['expires_at'], new DateTimeZone('UTC'));
    $expires->setTimezone(new DateTimeZone('Asia/Manila'));
    $is_expired = $now > $expires;
    echo "Is Expired: " . ($is_expired ? 'YES' : 'NO') . "\n";
} else {
    echo "❌ No active session found for Table 1\n";
}

echo "\n";

// Test 2: Check counter dashboard queries
echo "2. COUNTER DASHBOARD QUERIES:\n";

// Unconfirmed sessions
$result = $conn->query("SELECT qs.*, t.table_number, t.qr_code FROM qr_sessions qs JOIN tables t ON qs.table_id = t.table_id WHERE qs.status = 'active' AND qs.confirmed_by_counter = FALSE ORDER BY qs.created_at DESC");
if ($result) {
    echo "Unconfirmed sessions: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Table {$row['table_number']}: Session {$row['session_id']}\n";
    }
} else {
    echo "❌ Error querying unconfirmed sessions: " . $conn->error . "\n";
}

// Confirmed sessions
$result = $conn->query("SELECT qs.*, t.table_number, t.qr_code FROM qr_sessions qs JOIN tables t ON qs.table_id = t.table_id WHERE qs.status = 'active' AND qs.confirmed_by_counter = TRUE ORDER BY qs.created_at DESC");
if ($result) {
    echo "Confirmed sessions: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Table {$row['table_number']}: Session {$row['session_id']}\n";
    }
} else {
    echo "❌ Error querying confirmed sessions: " . $conn->error . "\n";
}

echo "\n";

// Test 3: Check customer page queries
echo "3. CUSTOMER PAGE QUERIES:\n";
$result = $conn->query("SELECT * FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "✅ Customer page can find session: {$session['session_id']}\n";
    echo "Confirmed: " . ($session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
} else {
    echo "❌ Customer page cannot find session\n";
}

echo "\n";

// Test 4: Check QR order details query
echo "4. QR ORDER DETAILS QUERY:\n";
$session_id = 6; // Use the current session
$result = $conn->query("SELECT qs.*, t.table_number, t.qr_code FROM qr_sessions qs LEFT JOIN tables t ON qs.table_id = t.table_id WHERE qs.session_id = {$session_id}");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "✅ QR order details can find session: {$session['session_id']}\n";
    echo "Table: {$session['table_number']}\n";
    echo "QR Code: {$session['qr_code']}\n";
} else {
    echo "❌ QR order details cannot find session\n";
}

// Check orders for this session
$result = $conn->query("SELECT qo.*, mi.name as item_name FROM qr_orders qo LEFT JOIN menu_items mi ON qo.menu_item_id = mi.item_id WHERE qo.session_id = {$session_id} ORDER BY qo.order_id");
if ($result) {
    echo "Orders for session: {$result->num_rows}\n";
} else {
    echo "❌ Error querying orders: " . $conn->error . "\n";
}

echo "\n";

// Test 5: Check menu items query
echo "5. MENU ITEMS QUERY:\n";
$result = $conn->query("SELECT mi.*, c.name as category_name FROM menu_items mi LEFT JOIN categories c ON mi.category_id = c.category_id WHERE mi.is_available = 1 ORDER BY c.display_order, mi.display_order");
if ($result) {
    echo "✅ Menu items query successful: {$result->num_rows} items\n";
} else {
    echo "❌ Error querying menu items: " . $conn->error . "\n";
}

echo "\n";

// Test 6: Check timezone handling
echo "6. TIMEZONE HANDLING:\n";
$now = new DateTime('now', new DateTimeZone('Asia/Manila'));
echo "Current Philippines time: " . $now->format('Y-m-d H:i:s') . "\n";
echo "Current Philippines time (12h): " . $now->format('g:i A') . "\n";

$conn->close();
?>






