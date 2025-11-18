<?php
session_start();
require_once 'includes/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get today's date
$today = date('Y-m-d');

// Get today's sales with discount breakdown
$sales_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as actual_revenue,
                SUM(CASE WHEN discount_amount > 0 THEN discount_amount ELSE 0 END) as total_discounts,
                SUM(CASE WHEN discount_amount > 0 THEN total_amount + discount_amount ELSE total_amount END) as original_revenue,
                COUNT(CASE WHEN discount_amount > 0 THEN 1 END) as discount_orders
              FROM orders 
              WHERE DATE(created_at) = ? AND status_id = 2";
$sales_stmt = $conn->prepare($sales_sql);
$sales_stmt->bind_param("s", $today);
$sales_stmt->execute();
$sales_result = $sales_stmt->get_result();
$sales_data = $sales_result->fetch_assoc();

// Get discount breakdown by type for today
$discount_breakdown_sql = "SELECT 
                            discount_type,
                            COUNT(*) as count,
                            SUM(discount_amount) as total_discount,
                            AVG(discount_percentage) as avg_percentage
                          FROM orders 
                          WHERE DATE(created_at) = ? AND discount_amount > 0 AND status_id = 2
                          GROUP BY discount_type
                          ORDER BY total_discount DESC";
$discount_stmt = $conn->prepare($discount_breakdown_sql);
$discount_stmt->bind_param("s", $today);
$discount_stmt->execute();
$discount_breakdown = $discount_stmt->get_result();

// Get current cash float
$float_sql = "SELECT * FROM cash_float WHERE date = ? AND status = 'active'";
$float_stmt = $conn->prepare($float_sql);
$float_stmt->bind_param("s", $today);
$float_stmt->execute();
$float_result = $float_stmt->get_result();
$current_float = $float_result->fetch_assoc();

// Calculate cash flow impact
$expected_cash = 0;
$actual_cash_from_sales = $sales_data['actual_revenue']; // What actually came in
$discount_impact = $sales_data['total_discounts']; // What was lost to discounts

if ($current_float) {
    $expected_cash = $current_float['opening_amount'] + $actual_cash_from_sales;
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Cash Float Management with Discount Tracking</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="daily_sales.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-graph-up"></i> Daily Sales Report
            </a>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
            <i class="bi bi-arrow-repeat"></i> Refresh
        </button>
    </div>
</div>

<!-- Discount Impact Alert -->
<?php if ($discount_impact > 0): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <strong><i class="bi bi-exclamation-triangle"></i> Discount Impact Alert!</strong>
    Today's discounts have reduced cash flow by ₱<?= number_format($discount_impact, 2) ?> 
    (<?= number_format(($discount_impact / ($sales_data['original_revenue'] ?: 1)) * 100, 1) ?>% of potential revenue).
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Enhanced Cash Flow Summary -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Today's Cash Flow Analysis - <?= date('M d, Y') ?></h5>
            </div>
            <div class="card-body">
                <?php if ($current_float): ?>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <h6>Opening Float</h6>
                        <h3 class="text-primary">₱<?= number_format($current_float['opening_amount'], 2) ?></h3>
                        <small class="text-muted">Starting cash</small>
                    </div>
                    <div class="col-md-3">
                        <h6>Actual Sales Revenue</h6>
                        <h3 class="text-success">₱<?= number_format($sales_data['actual_revenue'], 2) ?></h3>
                        <small class="text-muted">Cash received</small>
                    </div>
                    <div class="col-md-3">
                        <h6>Expected Cash Total</h6>
                        <h3 class="text-info">₱<?= number_format($expected_cash, 2) ?></h3>
                        <small class="text-muted">Float + Sales</small>
                    </div>
                    <div class="col-md-3">
                        <h6>Discount Impact</h6>
                        <h3 class="text-danger">-₱<?= number_format($discount_impact, 2) ?></h3>
                        <small class="text-muted">Revenue lost</small>
                    </div>
                </div>
                
                <!-- Discount Breakdown -->
                <?php if ($discount_impact > 0): ?>
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle"></i> Discount Breakdown</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Original Potential Revenue:</strong> ₱<?= number_format($sales_data['original_revenue'], 2) ?><br>
                            <strong>Actual Revenue Received:</strong> ₱<?= number_format($sales_data['actual_revenue'], 2) ?><br>
                            <strong>Total Discount Given:</strong> <span class="text-danger">₱<?= number_format($discount_impact, 2) ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Orders with Discounts:</strong> <?= $sales_data['discount_orders'] ?> of <?= $sales_data['total_orders'] ?><br>
                            <strong>Discount Rate:</strong> <?= number_format(($sales_data['discount_orders'] / ($sales_data['total_orders'] ?: 1)) * 100, 1) ?>%<br>
                            <strong>Revenue Impact:</strong> <span class="text-danger"><?= number_format(($discount_impact / ($sales_data['original_revenue'] ?: 1)) * 100, 1) ?>%</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="text-center py-4">
                    <h5 class="text-muted">No cash float set for today</h5>
                    <p class="text-muted">Set the opening cash amount to start tracking daily transactions</p>
                    <button type="button" class="btn btn-success btn-lg" onclick="showSetFloatModal()">
                        <i class="bi bi-cash-coin"></i> Set Cash Float
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Discount Summary Card -->
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bi bi-percent"></i> Discount Summary</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Total Discounts Given:</strong><br>
                    <span class="h4 text-danger">₱<?= number_format($discount_impact, 2) ?></span>
                </div>
                <div class="mb-3">
                    <strong>Orders with Discounts:</strong><br>
                    <span class="h5 text-warning"><?= $sales_data['discount_orders'] ?></span>
                    <small class="text-muted">of <?= $sales_data['total_orders'] ?> total</small>
                </div>
                <div class="mb-3">
                    <strong>Cash Flow Impact:</strong><br>
                    <span class="h5 text-danger">-<?= number_format(($discount_impact / ($sales_data['original_revenue'] ?: 1)) * 100, 1) ?>%</span>
                </div>
                
                <!-- Discount Types Breakdown -->
                <?php if ($discount_breakdown->num_rows > 0): ?>
                <hr>
                <h6>Discount Types Today:</h6>
                <?php while ($discount_type = $discount_breakdown->fetch_assoc()): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-secondary"><?= ucfirst($discount_type['discount_type']) ?></span>
                    <span class="text-danger">₱<?= number_format($discount_type['total_discount'], 2) ?></span>
                </div>
                <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Cash Float History with Discount Impact -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">Recent Cash Float History with Discount Impact</h5>
    </div>
    <div class="card-body">
        <?php
        $history_sql = "SELECT 
                          cf.*,
                          COALESCE(daily_sales.actual_revenue, 0) as daily_actual_revenue,
                          COALESCE(daily_sales.total_discounts, 0) as daily_discounts,
                          COALESCE(daily_sales.original_revenue, 0) as daily_original_revenue,
                          COALESCE(daily_sales.discount_orders, 0) as daily_discount_orders,
                          COALESCE(daily_sales.total_orders, 0) as daily_total_orders
                        FROM cash_float cf
                        LEFT JOIN (
                          SELECT 
                            DATE(created_at) as sale_date,
                            SUM(total_amount) as actual_revenue,
                            SUM(CASE WHEN discount_amount > 0 THEN discount_amount ELSE 0 END) as total_discounts,
                            SUM(CASE WHEN discount_amount > 0 THEN total_amount + discount_amount ELSE total_amount END) as original_revenue,
                            COUNT(CASE WHEN discount_amount > 0 THEN 1 END) as discount_orders,
                            COUNT(*) as total_orders
                          FROM orders 
                          WHERE status_id = 2
                          GROUP BY DATE(created_at)
                        ) daily_sales ON cf.date = daily_sales.sale_date
                        ORDER BY cf.date DESC 
                        LIMIT 10";
        $history_result = $conn->query($history_sql);
        ?>
        
        <?php if ($history_result && $history_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Opening Float</th>
                            <th>Sales Revenue</th>
                            <th>Discounts Given</th>
                            <th>Expected Cash</th>
                            <th>Actual Closing</th>
                            <th>Variance</th>
                            <th>Discount Impact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $history_result->fetch_assoc()): ?>
                            <?php
                            $expected_closing = $record['opening_amount'] + $record['daily_actual_revenue'];
                            $actual_closing = $record['closing_amount'];
                            $variance = $actual_closing !== null ? $actual_closing - $expected_closing : null;
                            $discount_impact_pct = $record['daily_original_revenue'] > 0 ? 
                                ($record['daily_discounts'] / $record['daily_original_revenue'] * 100) : 0;
                            ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($record['date'])) ?></td>
                                <td>₱<?= number_format($record['opening_amount'], 2) ?></td>
                                <td>₱<?= number_format($record['daily_actual_revenue'], 2) ?></td>
                                <td class="text-danger">₱<?= number_format($record['daily_discounts'], 2) ?></td>
                                <td>₱<?= number_format($expected_closing, 2) ?></td>
                                <td>
                                    <?php if ($actual_closing !== null): ?>
                                        ₱<?= number_format($actual_closing, 2) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not closed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($variance !== null): ?>
                                        <span class="<?= $variance >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $variance >= 0 ? '+' : '' ?>₱<?= number_format($variance, 2) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['daily_discounts'] > 0): ?>
                                        <span class="badge bg-warning text-dark">
                                            <?= number_format($discount_impact_pct, 1) ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">No discounts</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No cash float history available.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>