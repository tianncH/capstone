<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-t'); // Last day of current month
$venue_filter = $_GET['venue'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query for reservations
$sql = "SELECT r.*, v.venue_name 
        FROM reservations r 
        JOIN venues v ON r.venue_id = v.venue_id 
        WHERE r.reservation_date BETWEEN ? AND ?";
$params = [$date_from, $date_to];
$param_types = "ss";

if ($venue_filter) {
    $sql .= " AND r.venue_id = ?";
    $params[] = $venue_filter;
    $param_types .= "i";
}

if ($status_filter) {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

$sql .= " ORDER BY r.reservation_date, r.start_time";

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$reservations = $stmt->get_result();

// Get summary statistics
$stats_sql = "SELECT 
                COUNT(*) as total_reservations,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show,
                SUM(party_size) as total_guests,
                AVG(party_size) as avg_party_size
              FROM reservations r 
              WHERE r.reservation_date BETWEEN ? AND ?";
$stats_params = [$date_from, $date_to];
$stats_param_types = "ss";

if ($venue_filter) {
    $stats_sql .= " AND r.venue_id = ?";
    $stats_params[] = $venue_filter;
    $stats_param_types .= "i";
}

if ($status_filter) {
    $stats_sql .= " AND r.status = ?";
    $stats_params[] = $status_filter;
    $stats_param_types .= "s";
}

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param($stats_param_types, ...$stats_params);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get venues for filter
$venues_sql = "SELECT * FROM venues WHERE is_active = 1 ORDER BY venue_name";
$venues_result = $conn->query($venues_sql);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Reservation Reports</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-success" onclick="exportToCSV()">
                    <i class="bi bi-download"></i> Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title mb-0">Report Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $date_from ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $date_to ?>">
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3">
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
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <a href="reservation_reports.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= $stats['total_reservations'] ?></h5>
                    <p class="card-text">Total Reservations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success"><?= $stats['confirmed'] ?></h5>
                    <p class="card-text">Confirmed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning"><?= $stats['pending'] ?></h5>
                    <p class="card-text">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-danger"><?= $stats['cancelled'] ?></h5>
                    <p class="card-text">Cancelled</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info"><?= $stats['total_guests'] ?></h5>
                    <p class="card-text">Total Guests</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-secondary"><?= number_format($stats['avg_party_size'], 1) ?></h5>
                    <p class="card-text">Average Party Size</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-dark"><?= $stats['completed'] ?></h5>
                    <p class="card-text">Completed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservations Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Reservation Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="reservationsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Venue</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Party Size</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Confirmation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($reservation = $reservations->fetch_assoc()): ?>
                            <tr>
                                <td><?= $reservation['reservation_id'] ?></td>
                                <td><?= htmlspecialchars($reservation['customer_name']) ?></td>
                                <td><?= htmlspecialchars($reservation['venue_name']) ?></td>
                                <td><?= date('M j, Y', strtotime($reservation['reservation_date'])) ?></td>
                                <td><?= date('g:i A', strtotime($reservation['start_time'])) ?> - <?= date('g:i A', strtotime($reservation['end_time'])) ?></td>
                                <td><?= $reservation['party_size'] ?></td>
                                <td><?= ucfirst($reservation['reservation_type']) ?></td>
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
                                <td><code><?= $reservation['confirmation_code'] ?></code></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('reservationsTable');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cols = row.querySelectorAll('td, th');
        let csvRow = [];
        
        for (let j = 0; j < cols.length; j++) {
            let cellText = cols[j].innerText;
            // Remove badge styling and get clean text
            cellText = cellText.replace(/\n/g, ' ').trim();
            csvRow.push('"' + cellText + '"');
        }
        
        csv.push(csvRow.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'reservations_report_<?= $date_from ?>_to_<?= $date_to ?>.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include 'includes/footer.php'; ?>
