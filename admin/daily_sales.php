<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get daily sales data with discount transparency (only paid orders)
$sql = "SELECT 
            DATE(o.created_at) as sale_date,
            COUNT(DISTINCT o.order_id) as total_orders,
            SUM(o.total_amount) as total_sales,
            SUM(COALESCE(o.original_amount, o.total_amount)) as original_sales,
            SUM(COALESCE(o.discount_amount, 0)) as total_discounts,
            SUM(CASE WHEN o.discount_type = 'senior_citizen' THEN COALESCE(o.discount_amount, 0) ELSE 0 END) as senior_discounts,
            SUM(CASE WHEN o.discount_type = 'pwd' THEN COALESCE(o.discount_amount, 0) ELSE 0 END) as pwd_discounts
        FROM 
            orders o
        WHERE 
            DATE(o.created_at) BETWEEN ? AND ?
            AND o.status_id = 2
        GROUP BY 
            DATE(o.created_at)
        ORDER BY 
            sale_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary statistics with discount transparency
$total_orders = 0;
$total_revenue = 0;
$total_original_revenue = 0;
$total_discounts = 0;
$total_senior_discounts = 0;
$total_pwd_discounts = 0;
$daily_data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $daily_data[] = $row;
        $total_orders += $row['total_orders'];
        $total_revenue += $row['total_sales'];
        $total_original_revenue += $row['original_sales'];
        $total_discounts += $row['total_discounts'];
        $total_senior_discounts += $row['senior_discounts'];
        $total_pwd_discounts += $row['pwd_discounts'];
    }
}

// Get top selling items
$sql_top_items = "SELECT 
                    m.name as item_name,
                    COUNT(oi.order_item_id) as order_count,
                    SUM(oi.subtotal) as total_revenue
                FROM 
                    order_items oi
                JOIN 
                    menu_items m ON oi.item_id = m.item_id
                JOIN 
                    orders o ON oi.order_id = o.order_id
                WHERE 
                    DATE(o.created_at) BETWEEN ? AND ?
                    AND o.status_id = 2
                GROUP BY 
                    oi.item_id
                ORDER BY 
                    order_count DESC
                LIMIT 5";

$stmt_top = $conn->prepare($sql_top_items);
$stmt_top->bind_param("ss", $start_date, $end_date);
$stmt_top->execute();
$result_top = $stmt_top->get_result();
?>

<div class="container-fluid p-4">
    <h2>Daily Sales Report</h2>
    
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
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h2 class="display-6"><?php echo $total_orders; ?></h2>
                    <p class="card-text">From <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2 class="display-6">₱<?php echo number_format($total_revenue, 2, '.', ','); ?></h2>
                    <p class="card-text">From <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Order Value</h5>
                    <h2 class="display-6">₱<?php echo $total_orders > 0 ? number_format($total_revenue / $total_orders, 2, '.', ',') : '0.00'; ?></h2>
                    <p class="card-text">From <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Simple Discount Info -->
    <?php if ($total_discounts > 0): ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-light border">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> 
                    Original Revenue: ₱<?php echo number_format($total_original_revenue, 2, '.', ','); ?> | 
                    Discounts Applied: -₱<?php echo number_format($total_discounts, 2, '.', ','); ?> | 
                    Net Revenue: ₱<?php echo number_format($total_revenue, 2, '.', ','); ?>
                </small>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Daily Sales Table -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Daily Sales Data</h5>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_data as $day): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($day['sale_date'])); ?></td>
                                    <td><?php echo $day['total_orders']; ?></td>
                                    <td>₱<?php echo number_format($day['total_sales'], 2, '.', ','); ?></td>
                                    <td>₱<?php echo number_format($day['total_sales'] / $day['total_orders'], 2, '.', ','); ?></td>
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
    
    <!-- Top Selling Items -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Top Selling Items</h5>
        </div>
        <div class="card-body">
            <?php if ($result_top->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $result_top->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo $item['order_count']; ?></td>
                                    <td>₱<?php echo number_format($item['total_revenue'], 2, '.', ','); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No item sales data available for the selected date range.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
