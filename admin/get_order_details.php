<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

header('Content-Type: application/json');

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    // Get order details including discount fields
    $order_sql = "SELECT o.*, t.table_name, t.table_number, t.location,
                         o.discount_type, o.discount_percentage, o.discount_amount, o.original_amount
                  FROM orders o 
                  JOIN tables t ON o.table_id = t.table_id 
                  WHERE o.order_id = ?";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param('i', $order_id);
    $order_stmt->execute();
    $order = $order_stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Get order items
    $items_sql = "SELECT oi.*, mi.item_name, mi.description, mi.image_url 
                  FROM order_items oi 
                  JOIN menu_items mi ON oi.menu_item_id = mi.menu_item_id 
                  WHERE oi.order_id = ? 
                  ORDER BY oi.created_at";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get order add-ons
    $addons_sql = "SELECT oa.*, a.addon_name, a.price as addon_price
                   FROM order_addons oa
                   JOIN addons a ON oa.addon_id = a.addon_id
                   JOIN order_items oi ON oa.order_item_id = oi.order_item_id
                   WHERE oi.order_id = ?
                   ORDER BY oa.created_at";
    $addons_stmt = $conn->prepare($addons_sql);
    $addons_stmt->bind_param('i', $order_id);
    $addons_stmt->execute();
    $order_addons = $addons_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get payment transactions
    $payments_sql = "SELECT * FROM payment_transactions WHERE order_id = ? ORDER BY created_at";
    $payments_stmt = $conn->prepare($payments_sql);
    $payments_stmt->bind_param('i', $order_id);
    $payments_stmt->execute();
    $payments = $payments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get notifications
    $notifications_sql = "SELECT * FROM order_notifications WHERE order_id = ? ORDER BY created_at DESC LIMIT 10";
    $notifications_stmt = $conn->prepare($notifications_sql);
    $notifications_stmt->bind_param('i', $order_id);
    $notifications_stmt->execute();
    $notifications = $notifications_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Generate HTML
    $html = '
    <div class="row">
        <div class="col-md-6">
            <h6><i class="bi bi-info-circle"></i> Order Information</h6>
            <table class="table table-sm">
                <tr><td><strong>Order Number:</strong></td><td>' . htmlspecialchars($order['order_number']) . '</td></tr>
                <tr><td><strong>Table:</strong></td><td>' . htmlspecialchars($order['table_name']) . ' (' . htmlspecialchars($order['location']) . ')</td></tr>
                <tr><td><strong>Status:</strong></td><td><span class="badge bg-' . ($order['status'] == 'pending' ? 'warning' : ($order['status'] == 'preparing' ? 'info' : 'success')) . '">' . ucfirst($order['status']) . '</span></td></tr>
                <tr><td><strong>Payment Status:</strong></td><td><span class="badge bg-' . ($order['payment_status'] == 'paid' ? 'success' : 'warning') . '">' . ucfirst($order['payment_status']) . '</span></td></tr>
                <tr><td><strong>Created:</strong></td><td>' . date('M j, Y g:i A', strtotime($order['created_at'])) . '</td></tr>
                <tr><td><strong>Last Updated:</strong></td><td>' . date('M j, Y g:i A', strtotime($order['updated_at'])) . '</td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6><i class="bi bi-calculator"></i> Bill Summary</h6>
            <table class="table table-sm">
                <tr><td><strong>Subtotal:</strong></td><td>$' . number_format($order['subtotal'], 2) . '</td></tr>
                <tr><td><strong>Tax:</strong></td><td>$' . number_format($order['tax_amount'], 2) . '</td></tr>';
    
    if ($order['discount_amount'] > 0) {
        $discount_type = ucfirst(str_replace('_', ' ', $order['discount_type'] ?? 'discount'));
        $html .= '<tr><td><strong>Original Amount:</strong></td><td>$' . number_format($order['original_amount'] ?? $order['total_amount'] + $order['discount_amount'], 2) . '</td></tr>';
        $html .= '<tr><td><strong>' . $discount_type . ' Discount:</strong></td><td class="text-success">-$' . number_format($order['discount_amount'], 2) . '</td></tr>';
    }
    
    $html .= '
                <tr class="table-active"><td><strong>Total:</strong></td><td><strong>$' . number_format($order['total_amount'], 2) . '</strong></td></tr>
            </table>
        </div>
    </div>
    
    <div class="mt-4">
        <h6><i class="bi bi-list-check"></i> Order Items</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($order_items as $item) {
        $html .= '
                    <tr>
                        <td>
                            <div>
                                <strong>' . htmlspecialchars($item['item_name']) . '</strong>';
        
        if ($item['description']) {
            $html .= '<br><small class="text-muted">' . htmlspecialchars($item['description']) . '</small>';
        }
        
        if ($item['special_instructions']) {
            $html .= '<br><small class="text-info"><i class="bi bi-info-circle"></i> ' . htmlspecialchars($item['special_instructions']) . '</small>';
        }
        
        $html .= '
                            </div>
                        </td>
                        <td>' . $item['quantity'] . '</td>
                        <td>$' . number_format($item['unit_price'], 2) . '</td>
                        <td>$' . number_format($item['total_price'], 2) . '</td>
                        <td>
                            <span class="badge bg-' . ($item['status'] == 'pending' ? 'warning' : ($item['status'] == 'preparing' ? 'info' : 'success')) . '">' . ucfirst($item['status']) . '</span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="updateItemStatus(' . $item['order_item_id'] . ', \'' . $item['status'] . '\')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                        </td>
                    </tr>';
    }
    
    $html .= '
                </tbody>
            </table>
        </div>
    </div>';
    
    if (!empty($payments)) {
        $html .= '
        <div class="mt-4">
            <h6><i class="bi bi-credit-card"></i> Payment History</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($payments as $payment) {
            $html .= '
                        <tr>
                            <td>' . ucfirst(str_replace('_', ' ', $payment['payment_method'])) . '</td>
                            <td>$' . number_format($payment['amount'], 2) . '</td>
                            <td><span class="badge bg-' . ($payment['status'] == 'completed' ? 'success' : 'warning') . '">' . ucfirst($payment['status']) . '</span></td>
                            <td>' . date('M j, Y g:i A', strtotime($payment['created_at'])) . '</td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
    }
    
    if (!empty($notifications)) {
        $html .= '
        <div class="mt-4">
            <h6><i class="bi bi-bell"></i> Recent Notifications</h6>
            <div class="list-group">';
        
        foreach ($notifications as $notification) {
            $html .= '
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">' . ucfirst(str_replace('_', ' ', $notification['notification_type'])) . '</h6>
                        <small>' . date('M j, g:i A', strtotime($notification['created_at'])) . '</small>
                    </div>
                    <p class="mb-1">' . htmlspecialchars($notification['message']) . '</p>
                </div>';
        }
        
        $html .= '
            </div>
        </div>';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'order' => $order,
        'items' => $order_items,
        'payments' => $payments,
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
