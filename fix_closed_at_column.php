<?php
require_once 'admin/includes/db_connection.php';

echo "=== FIXING MISSING CLOSED_AT COLUMN ===\n\n";

// Add closed_at column to qr_sessions table
$sql = "ALTER TABLE qr_sessions ADD COLUMN closed_at TIMESTAMP NULL AFTER confirmed_by";

if ($conn->query($sql)) {
    echo "✅ Successfully added closed_at column to qr_sessions table\n";
} else {
    echo "❌ Error adding closed_at column: " . $conn->error . "\n";
}

// Verify the column was added
$result = $conn->query("SHOW COLUMNS FROM qr_sessions LIKE 'closed_at'");
if ($result && $result->num_rows > 0) {
    echo "✅ closed_at column now exists\n";
} else {
    echo "❌ closed_at column still missing\n";
}

$conn->close();
?>






