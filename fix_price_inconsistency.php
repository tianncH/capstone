<?php
require_once 'admin/includes/db_connection.php';

echo "=== FIXING PRICE INCONSISTENCY ===\n\n";

// Fix the price for Tangigue Sinigang/Tinola order
echo "1. FIXING TANGIGUE SINIGANG/TINOLA PRICE:\n";

// Get the correct price from menu
$result = $conn->query("SELECT price FROM menu_items WHERE item_id = 1");
if ($result && $result->num_rows > 0) {
    $menu_item = $result->fetch_assoc();
    $correct_price = $menu_item['price'];
    echo "Correct menu price: {$correct_price}\n";
    
    // Update the order with correct price
    $update_sql = "UPDATE qr_orders SET unit_price = ?, subtotal = quantity * ? WHERE order_id = 1";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('dd', $correct_price, $correct_price);
    
    if ($update_stmt->execute()) {
        echo "✅ Successfully updated order price\n";
        
        // Check the updated order
        $result = $conn->query("SELECT * FROM qr_orders WHERE order_id = 1");
        if ($result && $result->num_rows > 0) {
            $order = $result->fetch_assoc();
            echo "Updated order:\n";
            echo "- Quantity: {$order['quantity']}\n";
            echo "- Unit Price: {$order['unit_price']}\n";
            echo "- Subtotal: {$order['subtotal']}\n";
        }
    } else {
        echo "❌ Failed to update order price: " . $update_stmt->error . "\n";
    }
    $update_stmt->close();
} else {
    echo "❌ Menu item not found\n";
}

echo "\n";

// Check total amount after fix
echo "2. CHECKING TOTAL AMOUNT AFTER FIX:\n";
$result = $conn->query("SELECT SUM(subtotal) as total FROM qr_orders WHERE session_id = 6");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "New total amount: {$row['total']}\n";
} else {
    echo "❌ Error calculating total\n";
}

$conn->close();
?>





