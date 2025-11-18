<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

$success_message = '';
$error_message = '';

// Handle form submissions (Admin can only view, not modify orders)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Admin is read-only for order management
    // All order actions are handled by the counter staff
    $error_message = "Order actions are managed by the counter staff. Admin can only view orders.";
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$table_filter = $_GET['table'] ?? '';
$date_filter = $_GET['date'] ?? date('Y-m-d');

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter) {
    $where_conditions[] = "s.name = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($table_filter) {
    $where_conditions[] = "o.table_id = ?";
    $params[] = $table_filter;
    $param_types .= 'i';
}

if ($date_filter) {
    $where_conditions[] = "DATE(o.created_at) = ?";
    $params[] = $date_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get orders
$orders_sql = "SELECT o.*, t.table_number, s.name as status_name,
               o.discount_type, o.discount_percentage, o.discount_amount, o.original_amount,
               COUNT(oi.order_item_id) as item_count,
               SUM(oi.subtotal) as items_total
               FROM orders o 
               JOIN tables t ON o.table_id = t.table_id 
               JOIN order_statuses s ON o.status_id = s.status_id 
               LEFT JOIN order_items oi ON o.order_id = oi.order_id 
               $where_clause
               GROUP BY o.order_id 
               ORDER BY o.created_at DESC";
$orders_stmt = $conn->prepare($orders_sql);

if (!empty($params)) {
    $orders_stmt->bind_param($param_types, ...$params);
}

$orders_stmt->execute();
$orders = $orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all tables for filter
$tables_sql = "SELECT * FROM tables WHERE is_active = 1 ORDER BY table_number";
$tables_result = $conn->query($tables_sql);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Order Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshOrders()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="preparing" <?= $status_filter === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                        <option value="ready" <?= $status_filter === 'ready' ? 'selected' : '' ?>>Ready</option>
                        <option value="served" <?= $status_filter === 'served' ? 'selected' : '' ?>>Served</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="table" class="form-label">Table</label>
                    <select class="form-control" id="table" name="table">
                        <option value="">All Tables</option>
                        <?php while ($table = $tables_result->fetch_assoc()): ?>
                            <option value="<?= $table['table_id'] ?>" <?= $table_filter == $table['table_id'] ? 'selected' : '' ?>>
                                Table <?= htmlspecialchars($table['table_number']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?= $date_filter ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <a href="order_management.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders List -->
    <div class="row">
        <?php foreach ($orders as $order): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card order-card order-<?= $order['status_name'] ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-receipt"></i> Order #<?= htmlspecialchars($order['queue_number'] ?? $order['order_id']) ?>
                        </h6>
                        <span class="badge bg-<?= $order['status_name'] == 'pending' ? 'warning' : ($order['status_name'] == 'preparing' ? 'info' : ($order['status_name'] == 'ready' ? 'success' : 'secondary')) ?>">
                            <?= ucfirst($order['status_name']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <strong>Table:</strong><br>
                                Table <?= htmlspecialchars($order['table_number']) ?>
                            </div>
                            <div class="col-6">
                                <strong>Items:</strong><br>
                                <?= $order['item_count'] ?> items
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <strong>Total:</strong><br>
                                ₱<?= number_format($order['total_amount'], 2, '.', ',') ?>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <br><small class="text-success">
                                        <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $order['discount_type'])) ?> (₱<?= number_format($order['discount_amount'], 2) ?> off)
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-6">
                                <strong>Payment:</strong><br>
                                <span class="badge bg-<?= ($order['payment_status'] ?? 'pending') == 'paid' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Time:</strong><br>
                            <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                        </div>
                        
                        <?php if (!empty($order['notes'])): ?>
                            <div class="mb-3">
                                <strong>Special Instructions:</strong><br>
                                <small class="text-muted"><?= htmlspecialchars($order['notes']) ?></small>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails(<?= $order['order_id'] ?>)">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </div>
                        <div class="admin-note mt-2">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Order actions (Update, Cancel) are managed by the counter staff
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h3 class="text-muted">No orders found</h3>
            <p class="text-muted">Try adjusting your filters or check back later.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Admin Note: Order actions are managed by counter staff -->
<div class="admin-info-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Admin Information</h5>
        </div>
        <div class="modal-body">
            <p><i class="bi bi-info-circle"></i> Order actions (Update, Cancel) are managed by the counter staff.</p>
            <p>Admin can only view and monitor orders.</p>
        </div>
    </div>
</div>

<script>
function viewOrderDetails(orderId) {
    fetch(`get_order_details.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('orderDetailsContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
            } else {
                alert('Error loading order details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading order details.');
        });
}

function showAdminInfo() {
    alert('Order actions (Update, Cancel) are managed by the counter staff. Admin can only view orders.');
}

function refreshOrders() {
    location.reload();
}

// Auto-refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>
