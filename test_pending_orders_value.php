<?php
require_once 'admin/includes/db_connection.php';

echo "=== TESTING PENDING ORDERS VALUE ===\n\n";

// Get current session
$result = $conn->query("SELECT * FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $current_session = $result->fetch_assoc();
    
    // Get session orders (same logic as secure_qr_menu.php)
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
    
    // Calculate pending orders (same logic as secure_qr_menu.php)
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
    
    echo "Session ID: {$current_session['session_id']}\n";
    echo "Total Items: {$total_items}\n";
    echo "Total Amount: {$total_amount}\n";
    echo "Pending Orders: {$pending_orders}\n";
    
    echo "\n=== BUTTON TEXT SIMULATION ===\n";
    echo "Button should show: 'Confirm Orders ({$pending_orders})'\n";
    
    if ($pending_orders == 0) {
        echo "Button should be: DISABLED\n";
    } else {
        echo "Button should be: ENABLED\n";
    }
    
    echo "\n=== HTML OUTPUT SIMULATION ===\n";
    echo "<button class=\"btn btn-confirm\" onclick=\"confirmOrders()\" " . ($pending_orders == 0 ? 'disabled' : '') . ">\n";
    echo "    <i class=\"bi bi-check-circle\"></i> Confirm Orders ({$pending_orders})\n";
    echo "</button>\n";
    
} else {
    echo "❌ No active session found\n";
}

$conn->close();
?>





