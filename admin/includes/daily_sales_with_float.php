<?php
// Enhanced daily_sales.php with cash float integration
require_once 'includes/db_connection.php';
require_once 'includes/header.php';
require_once 'includes/cash_float_functions.php';

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get today's cash float status
$today = date('Y-m-d');
$current_float = getCashFloat($conn, $today);

// Get daily sales data with cash float information
$sql = "SELECT 
            DATE(o.created_at) as sale_date,
            COUNT(DISTINCT o.order_id) as total_orders,
            SUM(o.total_amount) as total_sales,
            cf.opening_amount,
            cf.closing_amount,
            cf.status as float_status
        FROM 
            orders o
        LEFT JOIN 
            cash_float cf ON DATE(o.created_at) = cf.date
        WHERE 
            DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY 
            DATE(o.created_at), cf.float_id
        ORDER BY 
            sale_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary statistics
$total_orders = 0;
$total_revenue = 0;
$daily_data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $daily_data[] = $row;
        $total_orders += $row['total_orders'];
        $total_revenue += $row['total_sales'];
    }
}
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Daily Sales Report</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="cash_float_management.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-cash-coin"></i> Cash Float Management
                </a>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                <i class="bi bi-arrow-repeat"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Cash Float Alert for Today -->
    <?php if (!$current_float['success'] && $today >= $start_date && $today <= $end_date): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Cash Float Not Set!</strong> No cash float has been set for today (<?php echo date('M d, Y'); ?>). 
            <a href="cash_float_management.php" class="alert-link">Set cash float now</a> to track daily cash management.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Date Filter Form -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Filter by Date</h5>
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    <a href="daily_sales.php" class="btn btn-outline-secondary ms-2">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h2 class="display-6"><?php echo $total_orders; ?></h2>
                    <p class="card-text">From <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2 class="display-6">₱<?php echo number_format($total_revenue, 2); ?></h2>
                    <p class="card-text">From <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Order Value</h5>
                    <h2 class="display-6">₱<?php echo $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00'; ?></h2>
                    <p class="card-text">From <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Cash Float Status</h5>
                    <h2 class="display-6">
                        <?php if ($current_float['success']): ?>
                            <i class="bi bi-check-circle"></i>
                        <?php else: ?>
                            <i class="bi bi-exclamation-circle"></i>
                        <?php endif; ?>
                    </h2>
                    <p class="card-text">
                        <?php if ($current_float['success']): ?>
                            Set: ₱<?php echo number_format($current_float['data']['opening_amount'], 2); ?>
                        <?php else: ?>
                            Not Set for Today
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Daily Sales Table with Cash Float Information -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Daily Sales Data with Cash Float</h5>
        </div>
        <div class="card-body">
            <?php if (count($daily_data) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th>Average Order</th>
                                <th>Opening Float</th>
                                <th>Expected Closing</th>
                                <th>Actual Closing</th>
                                <th>Variance</th>
                                <th>Float Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_data as $day): ?>
                                <?php
                                $expected_closing = $day['opening_amount'] ? $day['opening_amount'] + $day['total_sales'] : null;
                                $variance = ($day['closing_amount'] !== null && $expected_closing !== null) ? 
                                           $day['closing_amount'] - $expected_closing : null;
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($day['sale_date'])); ?></td>
                                    <td><?php echo $day['total_orders']; ?></td>
                                    <td>₱<?php echo number_format($day['total_sales'], 2); ?></td>
                                    <td>₱<?php echo number_format($day['total_sales'] / $day['total_orders'], 2); ?></td>
                                    <td>
                                        <?php if ($day['opening_amount'] !== null): ?>
                                            ₱<?php echo number_format($day['opening_amount'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not Set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($expected_closing !== null): ?>
                                            ₱<?php echo number_format($expected_closing, 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($day['closing_amount'] !== null): ?>
                                            ₱<?php echo number_format($day['closing_amount'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not Closed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($variance !== null): ?>
                                            <span class="<?php echo $variance >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $variance >= 0 ? '+' : ''; ?>₱<?php echo number_format($variance, 2); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($day['float_status']): ?>
                                            <span class="badge bg-<?php echo $day['float_status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($day['float_status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Not Set</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No sales data available for the selected date range.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>