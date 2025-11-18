<?php
require_once '../admin/includes/db_connection.php';

// Get parameters
$venue_id = isset($_GET['venue_id']) ? intval($_GET['venue_id']) : 0;
$venue_name = isset($_GET['venue_name']) ? $_GET['venue_name'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

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

// Calculate calendar dates
$current_date = new DateTime($month . '-01');
$year = $current_date->format('Y');
$month = $current_date->format('m');
$month_name = $current_date->format('F');

// Get first day of month and number of days
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$start_day = date('w', $first_day); // 0 = Sunday, 6 = Saturday

// Get reservations for the month
$reservations_sql = "SELECT reservation_date, COUNT(*) as booking_count,
                     GROUP_CONCAT(DISTINCT start_time ORDER BY start_time) as times
                     FROM reservations 
                     WHERE venue_id = ? AND YEAR(reservation_date) = ? AND MONTH(reservation_date) = ?
                     AND status IN ('pending', 'confirmed')
                     GROUP BY reservation_date";
$reservations_stmt = $conn->prepare($reservations_sql);
$reservations_stmt->bind_param('iii', $venue_id, $year, $month);
$reservations_stmt->execute();
$reservations_result = $reservations_stmt->get_result();

$reservations_by_date = [];
while ($row = $reservations_result->fetch_assoc()) {
    $reservations_by_date[$row['reservation_date']] = [
        'count' => $row['booking_count'],
        'times' => explode(',', $row['times'])
    ];
}
$reservations_stmt->close();

// Get venue restrictions for the month
$restrictions_sql = "SELECT * FROM venue_restrictions 
                     WHERE venue_id = ? AND is_active = 1
                     AND (start_date <= ? OR start_date IS NULL)
                     AND (end_date >= ? OR end_date IS NULL)";
$restrictions_stmt = $conn->prepare($restrictions_sql);
$last_day = date('Y-m-t', $first_day);
$first_day_str = $month . '-01';
$restrictions_stmt->bind_param('iss', $venue_id, $last_day, $first_day_str);
$restrictions_stmt->execute();
$restrictions_result = $restrictions_stmt->get_result();

$restrictions = [];
while ($restriction = $restrictions_result->fetch_assoc()) {
    $restrictions[] = $restriction;
}
$restrictions_stmt->close();

// Helper function to check if date is restricted
function isDateRestricted($date, $restrictions) {
    foreach ($restrictions as $restriction) {
        if ($restriction['start_date'] && $restriction['end_date']) {
            if ($date >= $restriction['start_date'] && $date <= $restriction['end_date']) {
                return true;
            }
        } elseif ($restriction['start_date'] && !$restriction['end_date']) {
            if ($date >= $restriction['start_date']) {
                return true;
            }
        } elseif (!$restriction['start_date'] && $restriction['end_date']) {
            if ($date <= $restriction['end_date']) {
                return true;
            }
        }
    }
    return false;
}

// Helper function to check if date is fully booked
function isDateFullyBooked($date, $venue, $reservations_by_date) {
    if (!isset($reservations_by_date[$date])) {
        return false;
    }
    
    // Calculate how many time slots are available for this venue
    $start_time = strtotime($venue['opening_time']);
    $end_time = strtotime($venue['closing_time']);
    $interval = ($venue['time_slot_interval'] ?? 60) * 60;
    $buffer = ($venue['buffer_time'] ?? 30) * 60;
    
    $total_slots = 0;
    for ($time = $start_time; $time < $end_time; $time += $interval) {
        $total_slots++;
    }
    
    // If we have reservations for more than 80% of available slots, consider it "fully booked"
    return $reservations_by_date[$date]['count'] >= ($total_slots * 0.8);
}

// Helper function to get availability status
function getAvailabilityStatus($date, $venue, $reservations_by_date, $restrictions) {
    if (isDateRestricted($date, $restrictions)) {
        return 'restricted';
    } elseif (isDateFullyBooked($date, $venue, $reservations_by_date)) {
        return 'fully_booked';
    } elseif (isset($reservations_by_date[$date])) {
        return 'partially_booked';
    } else {
        return 'available';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Booking - <?= htmlspecialchars($venue_name) ?></title>
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
            border-radius: 16px;
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
        
        .venue-info {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            margin: 20px;
            overflow: hidden;
        }
        
        .venue-image {
            width: 100%;
            height: 200px;
            background-image: url('<?= htmlspecialchars($venue['image_url'] ?? 'https://images.pexels.com/photos/1267320/pexels-photo-1267320.jpeg?auto=compress&cs=tinysrgb&w=800') ?>');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .venue-details {
            padding: 30px;
        }
        
        .calendar-container {
            padding: 30px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .calendar-nav button {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            transition: all 0.2s ease;
        }
        
        .calendar-nav button:hover {
            background: var(--secondary-color);
        }
        
        .calendar-nav button:disabled {
            background: var(--text-light);
            cursor: not-allowed;
        }
        
        .month-year {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            min-width: 200px;
            text-align: center;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-light);
        }
        
        .calendar-day-header {
            background: var(--secondary-color);
            color: white;
            padding: 15px 8px;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .calendar-day {
            background: white;
            min-height: 80px;
            padding: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .calendar-day:hover {
            background: var(--light-bg);
        }
        
        .calendar-day.other-month {
            background: #f8f9fa;
            color: var(--text-light);
        }
        
        .calendar-day.today {
            background: #e3f2fd;
            border: 2px solid var(--primary-color);
        }
        
        .calendar-day.available {
            background: #e8f5e8;
        }
        
        .calendar-day.available:hover {
            background: var(--success-color);
            color: white;
        }
        
        .calendar-day.partially_booked {
            background: #fff3cd;
        }
        
        .calendar-day.partially_booked:hover {
            background: var(--warning-color);
            color: white;
        }
        
        .calendar-day.fully_booked {
            background: #f8d7da;
            cursor: not-allowed;
        }
        
        .calendar-day.restricted {
            background: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .day-number {
            font-weight: 600;
            font-size: 1rem;
        }
        
        .day-status {
            font-size: 0.7rem;
            font-weight: 500;
            margin-top: auto;
        }
        
        .availability-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
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
                <p class="lead">Choose your preferred date</p>
            </div>
            
            <!-- Venue Info -->
            <div class="venue-info">
                <div class="venue-image"></div>
                <div class="venue-details">
                    <h3><?= htmlspecialchars($venue['venue_name']) ?></h3>
                    <p class="text-muted"><?= htmlspecialchars($venue['description']) ?></p>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <strong><i class="bi bi-people text-primary"></i> Capacity:</strong><br>
                            <?= $venue['min_party_size'] ?> - <?= $venue['max_capacity'] ?> people
                        </div>
                        <div class="col-md-3">
                            <strong><i class="bi bi-clock text-success"></i> Hours:</strong><br>
                            <?= date('g:i A', strtotime($venue['opening_time'])) ?> - <?= date('g:i A', strtotime($venue['closing_time'])) ?>
                        </div>
                        <div class="col-md-3">
                            <strong><i class="bi bi-currency-dollar text-warning"></i> Price:</strong><br>
                            ₱<?= number_format(rand(5000, 15000), 0) ?>/day
                        </div>
                        <div class="col-md-3">
                            <strong><i class="bi bi-calendar-check text-info"></i> Booking:</strong><br>
                            <a href="time_slots.php?venue_id=<?= $venue_id ?>&venue_name=<?= urlencode($venue_name) ?>" class="btn btn-primary-custom btn-sm">
                                View Time Slots
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Calendar -->
            <div class="calendar-container">
                <div class="calendar-header">
                    <div class="calendar-nav">
                        <a href="?venue_id=<?= $venue_id ?>&venue_name=<?= urlencode($venue_name) ?>&month=<?= date('Y-m', strtotime($month . '-01 -1 month')) ?>" 
                           class="btn btn-primary-custom">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <button onclick="goToToday()" class="btn btn-primary-custom">Today</button>
                        <a href="?venue_id=<?= $venue_id ?>&venue_name=<?= urlencode($venue_name) ?>&month=<?= date('Y-m', strtotime($month . '-01 +1 month')) ?>" 
                           class="btn btn-primary-custom">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                    
                    <div class="month-year">
                        <?= $month_name ?> <?= $year ?>
                    </div>
                    
                    <div></div>
                </div>
                
                <div class="calendar-grid">
                    <!-- Day headers -->
                    <div class="calendar-day-header">Sun</div>
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                    
                    <?php
                    // Generate calendar days
                    $current_date_obj = new DateTime();
                    $today = $current_date_obj->format('Y-m-d');
                    
                    // Add empty cells for days before the first day of the month
                    for ($i = 0; $i < $start_day; $i++) {
                        $prev_month = clone $current_date;
                        $prev_month->modify('first day of previous month');
                        $prev_month->modify('+' . (date('t', $prev_month->getTimestamp()) - $start_day + $i + 1) . ' days');
                        
                        echo '<div class="calendar-day other-month">';
                        echo '<div class="day-number">' . $prev_month->format('j') . '</div>';
                        echo '</div>';
                    }
                    
                    // Add days of the month
                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $date_obj = new DateTime($date);
                        
                        // Skip past dates
                        if ($date < $today) {
                            echo '<div class="calendar-day other-month">';
                            echo '<div class="day-number">' . $day . '</div>';
                            echo '<div class="day-status">Past</div>';
                            echo '</div>';
                            continue;
                        }
                        
                        $status = getAvailabilityStatus($date, $venue, $reservations_by_date, $restrictions);
                        $is_today = $date === $today;
                        
                        $class = 'calendar-day ' . $status;
                        if ($is_today) $class .= ' today';
                        
                        $onclick = '';
                        if ($status === 'available' || $status === 'partially_booked') {
                            $onclick = 'onclick="selectDate(\'' . $date . '\')"';
                        }
                        
                        echo '<div class="' . $class . '" ' . $onclick . '>';
                        echo '<div class="day-number">' . $day . '</div>';
                        
                        $status_text = '';
                        switch ($status) {
                            case 'available':
                                $status_text = 'Available';
                                break;
                            case 'partially_booked':
                                $booking_count = $reservations_by_date[$date]['count'] ?? 0;
                                $status_text = $booking_count . ' booked';
                                break;
                            case 'fully_booked':
                                $status_text = 'Full';
                                break;
                            case 'restricted':
                                $status_text = 'Restricted';
                                break;
                        }
                        
                        echo '<div class="day-status">' . $status_text . '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <!-- Availability Legend -->
                <div class="availability-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background: #e8f5e8;"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #fff3cd;"></div>
                        <span>Partially Booked</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #f8d7da;"></div>
                        <span>Fully Booked</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #f5f5f5;"></div>
                        <span>Restricted</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #e3f2fd; border: 2px solid var(--primary-color);"></div>
                        <span>Today</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function selectDate(date) {
            // Redirect to time slots page for the selected date
            const url = new URL(window.location);
            window.location.href = `time_slots.php?venue_id=<?= $venue_id ?>&venue_name=<?= urlencode($venue_name) ?>&date=${date}`;
        }
        
        function goToToday() {
            const today = new Date();
            const month = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');
            window.location.href = `?venue_id=<?= $venue_id ?>&venue_name=<?= urlencode($venue_name) ?>&month=${month}`;
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
