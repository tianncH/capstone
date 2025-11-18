<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Get filter parameters
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '30';
$discount_type = isset($_GET['discount_type']) ? $_GET['discount_type'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Set date range based on selection
if ($date_range && !$start_date && !$end_date) {
    switch ($date_range) {
        case '7':
            $start_date = date('Y-m-d', strtotime('-7 days'));
            $end_date = date('Y-m-d');
            break;
        case '30':
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $end_date = date('Y-m-d');
            break;
        case '90':
            $start_date = date('Y-m-d', strtotime('-90 days'));
            $end_date = date('Y-m-d');
            break;
        case 'month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d');
            break;
        case 'year':
            $start_date = date('Y-01-01');
            $end_date = date('Y-m-d');
            break;
    }
} elseif (!$start_date || !$end_date) {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

// Build discount type filter
$discount_filter = "";
if ($discount_type !== 'all') {
    $discount_filter = "AND discount_type = '" . $conn->real_escape_string($discount_type) . "'";
}

// Get comprehensive discount analytics
$analytics_sql = "SELECT 
    COUNT(*) as total_discount_orders,
    SUM(discount_amount) as total_discount_amount,
    SUM(total_amount + discount_amount) as total_original_revenue,
    SUM(total_amount) as total_actual_revenue,
    AVG(discount_percentage) as avg_discount_percentage,
    AVG(discount_amount) as avg_discount_amount,
    MIN(discount_percentage) as min_discount_rate,
    MAX(discount_percentage) as max_discount_rate,
    COUNT(DISTINCT DATE(created_at)) as active_days
FROM orders 
WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' 
AND discount_amount > 0 
$discount_filter";

$analytics_result = $conn->query($analytics_sql);
$analytics = $analytics_result->fetch_assoc();

// Get total orders for comparison
$total_orders_sql = "SELECT COUNT(*) as total_orders, SUM(total_amount) as total_revenue
                     FROM orders 
                     WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
$total_result = $conn->query($total_orders_sql);
$totals = $total_result->fetch_assoc();

// Calculate key metrics
$discount_penetration = $totals['total_orders'] > 0 ? 
    ($analytics['total_discount_orders'] / $totals['total_orders'] * 100) : 0;
$revenue_impact = $analytics['total_original_revenue'] > 0 ? 
    ($analytics['total_discount_amount'] / $analytics['total_original_revenue'] * 100) : 0;
$avg_daily_discount = $analytics['active_days'] > 0 ? 
    ($analytics['total_discount_amount'] / $analytics['active_days']) : 0;

// Get discount trends by day
$daily_trends_sql = "SELECT 
    DATE(created_at) as discount_date,
    COUNT(*) as daily_discount_orders,
    SUM(discount_amount) as daily_discount_amount,
    SUM(total_amount + discount_amount) as daily_original_revenue,
    AVG(discount_percentage) as daily_avg_rate,
    COUNT(DISTINCT discount_type) as discount_types_used
FROM orders 
WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' 
AND discount_amount > 0 
$discount_filter
GROUP BY DATE(created_at)
ORDER BY discount_date DESC";

$daily_trends_result = $conn->query($daily_trends_sql);
$daily_trends = [];
while ($row = $daily_trends_result->fetch_assoc()) {
    $daily_trends[] = $row;
}

// Get discount types breakdown
$types_sql = "SELECT 
    discount_type,
    COUNT(*) as type_orders,
    SUM(discount_amount) as type_total_discount,
    SUM(total_amount + discount_amount) as type_original_revenue,
    AVG(discount_percentage) as type_avg_percentage,
    MIN(discount_percentage) as type_min_percentage,
    MAX(discount_percentage) as type_max_percentage,
    COUNT(DISTINCT DATE(created_at)) as type_active_days
FROM orders 
WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' 
AND discount_amount > 0 
$discount_filter
GROUP BY discount_type
ORDER BY type_total_discount DESC";

$types_result = $conn->query($types_sql);
$discount_types = [];
while ($row = $types_result->fetch_assoc()) {
    $row['type_impact'] = $row['type_original_revenue'] > 0 ? 
        ($row['type_total_discount'] / $row['type_original_revenue'] * 100) : 0;
    $discount_types[] = $row;
}

// Get hourly discount patterns
$hourly_sql = "SELECT 
    HOUR(created_at) as discount_hour,
    COUNT(*) as hourly_orders,
    SUM(discount_amount) as hourly_discount_amount,
    AVG(discount_percentage) as hourly_avg_rate
FROM orders 
WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' 
AND discount_amount > 0 
$discount_filter
GROUP BY HOUR(created_at)
ORDER BY discount_hour";

$hourly_result = $conn->query($hourly_sql);
$hourly_patterns = [];
while ($row = $hourly_result->fetch_assoc()) {
    $hourly_patterns[] = $row;
}

// Get top discounted items
$items_sql = "SELECT 
    mi.name as item_name,
    mi.price as item_price,
    COUNT(DISTINCT o.order_id) as discounted_orders,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.subtotal) as actual_revenue,
    SUM(oi.quantity * mi.price) as original_revenue,
    SUM(oi.quantity * mi.price) - SUM(oi.subtotal) as discount_amount,
    AVG(o.discount_percentage) as avg_discount_rate
FROM orders o
JOIN order_items oi ON o.order_id = oi.order_id
JOIN menu_items mi ON oi.item_id = mi.item_id
WHERE DATE(o.created_at) BETWEEN '$start_date' AND '$end_date' 
AND o.discount_amount > 0 
$discount_filter
GROUP BY mi.item_id, mi.name, mi.price
HAVING discount_amount > 0
ORDER BY discount_amount DESC
LIMIT 10";

$items_result = $conn->query($items_sql);
$top_discounted_items = [];
while ($row = $items_result->fetch_assoc()) {
    $top_discounted_items[] = $row;
}

// Get available discount types for filter
$available_types_sql = "SELECT DISTINCT discount_type 
                        FROM orders 
                        WHERE discount_amount > 0 
                        ORDER BY discount_type";
$available_types_result = $conn->query($available_types_sql);
$available_types = [];
while ($row = $available_types_result->fetch_assoc()) {
    $available_types[] = $row['discount_type'];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-percent"></i> Discount Analytics & Reports</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="enhanced_sales_reports_with_discounts.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-graph-up"></i> Sales Reports
            </a>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="exportBtn">
            <i class="bi bi-download"></i> Export Data
        </button>
    </div>
</div>

<!-- Alert for High Impact -->
<?php if ($revenue_impact > 15): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="bi bi-exclamation-triangle"></i> Critical Discount Impact!</strong>
    Discounts are impacting <?= number_format($revenue_impact, 1) ?>% of potential revenue. 
    Consider immediate review of discount policies.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php elseif ($revenue_impact > 10): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <strong><i class="bi bi-info-circle"></i> Moderate Discount Impact</strong>
    Discounts are impacting <?= number_format($revenue_impact, 1) ?>% of potential revenue. Monitor closely.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-funnel"></i> Report Filters</h5>
    </div>
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-3">
                <label for="date_range" class="form-label">Quick Date Range</label>
                <select class="form-select" id="date_range" name="date_range" onchange="toggleCustomDates()">
                    <option value="7" <?= $date_range == '7' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="30" <?= $date_range == '30' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="90" <?= $date_range == '90' ? 'selected' : '' ?>>Last 90 Days</option>
                    <option value="month" <?= $date_range == 'month' ? 'selected' : '' ?>>This Month</option>
                    <option value="year" <?= $date_range == 'year' ? 'selected' : '' ?>>This Year</option>
                    <option value="custom" <?= ($start_date && $end_date && !$date_range) ? 'selected' : '' ?>>Custom Range</option>
                </select>
            </div>
            <div class="col-md-2" id="start_date_col" style="display: none;">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
            </div>
            <div class="col-md-2" id="end_date_col" style="display: none;">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
            </div>
            <div class="col-md-3">
                <label for="discount_type" class="form-label">Discount Type</label>
                <select class="form-select" id="discount_type" name="discount_type">
                    <option value="all" <?= $discount_type == 'all' ? 'selected' : '' ?>>All Types</option>
                    <?php foreach ($available_types as $type): ?>
                        <option value="<?= $type ?>" <?= $discount_type == $type ? 'selected' : '' ?>>
                            <?= ucfirst($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Key Metrics Dashboard -->
<div class="row mb-4">
    <div class="col-md-2 mb-3">
        <div class="card h-100 border-danger">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Total Discount Orders</h6>
                <h3 class="text-danger"><?= number_format($analytics['total_discount_orders'] ?: 0) ?></h3>
                <small class="text-muted">
                    <?= number_format($discount_penetration, 1) ?>% of all orders
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="card h-100 border-warning">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Revenue Lost</h6>
                <h3 class="text-warning">₱<?= number_format($analytics['total_discount_amount'] ?: 0, 2) ?></h3>
                <small class="text-muted">
                    <?= number_format($revenue_impact, 1) ?>% impact
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="card h-100 border-info">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Avg Discount Rate</h6>
                <h3 class="text-info"><?= number_format($analytics['avg_discount_percentage'] ?: 0, 1) ?>%</h3>
                <small class="text-muted">
                    Per discounted order
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="card h-100 border-success">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Avg Discount Amount</h6>
                <h3 class="text-success">₱<?= number_format($analytics['avg_discount_amount'] ?: 0, 2) ?></h3>
                <small class="text-muted">
                    Per discounted order
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="card h-100 border-primary">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Daily Avg Loss</h6>
                <h3 class="text-primary">₱<?= number_format($avg_daily_discount, 2) ?></h3>
                <small class="text-muted">
                    Revenue lost per day
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="card h-100 border-secondary">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Discount Range</h6>
                <h3 class="text-secondary">
                    <?= number_format($analytics['min_discount_rate'] ?: 0, 1) ?>% - <?= number_format($analytics['max_discount_rate'] ?: 0, 1) ?>%
                </h3>
                <small class="text-muted">
                    Min - Max rates
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Analysis -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Daily Discount Trends</h5>
            </div>
            <div class="card-body">
                <canvas id="discountTrendsChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Discount Types Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="discountTypesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Analysis Tabs -->
<ul class="nav nav-tabs mb-3" id="analysisTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab">
            <i class="bi bi-calendar-day"></i> Daily Breakdown
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="types-tab" data-bs-toggle="tab" data-bs-target="#types" type="button" role="tab">
            <i class="bi bi-tags"></i> Discount Types
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="items-tab" data-bs-toggle="tab" data-bs-target="#items" type="button" role="tab">
            <i class="bi bi-box"></i> Top Discounted Items
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="patterns-tab" data-bs-toggle="tab" data-bs-target="#patterns" type="button" role="tab">
            <i class="bi bi-graph-up"></i> Time Patterns
        </button>
    </li>
</ul>

<div class="tab-content" id="analysisTabContent">
    <!-- Daily Breakdown Tab -->
    <div class="tab-pane fade show active" id="daily" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Daily Discount Performance</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Discount Orders</th>
                                <th>Total Discount Amount</th>
                                <th>Original Revenue</th>
                                <th>Impact %</th>
                                <th>Avg Rate</th>
                                <th>Types Used</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daily_trends)): ?>
                                <?php foreach ($daily_trends as $day): 
                                    $day_impact = $day['daily_original_revenue'] > 0 ? 
                                        ($day['daily_discount_amount'] / $day['daily_original_revenue'] * 100) : 0;
                                ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($day['discount_date'])) ?></td>
                                    <td><?= number_format($day['daily_discount_orders']) ?></td>
                                    <td class="text-danger">₱<?= number_format($day['daily_discount_amount'], 2) ?></td>
                                    <td>₱<?= number_format($day['daily_original_revenue'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $day_impact > 15 ? 'danger' : ($day_impact > 10 ? 'warning' : 'info') ?>">
                                            <?= number_format($day_impact, 1) ?>%
                                        </span>
                                    </td>
                                    <td><?= number_format($day['daily_avg_rate'], 1) ?>%</td>
                                    <td><?= $day['discount_types_used'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No discount data available for the selected period</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Discount Types Tab -->
    <div class="tab-pane fade" id="types" role="tabpanel">
        <div class="row">
            <?php foreach ($discount_types as $type): ?>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header bg-<?= $type['type_impact'] > 15 ? 'danger' : ($type['type_impact'] > 10 ? 'warning' : 'info') ?> text-white">
                        <h6 class="mb-0"><?= ucfirst($type['discount_type']) ?> Discounts</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <strong>Orders:</strong> <?= number_format($type['type_orders']) ?><br>
                                <strong>Total Lost:</strong> ₱<?= number_format($type['type_total_discount'], 2) ?><br>
                                <strong>Impact:</strong> <?= number_format($type['type_impact'], 1) ?>%
                            </div>
                            <div class="col-6">
                                <strong>Avg Rate:</strong> <?= number_format($type['type_avg_percentage'], 1) ?>%<br>
                                <strong>Range:</strong> <?= number_format($type['type_min_percentage'], 1) ?>% - <?= number_format($type['type_max_percentage'], 1) ?>%<br>
                                <strong>Active Days:</strong> <?= $type['type_active_days'] ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Top Discounted Items Tab -->
    <div class="tab-pane fade" id="items" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Top Items by Discount Amount</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Item Name</th>
                                <th>Regular Price</th>
                                <th>Discounted Orders</th>
                                <th>Quantity Sold</th>
                                <th>Discount Amount</th>
                                <th>Avg Discount Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_discounted_items as $index => $item): ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?= $index + 1 ?></span></td>
                                <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                                <td>₱<?= number_format($item['item_price'], 2) ?></td>
                                <td><?= number_format($item['discounted_orders']) ?></td>
                                <td><?= number_format($item['total_quantity']) ?></td>
                                <td class="text-danger">₱<?= number_format($item['discount_amount'], 2) ?></td>
                                <td><?= number_format($item['avg_discount_rate'], 1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Time Patterns Tab -->
    <div class="tab-pane fade" id="patterns" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Hourly Discount Patterns</h6>
            </div>
            <div class="card-body">
                <canvas id="hourlyPatternsChart"></canvas>
                <div class="mt-3">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Hour</th>
                                    <th>Discount Orders</th>
                                    <th>Total Discount Amount</th>
                                    <th>Avg Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hourly_patterns as $hour): ?>
                                <tr>
                                    <td><?= date('g:00 A', strtotime($hour['discount_hour'] . ':00')) ?></td>
                                    <td><?= number_format($hour['hourly_orders']) ?></td>
                                    <td>₱<?= number_format($hour['hourly_discount_amount'], 2) ?></td>
                                    <td><?= number_format($hour['hourly_avg_rate'], 1) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Toggle custom date inputs
function toggleCustomDates() {
    const dateRange = document.getElementById('date_range').value;
    const startCol = document.getElementById('start_date_col');
    const endCol = document.getElementById('end_date_col');
    
    if (dateRange === 'custom') {
        startCol.style.display = 'block';
        endCol.style.display = 'block';
    } else {
        startCol.style.display = 'none';
        endCol.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCustomDates();
});

// Daily Discount Trends Chart
const trendsCtx = document.getElementById('discountTrendsChart').getContext('2d');
const trendsChart = new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: [<?php foreach ($daily_trends as $day) echo "'" . date('M d', strtotime($day['discount_date'])) . "',"; ?>],
        datasets: [{
            label: 'Discount Amount (₱)',
            data: [<?php foreach ($daily_trends as $day) echo $day['daily_discount_amount'] . ','; ?>],
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            borderColor: 'rgba(220, 53, 69, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Discount Types Pie Chart
const typesCtx = document.getElementById('discountTypesChart').getContext('2d');
const typesChart = new Chart(typesCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php foreach ($discount_types as $type) echo "'" . ucfirst($type['discount_type']) . "',"; ?>],
        datasets: [{
            data: [<?php foreach ($discount_types as $type) echo $type['type_total_discount'] . ','; ?>],
            backgroundColor: [
                'rgba(220, 53, 69, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(40, 167, 69, 0.8)',
                'rgba(0, 123, 255, 0.8)',
                'rgba(108, 117, 125, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ₱' + context.parsed.toLocaleString();
                    }
                }
            }
        }
    }
});

// Hourly Patterns Chart
const hourlyCtx = document.getElementById('hourlyPatternsChart').getContext('2d');
const hourlyChart = new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: [<?php foreach ($hourly_patterns as $hour) echo "'" . date('g A', strtotime($hour['discount_hour'] . ':00')) . "',"; ?>],
        datasets: [{
            label: 'Discount Amount (₱)',
            data: [<?php foreach ($hourly_patterns as $hour) echo $hour['hourly_discount_amount'] . ','; ?>],
            backgroundColor: 'rgba(255, 193, 7, 0.8)',
            borderColor: 'rgba(255, 193, 7, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Export functionality
document.getElementById('exportBtn').addEventListener('click', function() {
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Discount Analytics Report\n";
    csvContent += "Period: <?= date('F j, Y', strtotime($start_date)) ?> to <?= date('F j, Y', strtotime($end_date)) ?>\n\n";
    
    // Summary
    csvContent += "SUMMARY\n";
    csvContent += "Total Discount Orders,<?= $analytics['total_discount_orders'] ?: 0 ?>\n";
    csvContent += "Total Discount Amount,<?= $analytics['total_discount_amount'] ?: 0 ?>\n";
    csvContent += "Revenue Impact %,<?= number_format($revenue_impact, 2) ?>\n";
    csvContent += "Average Discount Rate %,<?= number_format($analytics['avg_discount_percentage'] ?: 0, 2) ?>\n\n";
    
    // Daily breakdown
    csvContent += "DAILY BREAKDOWN\n";
    csvContent += "Date,Orders,Discount Amount,Impact %\n";
    <?php foreach ($daily_trends as $day): ?>
    csvContent += "<?= $day['discount_date'] ?>,<?= $day['daily_discount_orders'] ?>,<?= $day['daily_discount_amount'] ?>,<?= number_format(($day['daily_original_revenue'] > 0 ? ($day['daily_discount_amount'] / $day['daily_original_revenue'] * 100) : 0), 2) ?>\n";
    <?php endforeach; ?>
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "discount_analytics_<?= $start_date ?>_to_<?= $end_date ?>.csv");
    document.body.appendChild(link);
    link.click();
});
</script>

<?php
require_once 'includes/footer.php';
$conn->close();
?>