<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

// Get current date or selected date
$current_date = $_GET['date'] ?? date('Y-m-d');
$selected_venue = $_GET['venue'] ?? '';

// Get all venues for filter
$venues_sql = "SELECT * FROM venues WHERE is_active = 1 ORDER BY venue_name";
$venues_result = $conn->query($venues_sql);

// Get reservations for the selected date
$reservations_sql = "SELECT r.*, v.venue_name, v.max_capacity 
                     FROM reservations r 
                     JOIN venues v ON r.venue_id = v.venue_id 
                     WHERE r.reservation_date = ?";
$params = [$current_date];
$param_types = "s";

if ($selected_venue) {
    $reservations_sql .= " AND r.venue_id = ?";
    $params[] = $selected_venue;
    $param_types .= "i";
}

$reservations_sql .= " ORDER BY r.start_time";
$stmt = $conn->prepare($reservations_sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$reservations = $stmt->get_result();

// Get venue restrictions for the selected date
$restrictions_sql = "SELECT * FROM venue_restrictions 
                     WHERE start_date <= ? AND (end_date >= ? OR end_date IS NULL) 
                     AND is_active = 1";
if ($selected_venue) {
    $restrictions_sql .= " AND venue_id = ?";
}
$restrictions_sql .= " ORDER BY start_time";
$rest_stmt = $conn->prepare($restrictions_sql);
if ($selected_venue) {
    $rest_stmt->bind_param('ssi', $current_date, $current_date, $selected_venue);
} else {
    $rest_stmt->bind_param('ss', $current_date, $current_date);
}
$rest_stmt->execute();
$restrictions = $rest_stmt->get_result();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Reservation Calendar</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addReservationModal">
                    <i class="bi bi-plus-circle"></i> Add Reservation
                </button>
                <a href="reservation_management.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-list"></i> All Reservations
                </a>
            </div>
        </div>
    </div>

    <!-- Date and Venue Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?= $current_date ?>">
                </div>
                <div class="col-md-4">
                    <label for="venue" class="form-label">Venue</label>
                    <select class="form-control" id="venue" name="venue">
                        <option value="">All Venues</option>
                        <?php 
                        $venues_result->data_seek(0);
                        while ($venue = $venues_result->fetch_assoc()): 
                        ?>
                            <option value="<?= $venue['venue_id'] ?>" <?= $selected_venue == $venue['venue_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($venue['venue_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="reservation_calendar.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Date Navigation -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="btn-group">
                <a href="?date=<?= date('Y-m-d', strtotime($current_date . ' -1 day')) ?>&venue=<?= $selected_venue ?>" class="btn btn-outline-primary">
                    <i class="bi bi-chevron-left"></i> Previous Day
                </a>
                <a href="?date=<?= date('Y-m-d') ?>&venue=<?= $selected_venue ?>" class="btn btn-outline-primary">Today</a>
                <a href="?date=<?= date('Y-m-d', strtotime($current_date . ' +1 day')) ?>&venue=<?= $selected_venue ?>" class="btn btn-outline-primary">
                    Next Day <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <h4><?= date('l, F j, Y', strtotime($current_date)) ?></h4>
        </div>
    </div>

    <!-- Calendar View -->
    <div class="row">
        <?php 
        $venues_result->data_seek(0);
        while ($venue = $venues_result->fetch_assoc()): 
            if ($selected_venue && $selected_venue != $venue['venue_id']) continue;
        ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <?= htmlspecialchars($venue['venue_name']) ?>
                            <span class="badge bg-info ms-2">Capacity: <?= $venue['max_capacity'] ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Time Slots -->
                        <div class="time-slots">
                            <?php
                            // Generate time slots for this venue
                            $start_time = strtotime($venue['opening_time']);
                            $end_time = strtotime($venue['closing_time']);
                            $interval = $venue['time_slot_interval'] * 60; // Convert to seconds
                            $buffer = $venue['buffer_time'] * 60;
                            
                            // Get reservations for this venue on this date
                            $venue_reservations = [];
                            $reservations->data_seek(0);
                            while ($res = $reservations->fetch_assoc()) {
                                if ($res['venue_id'] == $venue['venue_id']) {
                                    $venue_reservations[] = $res;
                                }
                            }
                            
                            // Get restrictions for this venue
                            $venue_restrictions = [];
                            $restrictions->data_seek(0);
                            while ($rest = $restrictions->fetch_assoc()) {
                                if ($rest['venue_id'] == $venue['venue_id']) {
                                    $venue_restrictions[] = $rest;
                                }
                            }
                            
                            for ($time = $start_time; $time < $end_time; $time += $interval):
                                $time_str = date('H:i:s', $time);
                                $time_display = date('g:i A', $time);
                                $slot_end = $time + $interval;
                                $slot_end_str = date('H:i:s', $slot_end);
                                
                                // Check if this slot is available
                                $is_available = true;
                                $is_restricted = false;
                                $reservation_info = null;
                                
                                // Check for restrictions
                                foreach ($venue_restrictions as $restriction) {
                                    if ($restriction['start_time'] && $restriction['end_time']) {
                                        $rest_start = strtotime($restriction['start_time']);
                                        $rest_end = strtotime($restriction['end_time']);
                                        if ($time < $rest_end && $slot_end > $rest_start) {
                                            $is_restricted = true;
                                            break;
                                        }
                                    }
                                }
                                
                                // Check for existing reservations
                                foreach ($venue_reservations as $reservation) {
                                    $res_start = strtotime($reservation['start_time']);
                                    $res_end = strtotime($reservation['end_time']);
                                    
                                    // Check for overlap (including buffer time)
                                    if ($time < $res_end + $buffer && $slot_end > $res_start - $buffer) {
                                        $is_available = false;
                                        $reservation_info = $reservation;
                                        break;
                                    }
                                }
                                
                                $slot_class = 'time-slot';
                                if ($is_restricted) {
                                    $slot_class .= ' restricted';
                                } elseif (!$is_available) {
                                    $slot_class .= ' booked';
                                } else {
                                    $slot_class .= ' available';
                                }
                            ?>
                                <div class="<?= $slot_class ?>" data-venue-id="<?= $venue['venue_id'] ?>" data-time="<?= $time_str ?>" data-end-time="<?= $slot_end_str ?>">
                                    <div class="slot-time"><?= $time_display ?></div>
                                    <?php if ($is_restricted): ?>
                                        <div class="slot-status">Restricted</div>
                                    <?php elseif (!$is_available && $reservation_info): ?>
                                        <div class="slot-status">
                                            <strong><?= htmlspecialchars($reservation_info['customer_name']) ?></strong><br>
                                            <small><?= $reservation_info['party_size'] ?> people</small><br>
                                            <span class="badge bg-<?= $reservation_info['status'] == 'confirmed' ? 'success' : ($reservation_info['status'] == 'pending' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($reservation_info['status']) ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="slot-status">Available</div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Add Reservation Modal -->
<div class="modal fade" id="addReservationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="add_reservation.php">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="venue_id" class="form-label">Venue *</label>
                                <select class="form-control" id="venue_id" name="venue_id" required>
                                    <option value="">Select Venue</option>
                                    <?php 
                                    $venues_result->data_seek(0);
                                    while ($venue = $venues_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $venue['venue_id'] ?>"><?= htmlspecialchars($venue['venue_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reservation_date" class="form-label">Date *</label>
                                <input type="date" class="form-control" id="reservation_date" name="reservation_date" value="<?= $current_date ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_time" class="form-label">End Time *</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Customer Name *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="party_size" class="form-label">Party Size *</label>
                                <input type="number" class="form-control" id="party_size" name="party_size" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reservation_type" class="form-label">Reservation Type</label>
                        <select class="form-control" id="reservation_type" name="reservation_type">
                            <option value="party">Party</option>
                            <option value="business">Business</option>
                            <option value="couple">Couple</option>
                            <option value="family">Family</option>
                            <option value="event">Event</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="special_requests" class="form-label">Special Requests</label>
                        <textarea class="form-control" id="special_requests" name="special_requests" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.time-slots {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 8px;
    max-height: 400px;
    overflow-y: auto;
}

.time-slot {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.time-slot.available {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.time-slot.available:hover {
    background-color: #c3e6cb;
}

.time-slot.booked {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
    cursor: not-allowed;
}

.time-slot.restricted {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
    cursor: not-allowed;
}

.slot-time {
    font-weight: bold;
    font-size: 0.9rem;
}

.slot-status {
    font-size: 0.8rem;
    margin-top: 4px;
}
</style>

<script>
// Auto-fill time when venue is selected
document.getElementById('venue_id').addEventListener('change', function() {
    const venueId = this.value;
    if (venueId) {
        // You can add AJAX call here to get venue details and set default times
        // For now, we'll just enable the time inputs
        document.getElementById('start_time').disabled = false;
        document.getElementById('end_time').disabled = false;
    }
});

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
</script>

<?php include 'includes/footer.php'; ?>
