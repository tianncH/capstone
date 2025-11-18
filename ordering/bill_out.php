<?php
require_once '../admin/includes/db_connection.php';

$order_id = $_GET['order_id'] ?? null;
$success_message = '';
$error_message = '';

if (!$order_id) {
    header("Location: index.php");
    exit;
}

// Get order details
$order_sql = "SELECT o.*, t.table_name, t.table_number 
              FROM orders o 
              JOIN tables t ON o.table_id = t.table_id 
              WHERE o.order_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param('i', $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Get order items
$items_sql = "SELECT oi.*, mi.item_name, mi.description 
              FROM order_items oi 
              JOIN menu_items mi ON oi.menu_item_id = mi.menu_item_id 
              WHERE oi.order_id = ? 
              ORDER BY oi.created_at";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $payment_method = $_POST['payment_method'];
        $payment_amount = (float)$_POST['payment_amount'];
        $customer_name = trim($_POST['customer_name']);
        $customer_email = trim($_POST['customer_email']);
        $split_payments = $_POST['split_payments'] ?? [];
        
        if ($payment_amount <= 0) {
            throw new Exception("Payment amount must be greater than zero.");
        }
        
        if ($payment_amount > $order['total_amount']) {
            throw new Exception("Payment amount cannot exceed order total.");
        }
        
        $conn->begin_transaction();
        
        // Update order status
        $update_order_sql = "UPDATE orders SET 
                            payment_status = ?, 
                            payment_method = ?, 
                            is_billed_out = 1, 
                            billed_out_at = NOW(),
                            customer_name = ?,
                            customer_email = ?
                            WHERE order_id = ?";
        $update_order_stmt = $conn->prepare($update_order_sql);
        $payment_status = $payment_amount >= $order['total_amount'] ? 'paid' : 'partial';
        $update_order_stmt->bind_param('ssssi', $payment_status, $payment_method, $customer_name, $customer_email, $order_id);
        
        if (!$update_order_stmt->execute()) {
            throw new Exception("Failed to update order: " . $update_order_stmt->error);
        }
        
        // Record payment transaction
        $transaction_sql = "INSERT INTO payment_transactions (order_id, transaction_type, payment_method, amount, status) VALUES (?, 'payment', ?, ?, 'completed')";
        $transaction_stmt = $conn->prepare($transaction_sql);
        $transaction_stmt->bind_param('isd', $order_id, $payment_method, $payment_amount);
        
        if (!$transaction_stmt->execute()) {
            throw new Exception("Failed to record payment: " . $transaction_stmt->error);
        }
        
        // Handle split payments if any
        if (!empty($split_payments)) {
            foreach ($split_payments as $split) {
                if (!empty($split['customer_name']) && $split['amount'] > 0) {
                    $split_sql = "INSERT INTO split_payments (order_id, split_number, customer_name, customer_email, amount, payment_method, status, paid_at) VALUES (?, ?, ?, ?, ?, ?, 'paid', NOW())";
                    $split_stmt = $conn->prepare($split_sql);
                    $split_number = 1; // You can make this dynamic
                    $split_stmt->bind_param('iissds', $order_id, $split_number, $split['customer_name'], $split['customer_email'], $split['amount'], $split['payment_method']);
                    $split_stmt->execute();
                }
            }
        }
        
        // Create notification
        $notification_sql = "INSERT INTO order_notifications (order_id, notification_type, message) VALUES (?, 'bill_out', 'Order billed out and payment received')";
        $notification_stmt = $conn->prepare($notification_sql);
        $notification_stmt->bind_param('i', $order_id);
        $notification_stmt->execute();
        
        $conn->commit();
        
        // Redirect to receipt page
        header("Location: receipt.php?order_id=" . $order_id);
        exit;
        
    } catch (Exception $e) {
        if ($conn->in_transaction()) {
            $conn->rollback();
        }
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Out - <?= htmlspecialchars($order['table_name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .bill-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 800px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 30px;
            text-align: center;
        }
        
        .bill-section {
            padding: 30px;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .item-description {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .item-price {
            font-weight: bold;
            color: #28a745;
        }
        
        .total-section {
            background: #e3f2fd;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-row.final {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 15px;
        }
        
        .payment-section {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        
        .payment-method.selected {
            border-color: #667eea;
            background: #e3f2fd;
        }
        
        .payment-method input[type="radio"] {
            margin-right: 15px;
        }
        
        .payment-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            color: #667eea;
        }
        
        .split-payment {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .btn-custom {
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-success-custom {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
        }
        
        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="bill-container">
            <!-- Header Section -->
            <div class="header-section">
                <h1 class="display-5 mb-3">
                    <i class="bi bi-receipt"></i> Bill Out
                </h1>
                <p class="lead">Complete your payment for <?= htmlspecialchars($order['table_name']) ?></p>
            </div>
            
            <!-- Bill Section -->
            <div class="bill-section">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <h4 class="mb-3">
                        <i class="bi bi-list-check"></i> Order Summary
                    </h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Order #:</strong> <?= htmlspecialchars($order['order_number']) ?><br>
                            <strong>Table:</strong> <?= htmlspecialchars($order['table_name']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Date:</strong> <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?><br>
                            <strong>Status:</strong> <span class="badge bg-<?= $order['status'] == 'pending' ? 'warning' : 'success' ?>"><?= ucfirst($order['status']) ?></span>
                        </div>
                    </div>
                    
                    <h6>Items Ordered:</h6>
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <?php if ($item['description']): ?>
                                    <div class="item-description"><?= htmlspecialchars($item['description']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="item-price">
                                <?= $item['quantity'] ?> × $<?= number_format($item['unit_price'], 2) ?> = $<?= number_format($item['total_price'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Total Section -->
                <div class="total-section">
                    <h5 class="mb-3">
                        <i class="bi bi-calculator"></i> Bill Breakdown
                    </h5>
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?= number_format($order['subtotal'], 2) ?></span>
                    </div>
                    <div class="total-row">
                        <span>Tax (10%):</span>
                        <span>$<?= number_format($order['tax_amount'], 2) ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="total-row">
                            <span>Discount:</span>
                            <span class="text-success">-$<?= number_format($order['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="total-row final">
                        <span>Total Amount:</span>
                        <span>$<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
                
                <!-- Payment Section -->
                <form method="POST" id="paymentForm">
                    <div class="payment-section">
                        <h5 class="mb-3">
                            <i class="bi bi-credit-card"></i> Payment Method
                        </h5>
                        
                        <div class="payment-method" onclick="selectPaymentMethod('cash')">
                            <input type="radio" name="payment_method" value="cash" id="cash" required>
                            <i class="bi bi-cash payment-icon"></i>
                            <div>
                                <strong>Cash</strong>
                                <div class="text-muted">Pay with cash at the counter</div>
                            </div>
                        </div>
                        
                        <div class="payment-method" onclick="selectPaymentMethod('card')">
                            <input type="radio" name="payment_method" value="card" id="card">
                            <i class="bi bi-credit-card payment-icon"></i>
                            <div>
                                <strong>Credit/Debit Card</strong>
                                <div class="text-muted">Pay with card at the counter</div>
                            </div>
                        </div>
                        
                        <div class="payment-method" onclick="selectPaymentMethod('digital_wallet')">
                            <input type="radio" name="payment_method" value="digital_wallet" id="digital_wallet">
                            <i class="bi bi-phone payment-icon"></i>
                            <div>
                                <strong>Digital Wallet</strong>
                                <div class="text-muted">Apple Pay, Google Pay, etc.</div>
                            </div>
                        </div>
                        
                        <div class="payment-method" onclick="selectPaymentMethod('split')">
                            <input type="radio" name="payment_method" value="split" id="split">
                            <i class="bi bi-people payment-icon"></i>
                            <div>
                                <strong>Split Payment</strong>
                                <div class="text-muted">Split the bill among multiple people</div>
                            </div>
                        </div>
                        
                        <!-- Split Payment Details -->
                        <div id="splitPaymentDetails" class="split-payment" style="display: none;">
                            <h6>Split Payment Details</h6>
                            <div id="splitPayments">
                                <div class="split-payment-item mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" name="split_payments[0][customer_name]" placeholder="Customer Name" required>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="email" class="form-control" name="split_payments[0][customer_email]" placeholder="Email (optional)">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="number" class="form-control" name="split_payments[0][amount]" placeholder="Amount" step="0.01" min="0.01" required>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-danger" onclick="removeSplitPayment(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addSplitPayment()">
                                <i class="bi bi-plus"></i> Add Another Person
                            </button>
                        </div>
                        
                        <!-- Customer Information -->
                        <div class="mt-4">
                            <h6>Customer Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Your name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="customer_email" name="customer_email" placeholder="your@email.com">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Amount -->
                        <div class="mt-4">
                            <div class="mb-3">
                                <label for="payment_amount" class="form-label">Payment Amount</label>
                                <input type="number" class="form-control" id="payment_amount" name="payment_amount" 
                                       value="<?= $order['total_amount'] ?>" step="0.01" min="0.01" max="<?= $order['total_amount'] ?>" required>
                                <div class="form-text">Maximum: $<?= number_format($order['total_amount'], 2) ?></div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success-custom btn-custom btn-lg">
                                <i class="bi bi-check-circle"></i> Complete Payment
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let splitPaymentCount = 1;
        
        function selectPaymentMethod(method) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            event.currentTarget.classList.add('selected');
            
            // Show/hide split payment details
            const splitDetails = document.getElementById('splitPaymentDetails');
            if (method === 'split') {
                splitDetails.style.display = 'block';
            } else {
                splitDetails.style.display = 'none';
            }
        }
        
        function addSplitPayment() {
            const container = document.getElementById('splitPayments');
            const newPayment = document.createElement('div');
            newPayment.className = 'split-payment-item mb-3';
            newPayment.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="split_payments[${splitPaymentCount}][customer_name]" placeholder="Customer Name" required>
                    </div>
                    <div class="col-md-4">
                        <input type="email" class="form-control" name="split_payments[${splitPaymentCount}][customer_email]" placeholder="Email (optional)">
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control" name="split_payments[${splitPaymentCount}][amount]" placeholder="Amount" step="0.01" min="0.01" required>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger" onclick="removeSplitPayment(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newPayment);
            splitPaymentCount++;
        }
        
        function removeSplitPayment(button) {
            button.closest('.split-payment-item').remove();
        }
        
        // Form validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method.');
                return;
            }
            
            if (paymentMethod.value === 'split') {
                const splitAmounts = document.querySelectorAll('input[name*="[amount]"]');
                let totalSplit = 0;
                splitAmounts.forEach(input => {
                    totalSplit += parseFloat(input.value) || 0;
                });
                
                const orderTotal = <?= $order['total_amount'] ?>;
                if (Math.abs(totalSplit - orderTotal) > 0.01) {
                    e.preventDefault();
                    alert(`Split payment total ($${totalSplit.toFixed(2)}) must equal order total ($${orderTotal.toFixed(2)}).`);
                    return;
                }
            }
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
