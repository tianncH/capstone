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

// Check if venue rating already used
if ($receipt['venue_used']) {
    die("❌ Venue rating already submitted for this receipt");
}

// Check if receipt expired (30 days)
$expires_at = new DateTime($receipt['expires_at']);
$now = new DateTime();
if ($now > $expires_at) {
    die("❌ This receipt has expired");
}

// Handle venue rating submission
$rating_submitted = false;
$error_message = '';

if (isset($_POST['submit_rating'])) {
    $venue_rating = $_POST['venue_rating'] ?? 0;
    $venue_comment = $_POST['venue_comment'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    
    if ($venue_rating < 1 || $venue_rating > 5) {
        $error_message = "Please select a venue rating";
    } else {
        // Insert venue rating
        $rating_sql = "INSERT INTO venue_ratings (receipt_number, customer_name, venue_rating, venue_comment, created_at) 
                       VALUES (?, ?, ?, ?, NOW())";
        $rating_stmt = $conn->prepare($rating_sql);
        $rating_stmt->bind_param("ssis", $receipt['receipt_number'], $customer_name, $venue_rating, $venue_comment);
        
        if ($rating_stmt->execute()) {
            // Mark venue rating as used
            $generator->markVenueUsed($receipt['receipt_number']);
            $rating_submitted = true;
        } else {
            $error_message = "Failed to submit venue rating";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Our Venue - Cianos Seafoods Grill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .rating-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 600px;
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
        .star-rating {
            font-size: 2rem;
            color: #ffc107;
            cursor: pointer;
        }
        .star-rating .star {
            transition: all 0.2s;
        }
        .star-rating .star:hover,
        .star-rating .star.active {
            transform: scale(1.2);
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
        .venue-badge {
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
        <div class="rating-container">
            <div class="header">
                <h1><i class="bi bi-star-fill"></i> Rate Our Venue</h1>
                <p>Thank you for choosing our venue for your event!</p>
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
                    <span class="venue-badge">
                        <i class="bi bi-calendar-event"></i> Venue Customer
                    </span>
                    <span class="venue-badge">
                        <i class="bi bi-star-fill"></i> Authentic Rating
                    </span>
                </div>
            </div>
            
            <?php if ($rating_submitted): ?>
                <div class="p-4">
                    <div class="success-message">
                        <h3><i class="bi bi-check-circle-fill"></i> Thank You!</h3>
                        <p>Your venue rating has been submitted successfully.</p>
                        <p>We appreciate your honest feedback and will use it to improve our venue services.</p>
                        <a href="../ordering/cianos_welcome.php" class="btn btn-primary mt-3">
                            <i class="bi bi-house"></i> Back to Home
                        </a>
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
                        <div class="mb-4">
                            <label class="form-label"><strong>🏢 How was our venue for your event?</strong></label>
                            <div class="star-rating" id="venueRating">
                                <i class="bi bi-star star" data-rating="1"></i>
                                <i class="bi bi-star star" data-rating="2"></i>
                                <i class="bi bi-star star" data-rating="3"></i>
                                <i class="bi bi-star star" data-rating="4"></i>
                                <i class="bi bi-star star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="venue_rating" id="venueRatingInput" value="0">
                        </div>
                        
                        <div class="mb-4">
                            <label for="customer_name" class="form-label"><strong>Your Name (Optional)</strong></label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                   placeholder="Enter your name">
                        </div>
                        
                        <div class="mb-4">
                            <label for="venue_comment" class="form-label"><strong>💭 Tell us about your venue experience (Optional)</strong></label>
                            <textarea class="form-control" id="venue_comment" name="venue_comment" rows="4" 
                                      placeholder="Share your thoughts about our venue, facilities, ambiance, etc..."></textarea>
                        </div>
                        
                        <button type="submit" name="submit_rating" class="btn-submit">
                            <i class="bi bi-star"></i> Submit Venue Rating
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality
        const stars = document.querySelectorAll('#venueRating .star');
        const ratingInput = document.getElementById('venueRatingInput');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                        s.classList.remove('bi-star');
                        s.classList.add('bi-star-fill');
                    } else {
                        s.classList.remove('active');
                        s.classList.remove('bi-star-fill');
                        s.classList.add('bi-star');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
        
        document.getElementById('venueRating').addEventListener('mouseleave', function() {
            const currentRating = parseInt(ratingInput.value);
            stars.forEach((s, index) => {
                if (index < currentRating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>






