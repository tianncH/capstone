<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING PROCESS PAYMENT BUTTON ===\n\n";

// Check QR bill request notifications
echo "1. QR BILL REQUEST NOTIFICATIONS:\n";
$result = $conn->query("SELECT qsn.*, t.table_number 
                       FROM qr_session_notifications qsn 
                       JOIN qr_sessions qs ON qsn.session_id = qs.session_id 
                       JOIN tables t ON qs.table_id = t.table_id 
                       WHERE qsn.notification_type = 'bill_request' 
                       AND qsn.status = 'pending'");
if ($result) {
    echo "QR Bill notifications: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Notification ID: {$row['notification_id']}\n";
        echo "  Table: {$row['table_number']}\n";
        echo "  Message: {$row['message']}\n";
        echo "  Status: {$row['status']}\n";
        echo "  Created: {$row['created_at']}\n";
        echo "  Data: {$row['data']}\n";
        echo "\n";
    }
} else {
    echo "❌ Error querying QR bill notifications: " . $conn->error . "\n";
}

echo "\n";

// Check what the button should be doing
echo "2. BUTTON FUNCTIONALITY:\n";
echo "The 'Process Payment' button should:\n";
echo "1. Have data-bs-toggle='modal' attribute\n";
echo "2. Have data-bs-target='#qrPaymentModal{notification_id}' attribute\n";
echo "3. Open a payment modal\n";
echo "4. Allow counter staff to process payment\n";

echo "\n";

// Check if there are any JavaScript errors or missing modals
echo "3. POTENTIAL ISSUES:\n";
echo "- Missing payment modal HTML\n";
echo "- JavaScript errors preventing modal from opening\n";
echo "- Bootstrap modal not loaded\n";
echo "- Incorrect modal ID targeting\n";

$conn->close();
?>





