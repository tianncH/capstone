<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

// Get current month or selected month
$current_month = $_GET['month'] ?? date('Y-m');
$selected_venue = $_GET['venue'] ?? '';

// Parse month and year
$month_year = explode('-', $current_month);
$year = (int)$month_year[0];
$month = (int)$month_year[1];

// Get first day of month and number of days
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$start_day = date('w', $first_day); // 0 = Sunday, 6 = Saturday

// Get all venues for filter
$venues_sql = "SELECT * FROM venues WHERE is_active = 1 ORDER BY venue_name";
$venues_result = $conn->query($venues_sql);

// Get reservations for the month
$reservations_sql = "SELECT r.*, v.venue_name, v.venue_id 
                     FROM reservations r 
                     JOIN venues v ON r.venue_id = v.venue_id 
                     WHERE YEAR(r.reservation_date) = ? AND MONTH(r.reservation_date) = ?";
$params = [$year, $month];
$param_types = "ii";

if ($selected_venue) {
    $reservations_sql .= " AND r.venue_id = ?";
    $params[] = $selected_venue;
    $param_types .= "i";
}

$reservations_sql .= " ORDER BY r.reservation_date, r.start_time";

$stmt = $conn->prepare($reservations_sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$reservations = $stmt->get_result();

// Organize reservations by date
$reservations_by_date = [];
while ($reservation = $reservations->fetch_assoc()) {
    $date = $reservation['reservation_date'];
    if (!isset($reservations_by_date[$date])) {
        $reservations_by_date[$date] = [];
    }
    $reservations_by_date[$date][] = $reservation;
}

// Get venue restrictions for the month
$restrictions_sql = "SELECT vr.*, v.venue_name 
                     FROM venue_restrictions vr 
                     JOIN venues v ON vr.venue_id = v.venue_id 
                     WHERE YEAR(vr.start_date) = ? AND MONTH(vr.start_date) = ? AND vr.is_active = 1";
$restriction_params = [$year, $month];
$restriction_param_types = "ii";

if ($selected_venue) {
    $restrictions_sql .= " AND vr.venue_id = ?";
    $restriction_params[] = $selected_venue;
    $restriction_param_types .= "i";
}

$restriction_stmt = $conn->prepare($restrictions_sql);
$restriction_stmt->bind_param($restriction_param_types, ...$restriction_params);
$restriction_stmt->execute();
$restrictions = $restriction_stmt->get_result();

// Organize restrictions by date
$restrictions_by_date = [];
while ($restriction = $restrictions->fetch_assoc()) {
    $start_date = $restriction['start_date'];
    $end_date = $restriction['end_date'] ?: $start_date;
    
    $current_date = $start_date;
    while ($current_date <= $end_date) {
        if (!isset($restrictions_by_date[$current_date])) {
            $restrictions_by_date[$current_date] = [];
        }
        $restrictions_by_date[$current_date][] = $restriction;
        $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Reservation Calendar View</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="reservation_calendar.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-calendar-week"></i> Time Slot View
                </a>
                <a href="reservation_management.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-list"></i> All Reservations
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title mb-0">Calendar Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="month" class="form-label">Month & Year</label>
                    <input type="month" class="form-control" id="month" name="month" value="<?= $current_month ?>">
                </div>
                <div class="col-md-4">
                    <label for="venue" class="form-label">Venue</label>
                    <select class="form-control" id="venue" name="venue">
                        <option value="">All Venues</option>
                        <?php while ($venue = $venues_result->fetch_assoc()): ?>
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
                        <a href="reservation_calendar_view.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Legend</h6>
            <div class="row">
                <div class="col-md-3">
                    <span class="badge bg-success me-2">Confirmed</span>
                    <span class="badge bg-warning me-2">Pending</span>
                </div>
                <div class="col-md-3">
                    <span class="badge bg-primary me-2">Completed</span>
                    <span class="badge bg-danger me-2">Cancelled</span>
                </div>
                <div class="col-md-3">
                    <span class="badge bg-dark me-2">No Show</span>
                    <span class="badge bg-secondary me-2">Restricted</span>
                </div>
                <div class="col-md-3">
                    <span class="badge bg-info me-2">Multiple Venues</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <?= date('F Y', $first_day) ?>
                </h5>
                <div class="btn-group">
                    <a href="?month=<?= date('Y-m', mktime(0, 0, 0, $month - 1, 1, $year)) ?>&venue=<?= $selected_venue ?>" 
                       class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                    <a href="?month=<?= date('Y-m') ?>&venue=<?= $selected_venue ?>" 
                       class="btn btn-sm btn-outline-primary">Today</a>
                    <a href="?month=<?= date('Y-m', mktime(0, 0, 0, $month + 1, 1, $year)) ?>&venue=<?= $selected_venue ?>" 
                       class="btn btn-sm btn-outline-primary">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">Sunday</th>
                            <th class="text-center">Monday</th>
                            <th class="text-center">Tuesday</th>
                            <th class="text-center">Wednesday</th>
                            <th class="text-center">Thursday</th>
                            <th class="text-center">Friday</th>
                            <th class="text-center">Saturday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $day = 1;
                        $current_date = date('Y-m-d');
                        
                        for ($week = 0; $week < 6; $week++):
                            if ($day > $days_in_month) break;
                        ?>
                            <tr>
                                <?php for ($day_of_week = 0; $day_of_week < 7; $day_of_week++): ?>
                                    <td class="calendar-day" style="height: 120px; vertical-align: top; position: relative;">
                                        <?php if ($week == 0 && $day_of_week < $start_day): ?>
                                            <!-- Empty cell before first day -->
                                            <div class="text-muted p-2"></div>
                                        <?php elseif ($day <= $days_in_month): ?>
                                            <?php
                                            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                            $is_today = $date_str === $current_date;
                                            $is_past = $date_str < $current_date;
                                            
                                            // Get reservations for this date
                                            $day_reservations = $reservations_by_date[$date_str] ?? [];
                                            $day_restrictions = $restrictions_by_date[$date_str] ?? [];
                                            
                                            // Count reservations by status
                                            $status_counts = [];
                                            $venue_counts = [];
                                            foreach ($day_reservations as $res) {
                                                $status = $res['status'];
                                                $venue_id = $res['venue_id'];
                                                
                                                if (!isset($status_counts[$status])) {
                                                    $status_counts[$status] = 0;
                                                }
                                                $status_counts[$status]++;
                                                
                                                if (!isset($venue_counts[$venue_id])) {
                                                    $venue_counts[$venue_id] = 0;
                                                }
                                                $venue_counts[$venue_id]++;
                                            }
                                            
                                            $total_reservations = count($day_reservations);
                                            $unique_venues = count($venue_counts);
                                            ?>
                                            
                                            <div class="p-2 <?= $is_today ? 'bg-primary text-white' : ($is_past ? 'bg-light' : '') ?>">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <strong class="<?= $is_today ? 'text-white' : '' ?>"><?= $day ?></strong>
                                                    <?php if ($is_today): ?>
                                                        <small class="text-white-50">Today</small>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Reservations Summary -->
                                                <?php if ($total_reservations > 0): ?>
                                                    <div class="mt-1">
                                                        <?php if ($unique_venues > 1): ?>
                                                            <span class="badge bg-info" title="Multiple venues have reservations">
                                                                <?= $total_reservations ?> reservations
                                                            </span>
                                                        <?php else: ?>
                                                            <?php foreach ($status_counts as $status => $count): ?>
                                                                <?php
                                                                $status_colors = [
                                                                    'confirmed' => 'success',
                                                                    'pending' => 'warning',
                                                                    'cancelled' => 'danger',
                                                                    'completed' => 'primary',
                                                                    'no_show' => 'dark'
                                                                ];
                                                                $color = $status_colors[$status] ?? 'secondary';
                                                                ?>
                                                                <span class="badge bg-<?= $color ?> me-1" title="<?= ucfirst($status) ?> reservations">
                                                                    <?= $count ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <!-- Quick Details -->
                                                    <div class="mt-1">
                                                        <?php foreach (array_slice($day_reservations, 0, 2) as $res): ?>
                                                            <div class="small text-truncate" title="<?= htmlspecialchars($res['customer_name']) ?> - <?= htmlspecialchars($res['venue_name']) ?> - <?= date('g:i A', strtotime($res['start_time'])) ?>">
                                                                <i class="bi bi-person"></i> <?= htmlspecialchars(substr($res['customer_name'], 0, 15)) ?>
                                                                <br><small class="text-muted"><?= date('g:i A', strtotime($res['start_time'])) ?></small>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        
                                                        <?php if ($total_reservations > 2): ?>
                                                            <small class="text-muted">+<?= $total_reservations - 2 ?> more</small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Restrictions -->
                                                <?php if (!empty($day_restrictions)): ?>
                                                    <div class="mt-1">
                                                        <?php foreach ($day_restrictions as $restriction): ?>
                                                            <span class="badge bg-secondary" title="<?= htmlspecialchars($restriction['title']) ?>">
                                                                <i class="bi bi-lock"></i> <?= htmlspecialchars(substr($restriction['title'], 0, 10)) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Click to view details -->
                                                <?php if ($total_reservations > 0 || !empty($day_restrictions)): ?>
                                                    <div class="mt-1">
                                                        <a href="reservation_calendar.php?date=<?= $date_str ?>&venue=<?= $selected_venue ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php $day++; ?>
                                        <?php else: ?>
                                            <!-- Empty cell after last day -->
                                            <div class="text-muted p-2"></div>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Summary Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= array_sum($status_counts) ?></h5>
                    <p class="card-text">Total Reservations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success"><?= $status_counts['confirmed'] ?? 0 ?></h5>
                    <p class="card-text">Confirmed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning"><?= $status_counts['pending'] ?? 0 ?></h5>
                    <p class="card-text">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-danger"><?= $status_counts['cancelled'] ?? 0 ?></h5>
                    <p class="card-text">Cancelled</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-day {
    min-height: 120px;
    transition: background-color 0.2s ease;
}

.calendar-day:hover {
    background-color: #f8f9fa !important;
}

.calendar-day .badge {
    font-size: 0.7rem;
    margin-bottom: 2px;
}

.calendar-day .small {
    font-size: 0.75rem;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-color: #dee2e6;
}

.table td {
    border-color: #dee2e6;
    padding: 0;
}

.bg-primary {
    background-color: #0d6efd !important;
}
</style>

<?php include 'includes/footer.php'; ?>
