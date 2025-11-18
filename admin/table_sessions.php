<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/currency_functions.php';

$success_message = '';
$error_message = '';

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'close_session':
                    $session_id = intval($_POST['session_id']);
                    
                    // Update session status
                    $close_sql = "UPDATE table_sessions SET status = 'closed', closed_at = NOW() WHERE session_id = ?";
                    $close_stmt = $conn->prepare($close_sql);
                    $close_stmt->bind_param('i', $session_id);
                    $close_stmt->execute();
                    $close_stmt->close();
                    
                    $success_message = "Table session closed successfully";
                    break;
                    
                // Removed acknowledge_notification - only counter should handle this
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}


// Get active table sessions
$active_sessions_sql = "SELECT ts.*, t.table_number, 
                               (SELECT COUNT(*) FROM orders WHERE session_id = ts.session_id) as items_count,
                               (SELECT SUM(total_amount) FROM orders WHERE session_id = ts.session_id) as total_amount
                        FROM table_sessions ts 
                        JOIN tables t ON ts.table_id = t.table_id 
                        WHERE ts.status = 'active' 
                        ORDER BY ts.created_at DESC";
$active_sessions = $conn->query($active_sessions_sql);

// Get pending notifications
$notifications_sql = "SELECT n.*, ts.session_id, t.table_number 
                      FROM table_session_notifications n 
                      JOIN table_sessions ts ON n.session_id = ts.session_id 
                      JOIN tables t ON ts.table_id = t.table_id 
                      WHERE n.status = 'pending' 
                      ORDER BY n.created_at DESC";
$notifications = $conn->query($notifications_sql);

// Get recent closed sessions
$closed_sessions_sql = "SELECT ts.*, t.table_number, 
                               (SELECT COUNT(*) FROM orders WHERE session_id = ts.session_id) as items_count,
                               (SELECT SUM(total_amount) FROM orders WHERE session_id = ts.session_id) as total_amount
                        FROM table_sessions ts 
                        JOIN tables t ON ts.table_id = t.table_id 
                        WHERE ts.status = 'closed' 
                        ORDER BY ts.closed_at DESC 
                        LIMIT 10";
$closed_sessions = $conn->query($closed_sessions_sql);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">🍽️ Table Sessions</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <span class="badge bg-success me-2">Premium Dining Experience</span>
            <button type="button" class="btn btn-primary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Pending Notifications -->
    <?php if ($notifications->num_rows > 0): ?>
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bell"></i> Pending Notifications
                </h5>
            </div>
            <div class="card-body">
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div class="alert alert-warning d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Table <?= $notification['table_number'] ?></strong> - 
                            <?= htmlspecialchars($notification['message']) ?>
                            <br><small class="text-muted">
                                <?= date('g:i A', strtotime($notification['created_at'])) ?>
                            </small>
                        </div>
                        <div class="text-muted">
                            <small><i class="bi bi-info-circle"></i> Counter will handle this notification</small>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Active Sessions -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Active Table Sessions</h5>
        </div>
        <div class="card-body">
            <?php if ($active_sessions->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Session Started</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($session = $active_sessions->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong>Table <?= $session['table_number'] ?></strong>
                                        <br><small class="text-muted">Session #<?= $session['session_id'] ?></small>
                                    </td>
                                    <td>
                                        <?= date('g:i A', strtotime($session['created_at'])) ?>
                                        <br><small class="text-muted"><?= date('M j', strtotime($session['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $session['items_count'] ?> items</span>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?= formatPeso($session['total_amount'] ?? 0) ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        try {
                                            $session_start = new DateTime($session['created_at']);
                                            $current_time = new DateTime();
                                            
                                            // Calculate the difference properly
                                            if ($session_start > $current_time) {
                                                // Session started in the future (timezone issue)
                                                echo '<span class="text-info">Just started</span>';
                                            } else {
                                                // Session started in the past - calculate duration
                                                $duration = $current_time->diff($session_start);
                                                $hours = $duration->h + ($duration->d * 24);
                                                $minutes = $duration->i;
                                                
                                                if ($hours == 0 && $minutes == 0) {
                                                    echo '<span class="text-info">Just started</span>';
                                                } else {
                                                    echo $hours . 'h ' . $minutes . 'm';
                                                }
                                            }
                                        } catch (Exception $e) {
                                            echo '<span class="text-muted">Unknown</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewSessionDetails(<?= $session['session_id'] ?>)">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="viewTableMenu(<?= $session['table_number'] ?>)">
                                                <i class="bi bi-table"></i> Menu
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="close_session">
                                                <input type="hidden" name="session_id" value="<?= $session['session_id'] ?>">
                                                <button type="submit" class="btn btn-outline-warning" 
                                                        onclick="return confirm('Close this table session?')">
                                                    <i class="bi bi-x-circle"></i> Close
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-table text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-muted">No Active Sessions</h4>
                    <p class="text-muted">No tables are currently in use.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Closed Sessions -->
    <?php if ($closed_sessions->num_rows > 0): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Closed Sessions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Session Duration</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Closed At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($session = $closed_sessions->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>Table <?= $session['table_number'] ?></strong></td>
                                    <td>
                                        <?php
                                        $duration = strtotime($session['closed_at']) - strtotime($session['created_at']);
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        echo $hours . 'h ' . $minutes . 'm';
                                        ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= $session['items_count'] ?> items</span></td>
                                    <td><strong><?= formatPeso($session['total_amount'] ?? 0) ?></strong></td>
                                    <td><?= date('g:i A', strtotime($session['closed_at'])) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="viewSessionDetails(<?= $session['session_id'] ?>)">
                                            <i class="bi bi-eye"></i> View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
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
    fetch(`table_session_details.php?session_id=${sessionId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Error loading session details.</div>';
        });
}

function viewTableMenu(tableNumber) {
    window.open(`../ordering/table_menu.php?table=${tableNumber}`, '_blank');
}

// Auto-refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>
