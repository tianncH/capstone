<?php
require_once 'admin/includes/db_connection.php';

echo "=== BUG TREASURE HUNT ANALYSIS ===\n\n";

// 1. Check qr_sessions table structure
echo "1. QR_SESSIONS TABLE STRUCTURE:\n";
$result = $conn->query("SHOW COLUMNS FROM qr_sessions");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n";

// 2. Check for duplicate Table 1 sessions
echo "2. DUPLICATE TABLE 1 SESSIONS:\n";
$result = $conn->query("SELECT session_id, table_id, session_token, device_fingerprint, created_at, confirmed_at, status, confirmed_by_counter FROM qr_sessions WHERE table_id = 1 ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Session {$row['session_id']}: Table {$row['table_id']}, Status: {$row['status']}, Confirmed: " . ($row['confirmed_by_counter'] ? 'YES' : 'NO') . ", Created: {$row['created_at']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n";

// 3. Check device fingerprints
echo "3. DEVICE FINGERPRINT ANALYSIS:\n";
$result = $conn->query("SELECT device_fingerprint, COUNT(*) as count FROM qr_sessions GROUP BY device_fingerprint ORDER BY count DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Device {$row['device_fingerprint']}: {$row['count']} sessions\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n";

// 4. Check line 189 issue (order_id)
echo "4. CHECKING COUNTER INDEX.PHP LINE 189:\n";
$file_content = file_get_contents('counter/index.php');
$lines = explode("\n", $file_content);
if (isset($lines[188])) { // Line 189 (0-indexed)
    echo "Line 189: " . trim($lines[188]) . "\n";
} else {
    echo "Line 189 not found\n";
}

echo "\n";

// 5. Check if closed_at column exists
echo "5. CHECKING FOR CLOSED_AT COLUMN:\n";
$result = $conn->query("SHOW COLUMNS FROM qr_sessions LIKE 'closed_at'");
if ($result && $result->num_rows > 0) {
    echo "closed_at column EXISTS\n";
} else {
    echo "closed_at column DOES NOT EXIST - THIS IS THE BUG!\n";
}

echo "\n";

// 6. Check all active sessions
echo "6. ALL ACTIVE SESSIONS:\n";
$result = $conn->query("SELECT session_id, table_id, status, confirmed_by_counter, created_at FROM qr_sessions WHERE status = 'active' ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Session {$row['session_id']}: Table {$row['table_id']}, Status: {$row['status']}, Confirmed: " . ($row['confirmed_by_counter'] ? 'YES' : 'NO') . ", Created: {$row['created_at']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
