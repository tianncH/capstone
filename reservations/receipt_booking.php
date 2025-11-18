<?php
require_once '../admin/includes/db_connection.php';
require_once '../receipt_qr_generator.php';

// Get QR code from URL
$qr_code = $_GET['qr'] ?? '';

if (empty($qr_code)) {
    die("❌ Invalid QR code");
}

$generator = new ReceiptQRGenerator($conn);

// Find receipt by QR code
$sql = "SELECT rqr.*, o.total_amount, o.created_at as order_date, t.table_number 
        FROM receipt_qr_codes rqr 
        JOIN orders o ON rqr.order_id = o.order_id 
        JOIN tables t ON rqr.table_id = t.table_id 
        WHERE rqr.venue_qr_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $qr_code);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();

if (!$receipt) {
    die("❌ Receipt not found or invalid QR code");
}

// Check if venue booking already used
if ($receipt['venue_used']) {
    die("❌ Venue booking already used for this receipt");
}

// Check if receipt expired (30 days)
$expires_at = new DateTime($receipt['expires_at']);
$now = new DateTime();
if ($now > $expires_at) {
    die("❌ This receipt has expired");
}

// Handle booking submission
$booking_submitted = false;
$error_message = '';

if ($_POST['submit_booking']) {
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $guest_count = $_POST['guest_count'] ?? 0;
    $event_type = $_POST['event_type'] ?? '';
    $special_requests = $_POST['special_requests'] ?? '';
    
    if (empty($customer_name) || empty($customer_phone) || empty($event_date) || empty($event_time) || empty($guest_count)) {
        $error_message = "Please fill in all required fields";
    } else {
        // Insert booking
        $booking_sql = "INSERT INTO reservations (customer_name, customer_phone, customer_email, event_date, event_time, guest_count, event_type, special_requests, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        $booking_stmt = $conn->prepare($booking_sql);
        $booking_stmt->bind_param("ssssiiss", $customer_name, $customer_phone, $customer_email, $event_date, $event_time, $guest_count, $event_type, $special_requests);
        
        if ($booking_stmt->execute()) {
            // Mark venue booking as used
            $generator->markVenueUsed($receipt['receipt_number']);
            $booking_submitted = true;
            
            // Store booking ID for rating redirect
            $booking_id = $conn->insert_id;
        } else {
            $error_message = "Failed to submit booking request";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Venue - Cianos Seafoods Grill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .booking-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 700px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .receipt-info {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 15px;
        }
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .btn-submit {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .benefit-badge {
            background: linear-gradient(135deg, #ffc107, #ff8c00);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="booking-container">
            <div class="header">
                <h1><i class="bi bi-calendar-event"></i> Book Our Venue</h1>
                <p>Special booking access for valued customers!</p>
            </div>
            
            <div class="receipt-info">
                <h5><i class="bi bi-receipt"></i> Receipt Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Receipt #:</strong> <?= htmlspecialchars($receipt['receipt_number']) ?></p>
                        <p><strong>Table:</strong> <?= htmlspecialchars($receipt['table_number']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Order Date:</strong> <?= date('M d, Y g:i A', strtotime($receipt['order_date'])) ?></p>
                        <p><strong>Total:</strong> ₱<?= number_format($receipt['total_amount'], 2) ?></p>
                    </div>
                </div>
                
                <div class="mt-3">
                    <span class="benefit-badge">
                        <i class="bi bi-star-fill"></i> VIP Customer Access
                    </span>
                    <span class="benefit-badge">
                        <i class="bi bi-gift"></i> Priority Booking
                    </span>
                </div>
            </div>
            
            <?php if ($booking_submitted): ?>
                <div class="p-4">
                    <div class="success-message">
                        <h3><i class="bi bi-check-circle-fill"></i> Booking Request Submitted!</h3>
                        <p>Thank you for choosing Cianos Seafoods Grill for your event!</p>
                        <p>We will contact you within 24 hours to confirm your booking details.</p>
                        <p><strong>What happens next:</strong></p>
                        <ul class="text-start">
                            <li>We'll review your request and available dates</li>
                            <li>Our team will contact you to discuss details</li>
                            <li>We'll provide a customized quote for your event</li>
                            <li>Once confirmed, you'll receive a booking confirmation</li>
                        </ul>
                        <div class="mt-3">
                            <a href="venue_rating.php?qr=<?= $venue_qr ?>" class="btn btn-warning me-2">
                                <i class="bi bi-star"></i> Rate Our Venue
                            </a>
                            <a href="../ordering/cianos_welcome.php" class="btn btn-primary">
                                <i class="bi bi-house"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-4">
                    <?php if ($error_message): ?>
                        <div class="error-message">
                            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_name" class="form-label"><strong>Full Name *</strong></label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                       placeholder="Enter your full name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customer_phone" class="form-label"><strong>Phone Number *</strong></label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                       placeholder="Enter your phone number" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_email" class="form-label"><strong>Email Address</strong></label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                   placeholder="Enter your email address">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="event_date" class="form-label"><strong>Event Date *</strong></label>
                                <input type="date" class="form-control" id="event_date" name="event_date" 
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="event_time" class="form-label"><strong>Event Time *</strong></label>
                                <input type="time" class="form-control" id="event_time" name="event_time" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="guest_count" class="form-label"><strong>Number of Guests *</strong></label>
                                <input type="number" class="form-control" id="guest_count" name="guest_count" 
                                       min="1" max="100" placeholder="Enter number of guests" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="event_type" class="form-label"><strong>Event Type</strong></label>
                                <select class="form-control" id="event_type" name="event_type">
                                    <option value="">Select event type</option>
                                    <option value="Birthday Party">Birthday Party</option>
                                    <option value="Anniversary">Anniversary</option>
                                    <option value="Corporate Event">Corporate Event</option>
                                    <option value="Wedding Reception">Wedding Reception</option>
                                    <option value="Family Gathering">Family Gathering</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="special_requests" class="form-label"><strong>Special Requests</strong></label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="4" 
                                      placeholder="Any special requirements, dietary restrictions, or requests for your event..."></textarea>
                        </div>
                        
                        <button type="submit" name="submit_booking" class="btn-submit">
                            <i class="bi bi-calendar-check"></i> Submit Booking Request
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to tomorrow
        document.getElementById('event_date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>
