<?php
require_once '../admin/includes/db_connection.php';

// Get current date or selected date
$current_date = $_GET['date'] ?? date('Y-m-d');
$selected_venue = $_GET['venue'] ?? '';
$error_message = $_GET['error'] ?? '';

// Get all active venues
$venues_sql = "SELECT * FROM venues WHERE is_active = 1 ORDER BY venue_name";
$venues_result = $conn->query($venues_sql);

// Get reservations for the selected date
$reservations_sql = "SELECT r.*, v.venue_name, v.max_capacity 
                     FROM reservations r 
                     JOIN venues v ON r.venue_id = v.venue_id 
                     WHERE r.reservation_date = ? AND r.status IN ('pending', 'confirmed')";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Venue - Cianos Seafoods Grill</title>
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
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            opacity: 0.3;
        }
        
        .header-section h1 {
            position: relative;
            z-index: 1;
            font-weight: 300;
            font-size: 2.5rem;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }
        
        .header-section p {
            position: relative;
            z-index: 1;
            opacity: 0.9;
            font-size: 1.1rem;
            font-weight: 300;
        }
        
        .venue-card {
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
            margin-bottom: 24px;
            background: white;
            overflow: hidden;
            position: relative;
        }
        
        .venue-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            border-color: var(--accent-color);
        }
        
        .venue-image {
            width: 100%;
            height: 200px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            overflow: hidden;
        }
        
        .venue-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.3) 100%);
            z-index: 1;
        }
        
        .venue-rating {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255,255,255,0.95);
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .venue-rating .stars {
            color: #ffc107;
            font-size: 0.8rem;
        }
        
        .venue-favorite {
            position: absolute;
            top: 12px;
            left: 12px;
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 2;
        }
        
        .venue-favorite:hover {
            background: var(--accent-color);
            color: white;
        }
        
        .venue-content {
            padding: 20px 24px 24px;
        }
        
        .venue-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .venue-location {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .venue-description {
            font-size: 0.9rem;
            color: var(--text-dark);
            line-height: 1.5;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .venue-features {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 16px;
        }
        
        .venue-feature {
            background: var(--light-bg);
            color: var(--text-dark);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .venue-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }
        
        .venue-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .venue-capacity {
            font-size: 0.85rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            padding: 24px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .time-slot {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            font-weight: 500;
        }
        
        .time-slot.available {
            border-color: var(--success-color);
            background: white;
            color: var(--success-color);
        }
        
        .time-slot.available:hover {
            background: var(--success-color);
            color: white;
            transform: translateY(-1px);
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
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .slot-status {
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .date-navigation {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .btn-custom {
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom {
            background: var(--primary-color);
            border: 1px solid var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .btn-primary-custom:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-1px);
            box-shadow: var(--shadow-light);
            color: white;
        }
        
        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
        
        .form-label {
            color: var(--text-dark);
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .venue-features {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }
        
        .feature-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
        }
        
        .capacity-info {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            margin-top: 10px;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .no-venues {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .venue-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <!-- Header Section -->
            <div class="header-section">
                <h1 class="mb-3" style="font-family: 'Playfair Display', serif; font-weight: 300;">
                    Book a Venue
                </h1>
                <p class="lead" style="font-weight: 300;">Reserve your perfect dining space for special occasions</p>
                
                       <div class="mt-4">
                           <a href="find_reservation.php" class="btn btn-outline-light" style="border-radius: 8px; padding: 12px 24px;">
                               <i class="bi bi-search"></i> Find My Reservation
                           </a>
                       </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="date" class="form-label fw-bold">
                            <i class="bi bi-calendar3"></i> Select Date
                        </label>
                        <input type="date" class="form-control form-control-lg" id="date" name="date" 
                               value="<?= $current_date ?>" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="venue" class="form-label fw-bold">
                            <i class="bi bi-building"></i> Choose Venue
                        </label>
                        <select class="form-control form-control-lg" id="venue" name="venue">
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
                        <label class="form-label fw-bold">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary-custom btn-custom me-2">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary btn-custom">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Date Navigation -->
            <div class="date-navigation">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="btn-group">
                            <a href="?date=<?= date('Y-m-d', strtotime($current_date . ' -1 day')) ?>&venue=<?= $selected_venue ?>" 
                               class="btn btn-outline-primary btn-custom">
                                <i class="bi bi-chevron-left"></i> Previous Day
                            </a>
                            <a href="?date=<?= date('Y-m-d') ?>&venue=<?= $selected_venue ?>" 
                               class="btn btn-outline-primary btn-custom">Today</a>
                            <a href="?date=<?= date('Y-m-d', strtotime($current_date . ' +1 day')) ?>&venue=<?= $selected_venue ?>" 
                               class="btn btn-outline-primary btn-custom">
                                Next Day <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <h3 class="mb-0 text-primary">
                            <i class="bi bi-calendar-date"></i> 
                            <?= date('l, F j, Y', strtotime($current_date)) ?>
                        </h3>
                    </div>
                </div>
            </div>
            
            <!-- Loading Spinner -->
            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading availability...</p>
            </div>
            
            <!-- Venues and Time Slots -->
            <div id="venuesContainer">
                <?php if ($venues_result->num_rows > 0): ?>
                    <div class="row">
                        <?php 
                        $venues_result->data_seek(0);
                        while ($venue = $venues_result->fetch_assoc()): 
                            if ($selected_venue && $selected_venue != $venue['venue_id']) continue;
                        ?>
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="venue-card" onclick="selectVenue(<?= $venue['venue_id'] ?>)">
                                    <!-- Venue Image -->
                                    <div class="venue-image" style="background-image: url('<?= htmlspecialchars($venue['image_url'] ?? 'https://images.pexels.com/photos/1267320/pexels-photo-1267320.jpeg?auto=compress&cs=tinysrgb&w=800') ?>')">
                                        <!-- Favorite Button -->
                                        <div class="venue-favorite">
                                            <i class="bi bi-heart"></i>
                                        </div>
                                        
                                        <!-- Rating -->
                                        <div class="venue-rating">
                                            <?php
                                            // Get real venue rating from feedback
                                            $rating_sql = "SELECT AVG(venue_quality_rating) as avg_rating, COUNT(*) as rating_count 
                                                         FROM feedback f 
                                                         JOIN reservations r ON f.reservation_id = r.reservation_id 
                                                         WHERE r.venue_id = ? AND f.venue_quality_rating > 0";
                                            $rating_stmt = $conn->prepare($rating_sql);
                                            $rating_stmt->bind_param('i', $venue['venue_id']);
                                            $rating_stmt->execute();
                                            $rating_result = $rating_stmt->get_result()->fetch_assoc();
                                            $rating_stmt->close();
                                            
                                            $avg_rating = $rating_result['avg_rating'] ?? 0;
                                            $rating_count = $rating_result['rating_count'] ?? 0;
                                            
                                            if ($rating_count > 0) {
                                                $rounded_rating = round($avg_rating, 1);
                                                $full_stars = floor($avg_rating);
                                                $has_half_star = ($avg_rating - $full_stars) >= 0.5;
                                                
                                                echo '<span class="stars">';
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $full_stars) {
                                                        echo '★';
                                                    } elseif ($i == $full_stars + 1 && $has_half_star) {
                                                        echo '☆';
                                                    } else {
                                                        echo '☆';
                                                    }
                                                }
                                                echo '</span>';
                                                echo '<span>' . $rounded_rating . '</span>';
                                            } else {
                                                echo '<span class="stars">☆☆☆☆☆</span>';
                                                echo '<span>No ratings</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Venue Content -->
                                    <div class="venue-content">
                                        <h3 class="venue-title"><?= htmlspecialchars($venue['venue_name']) ?></h3>
                                        
                                        <div class="venue-location">
                                            <i class="bi bi-geo-alt"></i>
                                            <span>Cianos Seafoods Grill</span>
                                        </div>
                                        
                                        <?php if ($venue['description']): ?>
                                            <p class="venue-description"><?= htmlspecialchars($venue['description']) ?></p>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        $features = json_decode($venue['features'], true);
                                        if ($features && count($features) > 0):
                                        ?>
                                            <div class="venue-features">
                                                <?php foreach (array_slice($features, 0, 3) as $feature): ?>
                                                    <span class="venue-feature"><?= ucfirst(str_replace('_', ' ', $feature)) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="venue-footer">
                                            <div class="venue-price">
                                                ₱<?= number_format(rand(5000, 15000), 0) ?>/day
                                            </div>
                                            <div class="venue-capacity">
                                                <i class="bi bi-people"></i>
                                                <?= $venue['min_party_size'] ?>-<?= $venue['max_capacity'] ?> guests
                                            </div>
                                        </div>
                                        
                                        <div class="text-center mt-3">
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-primary-custom btn-custom" 
                                                        onclick="viewAvailableTimes(<?= $venue['venue_id'] ?>, '<?= htmlspecialchars($venue['venue_name']) ?>')">
                                                    <i class="bi bi-calendar-check"></i> Time Slots
                                                </button>
                                                <button class="btn btn-outline-primary btn-custom" 
                                                        onclick="viewCalendar(<?= $venue['venue_id'] ?>, '<?= htmlspecialchars($venue['venue_name']) ?>')">
                                                    <i class="bi bi-calendar3"></i> Calendar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-venues">
                        <i class="bi bi-building display-1 text-muted"></i>
                        <h3 class="text-muted">No venues available</h3>
                        <p>Please contact us for more information about our dining options.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-plus"></i> Make Your Reservation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="bookingForm" method="POST" action="book_reservation.php">
                        <input type="hidden" id="booking_venue_id" name="venue_id">
                        <input type="hidden" id="booking_date" name="reservation_date">
                        <input type="hidden" id="booking_start_time" name="start_time">
                        <input type="hidden" id="booking_end_time" name="end_time">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary">
                                            <i class="bi bi-building"></i> Venue Details
                                        </h6>
                                        <p class="mb-1"><strong id="modal_venue_name"></strong></p>
                                        <p class="mb-1"><small id="modal_venue_capacity"></small></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary">
                                            <i class="bi bi-calendar-date"></i> Reservation Details
                                        </h6>
                                        <p class="mb-1"><strong id="modal_date"></strong></p>
                                        <p class="mb-1"><small id="modal_time"></small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="customer_email" name="customer_email" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="customer_phone" name="customer_phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="party_size" class="form-label">Party Size *</label>
                                    <input type="number" class="form-control" id="party_size" name="party_size" min="1" required>
                                    <small class="form-text text-muted" id="party_size_help"></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reservation_type" class="form-label">Reservation Type</label>
                            <select class="form-control" id="reservation_type" name="reservation_type">
                                <option value="party">Party/Celebration</option>
                                <option value="business">Business Meeting</option>
                                <option value="couple">Romantic Dinner</option>
                                <option value="family">Family Gathering</option>
                                <option value="event">Special Event</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Special Requests</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="3" 
                                      placeholder="Any dietary restrictions, accessibility needs, or special requests..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Important:</strong> Please arrive on time for your reservation. 
                            We hold reservations for 15 minutes past the scheduled time.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="bookingForm" class="btn btn-primary-custom btn-custom">
                        <i class="bi bi-check-circle"></i> Confirm Reservation
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewAvailableTimes(venueId, venueName) {
            // Redirect to time slots page for this specific venue
            const currentUrl = new URL(window.location);
            const date = currentUrl.searchParams.get('date') || '<?= $current_date ?>';
            window.location.href = `time_slots.php?venue_id=${venueId}&date=${date}&venue_name=${encodeURIComponent(venueName)}`;
        }
        
        function viewCalendar(venueId, venueName) {
            // Redirect to calendar booking page for this specific venue
            window.location.href = `calendar_booking.php?venue_id=${venueId}&venue_name=${encodeURIComponent(venueName)}`;
        }
        
        function openBookingModal(element) {
            const venueId = element.dataset.venueId;
            const venueName = element.dataset.venueName;
            const date = element.dataset.date;
            const time = element.dataset.time;
            const endTime = element.dataset.endTime;
            const capacity = element.dataset.capacity;
            const minParty = element.dataset.minParty;
            
            // Set form values
            document.getElementById('booking_venue_id').value = venueId;
            document.getElementById('booking_date').value = date;
            document.getElementById('booking_start_time').value = time;
            document.getElementById('booking_end_time').value = endTime;
            
            // Update modal display
            document.getElementById('modal_venue_name').textContent = venueName;
            document.getElementById('modal_venue_capacity').textContent = `Capacity: ${minParty} - ${capacity} people`;
            document.getElementById('modal_date').textContent = new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('modal_time').textContent = `${formatTime(time)} - ${formatTime(endTime)}`;
            
            // Update party size limits
            document.getElementById('party_size').min = minParty;
            document.getElementById('party_size').max = capacity;
            document.getElementById('party_size_help').textContent = `Minimum: ${minParty}, Maximum: ${capacity} people`;
            
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
        
        // Auto-refresh availability every 30 seconds
        setInterval(function() {
            // Only refresh if no modal is open
            if (!document.querySelector('.modal.show')) {
                refreshAvailability();
            }
        }, 30000);
        
        function refreshAvailability() {
            const currentUrl = new URL(window.location);
            const date = currentUrl.searchParams.get('date') || '<?= $current_date ?>';
            const venue = currentUrl.searchParams.get('venue') || '';
            
            // Update the page with current parameters
            window.location.href = `?date=${date}&venue=${venue}`;
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
