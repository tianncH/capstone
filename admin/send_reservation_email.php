<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/email_sender.php';

$success_message = '';
$error_message = '';

// Get reservation ID from URL
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reservation_id) {
    header('Location: reservation_management.php');
    exit;
}

// Get reservation details
$reservation_sql = "SELECT r.*, v.venue_name 
                    FROM reservations r 
                    JOIN venues v ON r.venue_id = v.venue_id 
                    WHERE r.reservation_id = ?";
$reservation_stmt = $conn->prepare($reservation_sql);
$reservation_stmt->bind_param('i', $reservation_id);
$reservation_stmt->execute();
$reservation = $reservation_stmt->get_result()->fetch_assoc();
$reservation_stmt->close();

if (!$reservation) {
    header('Location: reservation_management.php');
    exit;
}

// Process email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email_sender = new EmailSender();
        $email_type = $_POST['email_type'];
        
        $reservation_data = [
            'reservation_id' => $reservation['reservation_id'],
            'confirmation_code' => $reservation['confirmation_code'],
            'customer_name' => $reservation['customer_name'],
            'customer_email' => $reservation['customer_email'],
            'venue_name' => $reservation['venue_name'],
            'reservation_date' => $reservation['reservation_date'],
            'start_time' => $reservation['start_time'],
            'end_time' => $reservation['end_time'],
            'party_size' => $reservation['party_size'],
            'special_requests' => $reservation['special_requests']
        ];
        
        switch ($email_type) {
            case 'confirmation':
                $result = $email_sender->sendReservationConfirmation($reservation_data);
                break;
            case 'reminder':
                $result = $email_sender->sendReservationReminder($reservation_data);
                break;
            case 'cancellation':
                $result = $email_sender->sendReservationCancellation($reservation_data);
                break;
            default:
                throw new Exception('Invalid email type');
        }
        
        if ($result['success']) {
            $success_message = "Email sent successfully via " . $result['method'] . " to " . $reservation['customer_email'];
        } else {
            throw new Exception($result['error']);
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-envelope"></i> Send Reservation Email
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="reservation_management.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Reservations
            </a>
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

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calendar-check"></i> Reservation Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Customer Information</h6>
                            <p><strong>Name:</strong> <?= htmlspecialchars($reservation['customer_name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($reservation['customer_email']) ?></p>
                            <?php if ($reservation['customer_phone']): ?>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($reservation['customer_phone']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">Reservation Details</h6>
                            <p><strong>Venue:</strong> <?= htmlspecialchars($reservation['venue_name']) ?></p>
                            <p><strong>Date:</strong> <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?></p>
                            <p><strong>Time:</strong> <?= date('g:i A', strtotime($reservation['start_time'])) ?> - <?= date('g:i A', strtotime($reservation['end_time'])) ?></p>
                            <p><strong>Party Size:</strong> <?= $reservation['party_size'] ?> people</p>
                            <p><strong>Confirmation Code:</strong> <code><?= $reservation['confirmation_code'] ?></code></p>
                        </div>
                    </div>
                    
                    <?php if ($reservation['special_requests']): ?>
                        <div class="mt-3">
                            <h6 class="text-info">Special Requests</h6>
                            <p><?= htmlspecialchars($reservation['special_requests']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-envelope-paper"></i> Send Email
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email_type" class="form-label">Email Type</label>
                            <select class="form-select" id="email_type" name="email_type" required>
                                <option value="">Select email type...</option>
                                <option value="confirmation">Confirmation Email</option>
                                <option value="reminder">Reminder Email</option>
                                <option value="cancellation">Cancellation Email</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Recipient</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($reservation['customer_email']) ?></p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send"></i> Send Email
                        </button>
                    </form>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> Emails are sent using the configured email system. 
                            Check the server logs for delivery status.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

