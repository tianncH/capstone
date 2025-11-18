<?php
require_once '../admin/includes/db_connection.php';

$order_id = $_GET['order_id'] ?? null;

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

// Get payment transactions
$payment_sql = "SELECT * FROM payment_transactions WHERE order_id = ? ORDER BY created_at";
$payment_stmt = $conn->prepare($payment_sql);
$payment_stmt->bind_param('i', $order_id);
$payment_stmt->execute();
$payments = $payment_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get split payments
$split_sql = "SELECT * FROM split_payments WHERE order_id = ? ORDER BY created_at";
$split_stmt = $conn->prepare($split_sql);
$split_stmt->bind_param('i', $order_id);
$split_stmt->execute();
$split_payments = $split_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Order #<?= htmlspecialchars($order['order_number']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .receipt-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 600px;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 30px;
            text-align: center;
        }
        
        .receipt-body {
            padding: 30px;
        }
        
        .receipt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .receipt-item:last-child {
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
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
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
            background: #e3f2fd;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            padding: 10px;
            background: white;
            border-radius: 10px;
            margin-bottom: 10px;
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
            margin-top: 10px;
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
        
        .thank-you-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-top: 30px;
        }
        
        @media print {
            body {
                background: white !important;
            }
            
            .receipt-container {
                box-shadow: none !important;
                margin: 0 !important;
                border-radius: 0 !important;
            }
            
            .receipt-header {
                border-radius: 0 !important;
            }
            
            .btn-group {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="receipt-container">
            <!-- Receipt Header -->
            <div class="receipt-header">
                <h1 class="display-5 mb-3">
                    <i class="bi bi-check-circle"></i> Payment Complete!
                </h1>
                <p class="lead">Thank you for dining with us</p>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5>Order #<?= htmlspecialchars($order['order_number']) ?></h5>
                    </div>
                    <div class="col-md-6">
                        <h5><?= htmlspecialchars($order['table_name']) ?></h5>
                    </div>
                </div>
            </div>
            
            <!-- Receipt Body -->
            <div class="receipt-body">
                <!-- Order Details -->
                <div class="mb-4">
                    <h5 class="mb-3">
                        <i class="bi bi-list-check"></i> Order Details
                    </h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Date:</strong> <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?><br>
                            <strong>Table:</strong> <?= htmlspecialchars($order['table_name']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Billed Out:</strong> <?= date('M j, Y g:i A', strtotime($order['billed_out_at'])) ?><br>
                            <strong>Status:</strong> <span class="badge bg-success"><?= ucfirst($order['payment_status']) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Items Ordered -->
                <div class="mb-4">
                    <h6>Items Ordered:</h6>
                    <?php foreach ($order_items as $item): ?>
                        <div class="receipt-item">
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
                    <h6 class="mb-3">
                        <i class="bi bi-calculator"></i> Bill Summary
                    </h6>
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
                        <span>Total Paid:</span>
                        <span>$<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
                
                <!-- Payment Details -->
                <div class="payment-section">
                    <h6 class="mb-3">
                        <i class="bi bi-credit-card"></i> Payment Details
                    </h6>
                    
                    <?php foreach ($payments as $payment): ?>
                        <div class="payment-method">
                            <i class="bi bi-<?= $payment['payment_method'] == 'cash' ? 'cash' : ($payment['payment_method'] == 'card' ? 'credit-card' : 'phone') ?> payment-icon"></i>
                            <div class="flex-grow-1">
                                <strong><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></strong>
                                <div class="text-muted"><?= date('M j, Y g:i A', strtotime($payment['created_at'])) ?></div>
                            </div>
                            <div class="text-end">
                                <strong>$<?= number_format($payment['amount'], 2) ?></strong>
                                <div class="badge bg-success"><?= ucfirst($payment['status']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (!empty($split_payments)): ?>
                        <div class="split-payment">
                            <h6>Split Payments:</h6>
                            <?php foreach ($split_payments as $split): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><strong><?= htmlspecialchars($split['customer_name']) ?></strong></span>
                                    <span>$<?= number_format($split['amount'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Customer Information -->
                <?php if ($order['customer_name'] || $order['customer_email']): ?>
                    <div class="mb-4">
                        <h6>Customer Information:</h6>
                        <div class="row">
                            <?php if ($order['customer_name']): ?>
                                <div class="col-md-6">
                                    <strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($order['customer_email']): ?>
                                <div class="col-md-6">
                                    <strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="text-center mb-4">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary-custom btn-custom" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print Receipt
                        </button>
                        <button type="button" class="btn btn-success-custom btn-custom" onclick="downloadReceipt()">
                            <i class="bi bi-download"></i> Download PDF
                        </button>
                        <a href="../feedback/index.php?table_id=<?= $order['table_id'] ?>&order_id=<?= $order['order_id'] ?>" class="btn btn-outline-primary btn-custom">
                            <i class="bi bi-star"></i> Leave Feedback
                        </a>
                    </div>
                </div>
                
                <!-- Thank You Section -->
                <div class="thank-you-section">
                    <h4 class="mb-3">
                        <i class="bi bi-heart"></i> Thank You!
                    </h4>
                    <p class="mb-3">We hope you enjoyed your dining experience with us!</p>
                    <p class="mb-0">
                        <strong>Visit us again soon!</strong><br>
                        <small>Follow us on social media for updates and special offers</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function downloadReceipt() {
            // Create a new window for printing/downloading
            const printWindow = window.open('', '_blank');
            const receiptContent = document.querySelector('.receipt-container').innerHTML;
            
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Receipt - Order #<?= htmlspecialchars($order['order_number']) ?></title>
                        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
                        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .receipt-container { max-width: 600px; margin: 0 auto; }
                            @media print {
                                body { background: white !important; }
                                .btn-group { display: none !important; }
                            }
                        </style>
                    </head>
                    <body>
                        ${receiptContent}
                    </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.print();
        }
        
        // Auto-print on mobile devices
        if (window.innerWidth < 768) {
            setTimeout(() => {
                if (confirm('Would you like to print your receipt?')) {
                    window.print();
                }
            }, 2000);
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
