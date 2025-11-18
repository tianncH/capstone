<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING PAYMENT MODAL BUGS ===\n\n";

// Check the QR bill notification
echo "1. QR BILL NOTIFICATION:\n";
$result = $conn->query("SELECT * FROM qr_session_notifications WHERE notification_type = 'bill_request' AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $notification = $result->fetch_assoc();
    echo "Notification ID: {$notification['notification_id']}\n";
    echo "Message: {$notification['message']}\n";
    echo "Data: {$notification['data']}\n";
    
    // Extract total amount from message
    preg_match('/Total: ([\d,]+\.?\d*)/', $notification['message'], $matches);
    $total_amount = isset($matches[1]) ? str_replace(',', '', $matches[1]) : '0.00';
    echo "Extracted Total: {$total_amount}\n";
    
    // Parse JSON data
    $data = json_decode($notification['data'], true);
    if ($data) {
        echo "JSON Data Total: " . ($data['total_amount'] ?? 'Not found') . "\n";
    }
} else {
    echo "❌ No QR bill notification found\n";
}

echo "\n";

// Check cash_float_transactions table structure
echo "2. CASH_FLOAT_TRANSACTIONS TABLE STRUCTURE:\n";
$result = $conn->query("SHOW COLUMNS FROM cash_float_transactions");
if ($result) {
    echo "Columns in cash_float_transactions:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
} else {
    echo "❌ Error querying table structure: " . $conn->error . "\n";
}

echo "\n";

// Check if cash_float_session_id column exists
echo "3. CHECKING FOR CASH_FLOAT_SESSION_ID COLUMN:\n";
$result = $conn->query("SHOW COLUMNS FROM cash_float_transactions LIKE 'cash_float_session_id'");
if ($result && $result->num_rows > 0) {
    echo "✅ cash_float_session_id column exists\n";
} else {
    echo "❌ cash_float_session_id column does NOT exist\n";
}

$conn->close();
?>





