<?php
require_once 'admin/includes/db_connection.php';

echo "=== CHECKING CASH_FLOAT_TRANSACTIONS STRUCTURE ===\n\n";

// Check cash_float_transactions table structure
$result = $conn->query("SHOW COLUMNS FROM cash_float_transactions");
if ($result) {
    echo "CASH_FLOAT_TRANSACTIONS TABLE COLUMNS:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n";

// Check if there are any records
$result = $conn->query("SELECT COUNT(*) as count FROM cash_float_transactions");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total records: {$row['count']}\n";
}

echo "\n";

// Check sample data
$result = $conn->query("SELECT * FROM cash_float_transactions LIMIT 1");
if ($result && $result->num_rows > 0) {
    $transaction = $result->fetch_assoc();
    echo "SAMPLE TRANSACTION DATA:\n";
    foreach ($transaction as $key => $value) {
        echo "- {$key}: {$value}\n";
    }
} else {
    echo "No transactions found\n";
}

echo "\n";

// Check counter_users table structure
echo "COUNTER_USERS TABLE COLUMNS:\n";
$result = $conn->query("SHOW COLUMNS FROM counter_users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>





