<?php
require_once '../admin/includes/db_connection.php';

$reservation_id = $_GET['id'] ?? null;
$confirmation_code = $_GET['code'] ?? null;

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmation - Restaurant</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
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
        }
        
        .confirmation-container {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-medium);
            margin: 20px auto;
            max-width: 800px;
            overflow: hidden;
        }
        
        .header-section {
            background: var(--success-color);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .details-section {
            padding: 40px;
        }
        
        .detail-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #28a745;
        }
        
        .confirmation-code {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 25px;
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 2px;
            display: inline-block;
            margin: 20px 0;
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
        
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn-custom {
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            margin: 0 10px;
            transition: all 0.3s ease;
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
            color: white;
        }
        
        .btn-success-custom {
            background: var(--success-color);
            border: 1px solid var(--success-color);
            color: white;
        }
        
        .btn-success-custom:hover {
            background: #229954;
            border-color: #229954;
            transform: translateY(-1px);
            box-shadow: var(--shadow-light);
            color: white;
        }
        
        .btn-warning-custom {
            background: var(--accent-color);
            border: 1px solid var(--accent-color);
            color: white;
        }
        
        .btn-warning-custom:hover {
            background: #c0392b;
            border-color: #c0392b;
            transform: translateY(-1px);
            box-shadow: var(--shadow-light);
            color: white;
        }
        
        .info-section {
            background: #e3f2fd;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .info-section h6 {
            color: #1976d2;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-container">
            <!-- Header Section -->
            <div class="header-section">
                <div class="success-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h1 class="display-4 mb-3">Reservation Confirmed!</h1>
                <p class="lead">Thank you for choosing our restaurant. We look forward to serving you!</p>
                
                <div class="confirmation-code">
                    <?= $reservation['confirmation_code'] ?>
                </div>
                
                <div class="status-badge status-<?= $reservation['status'] ?>">
                    <?= ucfirst($reservation['status']) ?>
                </div>
            </div>
            
            <!-- Details Section -->
            <div class="details-section">
                <h3 class="text-center mb-4">
                    <i class="bi bi-calendar-check"></i> Reservation Details
                </h3>
                
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
                
                <!-- Important Information -->
                <div class="info-section">
                    <h6>
                        <i class="bi bi-info-circle"></i> Important Information
                    </h6>
                    <ul class="mb-0">
                        <li>Please arrive on time for your reservation. We hold reservations for 15 minutes past the scheduled time.</li>
                        <li>If you need to make changes or cancel your reservation, please contact us at least 2 hours in advance.</li>
                        <li>Keep your confirmation code safe - you may need it for any changes or inquiries.</li>
                        <li>We will send you a confirmation email shortly with all the details.</li>
                    </ul>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="index.php" class="btn btn-primary-custom btn-custom">
                        <i class="bi bi-calendar-plus"></i> Make Another Reservation
                    </a>
                    <a href="manage_reservation.php?id=<?= $reservation['reservation_id'] ?>&code=<?= $reservation['confirmation_code'] ?>" 
                       class="btn btn-success-custom btn-custom">
                        <i class="bi bi-gear"></i> Manage Reservation
                    </a>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-print confirmation (optional)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 1000);
        // }
        
        // Copy confirmation code to clipboard
        function copyConfirmationCode() {
            const code = '<?= $reservation['confirmation_code'] ?>';
            navigator.clipboard.writeText(code).then(function() {
                alert('Confirmation code copied to clipboard!');
            });
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
