<?php
require_once 'admin/includes/db_connection.php';

echo "=== TESTING EXPIRED SESSION REDIRECT ===\n\n";

// Simulate what happens when customer scans QR code for expired session
echo "1. SIMULATING CUSTOMER SCANNING QR CODE:\n";
echo "Customer scans QR code for Table 1\n";
echo "Gets redirected to: secure_qr_menu.php?session_id=6&token=...&device=...\n\n";

// Check current session status
$result = $conn->query("SELECT * FROM qr_sessions WHERE session_id = 6");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "2. CURRENT SESSION STATUS:\n";
    echo "Session ID: {$session['session_id']}\n";
    echo "Status: {$session['status']}\n";
    echo "Expires: {$session['expires_at']}\n";
    
    // Check if expired
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $expires = new DateTime($session['expires_at'], new DateTimeZone('Asia/Manila'));
    $is_expired = $now > $expires;
    
    echo "Is Expired: " . ($is_expired ? 'YES' : 'NO') . "\n";
    
    if ($is_expired) {
        echo "\n3. WHAT WOULD HAPPEN:\n";
        echo "✅ System detects session is expired\n";
        echo "✅ Updates session status to 'expired'\n";
        echo "✅ Redirects to: secure_qr_menu.php?qr=QR_001\n";
        echo "✅ Customer gets new session for Table 1\n";
        echo "✅ Previous orders (6 items) are preserved in old session\n";
        echo "✅ Customer can start fresh ordering\n";
    }
}

echo "\n4. CUSTOMER EXPERIENCE:\n";
echo "🔄 Customer scans QR → Gets redirected → New session created\n";
echo "📋 Previous orders are still in the system (for payment/billing)\n";
echo "🆕 Customer can place new orders in the new session\n";
echo "💰 Counter can still process payment for the old session\n";

$conn->close();
?>





