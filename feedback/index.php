<?php
require_once '../admin/includes/db_connection.php';

// Get parameters from QR code (via GET parameter)
$table_id = isset($_GET['table']) ? intval($_GET['table']) : null;
$reservation_id = isset($_GET['reservation_id']) ? intval($_GET['reservation_id']) : null;
$venue_id = isset($_GET['venue_id']) ? intval($_GET['venue_id']) : null;
$confirmation_code = isset($_GET['confirmation_code']) ? trim($_GET['confirmation_code']) : null;
$feedback_type = isset($_GET['type']) ? $_GET['type'] : 'table'; // 'table' or 'venue'

// Verify reservation and venue match if both are provided
$reservation_info = null;
if ($reservation_id && $confirmation_code && $venue_id) {
    $verify_sql = "SELECT r.*, v.venue_name 
                   FROM reservations r 
                   JOIN venues v ON r.venue_id = v.venue_id 
                   WHERE r.reservation_id = ? AND r.confirmation_code = ? AND r.venue_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param('isi', $reservation_id, $confirmation_code, $venue_id);
    $verify_stmt->execute();
    $reservation_info = $verify_stmt->get_result()->fetch_assoc();
    $verify_stmt->close();
    
    if (!$reservation_info) {
        $error_message = "Invalid reservation or venue access. Please use the correct QR code provided by our staff.";
    }
}

// Get table information if table_id is provided
$table = null;
if ($table_id) {
    $table_sql = "SELECT * FROM tables WHERE table_id = ?";
    $table_stmt = $conn->prepare($table_sql);
    $table_stmt->bind_param('i', $table_id);
    $table_stmt->execute();
    $table = $table_stmt->get_result()->fetch_assoc();
    $table_stmt->close();
}

$success_message = '';
$error_message = '';

// Process feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and validate multi-category ratings
        $food_rating = intval($_POST['food_quality_rating']);
        $service_rating = intval($_POST['service_quality_rating']);
        $venue_rating = intval($_POST['venue_quality_rating']);
        
        // Validate ratings
        if ($food_rating < 1 || $food_rating > 5 || $service_rating < 1 || $service_rating > 5 || $venue_rating < 1 || $venue_rating > 5) {
            throw new Exception('Please provide ratings for all categories (1-5 stars).');
        }
        
        // Get optional fields
        $customer_name = trim($_POST['customer_name']);
        $customer_email = trim($_POST['customer_email']);
        $customer_phone = trim($_POST['customer_phone']);
        $food_comments = trim($_POST['food_quality_comments']);
        $service_comments = trim($_POST['service_quality_comments']);
        $venue_comments = trim($_POST['venue_quality_comments']);
        $reservation_experience = $_POST['reservation_experience'] ?? 'not_applicable';
        $reservation_comments = trim($_POST['reservation_comments']);
        $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
        
        // Insert feedback into database with multi-category structure
        $sql = "INSERT INTO feedback (
                    table_id, reservation_id, customer_name, customer_email, customer_phone,
                    food_quality_rating, food_quality_comments,
                    service_quality_rating, service_quality_comments,
                    venue_quality_rating, venue_quality_comments,
                    reservation_experience, reservation_comments,
                    is_anonymous, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iisssisssisssi', 
            $table_id, $reservation_id, $customer_name, $customer_email, $customer_phone,
            $food_rating, $food_comments,
            $service_rating, $service_comments,
            $venue_rating, $venue_comments,
            $reservation_experience, $reservation_comments,
            $is_anonymous
        );
        
        if ($stmt->execute()) {
            $success_message = "Thank you for your detailed feedback! We appreciate your input and will use it to improve our service.";
        } else {
            throw new Exception('Failed to submit feedback. Please try again.');
        }
        $stmt->close();
        
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
    <title>Customer Feedback - Restaurant</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #ecf0f1;
            --shadow-light: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-medium: 0 4px 20px rgba(0,0,0,0.15);
            --gradient-primary: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            --gradient-success: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            --gradient-warning: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        body {
            background: var(--light-bg);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        .feedback-container {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-medium);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        /* Elegant Fine Dining Rating System */
        .elegant-rating {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 25px 0;
            flex-wrap: wrap;
        }
        
        .rating-option {
            cursor: pointer;
            padding: 20px 15px;
            border-radius: 12px;
            background: white;
            border: 2px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            min-width: 120px;
            text-align: center;
        }
        
        .rating-option:hover {
            transform: translateY(-3px);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-medium);
            background: var(--light-bg);
        }
        
        .rating-option.selected {
            background: var(--gradient-primary);
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }
        
        .rating-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--light-bg);
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            transition: all 0.3s ease;
        }
        
        .rating-option:hover .rating-circle {
            border-color: var(--primary-color);
            background: white;
            transform: scale(1.1);
        }
        
        .rating-option.selected .rating-circle {
            background: white;
            border-color: white;
            color: var(--primary-color);
        }
        
        .rating-circle i {
            font-size: 1.2rem;
            color: var(--text-light);
            transition: all 0.3s ease;
        }
        
        .rating-option:hover .rating-circle i {
            color: var(--primary-color);
        }
        
        .rating-option.selected .rating-circle i {
            color: var(--primary-color);
        }
        
        .rating-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-light);
            text-align: center;
            margin-top: 8px;
            transition: color 0.3s ease;
            line-height: 1.3;
        }
        
        .rating-option:hover .rating-label {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .rating-option.selected .rating-label {
            color: white;
            font-weight: 600;
        }
        
        
        /* Rating Section Styling */
        .rating-section {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            border: 1px solid var(--border-color);
        }
        
        .rating-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 15px;
            text-align: center;
        }
        
        .feedback-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .rating-stars {
            font-size: 2rem;
            color: #ffc107;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .rating-stars:hover {
            transform: scale(1.1);
        }
        
        .rating-stars.selected {
            color: #ffc107;
        }
        
        .rating-stars.unselected {
            color: #e9ecef;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
        }
        
        .table-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="feedback-container">
                    <div class="feedback-header">
                        <h1 class="h2 mb-3">
                            <i class="bi bi-heart-fill"></i> How was your dining experience?
                        </h1>
                        <p class="mb-0">
                            We'd love to hear about your visit! Your feedback helps us create amazing experiences for all our guests.
                        </p>
                    </div>
                    
                    <div class="p-4">
                        <?php if ($table): ?>
                            <div class="table-info">
                                <h6 class="text-primary mb-1">
                                    <i class="bi bi-tablet"></i> Table #<?= htmlspecialchars($table['table_number']) ?>
                                </h6>
                                <small class="text-muted">Thank you for dining with us!</small>
                            </div>
                        <?php endif; ?>
                        
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
                        
                        <?php if (!$success_message): ?>
        <form method="POST">
            <input type="hidden" name="table_id" value="<?= $table_id ?>">
            <input type="hidden" name="reservation_id" value="<?= $reservation_id ?>">
                                
                                <!-- Food Quality Rating -->
                                <div class="rating-section">
                                    <div class="rating-title">
                                        <i class="bi bi-egg-fried text-warning"></i> How was your food?
                                    </div>
                                    <div class="elegant-rating" data-category="food">
                                        <div class="rating-option" data-rating="1">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Needs Improvement</div>
                                        </div>
                                        <div class="rating-option" data-rating="2">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Fair</div>
                                        </div>
                                        <div class="rating-option" data-rating="3">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Good</div>
                                        </div>
                                        <div class="rating-option" data-rating="4">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Very Good</div>
                                        </div>
                                        <div class="rating-option" data-rating="5">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Exceptional</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="food_quality_rating" id="food_rating" required>
                                    <textarea class="form-control mt-3" name="food_quality_comments" rows="2" 
                                              placeholder="Tell us about the taste, presentation, temperature... (optional)"></textarea>
                                </div>
                                
                                <!-- Service Quality Rating -->
                                <div class="rating-section">
                                    <div class="rating-title">
                                        <i class="bi bi-person-heart text-primary"></i> How was our service?
                                    </div>
                                    <div class="elegant-rating" data-category="service">
                                        <div class="rating-option" data-rating="1">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Needs Attention</div>
                                        </div>
                                        <div class="rating-option" data-rating="2">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Adequate</div>
                                        </div>
                                        <div class="rating-option" data-rating="3">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Good</div>
                                        </div>
                                        <div class="rating-option" data-rating="4">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Excellent</div>
                                        </div>
                                        <div class="rating-option" data-rating="5">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Outstanding</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="service_quality_rating" id="service_rating" required>
                                    <textarea class="form-control mt-3" name="service_quality_comments" rows="2" 
                                              placeholder="Tell us about our staff, speed, friendliness... (optional)"></textarea>
                                </div>
                                
                                <!-- Overall Experience Rating -->
                                <div class="rating-section">
                                    <div class="rating-title">
                                        <i class="bi bi-heart-fill text-danger"></i> Overall Experience
                                    </div>
                                    <div class="elegant-rating" data-category="venue">
                                        <div class="rating-option" data-rating="1">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Disappointing</div>
                                        </div>
                                        <div class="rating-option" data-rating="2">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Below Expectations</div>
                                        </div>
                                        <div class="rating-option" data-rating="3">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Met Expectations</div>
                                        </div>
                                        <div class="rating-option" data-rating="4">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Exceeded Expectations</div>
                                        </div>
                                        <div class="rating-option" data-rating="5">
                                            <div class="rating-circle">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="rating-label">Exceptional Experience</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="venue_quality_rating" id="venue_rating" required>
                                    <textarea class="form-control mt-3" name="venue_quality_comments" rows="2" 
                                              placeholder="Tell us about your overall dining experience... (optional)"></textarea>
                                </div>
                                
                                
                                <!-- Customer Information -->
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="customer_name" class="form-label">Your Name (Optional)</label>
                                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                                   placeholder="Enter your name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="customer_email" class="form-label">Email (Optional)</label>
                                            <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                                   placeholder="Enter your email">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="customer_phone" class="form-label">Phone (Optional)</label>
                                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                                   placeholder="Enter your phone">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Anonymous Option -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_anonymous" name="is_anonymous">
                                        <label class="form-check-label" for="is_anonymous">
                                            Submit feedback anonymously
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-heart-fill"></i> Share Your Experience
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-heart-fill text-danger" style="font-size: 3rem;"></i>
                                <h4 class="mt-3">Thank You!</h4>
                                <p class="text-muted">Your feedback is valuable to us and helps us improve our service.</p>
                                <a href="../ordering/index.php<?= $table_id ? '?table=' . $table_id : '' ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left"></i> Back to Menu
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Elegant Fine Dining Rating System
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize elegant rating systems
            const elegantRatings = document.querySelectorAll('.elegant-rating');
            
            elegantRatings.forEach(ratingContainer => {
                const ratingOptions = ratingContainer.querySelectorAll('.rating-option');
                const hiddenInput = document.getElementById(ratingContainer.dataset.category + '_rating');
                
                ratingOptions.forEach(option => {
                    option.addEventListener('click', function() {
                        // Remove selected class from all options in this category
                        ratingOptions.forEach(opt => opt.classList.remove('selected'));
                        
                        // Add selected class to clicked option
                        this.classList.add('selected');
                        
                        // Update hidden input value
                        hiddenInput.value = this.dataset.rating;
                        
                        // Elegant selection feedback
                        this.style.transform = 'translateY(-5px)';
                    });
                });
            });
            
            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const foodRating = document.getElementById('food_rating').value;
                const serviceRating = document.getElementById('service_rating').value;
                const venueRating = document.getElementById('venue_rating').value;
                
                if (!foodRating || !serviceRating || !venueRating) {
                    e.preventDefault();
                    alert('Please rate all three categories before submitting your feedback.');
                    return false;
                }
            });
        });
        
        // Legacy star rating system (keeping for compatibility)
        function initializeRatingSystem(category) {
            const stars = document.querySelectorAll(`.${category}-rating`);
            const ratingInput = document.getElementById(`${category}_rating`);
            const ratingText = document.getElementById(`${category}-rating-text`);
            
            stars.forEach((star, index) => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    ratingInput.value = rating;
                    
                    // Update star display for this category
                    stars.forEach((s, i) => {
                        if (i < rating) {
                            s.classList.remove('unselected');
                            s.classList.add('selected');
                        } else {
                            s.classList.remove('selected');
                            s.classList.add('unselected');
                        }
                    });
                    
                    // Update rating text
                    ratingText.textContent = ratingTexts[rating];
                });
                
                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(this.dataset.rating);
                    stars.forEach((s, i) => {
                        if (i < rating) {
                            s.style.color = '#ffc107';
                        } else {
                            s.style.color = '#e9ecef';
                        }
                    });
                });
            });
            
            // Reset stars on mouse leave for this category
            stars[0].parentElement.addEventListener('mouseleave', function() {
                const currentRating = parseInt(ratingInput.value) || 0;
                stars.forEach((s, i) => {
                    if (i < currentRating) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#e9ecef';
                    }
                });
            });
            
            // Initialize stars as unselected
            stars.forEach(star => {
                star.classList.add('unselected');
            });
        }
        
        // Initialize all rating systems
        document.addEventListener('DOMContentLoaded', function() {
            initializeRatingSystem('food');
            initializeRatingSystem('service');
            initializeRatingSystem('venue');
            
            // Check if customer came from reservation system
            checkReservationExperience();
        });
        
        function checkReservationExperience() {
            const urlParams = new URLSearchParams(window.location.search);
            const reservationId = urlParams.get('reservation_id');
            const confirmationCode = urlParams.get('confirmation_code');
            
            if (reservationId && confirmationCode) {
                // Customer came from reservation system
                document.getElementById('reservation_experience').value = 'used_system';
                
                // Pre-fill customer information if available
                fetch(`../admin/get_reservation_details.php?id=${reservationId}&code=${confirmationCode}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.reservation) {
                            const reservation = data.reservation;
                            if (reservation.customer_name) {
                                document.getElementById('customer_name').value = reservation.customer_name;
                            }
                            if (reservation.customer_email) {
                                document.getElementById('customer_email').value = reservation.customer_email;
                            }
                            if (reservation.customer_phone) {
                                document.getElementById('customer_phone').value = reservation.customer_phone;
                            }
                        }
                    })
                    .catch(error => {
                        console.log('Could not fetch reservation details:', error);
                    });
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>