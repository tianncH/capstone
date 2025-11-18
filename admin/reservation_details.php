<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

$reservation_id = $_GET['id'] ?? null;
$success_message = '';
$error_message = '';

if (!$reservation_id) {
    header("Location: reservation_management.php");
    exit;
}

// Get reservation details
$sql = "SELECT r.*, v.venue_name, v.description as venue_description, v.max_capacity, 
               au.full_name as created_by_name
        FROM reservations r 
        JOIN venues v ON r.venue_id = v.venue_id 
        LEFT JOIN admin_users au ON r.created_by = au.admin_id
        WHERE r.reservation_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $reservation_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    header("Location: reservation_management.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_status':
                    $new_status = $_POST['status'];
                    $admin_notes = trim($_POST['admin_notes']);
                    
                    $update_sql = "UPDATE reservations SET status = ?, admin_notes = ? WHERE reservation_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param('ssi', $new_status, $admin_notes, $reservation_id);
                    
                    if ($update_stmt->execute()) {
                        $success_message = "Reservation status updated successfully!";
                        // Refresh reservation data
                        $stmt->execute();
                        $reservation = $stmt->get_result()->fetch_assoc();
                    } else {
                        throw new Exception("Failed to update reservation status: " . $update_stmt->error);
                    }
                    break;
                    
                case 'add_response':
                    $response_message = trim($_POST['response_message']);
                    
                    if (empty($response_message)) {
                        throw new Exception("Response message cannot be empty.");
                    }
                    
                    $response_sql = "INSERT INTO feedback_responses (feedback_id, admin_id, response_message) VALUES (?, ?, ?)";
                    $response_stmt = $conn->prepare($response_sql);
                    $admin_id = $_SESSION['admin_id'];
                    $response_stmt->bind_param('iis', $reservation_id, $admin_id, $response_message);
                    
                    if ($response_stmt->execute()) {
                        $success_message = "Response added successfully!";
                    } else {
                        throw new Exception("Failed to add response: " . $response_stmt->error);
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get admin responses (if this reservation has feedback)
$responses_sql = "SELECT fr.*, au.full_name as admin_name 
                  FROM feedback_responses fr 
                  LEFT JOIN admin_users au ON fr.admin_id = au.admin_id 
                  WHERE fr.feedback_id = ? 
                  ORDER BY fr.created_at ASC";
$responses_stmt = $conn->prepare($responses_sql);
$responses_stmt->bind_param('i', $reservation_id);
$responses_stmt->execute();
$responses = $responses_stmt->get_result();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Reservation Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="reservation_management.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Reservations
                </a>
                <a href="reservation_calendar.php?date=<?= $reservation['reservation_date'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-calendar"></i> View Calendar
                </a>
            </div>
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
        <!-- Reservation Details -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calendar-check"></i> Reservation #<?= $reservation['reservation_id'] ?>
                        <span class="badge bg-<?= $reservation['status'] == 'confirmed' ? 'success' : ($reservation['status'] == 'pending' ? 'warning' : ($reservation['status'] == 'cancelled' ? 'danger' : 'primary')) ?> ms-2">
                            <?= ucfirst($reservation['status']) ?>
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">
                                <i class="bi bi-person-circle"></i> Customer Information
                            </h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td><?= htmlspecialchars($reservation['customer_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?= htmlspecialchars($reservation['customer_email']) ?></td>
                                </tr>
                                <?php if ($reservation['customer_phone']): ?>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td><?= htmlspecialchars($reservation['customer_phone']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><strong>Confirmation Code:</strong></td>
                                    <td><code><?= $reservation['confirmation_code'] ?></code></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary">
                                <i class="bi bi-building"></i> Venue & Timing
                            </h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Venue:</strong></td>
                                    <td><?= htmlspecialchars($reservation['venue_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Date:</strong></td>
                                    <td><?= date('l, F j, Y', strtotime($reservation['reservation_date'])) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Time:</strong></td>
                                    <td><?= date('g:i A', strtotime($reservation['start_time'])) ?> - <?= date('g:i A', strtotime($reservation['end_time'])) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Party Size:</strong></td>
                                    <td><?= $reservation['party_size'] ?> people</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td><?= ucfirst($reservation['reservation_type']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($reservation['special_requests']): ?>
                        <div class="mt-3">
                            <h6 class="text-primary">
                                <i class="bi bi-chat-dots"></i> Special Requests
                            </h6>
                            <div class="alert alert-info">
                                <?= nl2br(htmlspecialchars($reservation['special_requests'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6 class="text-primary">
                                <i class="bi bi-info-circle"></i> Reservation Info
                            </h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td><?= date('M j, Y g:i A', strtotime($reservation['created_at'])) ?></td>
                                </tr>
                                <?php if ($reservation['created_by_name']): ?>
                                <tr>
                                    <td><strong>Created By:</strong></td>
                                    <td><?= htmlspecialchars($reservation['created_by_name']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><strong>Last Updated:</strong></td>
                                    <td><?= date('M j, Y g:i A', strtotime($reservation['updated_at'])) ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary">
                                <i class="bi bi-currency-dollar"></i> Payment Info
                            </h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Deposit Amount:</strong></td>
                                    <td><?= $reservation['deposit_amount'] ? '$' . number_format($reservation['deposit_amount'], 2) : 'N/A' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Deposit Paid:</strong></td>
                                    <td>
                                        <?php if ($reservation['deposit_paid']): ?>
                                            <span class="badge bg-success">Yes</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Total Amount:</strong></td>
                                    <td><?= $reservation['total_amount'] ? '$' . number_format($reservation['total_amount'], 2) : 'N/A' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Payment Status:</strong></td>
                                    <td>
                                        <?php
                                        $payment_colors = [
                                            'pending' => 'warning',
                                            'partial' => 'info',
                                            'paid' => 'success',
                                            'refunded' => 'danger'
                                        ];
                                        $color = $payment_colors[$reservation['payment_status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>"><?= ucfirst($reservation['payment_status']) ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions Panel -->
        <div class="col-md-4">
            <!-- Status Update -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-gear"></i> Update Status
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="pending" <?= $reservation['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="confirmed" <?= $reservation['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="cancelled" <?= $reservation['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="completed" <?= $reservation['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="no_show" <?= $reservation['status'] == 'no_show' ? 'selected' : '' ?>>No Show</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                      placeholder="Add any notes about this reservation..."><?= htmlspecialchars($reservation['admin_notes']) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-check"></i> Update
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightning"></i> Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="mailto:<?= htmlspecialchars($reservation['customer_email']) ?>?subject=Reservation Confirmation #<?= $reservation['confirmation_code'] ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-envelope"></i> Send Email
                        </a>
                        
                        <?php if ($reservation['customer_phone']): ?>
                        <a href="tel:<?= htmlspecialchars($reservation['customer_phone']) ?>" 
                           class="btn btn-outline-success btn-sm">
                            <i class="bi bi-telephone"></i> Call Customer
                        </a>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="copyConfirmationCode()">
                            <i class="bi bi-clipboard"></i> Copy Confirmation Code
                        </button>
                        
                        <a href="reservation_calendar.php?date=<?= $reservation['reservation_date'] ?>&venue=<?= $reservation['venue_id'] ?>" 
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-calendar"></i> View in Calendar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Admin Responses (if any) -->
    <?php if ($responses->num_rows > 0): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-chat-dots"></i> Admin Responses
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php while ($response = $responses->fetch_assoc()): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= $response['admin_name'] ?? 'System' ?></strong>
                                        <small class="text-muted ms-2">
                                            <?= date('M j, Y g:i A', strtotime($response['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <?= nl2br(htmlspecialchars($response['response_message'])) ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function copyConfirmationCode() {
    const code = '<?= $reservation['confirmation_code'] ?>';
    navigator.clipboard.writeText(code).then(function() {
        alert('Confirmation code copied to clipboard!');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
