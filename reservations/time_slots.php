<?php
require_once '../admin/includes/db_connection.php';

// Get parameters
$venue_id = isset($_GET['venue_id']) ? intval($_GET['venue_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$venue_name = isset($_GET['venue_name']) ? $_GET['venue_name'] : '';

if (!$venue_id) {
    header('Location: index.php');
    exit;
}

// Get venue details
$venue_sql = "SELECT * FROM venues WHERE venue_id = ? AND is_active = 1";
$venue_stmt = $conn->prepare($venue_sql);
$venue_stmt->bind_param('i', $venue_id);
$venue_stmt->execute();
$venue = $venue_stmt->get_result()->fetch_assoc();
$venue_stmt->close();

if (!$venue) {
    header('Location: index.php');
    exit;
}

// Get reservations for this venue on this date
$reservations_sql = "SELECT * FROM reservations WHERE venue_id = ? AND DATE(reservation_date) = ?";
$reservations_stmt = $conn->prepare($reservations_sql);
$reservations_stmt->bind_param('is', $venue_id, $date);
$reservations_stmt->execute();
$reservations = $reservations_stmt->get_result();
$reservations_stmt->close();

// Get restrictions for this venue
$restrictions_sql = "SELECT * FROM venue_restrictions WHERE venue_id = ?";
$restrictions_stmt = $conn->prepare($restrictions_sql);
$restrictions_stmt->bind_param('i', $venue_id);
$restrictions_stmt->execute();
$restrictions = $restrictions_stmt->get_result();
$restrictions_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Times - <?= htmlspecialchars($venue_name) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@300;400;500;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #ecf0f1;
            --shadow-light: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-medium: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        body {
            background: var(--light-bg);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        .main-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-medium);
            margin: 20px auto;
            max-width: 1200px;
            overflow: hidden;
        }
        
        .header-section {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .header-section h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 300;
            font-size: 2.2rem;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }
        
        .venue-info {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            margin: 20px;
            overflow: hidden;
        }
        
        .venue-image {
            width: 100%;
            height: 250px;
            background-image: url('<?= htmlspecialchars($venue['image_url'] ?? 'https://images.pexels.com/photos/1267320/pexels-photo-1267320.jpeg?auto=compress&cs=tinysrgb&w=800') ?>');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .venue-details {
            padding: 30px;
        }
        
        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 16px;
            padding: 30px;
        }
        
        .time-slot {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 20px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
        }
        
        .time-slot.available {
            border-color: var(--success-color);
            background: white;
            color: var(--success-color);
        }
        
        .time-slot.available:hover {
            background: var(--success-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }
        
        .time-slot.booked {
            border-color: var(--accent-color);
            background: #fef2f2;
            color: var(--accent-color);
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .time-slot.restricted {
            border-color: var(--warning-color);
            background: #fffbeb;
            color: var(--warning-color);
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .slot-time {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }
        
        .slot-status {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .btn-custom {
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary-custom {
            background: var(--primary-color);
            border: 1px solid var(--primary-color);
            color: white;
        }
        
        .btn-primary-custom:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-1px);
            box-shadow: var(--shadow-light);
        }
        
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            transition: all 0.2s ease;
        }
        
        .back-button:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <!-- Header Section -->
            <div class="header-section">
                <button class="back-button" onclick="history.back()">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                
                <h1><?= htmlspecialchars($venue_name) ?></h1>
                <p class="lead">Available Times for <?= date('l, F j, Y', strtotime($date)) ?></p>
            </div>
            
            <!-- Venue Info -->
            <div class="venue-info">
                <div class="venue-image"></div>
                <div class="venue-details">
                    <h3><?= htmlspecialchars($venue['venue_name']) ?></h3>
                    <p class="text-muted"><?= htmlspecialchars($venue['description']) ?></p>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <strong><i class="bi bi-people text-primary"></i> Capacity:</strong><br>
                            <?= $venue['min_party_size'] ?> - <?= $venue['max_capacity'] ?> people
                        </div>
                        <div class="col-md-4">
                            <strong><i class="bi bi-clock text-success"></i> Hours:</strong><br>
                            <?= date('g:i A', strtotime($venue['opening_time'])) ?> - <?= date('g:i A', strtotime($venue['closing_time'])) ?>
                        </div>
                        <div class="col-md-4">
                            <strong><i class="bi bi-currency-dollar text-warning"></i> Price:</strong><br>
                            ₱<?= number_format(rand(5000, 15000), 0) ?>/day
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="calendar_booking.php?venue_id=<?= $venue_id ?>&venue_name=<?= urlencode($venue_name) ?>" 
                           class="btn btn-outline-primary btn-custom">
                            <i class="bi bi-calendar3"></i> View Calendar Availability
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Time Slots -->
            <div class="time-slots-grid">
                <?php
                // Generate time slots
                $start_time = strtotime($venue['opening_time']);
                $end_time = strtotime($venue['closing_time']);
                $interval = ($venue['time_slot_interval'] ?? 60) * 60; // Convert to seconds
                $buffer = ($venue['buffer_time'] ?? 30) * 60;
                
                // Get existing reservations
                $existing_reservations = [];
                while ($reservation = $reservations->fetch_assoc()) {
                    $existing_reservations[] = $reservation;
                }
                
                // Get restrictions
                $venue_restrictions = [];
                while ($restriction = $restrictions->fetch_assoc()) {
                    $venue_restrictions[] = $restriction;
                }
                
                for ($time = $start_time; $time < $end_time; $time += $interval):
                    $time_str = date('H:i:s', $time);
                    $time_display = date('g:i A', $time);
                    $slot_end = $time + $interval;
                    $slot_end_str = date('H:i:s', $slot_end);
                    
                    // Check availability
                    $is_available = true;
                    $is_restricted = false;
                    $reservation_info = null;
                    
                    // Check restrictions
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
                    
                    // Check existing reservations
                    foreach ($existing_reservations as $reservation) {
                        $res_start = strtotime($reservation['start_time']);
                        $res_end = strtotime($reservation['end_time']);
                        
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
                    <div class="<?= $slot_class ?>" 
                         data-venue-id="<?= $venue['venue_id'] ?>"
                         data-date="<?= $date ?>"
                         data-start-time="<?= $time_str ?>"
                         data-end-time="<?= $slot_end_str ?>"
                         data-venue-name="<?= htmlspecialchars($venue['venue_name']) ?>"
                         data-max-capacity="<?= $venue['max_capacity'] ?>"
                         data-min-party-size="<?= $venue['min_party_size'] ?>"
                         <?php if ($is_available && !$is_restricted): ?>
                         onclick="openBookingModal(this)"
                         <?php endif; ?>>
                        <div class="slot-time"><?= $time_display ?></div>
                        <div class="slot-status">
                            <?php if ($is_restricted): ?>
                                <i class="bi bi-lock-fill"></i> Restricted
                            <?php elseif (!$is_available): ?>
                                <i class="bi bi-x-circle-fill"></i> Booked
                            <?php else: ?>
                                <i class="bi bi-check-circle-fill"></i> Available
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    
    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--primary-color); color: white;">
                    <h5 class="modal-title">Book Your Venue</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_booking.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="venue_id" id="modal_venue_id">
                        <input type="hidden" name="date" id="modal_date">
                        <input type="hidden" name="start_time" id="modal_start_time">
                        <input type="hidden" name="end_time" id="modal_end_time">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Venue:</label>
                            <div id="modal_venue_name" class="form-control-plaintext"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date & Time:</label>
                            <div id="modal_date_time" class="form-control-plaintext"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_name" class="form-label fw-bold">Full Name *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_email" class="form-label fw-bold">Email *</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_phone" class="form-label fw-bold">Phone Number *</label>
                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="party_size" class="form-label fw-bold">Party Size *</label>
                            <input type="number" class="form-control" id="party_size" name="party_size" required>
                            <div class="form-text" id="party_size_help"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="special_requests" class="form-label fw-bold">Special Requests</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="3" placeholder="Any special arrangements or requests..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openBookingModal(element) {
            const venueId = element.dataset.venueId;
            const venueName = element.dataset.venueName;
            const date = element.dataset.date;
            const startTime = element.dataset.startTime;
            const endTime = element.dataset.endTime;
            const maxCapacity = element.dataset.maxCapacity;
            const minPartySize = element.dataset.minPartySize;
            
            // Populate modal fields
            document.getElementById('modal_venue_id').value = venueId;
            document.getElementById('modal_date').value = date;
            document.getElementById('modal_start_time').value = startTime;
            document.getElementById('modal_end_time').value = endTime;
            document.getElementById('modal_venue_name').textContent = venueName;
            document.getElementById('modal_date_time').textContent = `${new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })} at ${formatTime(startTime)} - ${formatTime(endTime)}`;
            
            // Update party size limits
            document.getElementById('party_size').min = minPartySize;
            document.getElementById('party_size').max = maxCapacity;
            document.getElementById('party_size_help').textContent = `Minimum: ${minPartySize}, Maximum: ${maxCapacity} people`;
            
            // Show modal
            new bootstrap.Modal(document.getElementById('bookingModal')).show();
        }
        
        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const date = new Date();
            date.setHours(parseInt(hours), parseInt(minutes));
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
        
        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const partySize = parseInt(document.getElementById('party_size').value);
            const minParty = parseInt(document.getElementById('party_size').min);
            const maxParty = parseInt(document.getElementById('party_size').max);
            
            if (partySize < minParty || partySize > maxParty) {
                e.preventDefault();
                alert(`Party size must be between ${minParty} and ${maxParty} people.`);
                return false;
            }
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
