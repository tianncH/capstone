<?php
require_once 'includes/db_connection.php';

// Simple daily sales report - no blinking!
$date = $_GET['date'] ?? date('Y-m-d');

// Get daily sales data
$daily_sales_sql = "SELECT * FROM daily_sales WHERE date = ?";
$stmt = $conn->prepare($daily_sales_sql);
$stmt->bind_param("s", $date);
$stmt->execute();
$daily_sales = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get orders for the selected date
$orders_sql = "SELECT o.*, os.name as status_name, t.table_number 
               FROM orders o 
               LEFT JOIN tables t ON o.table_id = t.table_id 
               JOIN order_statuses os ON o.status_id = os.status_id 
               WHERE DATE(o.created_at) = ? 
               ORDER BY o.created_at DESC";

$stmt = $conn->prepare($orders_sql);
$stmt->bind_param("s", $date);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

// Calculate totals
$total_orders = 0;
$total_sales = 0;
$pending_orders = 0;
$completed_orders = 0;
$cancelled_orders = 0;

while ($order = $orders->fetch_assoc()) {
    $total_orders++;
    if ($order['status_id'] == 2) { // Only count Paid orders
        $total_sales += $order['total_amount'];
    }
    if ($order['status_id'] == 1) $pending_orders++;
    if (in_array($order['status_id'], [2, 4, 5])) $completed_orders++;
    if ($order['status_id'] == 6) $cancelled_orders++;
}

// Reset orders pointer
$orders->data_seek(0);
?>

<?php
$page_title = "Daily Sales Report";
require_once 'includes/header_clean.php';
?>
    <div class="container-fluid">
        <!-- Header -->
        <div class="header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="bi bi-graph-up"></i> Daily Sales Report - Clean Version</h1>
                    <p class="mb-0">Simple sales analytics</p>
                </div>
                <div class="col-md-6 text-end">
                    <form method="GET" class="d-inline-flex align-items-center gap-2">
                        <input type="date" class="form-control" name="date" value="<?= $date ?>" max="<?= date('Y-m-d') ?>" style="width: 150px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> View
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Summary Stats -->
        <div class="row">
            <div class="col-md-2">
                <div class="stats-card">
                    <h5><i class="bi bi-calendar"></i> Date</h5>
                    <h4><?= date('M j', strtotime($date)) ?></h4>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h5><i class="bi bi-receipt"></i> Total Orders</h5>
                    <h4><?= $total_orders ?></h4>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h5><i class="bi bi-currency-dollar"></i> Total Sales</h5>
                    <h4>₱<?= number_format($total_sales, 2) ?></h4>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h5><i class="bi bi-hourglass-split"></i> Pending</h5>
                    <h4><?= $pending_orders ?></h4>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h5><i class="bi bi-check-circle"></i> Completed</h5>
                    <h4><?= $completed_orders ?></h4>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h5><i class="bi bi-x-circle"></i> Cancelled</h5>
                    <h4><?= $cancelled_orders ?></h4>
                </div>
            </div>
        </div>
        
        <!-- Orders List -->
        <div class="header">
            <h3><i class="bi bi-list-ul"></i> Orders for <?= date('F j, Y', strtotime($date)) ?></h3>
        </div>
        
        <?php if ($orders->num_rows > 0): ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <div class="order-card order-<?= strtolower($order['status_name']) ?>">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6>
                                <span class="badge badge-<?= $order['status_id'] == 1 ? 'warning' : ($order['status_id'] == 2 ? 'primary' : ($order['status_id'] == 4 ? 'success' : ($order['status_id'] == 5 ? 'secondary' : 'danger'))) ?>">
                                    <?= $order['status_name'] ?>
                                </span>
                                Order #<?= $order['queue_number'] ?>
                                <?php if ($order['table_number']): ?>
                                    <small class="text-muted">- Table <?= $order['table_number'] ?></small>
                                <?php endif; ?>
                            </h6>
                            <p class="mb-1">
                                <i class="bi bi-clock"></i> <?= date('h:i A', strtotime($order['created_at'])) ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <h5 class="mb-0">
                                ₱<?= number_format($order['total_amount'], 2) ?>
                            </h5>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="order-card text-center">
                <h5><i class="bi bi-inbox"></i> No orders for this date</h5>
                <p class="text-muted">Orders will appear here when customers place them.</p>
            </div>
        <?php endif; ?>
        
        <!-- Navigation -->
        <div class="header text-center">
            <a href="index.php" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
            <a href="cash_float.php" class="btn btn-primary">
                <i class="bi bi-cash-coin"></i> Cash Float
            </a>
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>
    
<?php require_once 'includes/footer_clean.php'; ?>
