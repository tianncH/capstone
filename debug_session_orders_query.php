<?php
require_once 'admin/includes/db_connection.php';

echo "=== DEBUGGING SESSION ORDERS QUERY ===\n\n";

// Get current session
$result = $conn->query("SELECT * FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $current_session = $result->fetch_assoc();
    echo "Current Session ID: {$current_session['session_id']}\n";
    
    // Test the exact query from secure_qr_menu.php
    echo "\n1. TESTING SESSION ORDERS QUERY:\n";
    $orders_sql = "SELECT qo.*, mi.name as item_name, mi.description, mi.image_url 
                   FROM qr_orders qo 
                   JOIN menu_items mi ON qo.menu_item_id = mi.item_id 
                   WHERE qo.session_id = ? 
                   ORDER BY qo.created_at DESC";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param('i', $current_session['session_id']);
    $orders_stmt->execute();
    $session_orders = $orders_stmt->get_result();
    $orders_stmt->close();
    
    echo "Query executed successfully\n";
    echo "Number of rows returned: {$session_orders->num_rows}\n";
    
    if ($session_orders->num_rows > 0) {
        echo "\n2. ORDERS RETURNED:\n";
        while ($order = $session_orders->fetch_assoc()) {
            echo "- Order ID: {$order['order_id']}\n";
            echo "  Item: {$order['item_name']}\n";
            echo "  Quantity: {$order['quantity']}\n";
            echo "  Status: {$order['status']}\n";
            echo "  Subtotal: {$order['subtotal']}\n";
            echo "\n";
        }
        
        // Reset pointer and test the counting logic
        $session_orders->data_seek(0);
        
        echo "3. TESTING COUNTING LOGIC:\n";
        $total_items = 0;
        $total_amount = 0;
        $pending_orders = 0;
        
        while ($order = $session_orders->fetch_assoc()) {
            $total_items += $order['quantity'];
            $total_amount += $order['subtotal'];
            if ($order['status'] == 'pending') {
                $pending_orders++;
            }
        }
        
        echo "Total Items: {$total_items}\n";
        echo "Total Amount: {$total_amount}\n";
        echo "Pending Orders: {$pending_orders}\n";
        
        if ($pending_orders > 0) {
            echo "✅ Should show: 'Confirm Orders ({$pending_orders})'\n";
        } else {
            echo "❌ Shows: 'Confirm Orders (0)' - This is the bug!\n";
        }
    } else {
        echo "❌ No orders returned by query\n";
    }
} else {
    echo "❌ No active session found\n";
}

$conn->close();
?>





