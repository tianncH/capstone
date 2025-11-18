<?php
require_once 'admin/includes/db_connection.php';

echo "=== TESTING AJAX ENDPOINT ===\n\n";

// Simulate the AJAX request that the buttons make
$action = 'add_order';
$session_id = 6;
$session_token = '9b9d12d5dd83d7c02f165859e425439f';
$device_fingerprint = 'test_fingerprint';
$item_id = 2; // Try to add a different item
$quantity = 1;

echo "Simulating AJAX request:\n";
echo "Action: {$action}\n";
echo "Session ID: {$session_id}\n";
echo "Session Token: {$session_token}\n";
echo "Device Fingerprint: {$device_fingerprint}\n";
echo "Item ID: {$item_id}\n";
echo "Quantity: {$quantity}\n\n";

// Check if session exists and is valid
$session_sql = "SELECT * FROM qr_sessions WHERE session_id = ? AND session_token = ? AND status = 'active'";
$session_stmt = $conn->prepare($session_sql);
$session_stmt->bind_param('is', $session_id, $session_token);
$session_stmt->execute();
$session = $session_stmt->get_result()->fetch_assoc();
$session_stmt->close();

if (!$session) {
    echo "❌ Session not found or invalid\n";
    exit;
}

echo "✅ Session found and valid\n";
echo "Confirmed by Counter: " . ($session['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";

if (!$session['confirmed_by_counter']) {
    echo "❌ Session not confirmed by counter\n";
    exit;
}

// Check if menu item exists
$item_sql = "SELECT * FROM menu_items WHERE item_id = ? AND is_available = 1";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param('i', $item_id);
$item_stmt->execute();
$item = $item_stmt->get_result()->fetch_assoc();
$item_stmt->close();

if (!$item) {
    echo "❌ Menu item not found or not available\n";
    exit;
}

echo "✅ Menu item found: {$item['name']}\n";
echo "Price: {$item['price']}\n";

// Check if order already exists for this item
$existing_order_sql = "SELECT * FROM qr_orders WHERE session_id = ? AND menu_item_id = ? AND status = 'pending'";
$existing_stmt = $conn->prepare($existing_order_sql);
$existing_stmt->bind_param('ii', $session_id, $item_id);
$existing_stmt->execute();
$existing_order = $existing_stmt->get_result()->fetch_assoc();
$existing_stmt->close();

if ($existing_order) {
    echo "⚠️ Order already exists for this item, updating quantity\n";
    $new_quantity = $existing_order['quantity'] + $quantity;
    $new_subtotal = $new_quantity * $existing_order['unit_price'];
    
    $update_sql = "UPDATE qr_orders SET quantity = ?, subtotal = ? WHERE order_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('idi', $new_quantity, $new_subtotal, $existing_order['order_id']);
    
    if ($update_stmt->execute()) {
        echo "✅ Order quantity updated successfully\n";
    } else {
        echo "❌ Failed to update order: " . $update_stmt->error . "\n";
    }
    $update_stmt->close();
} else {
    echo "Creating new order...\n";
    $unit_price = $item['price'];
    $subtotal = $quantity * $unit_price;
    
    $insert_sql = "INSERT INTO qr_orders (session_id, menu_item_id, quantity, unit_price, subtotal, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param('iiidd', $session_id, $item_id, $quantity, $unit_price, $subtotal);
    
    if ($insert_stmt->execute()) {
        $order_id = $conn->insert_id;
        echo "✅ New order created successfully: ID {$order_id}\n";
    } else {
        echo "❌ Failed to create order: " . $insert_stmt->error . "\n";
    }
    $insert_stmt->close();
}

$conn->close();
?>





