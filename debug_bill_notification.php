<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING BILL NOTIFICATION ===\n\n";

// Check if bill request notification was created
echo "1. BILL REQUEST NOTIFICATIONS:\n";
$result = $conn->query("SELECT * FROM qr_session_notifications WHERE notification_type = 'bill_request' ORDER BY created_at DESC");
if ($result) {
    echo "Bill request notifications: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Notification ID: {$row['notification_id']}\n";
        echo "  Session ID: {$row['session_id']}\n";
        echo "  Message: {$row['message']}\n";
        echo "  Status: {$row['status']}\n";
        echo "  Created: {$row['created_at']}\n";
        echo "  Data: {$row['data']}\n";
        echo "\n";
    }
} else {
    echo "❌ Error querying notifications: " . $conn->error . "\n";
}

echo "\n";

// Check all notifications for the current session (ID: 6)
echo "2. ALL NOTIFICATIONS FOR SESSION 6:\n";
$result = $conn->query("SELECT * FROM qr_session_notifications WHERE session_id = 6 ORDER BY created_at DESC");
if ($result) {
    echo "Notifications for session 6: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Type: {$row['notification_type']}\n";
        echo "  Message: {$row['message']}\n";
        echo "  Status: {$row['status']}\n";
        echo "  Created: {$row['created_at']}\n";
        echo "\n";
    }
} else {
    echo "❌ Error querying session notifications: " . $conn->error . "\n";
}

echo "\n";

// Check if counter is seeing the notifications
echo "3. COUNTER NOTIFICATION QUERY:\n";
echo "The counter should be querying:\n";
echo "SELECT * FROM qr_session_notifications WHERE status = 'unread' AND notification_type = 'bill_request'\n";

$result = $conn->query("SELECT * FROM qr_session_notifications WHERE status = 'unread' AND notification_type = 'bill_request'");
if ($result) {
    echo "Unread bill request notifications: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Session ID: {$row['session_id']}\n";
        echo "  Message: {$row['message']}\n";
        echo "  Created: {$row['created_at']}\n";
    }
} else {
    echo "❌ Error querying unread notifications: " . $conn->error . "\n";
}

$conn->close();
?>





