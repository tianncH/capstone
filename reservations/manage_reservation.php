<?php
require_once '../admin/includes/db_connection.php';

$reservation_id = $_GET['id'] ?? null;
$confirmation_code = $_GET['code'] ?? null;
$success_message = '';
$error_message = '';

if (!$reservation_id || !$confirmation_code) {
    header("Location: index.php");
    exit;
}

// Get reservation details
$sql = "SELECT r.*, v.venue_name, v.description as venue_description, v.max_capacity 
        FROM reservations r 
        JOIN venues v ON r.venue_id = v.venue_id 
        WHERE r.reservation_id = ? AND r.confirmation_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $reservation_id, $confirmation_code);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    header("Location: index.php");
    exit;
}

// Handle cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    try {
        $cancellation_reason = trim($_POST['cancellation_reason']);
        
        if (empty($cancellation_reason)) {
            throw new Exception("Please provide a reason for cancellation.");
        }
        
        // Check if reservation can be cancelled (not in the past)
        if (strtotime($reservation['reservation_date'] . ' ' . $reservation['start_time']) < time()) {
            throw new Exception("Cannot cancel a reservation that has already passed.");
        }
        
        // Update reservation status
        $update_sql = "UPDATE reservations SET status = 'cancelled', admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nCancelled by customer: ', ?) WHERE reservation_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('si', $cancellation_reason, $reservation_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Your reservation has been cancelled successfully.";
            // Refresh reservation data
            $stmt->execute();
            $reservation = $stmt->get_result()->fetch_assoc();
        } else {
            throw new Exception("Failed to cancel reservation. Please try again.");
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservation - Restaurant</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .management-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 900px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 30px;
            text-align: center;
        }
        
        .details-section {
            padding: 40px;
        }
        
        .detail-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
            border: 2px solid #bee5eb;
        }
        
        .btn-custom {
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-danger-custom {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            color: white;
        }
        
        .btn-danger-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .confirmation-code {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 1.2rem;
            font-weight: bold;
            letter-spacing: 1px;
            display: inline-block;
        }
        
        .warning-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .info-section {
            background: #e3f2fd;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="management-container">
            <!-- Header Section -->
            <div class="header-section">
                <h1 class="display-5 mb-3">
                    <i class="bi bi-gear"></i> Manage Your Reservation
                </h1>
                <p class="lead">View and manage your reservation details</p>
                
                <div class="confirmation-code">
                    <?= $reservation['confirmation_code'] ?>
                </div>
                
                <div class="status-badge status-<?= $reservation['status'] ?>">
                    <?= ucfirst($reservation['status']) ?>
                </div>
            </div>
            
            <!-- Details Section -->
            <div class="details-section">
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-person-circle"></i> Customer Information
                            </h6>
                            <p><strong>Name:</strong> <?= htmlspecialchars($reservation['customer_name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($reservation['customer_email']) ?></p>
                            <?php if ($reservation['customer_phone']): ?>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($reservation['customer_phone']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-building"></i> Venue Information
                            </h6>
                            <p><strong>Venue:</strong> <?= htmlspecialchars($reservation['venue_name']) ?></p>
                            <?php if ($reservation['venue_description']): ?>
                                <p><strong>Description:</strong> <?= htmlspecialchars($reservation['venue_description']) ?></p>
                            <?php endif; ?>
                            <p><strong>Capacity:</strong> Up to <?= $reservation['max_capacity'] ?> people</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-calendar-date"></i> Date & Time
                            </h6>
                            <p><strong>Date:</strong> <?= date('l, F j, Y', strtotime($reservation['reservation_date'])) ?></p>
                            <p><strong>Time:</strong> <?= date('g:i A', strtotime($reservation['start_time'])) ?> - <?= date('g:i A', strtotime($reservation['end_time'])) ?></p>
                            <p><strong>Duration:</strong> <?= round((strtotime($reservation['end_time']) - strtotime($reservation['start_time'])) / 3600, 1) ?> hours</p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-people"></i> Party Details
                            </h6>
                            <p><strong>Party Size:</strong> <?= $reservation['party_size'] ?> people</p>
                            <p><strong>Reservation Type:</strong> <?= ucfirst($reservation['reservation_type']) ?></p>
                            <p><strong>Reservation ID:</strong> #<?= $reservation['reservation_id'] ?></p>
                        </div>
                    </div>
                </div>
                
                <?php if ($reservation['special_requests']): ?>
                    <div class="detail-card">
                        <h6 class="text-primary mb-3">
                            <i class="bi bi-chat-dots"></i> Special Requests
                        </h6>
                        <p><?= nl2br(htmlspecialchars($reservation['special_requests'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="text-center mt-4">
                    <?php if ($reservation['status'] === 'cancelled'): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            This reservation has been cancelled. If you need to make a new reservation, please use the link below.
                        </div>
                        <a href="index.php" class="btn btn-primary-custom btn-custom">
                            <i class="bi bi-calendar-plus"></i> Make New Reservation
                        </a>
                    <?php elseif ($reservation['status'] === 'completed'): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            This reservation has been completed. We hope you enjoyed your dining experience!
                        </div>
                        <a href="index.php" class="btn btn-primary-custom btn-custom">
                            <i class="bi bi-calendar-plus"></i> Make Another Reservation
                        </a>
                    <?php else: ?>
                        <!-- Check if reservation can be cancelled (not in the past) -->
                        <?php if (strtotime($reservation['reservation_date'] . ' ' . $reservation['start_time']) > time()): ?>
                            <button type="button" class="btn btn-danger-custom btn-custom" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                <i class="bi bi-x-circle"></i> Cancel Reservation
                            </button>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                This reservation has already passed and cannot be cancelled.
                            </div>
                        <?php endif; ?>
                        
                        <a href="index.php" class="btn btn-primary-custom btn-custom">
                            <i class="bi bi-calendar-plus"></i> Make Another Reservation
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Important Information -->
                <div class="info-section">
                    <h6>
                        <i class="bi bi-info-circle"></i> Important Information
                    </h6>
                    <ul class="mb-0">
                        <li>Please arrive on time for your reservation. We hold reservations for 15 minutes past the scheduled time.</li>
                        <li>If you need to make changes to your reservation, please contact us at least 2 hours in advance.</li>
                        <li>Keep your confirmation code safe - you may need it for any changes or inquiries.</li>
                        <li>For any questions or special requests, please contact us directly.</li>
                    </ul>
                </div>
                
                <!-- Contact Information -->
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <i class="bi bi-telephone"></i> Need help? Contact us at 
                        <strong>(555) 123-4567</strong> or 
                        <strong>reservations@restaurant.com</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancel Reservation Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Cancel Reservation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="cancel">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Warning:</strong> This action cannot be undone. Are you sure you want to cancel your reservation?
                        </div>
                        
                        <div class="mb-3">
                            <label for="cancellation_reason" class="form-label">Reason for Cancellation *</label>
                            <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="3" 
                                      placeholder="Please let us know why you're cancelling..." required></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            If you need to reschedule instead of cancel, please contact us directly.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Reservation</button>
                        <button type="submit" class="btn btn-danger">Cancel Reservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
