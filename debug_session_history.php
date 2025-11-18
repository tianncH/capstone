<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING SESSION HISTORY ===\n\n";

// Check all sessions for Table 1 (active and inactive)
echo "1. ALL SESSIONS FOR TABLE 1:\n";
$result = $conn->query("SELECT * FROM qr_sessions WHERE table_id = 1 ORDER BY created_at DESC");
if ($result) {
    echo "Total sessions: {$result->num_rows}\n";
    while ($row = $result->fetch_assoc()) {
        echo "- Session ID: {$row['session_id']}\n";
        echo "  Status: {$row['status']}\n";
        echo "  Created: {$row['created_at']}\n";
        echo "  Expires: {$row['expires_at']}\n";
        echo "  Confirmed: " . ($row['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
        if ($row['closed_at']) {
            echo "  Closed: {$row['closed_at']}\n";
        }
        echo "\n";
    }
} else {
    echo "❌ Error querying sessions: " . $conn->error . "\n";
}

echo "\n";

// Check if sessions were closed or expired
echo "2. SESSION STATUS BREAKDOWN:\n";
$result = $conn->query("SELECT status, COUNT(*) as count FROM qr_sessions WHERE table_id = 1 GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['status']}: {$row['count']} sessions\n";
    }
} else {
    echo "❌ Error querying session status: " . $conn->error . "\n";
}

echo "\n";

// Check the most recent session
echo "3. MOST RECENT SESSION:\n";
$result = $conn->query("SELECT * FROM qr_sessions WHERE table_id = 1 ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session ID: {$session['session_id']}\n";
    echo "Status: {$session['status']}\n";
    echo "Created: {$session['created_at']}\n";
    echo "Expires: {$session['expires_at']}\n";
    echo "Closed: " . ($session['closed_at'] ?: 'Not closed') . "\n";
    
    // Check if this session has orders
    $result = $conn->query("SELECT COUNT(*) as count FROM qr_orders WHERE session_id = {$session['session_id']}");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Orders in this session: {$row['count']}\n";
    }
    
    // Check if there's a corresponding order in orders table
    $result = $conn->query("SELECT * FROM orders WHERE table_id = 1 ORDER BY created_at DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();
        echo "Regular Order: {$order['queue_number']}, Status ID: {$order['status_id']}\n";
    }
} else {
    echo "❌ No sessions found for Table 1\n";
}

$conn->close();
?>





