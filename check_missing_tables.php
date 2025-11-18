<?php
require_once 'admin/includes/db_connection.php';

echo "=== CHECKING MISSING TABLES ===\n\n";

// Check what tables exist in the database
echo "1. EXISTING TABLES IN DATABASE:\n";
$result = $conn->query("SHOW TABLES");
if ($result) {
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    sort($tables);
    foreach ($tables as $table) {
        echo "- {$table}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n";

// Check if cash_transactions table exists
echo "2. CHECKING FOR CASH_TRANSACTIONS TABLE:\n";
$result = $conn->query("SHOW TABLES LIKE 'cash_transactions'");
if ($result && $result->num_rows > 0) {
    echo "✅ cash_transactions table exists\n";
} else {
    echo "❌ cash_transactions table does NOT exist\n";
}

echo "\n";

// Check if counter_users table exists
echo "3. CHECKING FOR COUNTER_USERS TABLE:\n";
$result = $conn->query("SHOW TABLES LIKE 'counter_users'");
if ($result && $result->num_rows > 0) {
    echo "✅ counter_users table exists\n";
} else {
    echo "❌ counter_users table does NOT exist\n";
}

echo "\n";

// Check what payment-related tables exist
echo "4. PAYMENT-RELATED TABLES:\n";
$payment_tables = ['cash_float_transactions', 'cash_float_sessions', 'payments', 'transactions'];
foreach ($payment_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
    if ($result && $result->num_rows > 0) {
        echo "✅ {$table} table exists\n";
    } else {
        echo "❌ {$table} table does NOT exist\n";
    }
}

$conn->close();
?>





