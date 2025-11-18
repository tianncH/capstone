<?php
require_once 'admin/includes/db_connection.php';

echo "=== FIXING QR SESSION AND CREATING BILL REQUEST ===\n\n";

// Get the current session (regardless of status)
$result = $conn->query("SELECT * FROM qr_sessions WHERE table_id = 1 ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Current Session ID: {$session['session_id']}\n";
    echo "Current Status: '{$session['status']}'\n";
    
    // Check if session has orders
    $result = $conn->query("SELECT COUNT(*) as count, SUM(subtotal) as total FROM qr_orders WHERE session_id = {$session['session_id']}");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Orders: {$row['count']}\n";
        echo "Total: ₱{$row['total']}\n";
        
        if ($row['count'] > 0) {
            // Set session status to 'locked' for bill request
            $lock_sql = "UPDATE qr_sessions SET status = 'locked' WHERE session_id = ?";
            $stmt = $conn->prepare($lock_sql);
            $stmt->bind_param('i', $session['session_id']);
            if ($stmt->execute()) {
                echo "✅ Session status set to 'locked'\n";
            } else {
                echo "❌ Error updating session: " . $stmt->error . "\n";
            }
            $stmt->close();
            
            // Create fresh bill request notification
            $notification_sql = "INSERT INTO qr_session_notifications (session_id, notification_type, message, data, status, created_at) VALUES (?, 'bill_request', ?, ?, 'pending', NOW())";
            $stmt = $conn->prepare($notification_sql);
            $message = "Table 1 requesting bill - Total: ₱{$row['total']}";
            $data = json_encode(['table_id' => 1, 'total_amount' => $row['total']]);
            $stmt->bind_param('iss', $session['session_id'], $message, $data);
            
            if ($stmt->execute()) {
                $notification_id = $conn->insert_id;
                echo "✅ Fresh bill request notification created (ID: {$notification_id})\n";
                echo "Message: {$message}\n";
                echo "Data: {$data}\n";
            } else {
                echo "❌ Error creating notification: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "❌ No orders found for this session\n";
        }
    }
} else {
    echo "❌ No session found for Table 1\n";
}

echo "\n=== VERIFICATION ===\n";

// Verify the notification was created
$result = $conn->query("SELECT * FROM qr_session_notifications WHERE notification_type = 'bill_request' AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $notification = $result->fetch_assoc();
    echo "✅ Fresh notification found:\n";
    echo "  ID: {$notification['notification_id']}\n";
    echo "  Message: {$notification['message']}\n";
    echo "  Status: {$notification['status']}\n";
    
    // Test the regex extraction
    preg_match('/Total: ([\d,]+\.?\d*)/', $notification['message'], $matches);
    $total_amount = isset($matches[1]) ? str_replace(',', '', $matches[1]) : '0.00';
    echo "  Extracted Total: {$total_amount}\n";
} else {
    echo "❌ No pending bill request notification found\n";
}

$conn->close();
?>
