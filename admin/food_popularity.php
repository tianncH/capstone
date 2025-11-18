<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/currency_functions.php';

// Get date range for filtering
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Get food popularity data
$popularity_sql = "SELECT 
    mi.name as item_name,
    mi.price,
    COALESCE(c.name, 'Uncategorized') as category,
    COUNT(oi.order_item_id) as order_count,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.subtotal) as total_revenue
FROM order_items oi
JOIN menu_items mi ON oi.item_id = mi.item_id
LEFT JOIN categories c ON mi.category_id = c.category_id
JOIN orders o ON oi.order_id = o.order_id
WHERE DATE(o.created_at) BETWEEN ? AND ?
GROUP BY mi.item_id, mi.name, mi.price, c.name
ORDER BY total_quantity DESC, total_revenue DESC";

$stmt = $conn->prepare($popularity_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$popularity_data = $stmt->get_result();
$stmt->close();

// Get category summary
$category_sql = "SELECT 
    COALESCE(c.name, 'Uncategorized') as category,
    COUNT(DISTINCT mi.item_id) as item_count,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.subtotal) as total_revenue
FROM order_items oi
JOIN menu_items mi ON oi.item_id = mi.item_id
LEFT JOIN categories c ON mi.category_id = c.category_id
JOIN orders o ON oi.order_id = o.order_id
WHERE DATE(o.created_at) BETWEEN ? AND ?
GROUP BY c.name
ORDER BY total_revenue DESC";

$stmt = $conn->prepare($category_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$category_data = $stmt->get_result();
$stmt->close();

// Get top performers
$top_items = [];
$total_revenue = 0;
$total_orders = 0;

$popularity_data->data_seek(0);
while ($row = $popularity_data->fetch_assoc()) {
    $top_items[] = $row;
    $total_revenue += $row['total_revenue'];
    $total_orders += $row['order_count'];
}
$popularity_data->data_seek(0); // Reset for display
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Popularity Report - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --gradient-primary: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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

        .report-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .report-header {
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

        .report-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .report-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }

        .report-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 5px 0 0 0;
        }

        .date-range {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
        }

        .date-text {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 15px;
        }

        .stat-icon.primary {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
        }

        .stat-icon.success {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            color: white;
        }

        .stat-icon.warning {
            background: linear-gradient(135deg, var(--warning-color), #fd7e14);
            color: var(--dark-color);
        }

        .stat-icon.info {
            background: linear-gradient(135deg, var(--info-color), #138496);
            color: white;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .main-card {
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
            padding: 0;
        }

        .popularity-table {
            width: 100%;
            border-collapse: collapse;
        }

        .popularity-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            border-bottom: 2px solid #e9ecef;
        }

        .popularity-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f3f4;
        }

        .popularity-table tr:hover {
            background: #f8f9fa;
        }

        .item-name {
            font-weight: 600;
            color: var(--dark-color);
        }

        .item-category {
            background: var(--light-color);
            color: var(--secondary-color);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .quantity-badge {
            background: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .revenue-amount {
            color: var(--success-color);
            font-weight: 600;
        }

        .rank-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 0.9rem;
        }

        .rank-1 { background: linear-gradient(135deg, #ffd700, #ffed4e); color: var(--dark-color); }
        .rank-2 { background: linear-gradient(135deg, #c0c0c0, #e5e5e5); color: var(--dark-color); }
        .rank-3 { background: linear-gradient(135deg, #cd7f32, #daa520); color: white; }
        .rank-other { background: var(--secondary-color); }

        .category-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .category-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f3f4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-name {
            font-weight: 600;
            color: var(--dark-color);
        }

        .category-stats {
            text-align: right;
        }

        .category-revenue {
            color: var(--success-color);
            font-weight: 600;
        }

        .category-quantity {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .filter-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .report-container {
                padding: 15px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <button class="back-button" onclick="history.back()" title="Go Back">
        <i class="bi bi-arrow-left"></i>
    </button>

    <div class="report-container">
        <!-- Report Header -->
        <div class="report-header">
            <div class="header-background">
                <div class="header-content">
                    <div class="header-left">
                        <div class="report-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div class="report-info">
                            <h1 class="report-title">Food Popularity Report</h1>
                            <p class="report-subtitle">Comprehensive analysis of menu item performance</p>
                        </div>
                    </div>
                    <div class="date-range">
                        <div class="date-text">
                            <?= date('M d', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-2"></i>Update Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-trophy"></i>
                </div>
                <div class="stat-number"><?= count($top_items) ?></div>
                <div class="stat-label">Menu Items Sold</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-basket"></i>
                </div>
                <div class="stat-number"><?= array_sum(array_column($top_items, 'total_quantity')) ?></div>
                <div class="stat-label">Total Quantity</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-number"><?= $total_orders ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-number">₱<?= number_format($total_revenue, 2) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-grid">
            <!-- Popularity Table -->
            <div class="main-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-list-ol"></i> Menu Item Popularity Ranking
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($popularity_data->num_rows > 0): ?>
                        <table class="popularity-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                while ($item = $popularity_data->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td>
                                            <div class="rank-badge <?= $rank <= 3 ? "rank-{$rank}" : 'rank-other' ?>">
                                                <?= $rank ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                        </td>
                                        <td>
                                            <span class="item-category"><?= htmlspecialchars($item['category']) ?></span>
                                        </td>
                                        <td>₱<?= number_format($item['price'], 2) ?></td>
                                        <td>
                                            <span class="quantity-badge"><?= $item['total_quantity'] ?></span>
                                        </td>
                                        <td class="revenue-amount">₱<?= number_format($item['total_revenue'], 2) ?></td>
                                    </tr>
                                <?php 
                                $rank++;
                                endwhile; 
                                ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-graph-down text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-muted">No Data Available</h5>
                            <p class="text-muted">No sales data found for the selected date range.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Category Summary -->
            <div class="category-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-grid-3x3-gap"></i> Category Performance
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($category_data->num_rows > 0): ?>
                        <?php while ($category = $category_data->fetch_assoc()): ?>
                            <div class="category-item">
                                <div>
                                    <div class="category-name"><?= htmlspecialchars($category['category']) ?></div>
                                    <div class="category-quantity"><?= $category['total_quantity'] ?> items sold</div>
                                </div>
                                <div class="category-stats">
                                    <div class="category-revenue">₱<?= number_format($category['total_revenue'], 2) ?></div>
                                    <div class="category-quantity"><?= $category['item_count'] ?> items</div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-grid text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No category data available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
