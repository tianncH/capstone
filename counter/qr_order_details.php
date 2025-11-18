<?php
// QR Order Details Viewer for Counter Staff
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

// Check if qr_session_id is provided
if (!isset($_GET['qr_session_id']) || empty($_GET['qr_session_id'])) {
    header("location: index.php");
    exit;
}

$qr_session_id = intval($_GET['qr_session_id']);

// Get QR session details
$sql_session = "SELECT qs.*, t.table_number, t.qr_code
                FROM qr_sessions qs 
                LEFT JOIN tables t ON qs.table_id = t.table_id 
                WHERE qs.session_id = ?";
$stmt_session = $conn->prepare($sql_session);
$stmt_session->bind_param("i", $qr_session_id);
$stmt_session->execute();
$result_session = $stmt_session->get_result();
$qr_session = $result_session->fetch_assoc();
$stmt_session->close();

if (!$qr_session) {
    header("location: index.php");
    exit;
}

// Get QR orders for this session
$sql_orders = "SELECT qo.*, mi.name as item_name, mi.description as item_description
               FROM qr_orders qo 
               LEFT JOIN menu_items mi ON qo.menu_item_id = mi.item_id 
               WHERE qo.session_id = ?
               ORDER BY qo.order_id";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $qr_session_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();

$orders = [];
$total_amount = 0;

while ($order = $result_orders->fetch_assoc()) {
    $orders[] = $order;
    $total_amount += $order['subtotal'];
}
$stmt_orders->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Order Details - Counter System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .order-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .order-info-row:last-child {
            border-bottom: none;
        }
        .item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        .item-header {
            background: #f8f9fa;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
        }
        .item-line {
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
        }
        .item-details {
            padding: 10px 15px;
            background: #f8f9fa;
            font-size: 0.9em;
            color: #6c757d;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8em;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-paid { background: #cce5ff; color: #004085; }
        .status-completed { background: #e2e3e5; color: #383d41; }
        .btn-back {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
        }
        .total-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .qr-badge {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="bi bi-qr-code"></i> QR Order Details</h2>
                    <p class="mb-0">Complete QR session information for counter staff</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Back to Counter
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- QR Session Information -->
        <div class="order-card">
            <div class="order-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-1">
                            <i class="bi bi-qr-code"></i> QR Session #<?= $qr_session['session_id'] ?>
                        </h3>
                        <p class="mb-0">
                            <i class="bi bi-table"></i> Table <?= $qr_session['table_number'] ?>
                            <span class="qr-badge ms-2"><?= htmlspecialchars($qr_session['qr_code']) ?></span>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="status-badge status-<?= strtolower($qr_session['status']) ?>">
                            <?= ucfirst($qr_session['status']) ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="p-4">
                <div class="order-info-row">
                    <span><strong>Session ID:</strong></span>
                    <span><?= $qr_session['session_id'] ?></span>
                </div>
                <div class="order-info-row">
                    <span><strong>QR Code:</strong></span>
                    <span><?= htmlspecialchars($qr_session['qr_code']) ?></span>
                </div>
                <div class="order-info-row">
                    <span><strong>Table:</strong></span>
                    <span>Table <?= $qr_session['table_number'] ?></span>
                </div>
                <div class="order-info-row">
                    <span><strong>Session Started:</strong></span>
                    <span><?= date('M d, Y h:i A', strtotime($qr_session['created_at'])) ?></span>
                </div>
                <div class="order-info-row">
                    <span><strong>Status:</strong></span>
                    <span class="status-badge status-<?= strtolower($qr_session['status']) ?>">
                        <?= ucfirst($qr_session['status']) ?>
                    </span>
                </div>
                <div class="order-info-row">
                    <span><strong>Counter Confirmed:</strong></span>
                    <span><?= $qr_session['confirmed_by_counter'] ? '✅ Yes' : '❌ No' ?></span>
                </div>
                <?php if ($qr_session['confirmed_at']): ?>
                <div class="order-info-row">
                    <span><strong>Confirmed At:</strong></span>
                    <span><?= date('M d, Y h:i A', strtotime($qr_session['confirmed_at'])) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($qr_session['closed_at']): ?>
                <div class="order-info-row">
                    <span><strong>Closed At:</strong></span>
                    <span><?= date('M d, Y h:i A', strtotime($qr_session['closed_at'])) ?></span>
                </div>
                <?php endif; ?>
                <div class="order-info-row">
                    <span><strong>Device Fingerprint:</strong></span>
                    <span style="font-family: monospace; font-size: 0.9em;"><?= htmlspecialchars($qr_session['device_fingerprint']) ?></span>
                </div>
            </div>
        </div>
        
        <!-- QR Orders -->
        <div class="order-card">
            <div class="order-header">
                <h4 class="mb-0"><i class="bi bi-list-ul"></i> QR Orders (<?= count($orders) ?>)</h4>
            </div>
            
            <div class="p-4">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                    <div class="item">
                        <div class="item-header">
                            <span>
                                <?php if ($order['item_name']): ?>
                                    <?= htmlspecialchars($order['item_name']) ?>
                                <?php else: ?>
                                    Menu Item #<?= $order['menu_item_id'] ?>
                                <?php endif; ?>
                            </span>
                            <span>₱<?= number_format($order['subtotal'], 2) ?></span>
                        </div>
                        
                        <div class="item-line">
                            <span>Quantity: <?= $order['quantity'] ?> x ₱<?= number_format($order['unit_price'], 2) ?></span>
                            <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                        
                        <?php if ($order['item_description']): ?>
                        <div class="item-details">
                            <strong>Description:</strong> <?= htmlspecialchars($order['item_description']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="item-details">
                            <strong>Order Time:</strong> <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?>
                        </div>
                        
                        <?php if ($order['payment_method']): ?>
                        <div class="item-details">
                            <strong>Payment Method:</strong> <?= ucfirst($order['payment_method']) ?>
                            <?php if ($order['payment_amount']): ?>
                                (₱<?= number_format($order['payment_amount'], 2) ?>)
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #6c757d;"></i>
                        <h5 class="mt-3">No orders found</h5>
                        <p class="text-muted">This QR session has no orders yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Total -->
        <div class="total-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-1"><i class="bi bi-calculator"></i> Session Summary</h4>
                    <p class="mb-0">Total amount for this QR session</p>
                </div>
                <div class="col-md-4 text-end">
                    <h2 class="mb-0">₱<?= number_format($total_amount, 2) ?></h2>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary btn-lg me-3">
                <i class="bi bi-arrow-left"></i> Back to Counter
            </a>
            <?php if (!$qr_session['confirmed_by_counter']): ?>
                <form method="POST" action="index.php" style="display: inline;">
                    <input type="hidden" name="action" value="confirm_qr_session">
                    <input type="hidden" name="session_id" value="<?= $qr_session['session_id'] ?>">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle"></i> Confirm Session
                    </button>
                </form>
            <?php endif; ?>
            <a href="../ordering/secure_qr_menu.php?qr=<?= urlencode($qr_session['qr_code']) ?>" 
               class="btn btn-info btn-lg" target="_blank">
                <i class="bi bi-eye"></i> View Customer Menu
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
