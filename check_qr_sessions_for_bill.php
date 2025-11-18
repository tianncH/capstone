<?php
require_once 'admin/includes/db_connection.php';

echo "=== CHECKING QR SESSIONS FOR BILL REQUEST ===\n\n";

// Check all QR sessions
echo "1. ALL QR SESSIONS:\n";
$result = $conn->query("SELECT * FROM qr_sessions ORDER BY created_at DESC");
if ($result) {
    echo "Total QR sessions: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Session ID: {$row['session_id']}\n";
        echo "  Table ID: {$row['table_id']}\n";
        echo "  Status: {$row['status']}\n";
        echo "  Created: {$row['created_at']}\n";
        echo "  Expires: {$row['expires_at']}\n";
        echo "  Confirmed: " . ($row['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
        echo "\n";
    }
} else {
    echo "❌ Error querying QR sessions: " . $conn->error . "\n";
}

echo "\n";

// Check QR orders for locked sessions
echo "2. QR ORDERS FOR LOCKED SESSIONS:\n";
$result = $conn->query("SELECT qs.*, COUNT(qo.order_id) as order_count, SUM(qo.subtotal) as total_amount
                       FROM qr_sessions qs 
                       LEFT JOIN qr_orders qo ON qs.session_id = qo.session_id 
                       WHERE qs.status = 'locked' 
                       GROUP BY qs.session_id");
if ($result) {
    echo "Locked sessions with orders: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Session ID: {$row['session_id']}\n";
        echo "  Table ID: {$row['table_id']}\n";
        echo "  Orders: {$row['order_count']}\n";
        echo "  Total: ₱{$row['total_amount']}\n";
        echo "\n";
    }
} else {
    echo "❌ Error querying locked sessions: " . $conn->error . "\n";
}

echo "\n";

// Check all QR notifications
echo "3. ALL QR NOTIFICATIONS:\n";
$result = $conn->query("SELECT * FROM qr_session_notifications ORDER BY created_at DESC");
if ($result) {
    echo "Total QR notifications: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Notification ID: {$row['notification_id']}\n";
        echo "  Session ID: {$row['session_id']}\n";
        echo "  Type: {$row['notification_type']}\n";
        echo "  Status: {$row['status']}\n";
        echo "  Message: {$row['message']}\n";
        echo "  Created: {$row['created_at']}\n";
        echo "\n";
    }
} else {
    echo "❌ Error querying QR notifications: " . $conn->error . "\n";
}

$conn->close();
?>





