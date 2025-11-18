<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Initialize variables
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : '';

$report_data = [];
$summary_data = [];

// Process report generation
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($report_type)) {
    switch ($report_type) {
        case 'daily':
            if (!empty($start_date) && !empty($end_date)) {
                // Generate daily sales report with discount transparency from orders table
                $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as total_orders,
                        COALESCE(SUM(total_amount), 0) as total_sales,
                        COALESCE(SUM(original_amount), SUM(total_amount)) as original_sales,
                        COALESCE(SUM(discount_amount), 0) as total_discounts,
                        COUNT(CASE WHEN status_id = 2 THEN 1 END) as paid_orders,
                        COUNT(CASE WHEN status_id = 5 THEN 1 END) as completed_orders,
                        COALESCE(SUM(CASE WHEN status_id = 2 THEN total_amount ELSE 0 END), 0) as actual_sales
                        FROM orders 
                        WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    $report_data[] = $row;
                }
                
                // Summary
                $summary_sql = "SELECT 
                    COUNT(DISTINCT DATE(created_at)) as total_days,
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_sales,
                    COALESCE(AVG(total_amount), 0) as avg_order_value,
                    COALESCE(SUM(CASE WHEN status_id IN (2, 5) THEN total_amount ELSE 0 END), 0) as actual_sales,
                    COUNT(CASE WHEN status_id = 2 THEN 1 END) as successful_orders
                    FROM orders 
                    WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
                $summary_result = $conn->query($summary_sql);
                $summary_data = $summary_result->fetch_assoc();
            }
            break;
            
        case 'monthly':
            if (!empty($year)) {
                // Generate monthly sales report directly from orders table
                $sql = "SELECT 
                        MONTH(created_at) as month,
                        MONTHNAME(created_at) as month_name,
                        COUNT(*) as total_orders,
                        COALESCE(SUM(total_amount), 0) as total_sales,
                        COUNT(CASE WHEN status_id = 2 THEN 1 END) as paid_orders,
                        COUNT(CASE WHEN status_id = 5 THEN 1 END) as completed_orders,
                        COALESCE(SUM(CASE WHEN status_id = 2 THEN total_amount ELSE 0 END), 0) as actual_sales
                        FROM orders 
                        WHERE YEAR(created_at) = '$year'
                        GROUP BY MONTH(created_at), MONTHNAME(created_at)
                        ORDER BY month ASC";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    $report_data[] = $row;
                }
                
                // Summary
                $summary_sql = "SELECT 
                    COUNT(DISTINCT MONTH(created_at)) as total_months,
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_sales,
                    COALESCE(AVG(total_amount), 0) as avg_order_value,
                    COALESCE(SUM(CASE WHEN status_id IN (2, 5) THEN total_amount ELSE 0 END), 0) as actual_sales,
                    COUNT(CASE WHEN status_id = 2 THEN 1 END) as successful_orders
                    FROM orders 
                    WHERE YEAR(created_at) = '$year'";
                $summary_result = $conn->query($summary_sql);
                $summary_data = $summary_result->fetch_assoc();
            }
            break;
            
        case 'yearly':
            // Generate yearly sales report directly from orders table
            $sql = "SELECT 
                    YEAR(created_at) as year,
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_sales,
                    COUNT(CASE WHEN status_id = 2 THEN 1 END) as paid_orders,
                    COUNT(CASE WHEN status_id = 5 THEN 1 END) as completed_orders,
                    COALESCE(SUM(CASE WHEN status_id IN (2, 5) THEN total_amount ELSE 0 END), 0) as actual_sales
                    FROM orders 
                    GROUP BY YEAR(created_at)
                    ORDER BY year ASC";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
            
            // Summary
            $summary_sql = "SELECT 
                COUNT(DISTINCT YEAR(created_at)) as total_years,
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_sales,
                COALESCE(AVG(total_amount), 0) as avg_order_value,
                COALESCE(SUM(CASE WHEN status_id IN (2, 5) THEN total_amount ELSE 0 END), 0) as actual_sales,
                COUNT(CASE WHEN status_id IN (2, 5) THEN 1 END) as successful_orders
                FROM orders";
            $summary_result = $conn->query($summary_sql);
            $summary_data = $summary_result->fetch_assoc();
            break;
            
        case 'order_analysis':
            if (!empty($start_date) && !empty($end_date)) {
                // Order status analysis
                $sql = "SELECT os.name as status_name, COUNT(o.order_id) as order_count, 
                        SUM(o.total_amount) as total_amount
                        FROM orders o 
                        JOIN order_statuses os ON o.status_id = os.status_id
                        WHERE DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'
                        GROUP BY o.status_id, os.name
                        ORDER BY order_count DESC";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    $report_data[] = $row;
                }
                
                // Summary
                $summary_sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_sales,
                    AVG(total_amount) as avg_order_value,
                    MAX(total_amount) as max_order_value,
                    MIN(total_amount) as min_order_value
                    FROM orders 
                    WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
                $summary_result = $conn->query($summary_sql);
                $summary_data = $summary_result->fetch_assoc();
            }
            break;
            
        case 'popular_items':
            if (!empty($start_date) && !empty($end_date)) {
                $sql = "SELECT mi.name, mi.price, 
                        COUNT(oi.item_id) as order_count,
                        SUM(oi.quantity) as total_quantity,
                        SUM(oi.subtotal) as total_revenue
                        FROM order_items oi
                        JOIN menu_items mi ON oi.item_id = mi.item_id
                        JOIN orders o ON oi.order_id = o.order_id
                        WHERE DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'
                        GROUP BY oi.item_id, mi.name, mi.price
                        ORDER BY total_quantity DESC
                        LIMIT 20";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    $report_data[] = $row;
                }
            }
            break;
    }
}

// Get available years for dropdown
$years_sql = "SELECT DISTINCT year FROM yearly_sales ORDER BY year DESC";
$years_result = $conn->query($years_sql);
$available_years = [];
while ($row = $years_result->fetch_assoc()) {
    $available_years[] = $row['year'];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Generate Reports</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="index.php" class="btn btn-sm btn-outline-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>

<!-- Discount Summary (if applicable) -->
<?php if (!empty($report_data) && isset($report_data[0]['total_discounts'])): ?>
<?php
$total_original = 0;
$total_discounts = 0;
$total_net = 0;
foreach ($report_data as $row) {
    $total_original += $row['original_sales'];
    $total_discounts += $row['total_discounts'];
    $total_net += $row['total_sales'];
}
?>
<?php if ($total_discounts > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-light border">
            <h6 class="mb-2"><i class="bi bi-tag"></i> Discount Summary</h6>
            <div class="row text-center">
                <div class="col-md-4">
                    <small class="text-muted">Original Revenue</small><br>
                    <strong>₱<?= number_format($total_original, 2) ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Total Discounts</small><br>
                    <strong class="text-danger">-₱<?= number_format($total_discounts, 2) ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Net Revenue</small><br>
                    <strong>₱<?= number_format($total_net, 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Report Generation Form -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold">Report Parameters</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="report_type">Report Type</label>
                        <select class="form-control" id="report_type" name="report_type" required>
                            <option value="">Select Report Type</option>
                            <option value="daily" <?= $report_type === 'daily' ? 'selected' : '' ?>>Daily Sales Report</option>
                            <option value="monthly" <?= $report_type === 'monthly' ? 'selected' : '' ?>>Monthly Sales Report</option>
                            <option value="yearly" <?= $report_type === 'yearly' ? 'selected' : '' ?>>Yearly Sales Report</option>
                            <option value="order_analysis" <?= $report_type === 'order_analysis' ? 'selected' : '' ?>>Order Analysis</option>
                            <option value="popular_items" <?= $report_type === 'popular_items' ? 'selected' : '' ?>>Popular Items</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3" id="date_range_div" style="display: none;">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                    </div>
                </div>
                
                <div class="col-md-3" id="end_date_div" style="display: none;">
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                    </div>
                </div>
                
                <div class="col-md-3" id="year_div" style="display: none;">
                    <div class="form-group">
                        <label for="year">Year</label>
                        <select class="form-control" id="year" name="year">
                            <?php foreach ($available_years as $available_year): ?>
                                <option value="<?= $available_year ?>" <?= $year == $available_year ? 'selected' : '' ?>><?= $available_year ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-file-earmark-text"></i> Generate Report
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportReport()" <?= empty($report_data) ? 'disabled' : '' ?>>
                        <i class="bi bi-download"></i> Export to CSV
                    </button>
                    <button type="button" class="btn btn-info" onclick="printReport()" <?= empty($report_data) ? 'disabled' : '' ?>>
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($report_data)): ?>
    <!-- Summary Cards -->
    <?php if (!empty($summary_data)): ?>
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card sales h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Sales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($summary_data['total_sales'], 2) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-dollar text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card orders h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary_data['total_orders']) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cart-check text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card items h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Average Sales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($summary_data['avg_daily_sales'] ?? $summary_data['avg_monthly_sales'] ?? $summary_data['avg_yearly_sales'] ?? 0, 2) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-up text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card customers h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Peak Sales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($summary_data['max_daily_sales'] ?? $summary_data['max_monthly_sales'] ?? $summary_data['max_yearly_sales'] ?? 0, 2) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-trophy text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Report Results -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold">
                <?php
                switch ($report_type) {
                    case 'daily': echo 'Daily Sales Report'; break;
                    case 'monthly': echo 'Monthly Sales Report for ' . $year; break;
                    case 'yearly': echo 'Yearly Sales Report'; break;
                    case 'order_analysis': echo 'Order Analysis Report'; break;
                    case 'popular_items': echo 'Popular Items Report'; break;
                }
                ?>
            </h6>
            <div class="text-muted small">
                Generated on <?= date('M d, Y h:i A') ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="reportTable">
                    <thead>
                        <tr>
                            <?php
                            switch ($report_type) {
                                case 'daily':
                                    echo '<th>Date</th><th>Orders</th><th>Sales</th><th>Avg Order Value</th>';
                                    break;
                                case 'monthly':
                                    echo '<th>Month</th><th>Orders</th><th>Sales</th><th>Avg Daily Sales</th>';
                                    break;
                                case 'yearly':
                                    echo '<th>Year</th><th>Orders</th><th>Sales</th><th>Avg Monthly Sales</th>';
                                    break;
                                case 'order_analysis':
                                    echo '<th>Status</th><th>Order Count</th><th>Total Amount</th><th>Percentage</th>';
                                    break;
                                case 'popular_items':
                                    echo '<th>Item Name</th><th>Price</th><th>Orders</th><th>Quantity Sold</th><th>Revenue</th>';
                                    break;
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_orders = 0;
                        $total_sales = 0;
                        
                        foreach ($report_data as $row) {
                            echo '<tr>';
                            
                            switch ($report_type) {
                                case 'daily':
                                    $avg_order_value = $row['total_orders'] > 0 ? $row['total_sales'] / $row['total_orders'] : 0;
                                    echo '<td>' . date('M d, Y', strtotime($row['date'])) . '</td>';
                                    echo '<td>' . number_format($row['total_orders']) . '</td>';
                                    echo '<td>₱' . number_format($row['total_sales'], 2) . '</td>';
                                    echo '<td>₱' . number_format($avg_order_value, 2) . '</td>';
                                    $total_orders += $row['total_orders'];
                                    $total_sales += $row['total_sales'];
                                    break;
                                    
                                case 'monthly':
                                    $avg_daily = $row['total_sales'] / 30; // Approximate
                                    echo '<td>' . $row['month_name'] . '</td>';
                                    echo '<td>' . number_format($row['total_orders']) . '</td>';
                                    echo '<td>₱' . number_format($row['total_sales'], 2) . '</td>';
                                    echo '<td>₱' . number_format($avg_daily, 2) . '</td>';
                                    $total_orders += $row['total_orders'];
                                    $total_sales += $row['total_sales'];
                                    break;
                                    
                                case 'yearly':
                                    $avg_monthly = $row['total_sales'] / 12;
                                    echo '<td>' . $row['year'] . '</td>';
                                    echo '<td>' . number_format($row['total_orders']) . '</td>';
                                    echo '<td>₱' . number_format($row['total_sales'], 2) . '</td>';
                                    echo '<td>₱' . number_format($avg_monthly, 2) . '</td>';
                                    $total_orders += $row['total_orders'];
                                    $total_sales += $row['total_sales'];
                                    break;
                                    
                                case 'order_analysis':
                                    $percentage = $summary_data['total_orders'] > 0 ? ($row['order_count'] / $summary_data['total_orders']) * 100 : 0;
                                    echo '<td>' . ucfirst($row['status_name']) . '</td>';
                                    echo '<td>' . number_format($row['order_count']) . '</td>';
                                    echo '<td>₱' . number_format($row['total_amount'], 2) . '</td>';
                                    echo '<td>' . number_format($percentage, 1) . '%</td>';
                                    break;
                                    
                                case 'popular_items':
                                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                                    echo '<td>₱' . number_format($row['price'], 2) . '</td>';
                                    echo '<td>' . number_format($row['order_count']) . '</td>';
                                    echo '<td>' . number_format($row['total_quantity']) . '</td>';
                                    echo '<td>₱' . number_format($row['total_revenue'], 2) . '</td>';
                                    break;
                            }
                            
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                    <?php if (in_array($report_type, ['daily', 'monthly', 'yearly'])): ?>
                    <tfoot>
                        <tr class="table-info">
                            <th>Total</th>
                            <th><?= number_format($total_orders) ?></th>
                            <th>₱<?= number_format($total_sales, 2) ?></th>
                            <th>-</th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Show/hide form fields based on report type
document.getElementById('report_type').addEventListener('change', function() {
    const reportType = this.value;
    const dateRangeDiv = document.getElementById('date_range_div');
    const endDateDiv = document.getElementById('end_date_div');
    const yearDiv = document.getElementById('year_div');
    
    // Hide all optional fields first
    dateRangeDiv.style.display = 'none';
    endDateDiv.style.display = 'none';
    yearDiv.style.display = 'none';
    
    // Show relevant fields based on report type
    if (['daily', 'order_analysis', 'popular_items'].includes(reportType)) {
        dateRangeDiv.style.display = 'block';
        endDateDiv.style.display = 'block';
    } else if (reportType === 'monthly') {
        yearDiv.style.display = 'block';
    }
});

// Initialize form fields on page load
document.addEventListener('DOMContentLoaded', function() {
    const reportType = document.getElementById('report_type').value;
    if (reportType) {
        document.getElementById('report_type').dispatchEvent(new Event('change'));
    }
});

// Export to CSV function
function exportReport() {
    const table = document.getElementById('reportTable');
    const reportType = document.getElementById('report_type').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const year = document.getElementById('year').value;
    
    let filename = reportType + '_report';
    if (startDate && endDate) {
        filename += '_' + startDate + '_to_' + endDate;
    } else if (year) {
        filename += '_' + year;
    }
    filename += '_' + new Date().toISOString().split('T')[0] + '.csv';
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        
        csv.push(row.join(','));
    }
    
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Print report function
function printReport() {
    const printContent = document.querySelector('.card.shadow.mb-4:last-of-type').outerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <html>
            <head>
                <title>Report - ${document.getElementById('report_type').selectedOptions[0].text}</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
                <style>
                    @media print {
                        .btn { display: none !important; }
                        .card-header .text-muted { display: none !important; }
                    }
                </style>
            </head>
            <body>
                ${printContent}
            </body>
        </html>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    window.location.reload();
}
</script>

<?php
require_once 'includes/footer.php';
$conn->close();
?>

