<?php
require_once 'admin/includes/db_connection.php';

echo "=== CHECKING TABLE_SESSIONS STRUCTURE ===\n\n";

$result = $conn->query("SHOW COLUMNS FROM table_sessions");
if ($result) {
    echo "TABLE_SESSIONS COLUMNS:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>






