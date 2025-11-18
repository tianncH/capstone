<?php
require_once '../admin/includes/db_connection.php';

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and validate input
        $customer_name = !empty($_POST['customer_name']) ? trim($_POST['customer_name']) : null;
        $customer_email = !empty($_POST['customer_email']) ? trim($_POST['customer_email']) : null;
        $customer_phone = !empty($_POST['customer_phone']) ? trim($_POST['customer_phone']) : null;
        $table_id = !empty($_POST['table_id']) ? (int)$_POST['table_id'] : null;
        $order_id = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
        
        // Validate ratings (1-5)
        $food_rating = (int)$_POST['food_quality_rating'];
        $service_rating = (int)$_POST['service_quality_rating'];
        $venue_rating = (int)$_POST['venue_quality_rating'];
        
        if ($food_rating < 1 || $food_rating > 5 || $service_rating < 1 || $service_rating > 5 || $venue_rating < 1 || $venue_rating > 5) {
            throw new Exception("All ratings must be between 1 and 5 stars.");
        }
        
        $food_comments = !empty($_POST['food_quality_comments']) ? trim($_POST['food_quality_comments']) : null;
        $service_comments = !empty($_POST['service_quality_comments']) ? trim($_POST['service_quality_comments']) : null;
        $venue_comments = !empty($_POST['venue_quality_comments']) ? trim($_POST['venue_quality_comments']) : null;
        $reservation_experience = $_POST['reservation_experience'] ?? 'not_applicable';
        $reservation_comments = !empty($_POST['reservation_comments']) ? trim($_POST['reservation_comments']) : null;
        $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
        
        // Insert feedback into database
        $sql = "INSERT INTO feedback (
            order_id, table_id, customer_name, customer_email, customer_phone,
            food_quality_rating, food_quality_comments,
            service_quality_rating, service_quality_comments,
            venue_quality_rating, venue_quality_comments,
            reservation_experience, reservation_comments,
            is_anonymous, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iisssisssisssi', 
            $order_id, $table_id, $customer_name, $customer_email, $customer_phone,
            $food_rating, $food_comments, $service_rating, $service_comments,
            $venue_rating, $venue_comments, $reservation_experience, $reservation_comments,
            $is_anonymous
        );
        
        if ($stmt->execute()) {
            $feedback_id = $conn->insert_id;
            
            // Send success message to parent window
            echo "<script>
                if (window.parent) {
                    window.parent.postMessage({type: 'feedbackSubmitted', feedbackId: $feedback_id}, '*');
                }
            </script>";
            
            $success_message = "Thank you for your feedback! Your submission has been received.";
        } else {
            throw new Exception("Failed to submit feedback. Please try again.");
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get available tables for dropdown
$tables_sql = "SELECT table_id, table_number FROM tables WHERE is_active = 1 ORDER BY table_number";
$tables_result = $conn->query($tables_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .feedback-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin: 20px;
        }
        
        .rating-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .rating-title i {
            margin-right: 8px;
            color: #667eea;
        }
        
        .star-rating {
            display: flex;
            justify-content: center;
            margin: 15px 0;
            gap: 3px;
        }
        
        .star {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        
        .star:hover,
        .star.active {
            color: #ffc107;
            transform: scale(1.1);
        }
        
        .star-rating input[type="radio"] {
            display: none;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 10px 12px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .customer-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .anonymous-option {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
        }
        
        .reservation-section {
            background: #fff3e0;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="feedback-form">
        <?php if ($success_message): ?>
            <div class="alert alert-success text-center" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$success_message): ?>
        <form method="POST" action="" id="feedbackForm">
            <!-- Customer Information Section -->
            <div class="customer-info">
                <h5><i class="bi bi-person-circle"></i> Your Information (Optional)</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="customer_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                   placeholder="Your name" value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="customer_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                   placeholder="your@email.com" value="<?= htmlspecialchars($_POST['customer_email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="customer_phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                   placeholder="Your phone" value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="table_id" class="form-label">Table Number</label>
                            <select class="form-control" id="table_id" name="table_id">
                                <option value="">Select your table (optional)</option>
                                <?php while ($table = $tables_result->fetch_assoc()): ?>
                                    <option value="<?= $table['table_id'] ?>" 
                                            <?= ($_POST['table_id'] ?? '') == $table['table_id'] ? 'selected' : '' ?>>
                                        Table <?= $table['table_number'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="order_id" class="form-label">Order Number</label>
                            <input type="text" class="form-control" id="order_id" name="order_id" 
                                   placeholder="Your order number (optional)" value="<?= htmlspecialchars($_POST['order_id'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="anonymous-option">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_anonymous" name="is_anonymous" 
                               <?= isset($_POST['is_anonymous']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_anonymous">
                            <strong>Submit anonymously</strong> - Your personal information will not be stored
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Food Quality Rating -->
            <div class="rating-section">
                <div class="rating-title">
                    <i class="bi bi-egg-fried"></i> Food Quality
                </div>
                <p class="text-muted small">How would you rate the taste, presentation, and temperature of your food?</p>
                
                <div class="star-rating" data-rating="food_quality_rating">
                    <input type="radio" name="food_quality_rating" value="1" id="food_1" required>
                    <label for="food_1" class="star">★</label>
                    <input type="radio" name="food_quality_rating" value="2" id="food_2">
                    <label for="food_2" class="star">★</label>
                    <input type="radio" name="food_quality_rating" value="3" id="food_3">
                    <label for="food_3" class="star">★</label>
                    <input type="radio" name="food_quality_rating" value="4" id="food_4">
                    <label for="food_4" class="star">★</label>
                    <input type="radio" name="food_quality_rating" value="5" id="food_5">
                    <label for="food_5" class="star">★</label>
                </div>
                
                <div class="form-group">
                    <label for="food_quality_comments" class="form-label">Comments (Optional)</label>
                    <textarea class="form-control" id="food_quality_comments" name="food_quality_comments" 
                              rows="2" placeholder="Tell us about your food experience..."><?= htmlspecialchars($_POST['food_quality_comments'] ?? '') ?></textarea>
                </div>
            </div>
            
            <!-- Service Quality Rating -->
            <div class="rating-section">
                <div class="rating-title">
                    <i class="bi bi-people"></i> Service Quality
                </div>
                <p class="text-muted small">How would you rate our staff's friendliness, attentiveness, and efficiency?</p>
                
                <div class="star-rating" data-rating="service_quality_rating">
                    <input type="radio" name="service_quality_rating" value="1" id="service_1" required>
                    <label for="service_1" class="star">★</label>
                    <input type="radio" name="service_quality_rating" value="2" id="service_2">
                    <label for="service_2" class="star">★</label>
                    <input type="radio" name="service_quality_rating" value="3" id="service_3">
                    <label for="service_3" class="star">★</label>
                    <input type="radio" name="service_quality_rating" value="4" id="service_4">
                    <label for="service_4" class="star">★</label>
                    <input type="radio" name="service_quality_rating" value="5" id="service_5">
                    <label for="service_5" class="star">★</label>
                </div>
                
                <div class="form-group">
                    <label for="service_quality_comments" class="form-label">Comments (Optional)</label>
                    <textarea class="form-control" id="service_quality_comments" name="service_quality_comments" 
                              rows="2" placeholder="Tell us about your service experience..."><?= htmlspecialchars($_POST['service_quality_comments'] ?? '') ?></textarea>
                </div>
            </div>
            
            <!-- Venue Quality Rating -->
            <div class="rating-section">
                <div class="rating-title">
                    <i class="bi bi-building"></i> Venue Quality
                </div>
                <p class="text-muted small">How would you rate the ambiance, cleanliness, and overall environment?</p>
                
                <div class="star-rating" data-rating="venue_quality_rating">
                    <input type="radio" name="venue_quality_rating" value="1" id="venue_1" required>
                    <label for="venue_1" class="star">★</label>
                    <input type="radio" name="venue_quality_rating" value="2" id="venue_2">
                    <label for="venue_2" class="star">★</label>
                    <input type="radio" name="venue_quality_rating" value="3" id="venue_3">
                    <label for="venue_3" class="star">★</label>
                    <input type="radio" name="venue_quality_rating" value="4" id="venue_4">
                    <label for="venue_4" class="star">★</label>
                    <input type="radio" name="venue_quality_rating" value="5" id="venue_5">
                    <label for="venue_5" class="star">★</label>
                </div>
                
                <div class="form-group">
                    <label for="venue_quality_comments" class="form-label">Comments (Optional)</label>
                    <textarea class="form-control" id="venue_quality_comments" name="venue_quality_comments" 
                              rows="2" placeholder="Tell us about the venue environment..."><?= htmlspecialchars($_POST['venue_quality_comments'] ?? '') ?></textarea>
                </div>
            </div>
            
            <!-- Reservation Experience (Placeholder) -->
            <div class="reservation-section">
                <div class="rating-title">
                    <i class="bi bi-calendar-check"></i> Reservation Experience
                </div>
                <p class="text-muted small">Did you make a reservation for this visit?</p>
                
                <div class="form-group mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="reservation_experience" 
                               id="reservation_no" value="did_not_use" checked>
                        <label class="form-check-label" for="reservation_no">
                            No, I didn't use a reservation system
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="reservation_experience" 
                               id="reservation_na" value="not_applicable">
                        <label class="form-check-label" for="reservation_na">
                            Not applicable
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="reservation_comments" class="form-label">Comments (Optional)</label>
                    <textarea class="form-control" id="reservation_comments" name="reservation_comments" 
                              rows="2" placeholder="Any comments about the reservation process..."><?= htmlspecialchars($_POST['reservation_comments'] ?? '') ?></textarea>
                </div>
                
                <div class="small text-muted">
                    <i class="bi bi-info-circle"></i> 
                    Our reservation system is coming soon! This feedback will help us improve the experience.
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-submit">
                    <i class="bi bi-send"></i> Submit Feedback
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality
        document.querySelectorAll('.star-rating').forEach(rating => {
            const stars = rating.querySelectorAll('.star');
            const inputs = rating.querySelectorAll('input[type="radio"]');
            
            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    // Clear all stars
                    stars.forEach(s => s.classList.remove('active'));
                    // Activate stars up to clicked one
                    for (let i = 0; i <= index; i++) {
                        stars[i].classList.add('active');
                    }
                    // Set the input value
                    inputs[index].checked = true;
                });
                
                star.addEventListener('mouseenter', () => {
                    // Clear all stars
                    stars.forEach(s => s.classList.remove('active'));
                    // Activate stars up to hovered one
                    for (let i = 0; i <= index; i++) {
                        stars[i].classList.add('active');
                    }
                });
            });
            
            rating.addEventListener('mouseleave', () => {
                // Find checked input and restore its state
                const checkedInput = rating.querySelector('input[type="radio"]:checked');
                if (checkedInput) {
                    const checkedIndex = Array.from(inputs).indexOf(checkedInput);
                    stars.forEach(s => s.classList.remove('active'));
                    for (let i = 0; i <= checkedIndex; i++) {
                        stars[i].classList.add('active');
                    }
                } else {
                    stars.forEach(s => s.classList.remove('active'));
                }
            });
        });
        
        // Form validation
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const requiredRatings = ['food_quality_rating', 'service_quality_rating', 'venue_quality_rating'];
            let isValid = true;
            
            requiredRatings.forEach(rating => {
                const checked = document.querySelector(`input[name="${rating}"]:checked`);
                if (!checked) {
                    isValid = false;
                    alert(`Please rate ${rating.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}`);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Anonymous checkbox functionality
        document.getElementById('is_anonymous').addEventListener('change', function() {
            const customerFields = ['customer_name', 'customer_email', 'customer_phone'];
            customerFields.forEach(field => {
                const input = document.getElementById(field);
                if (this.checked) {
                    input.value = '';
                    input.disabled = true;
                    input.placeholder = 'Anonymous submission';
                } else {
                    input.disabled = false;
                    input.placeholder = input.getAttribute('data-original-placeholder') || '';
                }
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
