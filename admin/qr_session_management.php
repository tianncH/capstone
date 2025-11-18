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
                // Removed confirm_session - handled by counter staff
                    
                case 'archive_session':
                    $session_id = intval($_POST['session_id']);
                    
                    // Get session details
                    $session_sql = "SELECT * FROM qr_sessions WHERE session_id = ?";
                    $session_stmt = $conn->prepare($session_sql);
                    $session_stmt->bind_param('i', $session_id);
                    $session_stmt->execute();
                    $session = $session_stmt->get_result()->fetch_assoc();
                    $session_stmt->close();
                    
                    // Calculate totals
                    $totals_sql = "SELECT COUNT(*) as total_orders, SUM(subtotal) as total_amount FROM qr_orders WHERE session_id = ? AND status != 'cancelled'";
                    $totals_stmt = $conn->prepare($totals_sql);
                    $totals_stmt->bind_param('i', $session_id);
                    $totals_stmt->execute();
                    $totals = $totals_stmt->get_result()->fetch_assoc();
                    $totals_stmt->close();
                    
                    // Archive session
                    $archive_sql = "INSERT INTO qr_session_archive 
                                   (original_session_id, table_id, session_token, device_fingerprint, total_orders, total_amount, status_at_archive, created_at, archived_by) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $archive_stmt = $conn->prepare($archive_sql);
                    $archive_stmt->bind_param('iissidssi', 
                        $session_id, 
                        $session['table_id'], 
                        $session['session_token'], 
                        $session['device_fingerprint'], 
                        $totals['total_orders'], 
                        $totals['total_amount'], 
                        $session['status'], 
                        $session['created_at'], 
                        $_SESSION['admin_id']
                    );
                    $archive_stmt->execute();
                    $archive_stmt->close();
                    
                    // Update session status
                    $update_sql = "UPDATE qr_sessions SET status = 'archived' WHERE session_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param('i', $session_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    $success_message = "QR session archived successfully";
                    break;
                    
                // Removed acknowledge_notification - only counter should handle this
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get active QR sessions
$active_sessions_sql = "SELECT qs.*, t.table_number, t.qr_code,
                               (SELECT COUNT(*) FROM qr_orders WHERE session_id = qs.session_id AND status != 'cancelled') as total_orders,
                               (SELECT SUM(subtotal) FROM qr_orders WHERE session_id = qs.session_id AND status != 'cancelled') as total_amount
                        FROM qr_sessions qs 
                        JOIN tables t ON qs.table_id = t.table_id 
                        WHERE qs.status IN ('active', 'locked') 
                        ORDER BY qs.created_at DESC";
$active_sessions = $conn->query($active_sessions_sql);

// Get pending notifications
$notifications_sql = "SELECT n.*, qs.session_id, t.table_number, t.qr_code 
                      FROM qr_session_notifications n 
                      JOIN qr_sessions qs ON n.session_id = qs.session_id 
                      JOIN tables t ON qs.table_id = t.table_id 
                      WHERE n.status = 'pending' 
                      ORDER BY n.created_at DESC";
$notifications = $conn->query($notifications_sql);

// Get recent archived sessions
$archived_sessions_sql = "SELECT qsa.*, t.table_number, t.qr_code
                          FROM qr_session_archive qsa 
                          JOIN tables t ON qsa.table_id = t.table_id 
                          ORDER BY qsa.archived_at DESC 
                          LIMIT 20";
$archived_sessions = $conn->query($archived_sessions_sql);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Secure QR Session Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <span class="badge bg-secondary me-2">Advanced Security</span>
            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
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
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bell"></i> Pending Notifications
                </h5>
            </div>
            <div class="card-body">
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div class="alert alert-light border d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Table <?= $notification['table_number'] ?> (<?= $notification['qr_code'] ?>)</strong> - 
                            <?= htmlspecialchars($notification['message']) ?>
                            <br><small class="text-muted">
                                <?= date('g:i A', strtotime($notification['created_at'])) ?>
                            </small>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="viewSessionDetails(<?= $notification['session_id'] ?>)">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <div class="text-muted mt-2">
                                <small><i class="bi bi-info-circle"></i> Counter will handle this notification</small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Active QR Sessions -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Active QR Sessions</h5>
        </div>
        <div class="card-body">
            <?php if ($active_sessions->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>QR Code</th>
                                <th>Session Status</th>
                                <th>Confirmed</th>
                                <th>Orders</th>
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
                                        <code><?= $session['qr_code'] ?></code>
                                        <br><small class="text-muted"><?= substr($session['session_token'], 0, 8) ?>...</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $session['status'] == 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($session['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($session['confirmed_by_counter']): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Confirmed
                                            </span>
                                            <br><small class="text-muted"><?= date('g:i A', strtotime($session['confirmed_at'])) ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-clock"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= $session['total_orders'] ?> orders</span>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= formatPeso($session['total_amount'] ?? 0) ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $duration = time() - strtotime($session['created_at']);
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        echo $hours . 'h ' . $minutes . 'm';
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    onclick="viewSessionDetails(<?= $session['session_id'] ?>)">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    onclick="viewQRMenu('<?= $session['qr_code'] ?>')">
                                                <i class="bi bi-qr-code"></i> Menu
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="archive_session">
                                                <input type="hidden" name="session_id" value="<?= $session['session_id'] ?>">
                                                <button type="submit" class="btn btn-outline-secondary" 
                                                        onclick="return confirm('Archive this session? This will end the customer session.')">
                                                    <i class="bi bi-archive"></i> Archive
                                                </button>
                                            </form>
                                        </div>
                                        <!-- Confirmation status - handled by counter staff -->
                                        <?php if (!$session['confirmed_by_counter']): ?>
                                            <div class="mt-2">
                                                <span class="badge bg-light text-dark">
                                                    <i class="bi bi-clock"></i> Pending Counter Confirmation
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-qr-code text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-muted">No Active QR Sessions</h4>
                    <p class="text-muted">No customers are currently using QR-based ordering.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Archived Sessions -->
    <?php if ($archived_sessions->num_rows > 0): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Archived Sessions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>QR Code</th>
                                <th>Session Duration</th>
                                <th>Orders</th>
                                <th>Total Amount</th>
                                <th>Archived At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($session = $archived_sessions->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>Table <?= $session['table_number'] ?></strong></td>
                                    <td><code><?= $session['qr_code'] ?></code></td>
                                    <td>
                                        <?php
                                        $duration = strtotime($session['archived_at']) - strtotime($session['created_at']);
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        echo $hours . 'h ' . $minutes . 'm';
                                        ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= $session['total_orders'] ?> orders</span></td>
                                    <td><strong><?= formatPeso($session['total_amount']) ?></strong></td>
                                    <td><?= date('g:i A', strtotime($session['archived_at'])) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="viewArchivedSession(<?= $session['original_session_id'] ?>)">
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
                <h5 class="modal-title" id="sessionDetailsModalLabel">QR Session Details</h5>
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
    fetch(`qr_session_details.php?session_id=${sessionId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Error loading session details.</div>';
        });
}

function viewQRMenu(qrCode) {
    window.open(`../ordering/secure_qr_menu.php?qr=${qrCode}`, '_blank');
}

function viewArchivedSession(sessionId) {
    // For archived sessions, we might want to show a different view
    alert('Archived session details would be shown here. Session ID: ' + sessionId);
}

// Auto-refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>



