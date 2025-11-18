<?php
require_once 'includes/db_connection.php';
require_once '../receipt_qr_generator.php';

// Get order ID from URL
$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    die("❌ No order ID provided");
}

// Get order details
$order_sql = "SELECT o.*, t.table_number, t.qr_code 
              FROM orders o 
              JOIN tables t ON o.table_id = t.table_id 
              WHERE o.order_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    die("❌ Order not found");
}

// Get order items
$items_sql = "SELECT oi.*, mi.name as item_name, mi.price as item_price 
              FROM order_items oi 
              JOIN menu_items mi ON oi.item_id = mi.item_id 
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Generate receipt QR codes
$generator = new ReceiptQRGenerator($conn);
$receipt_number = 'RCP_' . date('Ymd') . '_' . str_pad($order_id, 4, '0', STR_PAD_LEFT);

$qr_result = $generator->generateReceiptQRCodes($order_id, $order['table_id'], $receipt_number);

if (!$qr_result['success']) {
    die("❌ Failed to generate receipt QR codes: " . $qr_result['error']);
}

$feedback_qr = $qr_result['feedback_qr'];
$venue_qr = $qr_result['venue_qr'];

// Calculate totals
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['quantity'] * $item['item_price'];
}

$vat_rate = 0.12; // 12% VAT
$vat_amount = $subtotal * $vat_rate;
$total = $subtotal + $vat_amount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Cianos Seafoods Grill</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .receipt-header {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 10px;
        }
        
        .company-info {
            font-size: 12px;
            color: #7f8c8d;
            line-height: 1.4;
        }
        
        .receipt-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .receipt-title h2 {
            font-size: 16px;
            color: #2c3e50;
        }
        
        .receipt-date {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .receipt-number {
            padding: 0 20px 15px;
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .line-items {
            padding: 0 20px;
        }
        
        .line-items-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .items-table th {
            text-align: left;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            color: #7f8c8d;
            font-weight: normal;
        }
        
        .items-table td {
            padding: 6px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .item-description {
            color: #2c3e50;
        }
        
        .item-price {
            text-align: right;
            color: #2c3e50;
        }
        
        .item-vat {
            text-align: right;
            color: #7f8c8d;
        }
        
        .item-total {
            text-align: right;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .totals-section {
            padding: 15px 20px;
            border-top: 1px solid #eee;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .total-label {
            font-size: 14px;
            color: #2c3e50;
        }
        
        .total-amount {
            font-size: 16px;
            font-weight: bold;
            color: #27ae60;
        }
        
        .vat-breakdown {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #f8f9fa;
        }
        
        .vat-line {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 3px;
        }
        
        .payment-section {
            padding: 15px 20px;
            border-top: 1px solid #eee;
        }
        
        .payment-method {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .action-section {
            padding: 20px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        
        .action-text {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border: 1px solid #3498db;
            background: white;
            color: #3498db;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background: #3498db;
            color: white;
        }
        
        .qr-section {
            padding: 20px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        
        .qr-title {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .qr-codes {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .qr-code {
            text-align: center;
        }
        
        .qr-code img {
            width: 80px;
            height: 80px;
            border: 1px solid #eee;
        }
        
        .qr-label {
            font-size: 10px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .footer {
            padding: 20px;
            text-align: center;
            border-top: 1px solid #eee;
            background: #f8f9fa;
        }
        
        .footer-text {
            font-size: 11px;
            color: #7f8c8d;
            line-height: 1.4;
            margin-bottom: 10px;
        }
        
        .environmental {
            background: #27ae60;
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .environmental-icon {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .environmental-text {
            font-size: 12px;
            line-height: 1.4;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #27ae60;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #229954;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .print-button {
                display: none;
            }
            
            .receipt-container {
                box-shadow: none;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">
        🖨️ Print Receipt
    </button>
    
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="logo">🐟</div>
            <div class="company-name">Cianos Seafoods Grill</div>
            <div class="company-info">
                Fresh Seafood & Grilled Delights<br>
                Manila, Philippines<br>
                TIN: 123-456-789-000
            </div>
        </div>
        
        <!-- Receipt Title -->
        <div class="receipt-title">
            <h2>Receipt</h2>
            <div class="receipt-date"><?= date('m/d/Y, g:i:s A') ?></div>
        </div>
        
        <div class="receipt-number">
            Receipt #<?= $receipt_number ?>
        </div>
        
        <!-- Line Items -->
        <div class="line-items">
            <div class="line-items-title">Line-Items</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Price</th>
                        <th>VAT</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td class="item-description">
                            <?= $item['quantity'] ?> <?= htmlspecialchars($item['item_name']) ?>
                        </td>
                        <td class="item-price">₱<?= number_format($item['item_price'], 2) ?></td>
                        <td class="item-vat">12%</td>
                        <td class="item-total">₱<?= number_format($item['quantity'] * $item['item_price'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Totals -->
        <div class="totals-section">
            <div class="total-line">
                <span class="total-label">Sum total incl. VAT</span>
                <span class="total-amount">₱<?= number_format($total, 2) ?></span>
            </div>
            
            <div class="vat-breakdown">
                <div class="vat-line">
                    <span>VAT 12%</span>
                    <span>Net ₱<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="vat-line">
                    <span>VAT</span>
                    <span>₱<?= number_format($vat_amount, 2) ?></span>
                </div>
                <div class="vat-line">
                    <span>Gross</span>
                    <span>₱<?= number_format($total, 2) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Payment -->
        <div class="payment-section">
            <div class="payment-method">
                <span>Payment method</span>
                <span>Gross</span>
            </div>
            <div class="payment-method">
                <span>CASH</span>
                <span>₱<?= number_format($total, 2) ?></span>
            </div>
        </div>
        
        <!-- Action Section -->
        <div class="action-section">
            <div class="action-text">
                Thank you for dining with us! Use the QR codes below to leave feedback or book our venue for your next event.
            </div>
            <div class="action-buttons">
                <a href="#" class="action-btn" onclick="window.print()">
                    📄 Save as PDF
                </a>
                <a href="mailto:?subject=Receipt from Cianos Seafoods Grill&body=Thank you for dining with us!" class="action-btn">
                    📧 Forward by e-mail
                </a>
            </div>
        </div>
        
        <!-- QR Codes -->
        <div class="qr-section">
            <div class="qr-title">Customer Services</div>
            <div class="qr-codes">
                <div class="qr-code">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=http://192.168.1.2/capstone/feedback/enhanced_receipt_feedback.php?qr=<?= $feedback_qr ?>" alt="Feedback QR">
                    <div class="qr-label">Leave Feedback</div>
                </div>
                <div class="qr-code">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=http://192.168.1.2/capstone/reservations/enhanced_venue_rating.php?qr=<?= $venue_qr ?>" alt="Venue Rating QR">
                    <div class="qr-label">Rate Venue</div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                This receipt was generated by Cianos Seafoods Grill<br>
                For any questions, please contact us at info@cianos.com
            </div>
        </div>
        
        <!-- Environmental -->
        <div class="environmental">
            <div class="environmental-icon">💚</div>
            <div class="environmental-text">
                With this digital receipt you are saving 155.6 cm² of paper and 0.8 g of CO2.<br>
                Let's keep our planet a safe place - cianos.com
            </div>
        </div>
    </div>
    
    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
