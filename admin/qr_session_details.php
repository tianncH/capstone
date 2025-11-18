<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/currency_functions.php';
require_once 'includes/qr_fallback.php';

$session_id = intval($_GET['session_id']);

// Get QR session details
$session_sql = "SELECT qs.*, t.table_number, t.qr_code 
                FROM qr_sessions qs 
                JOIN tables t ON qs.table_id = t.table_id 
                WHERE qs.session_id = ?";
$session_stmt = $conn->prepare($session_sql);
$session_stmt->bind_param('i', $session_id);
$session_stmt->execute();
$session = $session_stmt->get_result()->fetch_assoc();
$session_stmt->close();

if (!$session) {
    echo '<div class="alert alert-danger">QR Session not found.</div>';
    exit;
}

// Get session orders (from qr_orders table)
$orders_sql = "SELECT qo.*, qo.status as status_name 
               FROM qr_orders qo 
               WHERE qo.session_id = ? 
               ORDER BY qo.created_at ASC";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param('i', $session_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result();
$orders_stmt->close();

// Calculate totals from actual orders
$total_items = 0;
$total_amount = 0;
$orders->data_seek(0);
while ($order = $orders->fetch_assoc()) {
    $total_items += intval($order['quantity']);
    $total_amount += floatval($order['subtotal']);
}
$orders->data_seek(0); // Reset pointer for display

// Get notifications
$notifications_sql = "SELECT * FROM qr_session_notifications 
                      WHERE session_id = ? 
                      ORDER BY created_at DESC";
$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->bind_param('i', $session_id);
$notifications_stmt->execute();
$notifications = $notifications_stmt->get_result();
$notifications_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Session Details - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #6c757d;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #6c757d;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --gradient-primary: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            --gradient-success: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            --gradient-warning: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
            --border-radius: 12px;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .session-details-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .session-header-modern {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .header-background {
            background: var(--gradient-primary);
            padding: 30px;
            color: white;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .session-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .session-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }

        .session-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 5px 0 0 0;
        }

        .session-stats {
            display: flex;
            gap: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .info-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .info-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .info-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .info-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .info-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .orders-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .order-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .order-header {
            padding: 15px 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .order-time {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .order-body {
            padding: 20px;
        }

        .order-summary {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .summary-value {
            font-weight: 600;
            color: var(--dark-color);
        }

        .summary-value.amount {
            color: var(--success-color);
            font-size: 1.1rem;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            color: white;
        }

        .status-badge.pending {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .status-badge.paid {
            background: var(--info-color);
        }

        .status-badge.preparing {
            background: var(--primary-color);
        }

        .status-badge.ready {
            background: var(--success-color);
        }

        .status-badge.completed {
            background: var(--secondary-color);
        }

        .status-badge.cancelled {
            background: var(--danger-color);
        }

        .qr-code-display {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .qr-code-content {
            padding: 30px;
            text-align: center;
        }

        .qr-code-image {
            max-width: 200px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .device-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }

        .device-info h6 {
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .device-info small {
            color: #6c757d;
            display: block;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .session-details-container {
                padding: 15px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="session-details-container">
        <!-- Modern Session Header -->
        <div class="session-header-modern">
            <div class="header-background">
                <div class="header-content">
                    <div class="header-left">
                        <div class="session-icon">
                            <i class="bi bi-qr-code"></i>
                        </div>
                        <div class="session-info">
                            <h1 class="session-title">Table <?= $session['table_number'] ?> QR Session</h1>
                            <p class="session-subtitle">QR Code: <?= htmlspecialchars($session['qr_code']) ?> • Session #<?= $session['session_id'] ?> • <?= ucfirst($session['status']) ?> Session</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="session-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?= $total_items ?></div>
                                <div class="stat-label">Total Items</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">₱<?= number_format($total_amount, 2) ?></div>
                                <div class="stat-label">Total Amount</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Display -->
        <div class="qr-code-display">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-qr-code"></i> QR Code Information
                </h3>
            </div>
            <div class="qr-code-content">
                <?php
                // Generate QR code for display
                $qr_fallback = new QRFallback();
                $qr_result = $qr_fallback->generateQR($session['qr_code_url'] ?? "http://localhost/capstone/ordering/secure_qr_menu.php?qr=" . $session['qr_code'], 200);
                ?>
                <img src="<?= htmlspecialchars($qr_result['url']) ?>" 
                     alt="QR Code for Table <?= $session['table_number'] ?>" 
                     class="qr-code-image">
                <h5>QR Code: <?= htmlspecialchars($session['qr_code']) ?></h5>
                <p class="text-muted">Table <?= $session['table_number'] ?> - Digital Menu Access</p>
                
                <div class="device-info">
                    <h6><i class="bi bi-phone"></i> Device Information</h6>
                    <small><strong>IP Address:</strong> <?= htmlspecialchars($session['ip_address'] ?? 'Unknown') ?></small>
                    <small><strong>User Agent:</strong> <?= htmlspecialchars(substr($session['user_agent'] ?? 'Unknown', 0, 50)) ?>...</small>
                    <small><strong>Device Fingerprint:</strong> <?= htmlspecialchars(substr($session['device_fingerprint'] ?? 'Unknown', 0, 16)) ?>...</small>
                </div>
            </div>
        </div>

        <!-- Session Information -->
        <div class="content-grid">
            <div class="info-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-info-circle"></i> Session Information
                    </h3>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon text-secondary">
                                <i class="bi bi-table"></i>
                            </div>
                            <div class="info-value"><?= $session['table_number'] ?></div>
                            <div class="info-label">Table Number</div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon text-secondary">
                                <i class="bi bi-play-circle"></i>
                            </div>
                            <div class="info-value"><?= date('h:i A', strtotime($session['created_at'])) ?></div>
                            <div class="info-label">Session Started</div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon text-secondary">
                                <i class="bi bi-stop-circle"></i>
                            </div>
                            <div class="info-value"><?= $session['expires_at'] ? date('h:i A', strtotime($session['expires_at'])) : 'No Expiry' ?></div>
                            <div class="info-label">Session Expires</div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon text-secondary">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="info-value">
                                <?php
                                $start = strtotime($session['created_at']);
                                $end = $session['expires_at'] ? strtotime($session['expires_at']) : time();
                                $duration = $end - $start;
                                $hours = floor($duration / 3600);
                                $minutes = floor(($duration % 3600) / 60);
                                echo $hours . 'h ' . $minutes . 'm';
                                ?>
                            </div>
                            <div class="info-label">Duration</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-graph-up"></i> Session Summary
                    </h3>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon text-secondary">
                                <i class="bi bi-receipt"></i>
                            </div>
                            <div class="info-value"><?= $orders->num_rows ?></div>
                            <div class="info-label">Total Orders</div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon text-secondary">
                                <i class="bi bi-basket"></i>
                            </div>
                            <div class="info-value"><?= $total_items ?></div>
                            <div class="info-label">Total Items</div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon text-secondary">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="info-value">₱<?= number_format($total_amount, 2) ?></div>
                            <div class="info-label">Total Amount</div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon text-secondary">
                                <i class="bi bi-key"></i>
                            </div>
                            <div class="info-value"><?= substr($session['session_token'], 0, 8) ?>...</div>
                            <div class="info-label">Session Token</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Section -->
        <div class="orders-section">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-list-ul"></i> Orders in This Session
                </h3>
            </div>
            <div class="card-body">
                <?php if ($orders->num_rows > 0): ?>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-number">Order #<?= $order['order_id'] ?></div>
                                    <div class="order-time"><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></div>
                                </div>
                                <div>
                                    <span class="status-badge <?= $order['status_name'] ?>">
                                        <?= ucfirst($order['status_name']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="order-body">
                                <div class="order-summary">
                                    <div class="summary-item">
                                        <div class="summary-label">Menu Item ID</div>
                                        <div class="summary-value"><?= $order['menu_item_id'] ?></div>
                                    </div>
                                    <div class="summary-item">
                                        <div class="summary-label">Quantity</div>
                                        <div class="summary-value"><?= $order['quantity'] ?></div>
                                    </div>
                                    <div class="summary-item">
                                        <div class="summary-label">Subtotal</div>
                                        <div class="summary-value amount">₱<?= number_format($order['subtotal'], 2) ?></div>
                                    </div>
                                </div>
                                
                                <div class="order-details mt-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Unit Price:</strong> ₱<?= number_format($order['unit_price'], 2) ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Status:</strong> 
                                            <span class="status-badge <?= $order['status_name'] ?>">
                                                <?= ucfirst($order['status_name']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($order['notes'])): ?>
                                    <div class="alert alert-light mt-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Notes:</strong> <?= htmlspecialchars($order['notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-basket text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-muted">No Orders in This Session</h5>
                        <p class="text-muted">No orders have been placed in this QR session yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
