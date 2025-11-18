<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING EXPIRED SESSION BEHAVIOR ===\n\n";

// Check the current expired session
echo "1. CURRENT EXPIRED SESSION (ID: 6):\n";
$result = $conn->query("SELECT * FROM qr_sessions WHERE session_id = 6");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session ID: {$session['session_id']}\n";
    echo "Table ID: {$session['table_id']}\n";
    echo "Status: {$session['status']}\n";
    echo "Created: {$session['created_at']}\n";
    echo "Expires: {$session['expires_at']}\n";
    echo "Confirmed: " . ($session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
    
    // Check if session has orders
    $result = $conn->query("SELECT COUNT(*) as count FROM qr_orders WHERE session_id = 6");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Orders in session: {$row['count']}\n";
    }
}

echo "\n";

// Check what happens when customer tries to access expired session
echo "2. WHAT HAPPENS WHEN CUSTOMER ACCESSES EXPIRED SESSION:\n";
echo "The customer would scan the QR code and be directed to:\n";
echo "secure_qr_menu.php?session_id=6&token=...&device=...\n\n";

echo "The secure_qr_menu.php would:\n";
echo "1. Check if session exists\n";
echo "2. Check if session is active/locked\n";
echo "3. Check if session is expired\n";
echo "4. If expired, what does it do?\n\n";

// Let's check the secure_qr_menu.php logic
echo "3. SECURE_QR_MENU.PHP EXPIRY HANDLING:\n";
echo "Looking for session expiry logic...\n";

$conn->close();
?>





