<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and validate input
        $venue_id = (int)$_POST['venue_id'];
        $customer_name = trim($_POST['customer_name']);
        $customer_email = !empty($_POST['customer_email']) ? trim($_POST['customer_email']) : null;
        $customer_phone = !empty($_POST['customer_phone']) ? trim($_POST['customer_phone']) : null;
        $reservation_date = $_POST['reservation_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $party_size = (int)$_POST['party_size'];
        $reservation_type = $_POST['reservation_type'];
        $special_requests = !empty($_POST['special_requests']) ? trim($_POST['special_requests']) : null;
        
        // Validate required fields
        if (empty($venue_id) || empty($customer_name) || empty($reservation_date) || empty($start_time) || empty($end_time) || empty($party_size)) {
            throw new Exception("All required fields must be filled.");
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
        
        // Check for conflicts with existing reservations
        $conflict_sql = "SELECT COUNT(*) as count FROM reservations 
                         WHERE venue_id = ? AND reservation_date = ? 
                         AND status IN ('pending', 'confirmed') 
                         AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))";
        $conflict_stmt = $conn->prepare($conflict_sql);
        $conflict_stmt->bind_param('isssss', $venue_id, $reservation_date, $end_time, $start_time, $end_time, $start_time);
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
        
        // Generate confirmation code
        $confirmation_code = strtoupper(substr(md5(uniqid()), 0, 8));
        
        // Insert reservation
        $insert_sql = "INSERT INTO reservations (
            venue_id, customer_name, customer_email, customer_phone, 
            reservation_date, start_time, end_time, party_size, 
            reservation_type, special_requests, confirmation_code, 
            status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?)";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $admin_id = $_SESSION['admin_id'];
        $insert_stmt->bind_param('isssssiisssi', 
            $venue_id, $customer_name, $customer_email, $customer_phone,
            $reservation_date, $start_time, $end_time, $party_size,
            $reservation_type, $special_requests, $confirmation_code, $admin_id
        );
        
        if ($insert_stmt->execute()) {
            $reservation_id = $conn->insert_id;
            $success_message = "Reservation created successfully! Confirmation Code: " . $confirmation_code;
            
            // Redirect to reservation details or calendar
            header("Location: reservation_calendar.php?date=" . $reservation_date . "&success=1");
            exit;
        } else {
            throw new Exception("Failed to create reservation: " . $insert_stmt->error);
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// If we get here, there was an error
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Add Reservation</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="reservation_calendar.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Calendar
            </a>
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
                    <h5 class="card-title mb-0">Reservation Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="venue_id" class="form-label">Venue *</label>
                                    <select class="form-control" id="venue_id" name="venue_id" required>
                                        <option value="">Select Venue</option>
                                        <?php
                                        $venues_sql = "SELECT * FROM venues WHERE is_active = 1 ORDER BY venue_name";
                                        $venues_result = $conn->query($venues_sql);
                                        while ($venue = $venues_result->fetch_assoc()):
                                        ?>
                                            <option value="<?= $venue['venue_id'] ?>" <?= (isset($_POST['venue_id']) && $_POST['venue_id'] == $venue['venue_id']) ? 'selected' : '' ?>>
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
                                           value="<?= $_POST['reservation_date'] ?? date('Y-m-d') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Start Time *</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" 
                                           value="<?= $_POST['start_time'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">End Time *</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" 
                                           value="<?= $_POST['end_time'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_name" class="form-label">Customer Name *</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                           value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="party_size" class="form-label">Party Size *</label>
                                    <input type="number" class="form-control" id="party_size" name="party_size" 
                                           min="1" value="<?= $_POST['party_size'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                           value="<?= htmlspecialchars($_POST['customer_email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                           value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reservation_type" class="form-label">Reservation Type</label>
                            <select class="form-control" id="reservation_type" name="reservation_type">
                                <option value="party" <?= (isset($_POST['reservation_type']) && $_POST['reservation_type'] == 'party') ? 'selected' : '' ?>>Party</option>
                                <option value="business" <?= (isset($_POST['reservation_type']) && $_POST['reservation_type'] == 'business') ? 'selected' : '' ?>>Business</option>
                                <option value="couple" <?= (isset($_POST['reservation_type']) && $_POST['reservation_type'] == 'couple') ? 'selected' : '' ?>>Couple</option>
                                <option value="family" <?= (isset($_POST['reservation_type']) && $_POST['reservation_type'] == 'family') ? 'selected' : '' ?>>Family</option>
                                <option value="event" <?= (isset($_POST['reservation_type']) && $_POST['reservation_type'] == 'event') ? 'selected' : '' ?>>Event</option>
                                <option value="other" <?= (isset($_POST['reservation_type']) && $_POST['reservation_type'] == 'other') ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Special Requests</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="3"><?= htmlspecialchars($_POST['special_requests'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="text-end">
                            <a href="reservation_calendar.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Reservation</button>
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
                        <li><i class="bi bi-check-circle text-success"></i> Confirmation code will be generated automatically</li>
                    </ul>
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
