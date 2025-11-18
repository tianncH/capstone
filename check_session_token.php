<?php
require_once 'admin/includes/db_connection.php';

echo "=== CHECKING SESSION TOKEN ===\n\n";

// Check session token in database
echo "1. SESSION TOKEN IN DATABASE:\n";
$result = $conn->query("SELECT session_id, session_token, table_id, status FROM qr_sessions WHERE session_id = 6");
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "Session ID: {$session['session_id']}\n";
    echo "Session Token: " . ($session['session_token'] ?: 'NULL/EMPTY') . "\n";
    echo "Table ID: {$session['table_id']}\n";
    echo "Status: {$session['status']}\n";
} else {
    echo "❌ Session not found\n";
}

echo "\n";

// Check if session_token column exists
echo "2. CHECKING SESSION_TOKEN COLUMN:\n";
$result = $conn->query("SHOW COLUMNS FROM qr_sessions LIKE 'session_token'");
if ($result && $result->num_rows > 0) {
    $column = $result->fetch_assoc();
    echo "✅ session_token column exists: {$column['Type']}\n";
} else {
    echo "❌ session_token column does not exist\n";
}

echo "\n";

// Check all columns in qr_sessions
echo "3. ALL COLUMNS IN QR_SESSIONS:\n";
$result = $conn->query("SHOW COLUMNS FROM qr_sessions");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
}

$conn->close();
?>





