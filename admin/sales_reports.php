<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Get report type and date range
$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $start_date = date('Y-m-d');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $end_date = date('Y-m-d');
}

// Ensure end_date is not before start_date
if (strtotime($end_date) < strtotime($start_date)) {
    $end_date = $start_date;
}

// Get sales data based on report type
$sql = "";
$chart_labels = [];
$chart_data = [];

switch ($report_type) {
    case 'daily':
        $sql = "SELECT date, total_orders, total_sales 
                FROM daily_sales 
                WHERE date BETWEEN '$start_date' AND '$end_date' 
                ORDER BY date ASC";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $chart_labels[] = date('M d', strtotime($row['date']));
            $chart_data[] = $row['total_sales'];
        }
        break;
        
    case 'monthly':
        $sql = "SELECT DATE_FORMAT(date, '%Y-%m') as month, 
                       SUM(total_orders) as total_orders, 
                       SUM(total_sales) as total_sales 
                FROM daily_sales 
                WHERE date BETWEEN '$start_date' AND '$end_date' 
                GROUP BY DATE_FORMAT(date, '%Y-%m') 
                ORDER BY month ASC";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $chart_labels[] = date('M Y', strtotime($row['month'] . '-01'));
            $chart_data[] = $row['total_sales'];
        }
        break;
        
    case 'yearly':
        $sql = "SELECT DATE_FORMAT(date, '%Y') as year, 
                       SUM(total_orders) as total_orders, 
                       SUM(total_sales) as total_sales 
                FROM daily_sales 
                WHERE date BETWEEN '$start_date' AND '$end_date' 
                GROUP BY DATE_FORMAT(date, '%Y') 
                ORDER BY year ASC";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $chart_labels[] = $row['year'];
            $chart_data[] = $row['total_sales'];
        }
        break;
        
    default:
        $sql = "SELECT date, total_orders, total_sales 
                FROM daily_sales 
                WHERE date BETWEEN '$start_date' AND '$end_date' 
                ORDER BY date ASC";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $chart_labels[] = date('M d', strtotime($row['date']));
            $chart_data[] = $row['total_sales'];
        }
}

// Calculate totals
$total_orders = 0;
$total_sales = 0;
$avg_order_value = 0;

if ($result->num_rows > 0) {
    $result->data_seek(0); // Reset result pointer
    while ($row = $result->fetch_assoc()) {
        $total_orders += $row['total_orders'];
        $total_sales += $row['total_sales'];
    }
    
    if ($total_orders > 0) {
        $avg_order_value = $total_sales / $total_orders;
    }
}

// Reset result pointer
if ($result->num_rows > 0) {
    $result->data_seek(0);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Sales Reports</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Report
        </button>
    </div>
</div>

<!-- Report Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-3">
                <label for="type" class="form-label">Report Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="daily" <?= $report_type == 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="monthly" <?= $report_type == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="yearly" <?= $report_type == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Report Summary -->
<div class="row mb-4">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Total Orders</h6>
                <h2 class="display-4"><?= number_format($total_orders) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Total Sales</h6>
                <h2 class="display-4">₱<?= number_format($total_sales, 2) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Average Order Value</h6>
                <h2 class="display-4">₱<?= number_format($avg_order_value, 2) ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Sales Chart -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Sales Trend</h5>
    </div>
    <div class="card-body">
        <div class="chart-container">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
</div>

<!-- Detailed Sales Data -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Detailed Sales Data</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <?php if ($report_type == 'daily') { ?>
                            <th>Date</th>
                        <?php } elseif ($report_type == 'monthly') { ?>
                            <th>Month</th>
                        <?php } else { ?>
                            <th>Year</th>
                        <?php } ?>
                        <th>Orders</th>
                        <th>Sales</th>
                        <th>Average Order</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $avg = $row['total_orders'] > 0 ? $row['total_sales'] / $row['total_orders'] : 0;
                            ?>
                            <tr>
                                <?php if ($report_type == 'daily') { ?>
                                    <td><?= date('F j, Y', strtotime($row['date'])) ?></td>
                                <?php } elseif ($report_type == 'monthly') { ?>
                                    <td><?= date('F Y', strtotime($row['month'] . '-01')) ?></td>
                                <?php } else { ?>
                                    <td><?= $row['year'] ?></td>
                                <?php } ?>
                                <td><?= number_format($row['total_orders']) ?></td>
                                <td>₱<?= number_format($row['total_sales'], 2) ?></td>
                                <td>₱<?= number_format($avg, 2) ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="4" class="text-center">No sales data available for the selected period</td></tr>';
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td><?= number_format($total_orders) ?></td>
                        <td>₱<?= number_format($total_sales, 2) ?></td>
                        <td>₱<?= number_format($avg_order_value, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
// Sales Chart
var ctx = document.getElementById('salesChart').getContext('2d');
var salesChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Sales (₱)',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: 'rgba(78, 115, 223, 0.8)',
            borderColor: 'rgba(78, 115, 223, 1)',
            borderWidth: 1
        }]
    },
    options: {
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
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Sales: ₱' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php
require_once 'includes/footer.php';
$conn->close();
?>