<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING PRICE ANOMALY ===\n\n";

// Check Tangigue Sinigang/Tinola price
echo "1. TANGIGUE SINIGANG/TINOLA PRICE:\n";
$result = $conn->query("SELECT item_id, name, price FROM menu_items WHERE name LIKE '%Tangigue%' AND name LIKE '%Sinigang%'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Item ID: {$row['item_id']}\n";
        echo "Name: {$row['name']}\n";
        echo "Price: {$row['price']}\n\n";
    }
} else {
    echo "❌ Tangigue Sinigang/Tinola not found\n";
}

echo "\n";

// Check all Tangigue items
echo "2. ALL TANGIGUE ITEMS:\n";
$result = $conn->query("SELECT item_id, name, price FROM menu_items WHERE name LIKE '%Tangigue%' ORDER BY price");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['name']}: P{$row['price']}\n";
    }
} else {
    echo "❌ No Tangigue items found\n";
}

echo "\n";

// Check the order that has the high price
echo "3. ORDER WITH HIGH PRICE:\n";
$result = $conn->query("SELECT qo.*, mi.name as item_name FROM qr_orders qo LEFT JOIN menu_items mi ON qo.menu_item_id = mi.item_id WHERE qo.session_id = 6 AND qo.unit_price > 100");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Order ID: {$row['order_id']}\n";
        echo "Item: {$row['item_name']}\n";
        echo "Menu Item ID: {$row['menu_item_id']}\n";
        echo "Unit Price: {$row['unit_price']}\n";
        echo "Quantity: {$row['quantity']}\n";
        echo "Subtotal: {$row['subtotal']}\n";
    }
} else {
    echo "❌ No orders with high price found\n";
}

echo "\n";

// Check if there's a data inconsistency
echo "4. DATA CONSISTENCY CHECK:\n";
$result = $conn->query("SELECT qo.menu_item_id, qo.unit_price, mi.price as menu_price, mi.name FROM qr_orders qo LEFT JOIN menu_items mi ON qo.menu_item_id = mi.item_id WHERE qo.session_id = 6 AND qo.unit_price != mi.price");
if ($result && $result->num_rows > 0) {
    echo "❌ Found price inconsistencies:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['name']}: Order price {$row['unit_price']} vs Menu price {$row['menu_price']}\n";
    }
} else {
    echo "✅ All order prices match menu prices\n";
}

$conn->close();
?>





