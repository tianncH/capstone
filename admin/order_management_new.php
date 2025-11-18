<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_status':
                    $order_id = (int)$_POST['order_id'];
                    $new_status = $_POST['status'];
                    
                    $sql = "UPDATE orders SET status_id = (SELECT status_id FROM order_statuses WHERE name = ?), updated_at = NOW() WHERE order_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('si', $new_status, $order_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Order status updated successfully!";
                    } else {
                        throw new Exception("Failed to update order status: " . $stmt->error);
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$table_filter = $_GET['table'] ?? 'all';
$date_filter = $_GET['date'] ?? date('Y-m-d');

// Build query
$where_conditions = ["DATE(o.created_at) = ?"];
$params = [$date_filter];
$param_types = "s";

if ($status_filter !== 'all') {
    $where_conditions[] = "os.name = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if ($table_filter !== 'all') {
    $where_conditions[] = "t.table_number = ?";
    $params[] = $table_filter;
    $param_types .= "s";
}

$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT o.*, os.name as status_name, t.table_number, ts.session_id,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
        FROM orders o 
        LEFT JOIN tables t ON o.table_id = t.table_id 
        JOIN order_statuses os ON o.status_id = os.status_id 
        LEFT JOIN table_sessions ts ON o.session_id = ts.session_id
        WHERE $where_clause
        ORDER BY ts.session_id DESC, o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

// Group orders by session
$session_groups = [];
while ($order = $orders->fetch_assoc()) {
    $session_id = $order['session_id'] ?? 'no-session';
    if (!isset($session_groups[$session_id])) {
        $session_groups[$session_id] = [
            'session_id' => $session_id,
            'table_number' => $order['table_number'],
            'orders' => [],
            'total_amount' => 0,
            'total_items' => 0,
            'status_summary' => []
        ];
    }
    $session_groups[$session_id]['orders'][] = $order;
    $session_groups[$session_id]['total_amount'] += floatval($order['total_amount']);
    $session_groups[$session_id]['total_items'] += intval($order['item_count']);
    
    // Track status summary
    $status = $order['status_name'];
    if (!isset($session_groups[$session_id]['status_summary'][$status])) {
        $session_groups[$session_id]['status_summary'][$status] = 0;
    }
    $session_groups[$session_id]['status_summary'][$status]++;
}

// Get statuses for filter
$statuses_sql = "SELECT name FROM order_statuses ORDER BY status_id";
$statuses_result = $conn->query($statuses_sql);

// Get tables for filter
$tables_sql = "SELECT table_number FROM tables ORDER BY table_number";
$tables_result = $conn->query($tables_sql);

include 'includes/header.php';
?>

<!-- Modern Admin Order Management -->
<div class="admin-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
                <h1 class="dashboard-title">
                    <i class="bi bi-clipboard-data"></i> Orders Management
                </h1>
                <p class="dashboard-subtitle">Real-time order monitoring and management</p>
            </div>
            <div class="header-right">
                <div class="live-stats">
                    <div class="stat-card total-orders">
                        <div class="stat-number"><?= count($session_groups) ?></div>
                        <div class="stat-label">Active Sessions</div>
                    </div>
                    <div class="stat-card today-date">
                        <div class="stat-number"><?= array_sum(array_column($session_groups, 'total_items')) ?></div>
                        <div class="stat-label">Total Items</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <?php while ($status = $statuses_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($status['name']) ?>" <?= $status_filter === $status['name'] ? 'selected' : '' ?>>
                                <?= ucfirst(htmlspecialchars($status['name'])) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="table">Table</label>
                    <select name="table" id="table" class="form-select">
                        <option value="all" <?= $table_filter === 'all' ? 'selected' : '' ?>>All Tables</option>
                        <?php while ($table = $tables_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($table['table_number']) ?>" <?= $table_filter === $table['table_number'] ? 'selected' : '' ?>>
                                Table <?= htmlspecialchars($table['table_number']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="order_management_new.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Sessions Grid -->
    <div class="sessions-grid">
        <?php if (!empty($session_groups)): ?>
            <?php foreach ($session_groups as $session): ?>
                <div class="session-card">
                    <div class="session-header">
                        <div class="session-info">
                            <h3 class="session-title">
                                <i class="bi bi-table"></i> Table <?= $session['table_number'] ?? 'Takeout' ?>
                                <span class="session-badge"><?= count($session['orders']) ?> Orders</span>
                            </h3>
                            <div class="session-meta">
                                <span class="session-total">
                                    <i class="bi bi-currency-dollar"></i> ₱<?= number_format($session['total_amount'], 2) ?>
                                </span>
                                <span class="session-items">
                                    <i class="bi bi-basket"></i> <?= $session['total_items'] ?> items
                                </span>
                            </div>
                        </div>
                        <div class="session-status">
                            <?php
                            // Determine overall session status
                            $has_pending = isset($session['status_summary']['pending']);
                            $has_preparing = isset($session['status_summary']['preparing']);
                            $has_ready = isset($session['status_summary']['ready']);
                            $all_completed = !$has_pending && !$has_preparing && !$has_ready && isset($session['status_summary']['completed']);
                            
                            if ($all_completed) {
                                $status_class = 'completed';
                                $status_icon = 'check-circle-fill';
                                $status_text = 'Completed';
                            } elseif ($has_ready) {
                                $status_class = 'ready';
                                $status_icon = 'check2-all';
                                $status_text = 'Ready';
                            } elseif ($has_preparing) {
                                $status_class = 'preparing';
                                $status_icon = 'gear';
                                $status_text = 'Preparing';
                            } else {
                                $status_class = 'pending';
                                $status_icon = 'hourglass-split';
                                $status_text = 'Pending';
                            }
                            ?>
                            <span class="status-badge <?= $status_class ?>">
                                <i class="bi bi-<?= $status_icon ?>"></i>
                                <?= $status_text ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="session-body">
                        <div class="orders-list">
                            <?php foreach ($session['orders'] as $order): ?>
                                <div class="order-item">
                                    <div class="order-details">
                                        <span class="order-number">#<?= htmlspecialchars($order['queue_number']) ?></span>
                                        <span class="order-time"><?= date('h:i A', strtotime($order['created_at'])) ?></span>
                                        <span class="order-amount">₱<?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                    <div class="order-status">
                                        <span class="status-dot <?= $order['status_name'] ?>"></span>
                                        <?= ucfirst($order['status_name']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="session-actions">
                            <button class="action-btn expand-btn" onclick="toggleSessionOrders(this, '<?= $session['session_id'] ?>')">
                                <i class="bi bi-chevron-down"></i> Expand (<?= count($session['orders']) ?>)
                            </button>
                            <div class="admin-note">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> Order actions are managed by the counter staff
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <h3>No Sessions Found</h3>
                <p>No order sessions match your current filter criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>


<script>
function toggleSessionOrders(button, sessionId) {
    console.log('Toggle Session Orders clicked for session ID:', sessionId);
    const sessionCard = button.closest('.session-card');
    const ordersList = sessionCard.querySelector('.orders-list');
    const expandIcon = button.querySelector('i');
    
    console.log('Session card found:', sessionCard);
    console.log('Orders list found:', ordersList);
    console.log('Current display:', ordersList.style.display);
    
    // Extract the count from the button text
    const countMatch = button.textContent.match(/\((\d+)\)/);
    const count = countMatch ? countMatch[1] : '';
    
    if (ordersList.style.display === 'none' || ordersList.style.display === '') {
        ordersList.style.display = 'block';
        expandIcon.className = 'bi bi-chevron-up';
        button.innerHTML = `<i class="bi bi-chevron-up"></i> Collapse (${count})`;
        console.log('Expanded orders list');
    } else {
        ordersList.style.display = 'none';
        expandIcon.className = 'bi bi-chevron-down';
        button.innerHTML = `<i class="bi bi-chevron-down"></i> Expand (${count})`;
        console.log('Collapsed orders list');
    }
}
</script>

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

.admin-dashboard {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.dashboard-header {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: var(--shadow-lg);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.dashboard-title {
    color: var(--dark-color);
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
}

.dashboard-subtitle {
    color: #6c757d;
    margin: 5px 0 0 0;
    font-size: 1.1rem;
}

.live-stats {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.stat-card {
    background: var(--gradient-primary);
    color: white;
    padding: 20px;
    border-radius: var(--border-radius);
    text-align: center;
    min-width: 120px;
    box-shadow: var(--shadow);
}

.stat-card.today-date {
    background: var(--gradient-success);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.filter-section {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: var(--shadow);
}

.filter-form {
    width: 100%;
}

.filter-row {
    display: flex;
    gap: 20px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 150px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: var(--dark-color);
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.sessions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
    gap: 20px;
}

.session-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: all 0.3s ease;
    border-left: 5px solid var(--primary-color);
}

.session-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.session-header {
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.session-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--dark-color);
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.session-badge {
    background: var(--primary-color);
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.session-meta {
    display: flex;
    gap: 20px;
    color: #6c757d;
    font-size: 0.9rem;
}

.session-body {
    padding: 20px;
}

.orders-list {
    margin-bottom: 20px;
    display: none; /* Initially collapsed */
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f1f3f4;
}

.order-item:last-child {
    border-bottom: none;
}

.order-details {
    display: flex;
    gap: 15px;
    align-items: center;
}

.order-number {
    font-weight: 600;
    color: var(--dark-color);
}

.order-time {
    color: #6c757d;
    font-size: 0.9rem;
}

.order-amount {
    color: var(--success-color);
    font-weight: 600;
}

.order-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.status-dot.pending {
    background: var(--warning-color);
}

.status-dot.paid {
    background: var(--info-color);
}

.status-dot.preparing {
    background: var(--primary-color);
}

.status-dot.ready {
    background: var(--success-color);
}

.status-dot.completed {
    background: var(--secondary-color);
}

.status-dot.cancelled {
    background: var(--danger-color);
}

.session-actions {
    display: flex;
    gap: 10px;
}

.expand-btn {
    background: var(--secondary-color);
    color: white;
}

.expand-btn:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.order-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.order-card.pending {
    border-left-color: var(--warning-color);
}

.order-card.paid {
    border-left-color: var(--info-color);
}

.order-card.preparing {
    border-left-color: var(--primary-color);
}

.order-card.ready {
    border-left-color: var(--success-color);
}

.order-card.completed {
    border-left-color: var(--secondary-color);
}

.order-card.cancelled {
    border-left-color: var(--danger-color);
}

.order-header {
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.order-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--dark-color);
    margin: 0 0 10px 0;
}

.order-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.table-info, .order-time {
    color: #6c757d;
    font-size: 0.9rem;
}

.status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
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

.order-body {
    padding: 20px;
}

.order-summary {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.summary-item {
    text-align: center;
}

.summary-item .label {
    display: block;
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.summary-item .value {
    display: block;
    font-weight: 600;
    color: var(--dark-color);
}

.summary-item .value.amount {
    color: var(--success-color);
    font-size: 1.1rem;
}

.payment-status.paid {
    color: var(--success-color);
}

.payment-status.pending {
    color: var(--warning-color);
}

.order-notes {
    background: #fff3cd;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
    border-left: 4px solid var(--warning-color);
}

.order-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.action-btn {
    width: 100%;
    padding: 10px 15px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}


.update-btn {
    background: var(--success-color);
    color: white;
}

.update-btn:hover {
    background: #218838;
    transform: translateY(-2px);
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.empty-icon {
    font-size: 4rem;
    color: #6c757d;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: var(--dark-color);
    margin-bottom: 10px;
}

.empty-state p {
    color: #6c757d;
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .admin-dashboard {
        padding: 15px;
    }
    
    .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .orders-grid {
        grid-template-columns: 1fr;
    }
    
    .order-summary {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
