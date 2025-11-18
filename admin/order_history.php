<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Get filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? intval($_GET['status']) : 0;

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

// Build query
$sql = "SELECT o.*, os.name as status_name, t.table_number 
         FROM orders o 
         LEFT JOIN tables t ON o.table_id = t.table_id 
         JOIN order_statuses os ON o.status_id = os.status_id 
         WHERE DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'";

if ($status_filter > 0) {
    $sql .= " AND o.status_id = $status_filter";
}

$sql .= " ORDER BY o.created_at DESC";

$result = $conn->query($sql);

// Get order statuses for filter dropdown
$sql_statuses = "SELECT * FROM order_statuses ORDER BY status_id";
$result_statuses = $conn->query($sql_statuses);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Order History</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="0">All Statuses</option>
                    <?php while ($status = $result_statuses->fetch_assoc()) { ?>
                        <option value="<?= $status['status_id'] ?>" <?= $status_filter == $status['status_id'] ? 'selected' : '' ?>>
                            <?= $status['name'] ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter Orders</button>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Table</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($order = $result->fetch_assoc()) {
                            // Get item count
                            $sql_item_count = "SELECT SUM(quantity) as total_items FROM order_items WHERE order_id = " . $order['order_id'];
                            $result_item_count = $conn->query($sql_item_count);
                            $item_count = $result_item_count->fetch_assoc()['total_items'];
                            
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
                                <td><?= $order['queue_number'] ?></td>
                                <td><?= $order['table_number'] ? $order['table_number'] : '-' ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></td>
                                <td><span class="badge <?= $status_class ?>"><?= $order['status_name'] ?></span></td>
                                <td><?= $item_count ?> items</td>
                                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <a href="order_details.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">No orders found for the selected criteria</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>