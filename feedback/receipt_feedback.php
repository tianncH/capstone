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
        WHERE rqr.feedback_qr_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $qr_code);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();

if (!$receipt) {
    die("❌ Receipt not found or invalid QR code");
}

// Check if feedback already used
if ($receipt['feedback_used']) {
    die("❌ Feedback already submitted for this receipt");
}

// Check if receipt expired (30 days)
$expires_at = new DateTime($receipt['expires_at']);
$now = new DateTime();
if ($now > $expires_at) {
    die("❌ This receipt has expired");
}

// Handle feedback submission
$feedback_submitted = false;
$error_message = '';

if (isset($_POST['submit_feedback'])) {
    $food_quality = $_POST['food_quality'] ?? 0;
    $service_quality = $_POST['service_quality'] ?? 0;
    $place_quality = $_POST['place_quality'] ?? 0;
    $suggestions = $_POST['suggestions'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    
    if ($food_quality < 1 || $food_quality > 5 || $service_quality < 1 || $service_quality > 5 || $place_quality < 1 || $place_quality > 5) {
        $error_message = "Please rate all three categories (Food, Service, Place)";
    } else {
        // Calculate overall rating
        $overall_rating = round(($food_quality + $service_quality + $place_quality) / 3, 1);
        
        // Insert feedback
        $feedback_sql = "INSERT INTO feedback (table_id, customer_name, rating, comment, feedback_type, food_quality, service_quality, place_quality, suggestions, created_at) 
                        VALUES (?, ?, ?, ?, 'receipt', ?, ?, ?, ?, NOW())";
        $feedback_stmt = $conn->prepare($feedback_sql);
        $feedback_stmt->bind_param("isdisiis", $receipt['table_id'], $customer_name, $overall_rating, $suggestions, $food_quality, $service_quality, $place_quality, $suggestions);
        
        if ($feedback_stmt->execute()) {
            // Mark feedback as used
            $generator->markFeedbackUsed($receipt['receipt_number']);
            $feedback_submitted = true;
        } else {
            $error_message = "Failed to submit feedback";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Feedback - Cianos Seafoods Grill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .feedback-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 600px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
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
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
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
    </style>
</head>
<body>
    <div class="container">
        <div class="feedback-container">
            <div class="header">
                <h1><i class="bi bi-star-fill"></i> Leave Feedback</h1>
                <p>Thank you for dining with us!</p>
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
            </div>
            
            <?php if ($feedback_submitted): ?>
                <div class="p-4">
                    <div class="success-message">
                        <h3><i class="bi bi-check-circle-fill"></i> Thank You!</h3>
                        <p>Your feedback has been submitted successfully.</p>
                        <p>We appreciate your time and will use your feedback to improve our service.</p>
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
                            <label class="form-label"><strong>🍽️ Food Quality</strong></label>
                            <div class="star-rating" id="foodRating">
                                <i class="bi bi-star star" data-rating="1"></i>
                                <i class="bi bi-star star" data-rating="2"></i>
                                <i class="bi bi-star star" data-rating="3"></i>
                                <i class="bi bi-star star" data-rating="4"></i>
                                <i class="bi bi-star star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="food_quality" id="foodRatingInput" value="0">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label"><strong>🛎️ Service Quality</strong></label>
                            <div class="star-rating" id="serviceRating">
                                <i class="bi bi-star star" data-rating="1"></i>
                                <i class="bi bi-star star" data-rating="2"></i>
                                <i class="bi bi-star star" data-rating="3"></i>
                                <i class="bi bi-star star" data-rating="4"></i>
                                <i class="bi bi-star star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="service_quality" id="serviceRatingInput" value="0">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label"><strong>🏢 Place Quality</strong></label>
                            <div class="star-rating" id="placeRating">
                                <i class="bi bi-star star" data-rating="1"></i>
                                <i class="bi bi-star star" data-rating="2"></i>
                                <i class="bi bi-star star" data-rating="3"></i>
                                <i class="bi bi-star star" data-rating="4"></i>
                                <i class="bi bi-star star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="place_quality" id="placeRatingInput" value="0">
                        </div>
                        
                        <div class="mb-4">
                            <label for="customer_name" class="form-label"><strong>Your Name (Optional)</strong></label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                   placeholder="Enter your name">
                        </div>
                        
                        <div class="mb-4">
                            <label for="suggestions" class="form-label"><strong>💭 Suggestions (Optional)</strong></label>
                            <textarea class="form-control" id="suggestions" name="suggestions" rows="4" 
                                      placeholder="Share your suggestions to help us improve..."></textarea>
                        </div>
                        
                        <button type="submit" name="submit_feedback" class="btn-submit">
                            <i class="bi bi-send"></i> Submit Feedback
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality for all three categories
        function setupStarRating(containerId, inputId) {
            const stars = document.querySelectorAll(`#${containerId} .star`);
            const ratingInput = document.getElementById(inputId);
            
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
            
            document.getElementById(containerId).addEventListener('mouseleave', function() {
                const currentRating = parseInt(ratingInput.value);
                stars.forEach((s, index) => {
                    if (index < currentRating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        }
        
        // Setup all three rating systems
        setupStarRating('foodRating', 'foodRatingInput');
        setupStarRating('serviceRating', 'serviceRatingInput');
        setupStarRating('placeRating', 'placeRatingInput');
    </script>
</body>
</html>
