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

// Get venue types from database
$venue_types_sql = "SELECT venue_name FROM venue_types WHERE is_active = 1 ORDER BY display_order, venue_name";
$venue_types_result = $conn->query($venue_types_sql);
$venue_types = [];
while ($row = $venue_types_result->fetch_assoc()) {
    $venue_types[] = $row['venue_name'];
}

// Handle venue rating submission
$rating_submitted = false;
$error_message = '';

if (isset($_POST['submit_rating'])) {
    $venue_reservation_quality = $_POST['venue_reservation_quality'] ?? 0;
    $venue_type = $_POST['venue_type'] ?? '';
    $venue_quality_rating = $_POST['venue_quality_rating'] ?? 0;
    $suggestions = $_POST['suggestions'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    
    if ($venue_reservation_quality < 1 || $venue_reservation_quality > 5 || $venue_quality_rating < 1 || $venue_quality_rating > 5 || empty($venue_type)) {
        $error_message = "Please rate both categories and select venue type";
    } else {
        // Get device type and IP
        $device_type = 'desktop';
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            if (preg_match('/Mobile|Android|iPhone|iPad/', $user_agent)) {
                $device_type = 'mobile';
            } elseif (preg_match('/Tablet|iPad/', $user_agent)) {
                $device_type = 'tablet';
            }
        }
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Insert venue rating
        $rating_sql = "INSERT INTO venue_ratings (receipt_number, customer_name, venue_reservation_quality, venue_type, venue_quality_rating, suggestions, device_type, ip_address, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $rating_stmt = $conn->prepare($rating_sql);
        $rating_stmt->bind_param("ssiissss", $receipt['receipt_number'], $customer_name, $venue_reservation_quality, $venue_type, $venue_quality_rating, $suggestions, $device_type, $ip_address);
        
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
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .rating-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 700px;
            overflow: hidden;
            width: 95%;
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
            color: var(--warning-color);
            cursor: pointer;
            display: flex;
            justify-content: center;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .star-rating .star {
            transition: all 0.2s;
            user-select: none;
        }
        
        .star-rating .star:hover,
        .star-rating .star.active {
            transform: scale(1.2);
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 15px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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
        
        .rating-section {
            margin-bottom: 2rem;
        }
        
        .rating-label {
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
        }
        
        .rating-description {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .rating-container {
                margin: 10px auto;
                border-radius: 15px;
            }
            
            .header {
                padding: 20px;
            }
            
            .star-rating {
                font-size: 1.8rem;
                gap: 3px;
            }
            
            .form-control, .form-select {
                padding: 12px;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .btn-submit {
                padding: 12px 25px;
                font-size: 1rem;
            }
        }
        
        /* Tablet optimizations */
        @media (min-width: 769px) and (max-width: 1024px) {
            .rating-container {
                max-width: 800px;
            }
            
            .star-rating {
                font-size: 2.2rem;
                gap: 8px;
            }
        }
        
        /* Large screen optimizations */
        @media (min-width: 1025px) {
            .rating-container {
                max-width: 700px;
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .star-rating .star {
                padding: 5px;
            }
            
            .btn-submit {
                min-height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
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
            </div>
            
            <?php if ($rating_submitted): ?>
                <div class="p-4">
                    <div class="success-message">
                        <h3><i class="bi bi-check-circle-fill"></i> Thank You!</h3>
                        <p>Your venue rating has been submitted successfully.</p>
                        <p><strong>Ratings submitted:</strong></p>
                        <ul class="text-start">
                            <li>📅 Reservation Quality: <?= $venue_reservation_quality ?>/5</li>
                            <li>🏢 Venue Type: <?= $venue_type ?></li>
                            <li>⭐ Venue Quality: <?= $venue_quality_rating ?>/5</li>
                        </ul>
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
                    
                    <form method="POST" id="venueRatingForm">
                        <div class="rating-section">
                            <label class="rating-label"><strong>📅 Venue Reservation Quality</strong></label>
                            <div class="star-rating" id="reservationRating">
                                <i class="bi bi-star star" data-rating="1"></i>
                                <i class="bi bi-star star" data-rating="2"></i>
                                <i class="bi bi-star star" data-rating="3"></i>
                                <i class="bi bi-star star" data-rating="4"></i>
                                <i class="bi bi-star star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="venue_reservation_quality" id="reservationRatingInput" value="0">
                            <div class="rating-description">Rate the reservation process and staff assistance</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="venue_type" class="form-label"><strong>🏢 Venue Type *</strong></label>
                            <select class="form-select" id="venue_type" name="venue_type" required>
                                <option value="">Select venue type</option>
                                <?php foreach ($venue_types as $type): ?>
                                    <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="rating-section">
                            <label class="rating-label"><strong>⭐ Venue Quality Rating</strong></label>
                            <div class="star-rating" id="venueRating">
                                <i class="bi bi-star star" data-rating="1"></i>
                                <i class="bi bi-star star" data-rating="2"></i>
                                <i class="bi bi-star star" data-rating="3"></i>
                                <i class="bi bi-star star" data-rating="4"></i>
                                <i class="bi bi-star star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="venue_quality_rating" id="venueRatingInput" value="0">
                            <div class="rating-description">Rate the venue facilities, ambiance, and overall experience</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="customer_name" class="form-label"><strong>Your Name (Optional)</strong></label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                   placeholder="Enter your name">
                        </div>
                        
                        <div class="mb-4">
                            <label for="suggestions" class="form-label"><strong>💭 Suggestions (Optional)</strong></label>
                            <textarea class="form-control" id="suggestions" name="suggestions" rows="4" 
                                      placeholder="Share your suggestions to help us improve our venue services..."></textarea>
                        </div>
                        
                        <button type="submit" name="submit_rating" class="btn-submit" id="submitBtn">
                            <i class="bi bi-star"></i> Submit Venue Rating
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality for both rating systems
        function setupStarRating(containerId, inputId) {
            const stars = document.querySelectorAll(`#${containerId} .star`);
            const ratingInput = document.getElementById(inputId);
            
            stars.forEach(star => {
                // Click event
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    ratingInput.value = rating;
                    updateStars(stars, rating);
                    checkFormCompletion();
                });
                
                // Touch event for mobile
                star.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    const rating = parseInt(this.dataset.rating);
                    ratingInput.value = rating;
                    updateStars(stars, rating);
                    checkFormCompletion();
                });
                
                // Hover events (desktop only)
                star.addEventListener('mouseenter', function() {
                    if (window.innerWidth > 768) {
                        const rating = parseInt(this.dataset.rating);
                        updateStars(stars, rating, true);
                    }
                });
            });
            
            // Mouse leave event
            document.getElementById(containerId).addEventListener('mouseleave', function() {
                if (window.innerWidth > 768) {
                    const currentRating = parseInt(ratingInput.value);
                    updateStars(stars, currentRating);
                }
            });
        }
        
        function updateStars(stars, rating, isHover = false) {
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
        }
        
        function checkFormCompletion() {
            const reservationRating = parseInt(document.getElementById('reservationRatingInput').value);
            const venueRating = parseInt(document.getElementById('venueRatingInput').value);
            const venueType = document.getElementById('venue_type').value;
            
            const submitBtn = document.getElementById('submitBtn');
            
            if (reservationRating > 0 && venueRating > 0 && venueType !== '') {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
            }
        }
        
        // Setup both rating systems
        setupStarRating('reservationRating', 'reservationRatingInput');
        setupStarRating('venueRating', 'venueRatingInput');
        
        // Venue type change event
        document.getElementById('venue_type').addEventListener('change', checkFormCompletion);
        
        // Form validation
        document.getElementById('venueRatingForm').addEventListener('submit', function(e) {
            const reservationRating = parseInt(document.getElementById('reservationRatingInput').value);
            const venueRating = parseInt(document.getElementById('venueRatingInput').value);
            const venueType = document.getElementById('venue_type').value;
            
            if (reservationRating < 1 || venueRating < 1 || venueType === '') {
                e.preventDefault();
                alert('Please rate both categories and select venue type before submitting.');
                return false;
            }
        });
        
        // Initial form state
        checkFormCompletion();
    </script>
</body>
</html>






