<?php
require_once 'admin/includes/db_connection.php';

echo "=== CREATING FRESH QR BILL REQUEST ===\n\n";

// Get the current active session
$result = $conn->query("SELECT * FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Current Session ID: {$session['session_id']}\n";
    echo "Status: {$session['status']}\n";
    
    // Check if session has orders
    $result = $conn->query("SELECT COUNT(*) as count, SUM(subtotal) as total FROM qr_orders WHERE session_id = {$session['session_id']}");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Orders: {$row['count']}\n";
        echo "Total: ₱{$row['total']}\n";
        
        if ($row['count'] > 0) {
            // Lock the session
            $lock_sql = "UPDATE qr_sessions SET status = 'locked' WHERE session_id = ?";
            $stmt = $conn->prepare($lock_sql);
            $stmt->bind_param('i', $session['session_id']);
            if ($stmt->execute()) {
                echo "✅ Session locked successfully\n";
            } else {
                echo "❌ Error locking session: " . $stmt->error . "\n";
            }
            $stmt->close();
            
            // Create bill request notification
            $notification_sql = "INSERT INTO qr_session_notifications (session_id, notification_type, message, data, status, created_at) VALUES (?, 'bill_request', ?, ?, 'pending', NOW())";
            $stmt = $conn->prepare($notification_sql);
            $message = "Table 1 requesting bill - Total: ₱{$row['total']}";
            $data = json_encode(['table_id' => 1, 'total_amount' => $row['total']]);
            $stmt->bind_param('isss', $session['session_id'], $message, $data);
            
            if ($stmt->execute()) {
                $notification_id = $conn->insert_id;
                echo "✅ Bill request notification created (ID: {$notification_id})\n";
                echo "Message: {$message}\n";
            } else {
                echo "❌ Error creating notification: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "❌ No orders found for this session\n";
        }
    }
} else {
    echo "❌ No active session found for Table 1\n";
}

$conn->close();
?>





