<?php
// Receipt Printing for Counter Staff
require_once 'includes/db_connection.php';

// Initialize the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if(!isset($_SESSION["counter_loggedin"]) || $_SESSION["counter_loggedin"] !== true){
    header("location: counter_login.php");
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("location: index.php");
    exit;
}

$order_id = intval($_GET['order_id']);

// Get order details with discount information
$sql_order = "SELECT o.*, os.name as status_name, t.table_number,
                     o.discount_type, o.discount_percentage, o.discount_amount, o.original_amount
              FROM orders o 
              LEFT JOIN tables t ON o.table_id = t.table_id 
              JOIN order_statuses os ON o.status_id = os.status_id 
              WHERE o.order_id = ?";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();
$order = $result_order->fetch_assoc();
$stmt_order->close();

if (!$order) {
    header("location: index.php");
    exit;
}

// Get order items
$sql_items = "SELECT oi.*, mi.name as item_name, mi.description as item_description,
                     iv.name as variation_name, iv.price_adjustment as variation_price_adj
              FROM order_items oi 
              JOIN menu_items mi ON oi.item_id = mi.item_id 
              LEFT JOIN item_variations iv ON oi.variation_id = iv.variation_id 
              WHERE oi.order_id = ?
              ORDER BY oi.order_item_id";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result();
$stmt_items->close();

// Get restaurant information (you might want to add this to a settings table)
$restaurant_name = "Cianos Restaurant";
$restaurant_address = "123 Fine Dining Street, Manila, Philippines";
$restaurant_phone = "+63 2 1234 5678";
$restaurant_email = "info@cianos.com";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Order #<?= $order['queue_number'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            padding: 20px;
        }

        .receipt {
            max-width: 300px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
            padding: 15px;
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .restaurant-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .restaurant-details {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }

        .order-info {
            margin-bottom: 15px;
        }

        .order-info div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .items-section {
            margin-bottom: 15px;
        }

        .item {
            margin-bottom: 8px;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 5px;
        }

        .item-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .item-details {
            font-size: 10px;
            color: #666;
            margin-bottom: 2px;
        }

        .item-price {
            text-align: right;
            font-weight: bold;
        }

        .addon {
            margin-left: 10px;
            font-size: 10px;
            color: #666;
        }

        .totals {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-bottom: 15px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .total-row.final {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }

        .discount-section {
            background: #f8f9fa;
            padding: 8px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .discount-title {
            font-weight: bold;
            color: #28a745;
            margin-bottom: 5px;
        }

        .qr-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #000;
        }

        .qr-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .qr-codes {
            display: flex;
            justify-content: space-around;
            margin-bottom: 10px;
        }

        .qr-item {
            text-align: center;
        }

        .qr-code {
            width: 80px;
            height: 80px;
            border: 1px solid #ddd;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        .qr-code img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .qr-label {
            font-size: 10px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 10px;
            color: #666;
        }

        .thank-you {
            font-weight: bold;
            margin-bottom: 5px;
        }

        @media print {
            body {
                padding: 0;
            }
            
            .receipt {
                border: none;
                max-width: none;
                width: 100%;
            }
            
            .no-print {
                display: none;
            }
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print Receipt</button>
    <a href="index.php" class="back-button no-print">← Back to Counter</a>

    <div class="receipt">
        <!-- Restaurant Header -->
        <div class="header">
            <div class="restaurant-name"><?= $restaurant_name ?></div>
            <div class="restaurant-details"><?= $restaurant_address ?></div>
            <div class="restaurant-details"><?= $restaurant_phone ?></div>
            <div class="restaurant-details"><?= $restaurant_email ?></div>
        </div>

        <!-- Order Information -->
        <div class="order-info">
            <div><span>Order #:</span><span><?= $order['queue_number'] ?></span></div>
            <div><span>Date:</span><span><?= date('M d, Y', strtotime($order['created_at'])) ?></span></div>
            <div><span>Time:</span><span><?= date('h:i A', strtotime($order['created_at'])) ?></span></div>
            <div><span>Table:</span><span><?= $order['table_number'] ?? 'Takeout' ?></span></div>
            <div><span>Status:</span><span><?= ucfirst($order['status_name']) ?></span></div>
        </div>

        <!-- Order Items -->
        <div class="items-section">
            <div style="font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 3px;">ORDER ITEMS</div>
            <?php while ($item = $items->fetch_assoc()): ?>
                <div class="item">
                    <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                    <?php if ($item['variation_name']): ?>
                        <div class="item-details">Variation: <?= htmlspecialchars($item['variation_name']) ?></div>
                    <?php endif; ?>
                    <div class="item-details">Qty: <?= $item['quantity'] ?> × ₱<?= number_format($item['unit_price'], 2) ?></div>
                    <?php if ($item['notes']): ?>
                        <div class="item-details">Note: <?= htmlspecialchars($item['notes']) ?></div>
                    <?php endif; ?>
                    <div class="item-price">₱<?= number_format($item['subtotal'], 2) ?></div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Discount Section -->
        <?php if ($order['discount_amount'] > 0): ?>
        <div class="discount-section">
            <div class="discount-title">
                <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $order['discount_type'])) ?> Discount Applied
            </div>
            <div class="total-row">
                <span>Original Amount:</span>
                <span>₱<?= number_format($order['original_amount'] ?? $order['total_amount'] + $order['discount_amount'], 2) ?></span>
            </div>
            <div class="total-row">
                <span>Discount (<?= $order['discount_percentage'] ?>%):</span>
                <span class="text-success">-₱<?= number_format($order['discount_amount'], 2) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Totals -->
        <div class="totals">
            <div class="total-row final">
                <span>TOTAL AMOUNT:</span>
                <span>₱<?= number_format($order['total_amount'], 2) ?></span>
            </div>
        </div>

        <!-- QR Codes Section -->
        <div class="qr-section">
            <div class="qr-title">SCAN FOR MORE SERVICES</div>
            <div class="qr-codes">
                <div class="qr-item">
                    <div class="qr-code">
                        <!-- Feedback QR Code -->
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?= urlencode('http://localhost/capstone/feedback/index.php?table=' . $order['table_id']) ?>" 
                             alt="Feedback QR Code" 
                             style="width: 80px; height: 80px; display: block; margin: 0 auto;">
                    </div>
                    <div class="qr-label">Rate Your Experience</div>
                </div>
                <div class="qr-item">
                    <div class="qr-code">
                        <!-- Venue Booking QR Code -->
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?= urlencode('http://localhost/capstone/booking.php') ?>" 
                             alt="Booking QR Code" 
                             style="width: 80px; height: 80px; display: block; margin: 0 auto;">
                    </div>
                    <div class="qr-label">Book a Venue</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="thank-you">Thank you for dining with us!</div>
            <div>We appreciate your business</div>
            <div style="margin-top: 5px;">Visit us again soon</div>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>