<?php
require_once 'admin/includes/db_connection.php';

echo "=== CHECKING ORDERS TABLE STRUCTURE ===\n\n";

// Check orders table structure
$result = $conn->query("SHOW COLUMNS FROM orders");
if ($result) {
    echo "ORDERS TABLE COLUMNS:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n";

// Check if there are any orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total orders: {$row['count']}\n";
}

echo "\n";

// Check sample order data
$result = $conn->query("SELECT * FROM orders LIMIT 1");
if ($result && $result->num_rows > 0) {
    $order = $result->fetch_assoc();
    echo "SAMPLE ORDER DATA:\n";
    foreach ($order as $key => $value) {
        echo "- {$key}: {$value}\n";
    }
} else {
    echo "No orders found\n";
}

$conn->close();
?>





