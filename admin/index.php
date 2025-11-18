<?php
// Error handling - prevent fatal errors from killing page output
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Get today's date
$today = date('Y-m-d');
$current_month = date('Y-m');
$current_year = date('Y');

// Get daily sales from orders table with discount transparency (only paid orders)
$sql_daily = "SELECT COUNT(*) as total_orders, 
                     COALESCE(SUM(total_amount), 0) as total_sales,
                     COALESCE(SUM(original_amount), SUM(total_amount)) as original_sales,
                     COALESCE(SUM(discount_amount), 0) as total_discounts
              FROM orders 
              WHERE DATE(created_at) = '$today' AND status_id = 2";
$result_daily = $conn->query($sql_daily);
if (!$result_daily) {
    error_log("DB ERROR in daily sales: " . $conn->error);
    $daily_sales = ['total_orders' => 0, 'total_sales' => 0, 'original_sales' => 0, 'total_discounts' => 0];
} else {
    $daily_sales = $result_daily->fetch_assoc() ?: ['total_orders' => 0, 'total_sales' => 0, 'original_sales' => 0, 'total_discounts' => 0];
}

// Get monthly sales from orders table with discount transparency (only paid orders)
$sql_monthly = "SELECT COUNT(*) as total_orders, 
                       COALESCE(SUM(total_amount), 0) as total_sales,
                       COALESCE(SUM(original_amount), SUM(total_amount)) as original_sales,
                       COALESCE(SUM(discount_amount), 0) as total_discounts
                FROM orders 
                WHERE DATE_FORMAT(created_at, '%Y-%m') = '$current_month' AND status_id = 2";
$result_monthly = $conn->query($sql_monthly);
if (!$result_monthly) {
    error_log("DB ERROR in monthly sales: " . $conn->error);
    $monthly_sales = ['total_orders' => 0, 'total_sales' => 0, 'original_sales' => 0, 'total_discounts' => 0];
} else {
    $monthly_sales = $result_monthly->fetch_assoc() ?: ['total_orders' => 0, 'total_sales' => 0, 'original_sales' => 0, 'total_discounts' => 0];
}

// Get yearly sales from orders table with discount transparency (only paid orders)
$sql_yearly = "SELECT COUNT(*) as total_orders, 
                      COALESCE(SUM(total_amount), 0) as total_sales,
                      COALESCE(SUM(original_amount), SUM(total_amount)) as original_sales,
                      COALESCE(SUM(discount_amount), 0) as total_discounts
               FROM orders 
               WHERE DATE_FORMAT(created_at, '%Y') = '$current_year' AND status_id = 2";
$result_yearly = $conn->query($sql_yearly);
if (!$result_yearly) {
    error_log("DB ERROR in yearly sales: " . $conn->error);
    $yearly_sales = ['total_orders' => 0, 'total_sales' => 0, 'original_sales' => 0, 'total_discounts' => 0];
} else {
    $yearly_sales = $result_yearly->fetch_assoc() ?: ['total_orders' => 0, 'total_sales' => 0, 'original_sales' => 0, 'total_discounts' => 0];
}

// Get total menu items
$sql_items = "SELECT COUNT(*) as total_items FROM menu_items WHERE is_available = 1";
$result_items = $conn->query($sql_items);
if (!$result_items) {
    error_log("DB ERROR in menu items: " . $conn->error);
    $total_items = 0;
} else {
    $row = $result_items->fetch_assoc();
    $total_items = $row ? (int)$row['total_items'] : 0;
}

// Get booking statistics
$sql_bookings = "SELECT 
                    COUNT(*) as total_bookings,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
                    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
                    COUNT(CASE WHEN DATE(reservation_date) = '$today' THEN 1 END) as today_bookings
                 FROM reservations";
$result_bookings = $conn->query($sql_bookings);
if (!$result_bookings) {
    error_log("DB ERROR in bookings: " . $conn->error);
    $booking_stats = ['total_bookings' => 0, 'pending_bookings' => 0, 'confirmed_bookings' => 0, 'today_bookings' => 0];
} else {
    $booking_stats = $result_bookings->fetch_assoc() ?: ['total_bookings' => 0, 'pending_bookings' => 0, 'confirmed_bookings' => 0, 'today_bookings' => 0];
}

// Get popular venues
$sql_venues = "SELECT v.venue_name, COUNT(r.reservation_id) as booking_count
               FROM venues v
               LEFT JOIN reservations r ON v.venue_id = r.venue_id 
               WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
               GROUP BY v.venue_id, v.venue_name
               ORDER BY booking_count DESC
               LIMIT 3";
$result_venues = $conn->query($sql_venues);
if (!$result_venues) {
    error_log("DB ERROR in venues: " . $conn->error);
    // Create a mock empty result object
    $result_venues = (object)['num_rows' => 0];
}

// Get recent orders (including QR orders)
$sql_recent_orders = "SELECT o.*, os.name as status_name, t.table_number 
                      FROM orders o 
                      LEFT JOIN tables t ON o.table_id = t.table_id 
                      JOIN order_statuses os ON o.status_id = os.status_id 
                      WHERE o.status_id IN (2, 3, 4, 5)  -- Show paid and processed orders (but sales only count paid)
                      ORDER BY o.created_at DESC LIMIT 10";
$result_recent_orders = $conn->query($sql_recent_orders);
if (!$result_recent_orders) {
    error_log("DB ERROR in recent orders: " . $conn->error);
    // Create a mock empty result object
    $result_recent_orders = (object)['num_rows' => 0];
}

// Get popular items (including QR orders)
$sql_popular = "SELECT mi.name, 
                       (COUNT(DISTINCT oi.order_item_id) + COUNT(DISTINCT qo.order_id)) as order_count,
                       (COALESCE(SUM(oi.quantity), 0) + COALESCE(SUM(qo.quantity), 0)) as total_quantity
                FROM menu_items mi
                LEFT JOIN order_items oi ON mi.item_id = oi.item_id
                LEFT JOIN orders o ON oi.order_id = o.order_id AND DATE(o.created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                LEFT JOIN qr_orders qo ON mi.item_id = qo.menu_item_id
                LEFT JOIN qr_sessions qs ON qo.session_id = qs.session_id AND DATE(qs.created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                WHERE (oi.order_item_id IS NOT NULL OR qo.order_id IS NOT NULL)
                GROUP BY mi.item_id, mi.name
                HAVING total_quantity > 0
                ORDER BY total_quantity DESC
                LIMIT 5";
$result_popular = $conn->query($sql_popular);
if (!$result_popular) {
    error_log("DB ERROR in popular items: " . $conn->error);
    // Create a mock empty result object
    $result_popular = (object)['num_rows' => 0];
}

// Get sales data for the last 7 days for chart from orders table
$sql_chart = "SELECT DATE(created_at) as date, COALESCE(SUM(total_amount), 0) as total_sales 
              FROM orders 
              WHERE DATE(created_at) BETWEEN DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY) AND CURRENT_DATE 
              AND status_id = 2
              GROUP BY DATE(created_at)
              ORDER BY date ASC";
$result_chart = $conn->query($sql_chart);
$chart_dates = [];
$chart_sales = [];

if ($result_chart) {
    while ($row = $result_chart->fetch_assoc()) {
        $chart_dates[] = date('M d', strtotime($row['date']));
        $chart_sales[] = $row['total_sales'];
    }
} else {
    error_log("DB ERROR in chart data: " . $conn->error);
}

// Fill in missing dates with zero values
$date_range = [];
$sales_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $formatted_date = date('M d', strtotime($date));
    $date_range[] = $formatted_date;
    
    $found = false;
    foreach ($chart_dates as $index => $chart_date) {
        if ($chart_date == $formatted_date) {
            $sales_data[] = $chart_sales[$index];
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $sales_data[] = 0;
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="sales_reports.php" class="btn btn-sm btn-outline-secondary">Detailed Reports</a>
            <a href="generate_reports.php" class="btn btn-sm btn-outline-primary">Generate Reports</a>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
            <i class="bi bi-arrow-repeat"></i> Refresh
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card sales h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Today's Sales</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($daily_sales['total_sales'], 2) ?></div>
                        <div class="text-muted small"><?= $daily_sales['total_orders'] ?> orders</div>
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
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Monthly Sales</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($monthly_sales['total_sales'], 2) ?></div>
                        <div class="text-muted small"><?= $monthly_sales['total_orders'] ?> orders</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-check text-gray-300 stat-icon"></i>
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
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Yearly Sales</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($yearly_sales['total_sales'], 2) ?></div>
                        <div class="text-muted small"><?= $yearly_sales['total_orders'] ?> orders</div>
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
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Menu Items</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_items ?></div>
                        <div class="text-muted small">Active items</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-list-ul text-gray-300 stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Statistics -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-check text-primary"></i> Booking Statistics
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-primary"><?= $booking_stats['total_bookings'] ?></div>
                            <div class="text-muted">Total Bookings</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-warning"><?= $booking_stats['pending_bookings'] ?></div>
                            <div class="text-muted">Pending</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-success"><?= $booking_stats['confirmed_bookings'] ?></div>
                            <div class="text-muted">Confirmed</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-info"><?= $booking_stats['today_bookings'] ?></div>
                            <div class="text-muted">Today's Bookings</div>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($result_venues) && is_object($result_venues) && method_exists($result_venues, 'fetch_assoc') && $result_venues->num_rows > 0): ?>
                <hr>
                <h6 class="text-muted mb-3">Popular Venues (Last 30 Days)</h6>
                <div class="row">
                    <?php 
                    if (method_exists($result_venues, 'fetch_assoc')) {
                        while ($venue = $result_venues->fetch_assoc()): 
                    ?>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                            <span><?= htmlspecialchars($venue['venue_name']) ?></span>
                            <span class="badge bg-primary"><?= $venue['booking_count'] ?> bookings</span>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Sales Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold">Sales Last 7 Days</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Items -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Popular Items (Last 30 Days)</h6>
            </div>
            <div class="card-body">
                <?php if (isset($result_popular) && is_object($result_popular) && method_exists($result_popular, 'fetch_assoc') && $result_popular->num_rows > 0) { ?>
                    <ul class="list-group list-group-flush">
                        <?php 
                        $colors = ['primary', 'success', 'danger', 'warning', 'info'];
                        $i = 0;
                        if (method_exists($result_popular, 'fetch_assoc')) {
                            while ($item = $result_popular->fetch_assoc()) { 
                            $color = $colors[$i % count($colors)];
                        ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($item['name']) ?>
                                <span class="badge bg-<?= $color ?> rounded-pill"><?= $item['total_quantity'] ?> sold</span>
                            </li>
                        <?php 
                                $i++;
                            } 
                        }
                        ?>
                    </ul>
                <?php } else { ?>
                    <p class="text-center text-muted">No sales data available</p>
                <?php } ?>
                <div class="text-center mt-3">
                    <a href="food_popularity.php" class="btn btn-sm btn-primary">View Full Report</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Discount Transparency Summary -->
<?php if ($daily_sales['total_discounts'] > 0 || $monthly_sales['total_discounts'] > 0 || $yearly_sales['total_discounts'] > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-light border">
            <h6 class="mb-2"><i class="bi bi-tag"></i> Discount Summary</h6>
            <div class="row text-center">
                <div class="col-md-4">
                    <small class="text-muted">Today</small><br>
                    <strong>₱<?= number_format($daily_sales['total_discounts'], 2) ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">This Month</small><br>
                    <strong>₱<?= number_format($monthly_sales['total_discounts'], 2) ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">This Year</small><br>
                    <strong>₱<?= number_format($yearly_sales['total_discounts'], 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Orders -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold">Recent Orders</h6>
        <a href="order_history.php" class="btn btn-sm btn-primary">View All</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Table</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_recent_orders && $result_recent_orders->num_rows > 0) {
                        while ($order = $result_recent_orders->fetch_assoc()) {
                            // Get item count
                            $sql_item_count = "SELECT SUM(quantity) as total_items FROM order_items WHERE order_id = " . (int)$order['order_id'];
                            $result_item_count = $conn->query($sql_item_count);
                            $item_count = 0;
                            if ($result_item_count) {
                                $count_row = $result_item_count->fetch_assoc();
                                $item_count = $count_row ? (int)$count_row['total_items'] : 0;
                            }
                            
                            // Determine status badge color
                            $status_class = '';
                            switch ($order['status_id']) {
                                case 1: $status_class = 'bg-warning'; break;
                                case 2: $status_class = 'bg-primary'; break;
                                case 3: $status_class = 'bg-info'; break;
                                case 4: $status_class = 'bg-success'; break;
                                case 5: $status_class = 'bg-secondary'; break;
                                case 6: $status_class = 'bg-danger'; break;
                                default: $status_class = 'bg-secondary';
                            }
                            ?>
                            <tr>
                                <td><a href="order_details.php?id=<?= $order['order_id'] ?>"><?= $order['queue_number'] ?></a></td>
                                <td><?= $order['table_number'] ? $order['table_number'] : '-' ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></td>
                                <td><span class="badge <?= $status_class ?>"><?= $order['status_name'] ?></span></td>
                                <td><?= $item_count ?> items</td>
                                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center">No orders found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Load Chart.js BEFORE using it -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Sales Chart - Wrap in DOMContentLoaded and check if Chart is available
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js did not load.');
        return; // Prevent crash + Cypress hangup
    }

    var canvasElement = document.getElementById('salesChart');
    if (!canvasElement) {
        console.error('Sales chart canvas not found.');
        return;
    }

    var ctx = canvasElement.getContext('2d');
    if (!ctx) {
        console.error('Could not get canvas context.');
        return;
    }

    var salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($date_range) ?>,
        datasets: [{
            label: 'Daily Sales (₱)',
            data: <?= json_encode($sales_data) ?>,
            backgroundColor: 'rgba(78, 115, 223, 0.05)',
            borderColor: 'rgba(78, 115, 223, 1)',
            pointRadius: 3,
            pointBackgroundColor: 'rgba(78, 115, 223, 1)',
            pointBorderColor: 'rgba(78, 115, 223, 1)',
            pointHoverRadius: 5,
            pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
            pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
            pointHitRadius: 10,
            pointBorderWidth: 2,
            borderWidth: 2,
            fill: true
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
}); // End DOMContentLoaded
</script>

<?php
require_once 'includes/footer.php';
$conn->close();
?>