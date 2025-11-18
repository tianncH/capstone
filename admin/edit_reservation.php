<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

$reservation_id = $_GET['id'] ?? null;
$success_message = '';
$error_message = '';

if (!$reservation_id) {
    header("Location: reservation_management.php");
    exit;
}

// Get reservation details
$sql = "SELECT r.*, v.venue_name, v.max_capacity, v.min_party_size, v.opening_time, v.closing_time
        FROM reservations r 
        JOIN venues v ON r.venue_id = v.venue_id 
        WHERE r.reservation_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $reservation_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    header("Location: reservation_management.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and validate input
        $venue_id = (int)$_POST['venue_id'];
        $customer_name = trim($_POST['customer_name']);
        $customer_email = trim($_POST['customer_email']);
        $customer_phone = !empty($_POST['customer_phone']) ? trim($_POST['customer_phone']) : null;
        $reservation_date = $_POST['reservation_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $party_size = (int)$_POST['party_size'];
        $reservation_type = $_POST['reservation_type'];
        $special_requests = !empty($_POST['special_requests']) ? trim($_POST['special_requests']) : null;
        $status = $_POST['status'];
        $admin_notes = !empty($_POST['admin_notes']) ? trim($_POST['admin_notes']) : null;
        
        // Validate required fields
        if (empty($venue_id) || empty($customer_name) || empty($customer_email) || empty($reservation_date) || empty($start_time) || empty($end_time) || empty($party_size)) {
            throw new Exception("All required fields must be filled.");
        }
        
        // Validate email
        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Validate date
        if (strtotime($reservation_date) < strtotime(date('Y-m-d'))) {
            throw new Exception("Reservation date cannot be in the past.");
        }
        
        // Validate time
        if (strtotime($start_time) >= strtotime($end_time)) {
            throw new Exception("End time must be after start time.");
        }
        
        // Get venue details
        $venue_sql = "SELECT * FROM venues WHERE venue_id = ? AND is_active = 1";
        $venue_stmt = $conn->prepare($venue_sql);
        $venue_stmt->bind_param('i', $venue_id);
        $venue_stmt->execute();
        $venue = $venue_stmt->get_result()->fetch_assoc();
        
        if (!$venue) {
            throw new Exception("Selected venue is not available.");
        }
        
        // Check party size against venue capacity
        if ($party_size > $venue['max_capacity']) {
            throw new Exception("Party size exceeds venue capacity of " . $venue['max_capacity'] . " people.");
        }
        
        if ($party_size < $venue['min_party_size']) {
            throw new Exception("Party size is below minimum requirement of " . $venue['min_party_size'] . " people.");
        }
        
        // Check venue operating hours
        $venue_open = strtotime($venue['opening_time']);
        $venue_close = strtotime($venue['closing_time']);
        $res_start = strtotime($start_time);
        $res_end = strtotime($end_time);
        
        if ($res_start < $venue_open || $res_end > $venue_close) {
            throw new Exception("Reservation time is outside venue operating hours (" . date('g:i A', $venue_open) . " - " . date('g:i A', $venue_close) . ").");
        }
        
        // Check for conflicts with existing reservations (excluding current reservation)
        $conflict_sql = "SELECT COUNT(*) as count FROM reservations 
                         WHERE venue_id = ? AND reservation_date = ? 
                         AND reservation_id != ?
                         AND status IN ('pending', 'confirmed') 
                         AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))";
        $conflict_stmt = $conn->prepare($conflict_sql);
        $conflict_stmt->bind_param('isissss', $venue_id, $reservation_date, $reservation_id, $end_time, $start_time, $end_time, $start_time);
        $conflict_stmt->execute();
        $conflict_result = $conflict_stmt->get_result()->fetch_assoc();
        
        if ($conflict_result['count'] > 0) {
            throw new Exception("Time slot conflicts with existing reservation. Please choose a different time.");
        }
        
        // Check for venue restrictions
        $restriction_sql = "SELECT COUNT(*) as count FROM venue_restrictions 
                            WHERE venue_id = ? AND start_date <= ? AND (end_date >= ? OR end_date IS NULL) 
                            AND is_active = 1 
                            AND ((start_time IS NULL OR start_time <= ?) AND (end_time IS NULL OR end_time >= ?))";
        $restriction_stmt = $conn->prepare($restriction_sql);
        $restriction_stmt->bind_param('isssss', $venue_id, $reservation_date, $reservation_date, $end_time, $start_time);
        $restriction_stmt->execute();
        $restriction_result = $restriction_stmt->get_result()->fetch_assoc();
        
        if ($restriction_result['count'] > 0) {
            throw new Exception("Selected time slot is restricted. Please choose a different time.");
        }
        
        // Update reservation
        $update_sql = "UPDATE reservations SET 
                       venue_id = ?, customer_name = ?, customer_email = ?, customer_phone = ?, 
                       reservation_date = ?, start_time = ?, end_time = ?, party_size = ?, 
                       reservation_type = ?, special_requests = ?, status = ?, admin_notes = ?
                       WHERE reservation_id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('isssssiissssi', 
            $venue_id, $customer_name, $customer_email, $customer_phone,
            $reservation_date, $start_time, $end_time, $party_size,
            $reservation_type, $special_requests, $status, $admin_notes, $reservation_id
        );
        
        if ($update_stmt->execute()) {
            $success_message = "Reservation updated successfully!";
            
            // Refresh reservation data
            $stmt->execute();
            $reservation = $stmt->get_result()->fetch_assoc();
        } else {
            throw new Exception("Failed to update reservation: " . $update_stmt->error);
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get all venues for the dropdown
$venues_sql = "SELECT * FROM venues WHERE is_active = 1 ORDER BY venue_name";
$venues_result = $conn->query($venues_sql);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Reservation</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="reservation_management.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Reservations
                </a>
                <a href="reservation_details.php?id=<?= $reservation_id ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> View Details
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

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pencil-square"></i> Edit Reservation #<?= $reservation['reservation_id'] ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="venue_id" class="form-label">Venue *</label>
                                    <select class="form-control" id="venue_id" name="venue_id" required>
                                        <option value="">Select Venue</option>
                                        <?php while ($venue = $venues_result->fetch_assoc()): ?>
                                            <option value="<?= $venue['venue_id'] ?>" 
                                                    <?= ($_POST['venue_id'] ?? $reservation['venue_id']) == $venue['venue_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($venue['venue_name']) ?> (Capacity: <?= $venue['max_capacity'] ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reservation_date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" id="reservation_date" name="reservation_date" 
                                           value="<?= $_POST['reservation_date'] ?? $reservation['reservation_date'] ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Start Time *</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" 
                                           value="<?= $_POST['start_time'] ?? $reservation['start_time'] ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">End Time *</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" 
                                           value="<?= $_POST['end_time'] ?? $reservation['end_time'] ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_name" class="form-label">Customer Name *</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                           value="<?= htmlspecialchars($_POST['customer_name'] ?? $reservation['customer_name']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="party_size" class="form-label">Party Size *</label>
                                    <input type="number" class="form-control" id="party_size" name="party_size" 
                                           min="1" value="<?= $_POST['party_size'] ?? $reservation['party_size'] ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                           value="<?= htmlspecialchars($_POST['customer_email'] ?? $reservation['customer_email']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                           value="<?= htmlspecialchars($_POST['customer_phone'] ?? $reservation['customer_phone']) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reservation_type" class="form-label">Reservation Type</label>
                                    <select class="form-control" id="reservation_type" name="reservation_type">
                                        <option value="party" <?= ($_POST['reservation_type'] ?? $reservation['reservation_type']) == 'party' ? 'selected' : '' ?>>Party</option>
                                        <option value="business" <?= ($_POST['reservation_type'] ?? $reservation['reservation_type']) == 'business' ? 'selected' : '' ?>>Business</option>
                                        <option value="couple" <?= ($_POST['reservation_type'] ?? $reservation['reservation_type']) == 'couple' ? 'selected' : '' ?>>Couple</option>
                                        <option value="family" <?= ($_POST['reservation_type'] ?? $reservation['reservation_type']) == 'family' ? 'selected' : '' ?>>Family</option>
                                        <option value="event" <?= ($_POST['reservation_type'] ?? $reservation['reservation_type']) == 'event' ? 'selected' : '' ?>>Event</option>
                                        <option value="other" <?= ($_POST['reservation_type'] ?? $reservation['reservation_type']) == 'other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="pending" <?= ($_POST['status'] ?? $reservation['status']) == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="confirmed" <?= ($_POST['status'] ?? $reservation['status']) == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="cancelled" <?= ($_POST['status'] ?? $reservation['status']) == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        <option value="completed" <?= ($_POST['status'] ?? $reservation['status']) == 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="no_show" <?= ($_POST['status'] ?? $reservation['status']) == 'no_show' ? 'selected' : '' ?>>No Show</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Special Requests</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="3"><?= htmlspecialchars($_POST['special_requests'] ?? $reservation['special_requests']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                      placeholder="Internal notes about this reservation..."><?= htmlspecialchars($_POST['admin_notes'] ?? $reservation['admin_notes']) ?></textarea>
                        </div>
                        
                        <div class="text-end">
                            <a href="reservation_management.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check"></i> Update Reservation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Reservation Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle text-success"></i> Reservations must be made for future dates only</li>
                        <li><i class="bi bi-check-circle text-success"></i> Party size must be within venue capacity</li>
                        <li><i class="bi bi-check-circle text-success"></i> Time slots must be within venue operating hours</li>
                        <li><i class="bi bi-check-circle text-success"></i> No overlapping reservations allowed</li>
                        <li><i class="bi bi-check-circle text-success"></i> Confirmation code remains unchanged</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">Current Reservation Info</h6>
                </div>
                <div class="card-body">
                    <p><strong>Confirmation Code:</strong><br><code><?= $reservation['confirmation_code'] ?></code></p>
                    <p><strong>Created:</strong><br><?= date('M j, Y g:i A', strtotime($reservation['created_at'])) ?></p>
                    <p><strong>Last Updated:</strong><br><?= date('M j, Y g:i A', strtotime($reservation['updated_at'])) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-calculate end time when start time changes
document.getElementById('start_time').addEventListener('change', function() {
    const startTime = this.value;
    if (startTime) {
        const start = new Date('2000-01-01T' + startTime);
        const end = new Date(start.getTime() + 2 * 60 * 60 * 1000); // Add 2 hours
        const endTime = end.toTimeString().slice(0, 5);
        document.getElementById('end_time').value = endTime;
    }
});

// Update party size limits when venue changes
document.getElementById('venue_id').addEventListener('change', function() {
    const venueId = this.value;
    if (venueId) {
        // You can add AJAX call here to get venue details and update party size limits
        // For now, we'll just enable the party size input
        document.getElementById('party_size').disabled = false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
