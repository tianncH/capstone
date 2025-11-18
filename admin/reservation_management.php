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
                    $reservation_id = (int)$_POST['reservation_id'];
                    $new_status = $_POST['status'];
                    
                    $sql = "UPDATE reservations SET status = ? WHERE reservation_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('si', $new_status, $reservation_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Reservation status updated successfully!";
                    } else {
                        throw new Exception("Failed to update reservation status: " . $stmt->error);
                    }
                    break;
                    
                case 'cancel_reservation':
                    $reservation_id = (int)$_POST['reservation_id'];
                    $cancellation_reason = trim($_POST['cancellation_reason']);
                    
                    $sql = "UPDATE reservations SET status = 'cancelled', admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nCancelled: ', ?) WHERE reservation_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('si', $cancellation_reason, $reservation_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Reservation cancelled successfully!";
                    } else {
                        throw new Exception("Failed to cancel reservation: " . $stmt->error);
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$venue_filter = $_GET['venue'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT r.*, v.venue_name, v.max_capacity, au.full_name as created_by_name 
        FROM reservations r 
        JOIN venues v ON r.venue_id = v.venue_id 
        LEFT JOIN admin_users au ON r.created_by = au.admin_id 
        WHERE 1=1";
$params = [];
$param_types = "";

if ($status_filter) {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if ($venue_filter) {
    $sql .= " AND r.venue_id = ?";
    $params[] = $venue_filter;
    $param_types .= "i";
}

if ($date_from) {
    $sql .= " AND r.reservation_date >= ?";
    $params[] = $date_from;
    $param_types .= "s";
}

if ($date_to) {
    $sql .= " AND r.reservation_date <= ?";
    $params[] = $date_to;
    $param_types .= "s";
}

if ($search) {
    $sql .= " AND (r.customer_name LIKE ? OR r.customer_email LIKE ? OR r.confirmation_code LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}

$sql .= " ORDER BY r.reservation_date DESC, r.start_time DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$reservations = $stmt->get_result();

// Get venues for filter
$venues_sql = "SELECT * FROM venues WHERE is_active = 1 ORDER BY venue_name";
$venues_result = $conn->query($venues_sql);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Reservation Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="reservation_calendar.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-calendar"></i> Calendar View
                </a>
                <a href="add_reservation.php" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-circle"></i> Add Reservation
                </a>
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
        <div class="card-header">
            <h6 class="card-title mb-0">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="no_show" <?= $status_filter == 'no_show' ? 'selected' : '' ?>>No Show</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="venue" class="form-label">Venue</label>
                    <select class="form-control" id="venue" name="venue">
                        <option value="">All Venues</option>
                        <?php while ($venue = $venues_result->fetch_assoc()): ?>
                            <option value="<?= $venue['venue_id'] ?>" <?= $venue_filter == $venue['venue_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($venue['venue_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $date_from ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $date_to ?>">
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Customer name, email, or confirmation code">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
            <div class="mt-2">
                <a href="reservation_management.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
            </div>
        </div>
    </div>

    <!-- Reservations Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Reservations</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Venue</th>
                            <th>Date & Time</th>
                            <th>Party Size</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Confirmation</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($reservation = $reservations->fetch_assoc()): ?>
                            <tr>
                                <td><?= $reservation['reservation_id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($reservation['customer_name']) ?></strong>
                                    <?php if ($reservation['customer_email']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($reservation['customer_email']) ?></small>
                                    <?php endif; ?>
                                    <?php if ($reservation['customer_phone']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($reservation['customer_phone']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($reservation['venue_name']) ?>
                                    <br><small class="text-muted">Capacity: <?= $reservation['max_capacity'] ?></small>
                                </td>
                                <td>
                                    <strong><?= date('M j, Y', strtotime($reservation['reservation_date'])) ?></strong>
                                    <br><?= date('g:i A', strtotime($reservation['start_time'])) ?> - <?= date('g:i A', strtotime($reservation['end_time'])) ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $reservation['party_size'] ?> people</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= ucfirst($reservation['reservation_type']) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'pending' => 'warning',
                                        'confirmed' => 'success',
                                        'cancelled' => 'danger',
                                        'completed' => 'primary',
                                        'no_show' => 'dark'
                                    ];
                                    $color = $status_colors[$reservation['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color ?>"><?= ucfirst($reservation['status']) ?></span>
                                </td>
                                <td>
                                    <code><?= $reservation['confirmation_code'] ?></code>
                                </td>
                                <td>
                                    <?= $reservation['created_by_name'] ? htmlspecialchars($reservation['created_by_name']) : 'System' ?>
                                    <br><small class="text-muted"><?= date('M j, g:i A', strtotime($reservation['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="viewReservation(<?= $reservation['reservation_id'] ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="editReservation(<?= $reservation['reservation_id'] ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($reservation['status'] != 'cancelled'): ?>
                                            <button type="button" class="btn btn-outline-warning" onclick="updateStatus(<?= $reservation['reservation_id'] ?>, '<?= $reservation['status'] ?>')">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="reservation_id" id="update_reservation_id">
                <div class="modal-header">
                    <h5 class="modal-title">Update Reservation Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">New Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                            <option value="no_show">No Show</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Reservation Modal -->
<div class="modal fade" id="cancelReservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="cancel_reservation">
                <input type="hidden" name="reservation_id" id="cancel_reservation_id">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">Cancellation Reason</label>
                        <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Cancel Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewReservation(reservationId) {
    // You can implement a detailed view modal here
    window.open('reservation_details.php?id=' + reservationId, '_blank');
}

function editReservation(reservationId) {
    // You can implement an edit form here
    window.location.href = 'edit_reservation.php?id=' + reservationId;
}

function updateStatus(reservationId, currentStatus) {
    document.getElementById('update_reservation_id').value = reservationId;
    document.getElementById('status').value = currentStatus;
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}

function cancelReservation(reservationId) {
    document.getElementById('cancel_reservation_id').value = reservationId;
    new bootstrap.Modal(document.getElementById('cancelReservationModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
