<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/currency_functions.php';

// Get date range for filtering
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Get cash float sessions for the date range
$sessions_sql = "SELECT s.*, 
                        (SELECT COUNT(*) FROM cash_float_transactions WHERE shift_date = s.shift_date) as transaction_count,
                        (SELECT SUM(amount) FROM cash_float_transactions WHERE shift_date = s.shift_date AND transaction_type = 'sale') as total_sales,
                        (SELECT SUM(amount) FROM cash_float_transactions WHERE shift_date = s.shift_date AND transaction_type = 'refund') as total_refunds
                 FROM cash_float_sessions s 
                 WHERE s.shift_date BETWEEN ? AND ? 
                 ORDER BY s.shift_date DESC";
$sessions_stmt = $conn->prepare($sessions_sql);
$sessions_stmt->bind_param('ss', $start_date, $end_date);
$sessions_stmt->execute();
$sessions_result = $sessions_stmt->get_result();
$sessions_stmt->close();

// Calculate summary statistics
$summary_sql = "SELECT 
                    COUNT(*) as total_sessions,
                    SUM(opening_amount) as total_opening,
                    SUM(closing_amount) as total_closing,
                    AVG(closing_amount - opening_amount) as avg_difference,
                    SUM(adjustments) as total_adjustments
                FROM cash_float_sessions 
                WHERE shift_date BETWEEN ? AND ? AND status = 'closed'";
$summary_stmt = $conn->prepare($summary_sql);
$summary_stmt->bind_param('ss', $start_date, $end_date);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();
$summary_stmt->close();

// Get today's current session
$today = date('Y-m-d');
$today_session_sql = "SELECT * FROM cash_float_sessions WHERE shift_date = ? AND status = 'active'";
$today_session_stmt = $conn->prepare($today_session_sql);
$today_session_stmt->bind_param('s', $today);
$today_session_stmt->execute();
$today_session = $today_session_stmt->get_result()->fetch_assoc();
$today_session_stmt->close();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Cash Float Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="cash_float_counter.php" class="btn btn-primary me-2">
                <i class="bi bi-calculator"></i> Counter Interface
            </a>
            <button type="button" class="btn btn-success" onclick="exportCashFloatReport()">
                <i class="bi bi-download"></i> Export Report
            </button>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="cash_float_admin.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Sessions</h5>
                    <h2 class="display-6"><?= $summary['total_sessions'] ?? 0 ?></h2>
                    <p class="card-text">Closed sessions in period</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Opening</h5>
                    <h2 class="display-6"><?= formatPeso($summary['total_opening'] ?? 0) ?></h2>
                    <p class="card-text">Total cash float opened</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Closing</h5>
                    <h2 class="display-6"><?= formatPeso($summary['total_closing'] ?? 0) ?></h2>
                    <p class="card-text">Total cash float closed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-<?= ($summary['avg_difference'] ?? 0) >= 0 ? 'success' : 'danger' ?> text-white">
                <div class="card-body">
                    <h5 class="card-title">Avg Difference</h5>
                    <h2 class="display-6"><?= formatPeso($summary['avg_difference'] ?? 0) ?></h2>
                    <p class="card-text">Average daily difference</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Status -->
    <?php if ($today_session): ?>
        <div class="alert alert-info">
            <h5><i class="bi bi-info-circle"></i> Today's Session Status</h5>
            <p class="mb-0">
                <strong>Session Active:</strong> Opened at <?= date('g:i A', strtotime($today_session['opened_at'])) ?> 
                with <?= formatPeso($today_session['opening_amount']) ?> opening amount.
                <a href="cash_float_counter.php" class="btn btn-sm btn-primary ms-2">View Counter</a>
            </p>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> No Active Session</h5>
            <p class="mb-0">
                No cash float session is currently active for today.
                <a href="cash_float_counter.php" class="btn btn-sm btn-success ms-2">Open Session</a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Cash Float Sessions -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Cash Float Sessions (<?= date('M j', strtotime($start_date)) ?> - <?= date('M j', strtotime($end_date)) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if ($sessions_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Opening Amount</th>
                                <th>Closing Amount</th>
                                <th>Difference</th>
                                <th>Adjustments</th>
                                <th>Transactions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($session = $sessions_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= date('M j, Y', strtotime($session['shift_date'])) ?></strong>
                                        <?php if ($session['shift_date'] == $today): ?>
                                            <span class="badge bg-primary">Today</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $session['status'] == 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($session['status']) ?>
                                        </span>
                                    </td>
                                    <td><strong><?= formatPeso($session['opening_amount']) ?></strong></td>
                                    <td>
                                        <?php if ($session['closing_amount'] !== null): ?>
                                            <strong><?= formatPeso($session['closing_amount']) ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">Not closed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($session['closing_amount'] !== null): ?>
                                            <?php $difference = $session['closing_amount'] - $session['opening_amount']; ?>
                                            <span class="text-<?= $difference >= 0 ? 'success' : 'danger' ?>">
                                                <strong><?= $difference >= 0 ? '+' : '' ?><?= formatPeso($difference) ?></strong>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($session['adjustments'] != 0): ?>
                                            <span class="text-info"><?= formatPeso($session['adjustments']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">₱0.00</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $session['transaction_count'] ?> transactions</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="viewSessionDetails(<?= $session['session_id'] ?>)">
                                            <i class="bi bi-eye"></i> View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                    <h4 class="mt-3 text-muted">No Sessions Found</h4>
                    <p class="text-muted">No cash float sessions found for the selected date range.</p>
                    <a href="cash_float_counter.php" class="btn btn-primary">Start First Session</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Session Details Modal -->
<div class="modal fade" id="sessionDetailsModal" tabindex="-1" aria-labelledby="sessionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sessionDetailsModalLabel">Session Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="sessionDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewSessionDetails(sessionId) {
    const modal = new bootstrap.Modal(document.getElementById('sessionDetailsModal'));
    const content = document.getElementById('sessionDetailsContent');
    
    content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modal.show();
    
    // Fetch session details via AJAX
    fetch(`cash_float_session_details.php?session_id=${sessionId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Error loading session details.</div>';
        });
}

function exportCashFloatReport() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    window.open(`cash_float_export.php?start_date=${startDate}&end_date=${endDate}`, '_blank');
}
</script>

<?php include 'includes/footer.php'; ?>





