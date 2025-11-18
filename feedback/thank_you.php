<?php
require_once '../admin/includes/db_connection.php';

$feedback_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get feedback details for confirmation
$feedback_data = null;
if ($feedback_id > 0) {
    $sql = "SELECT f.*, t.table_number FROM feedback f 
            LEFT JOIN tables t ON f.table_id = t.table_id 
            WHERE f.feedback_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $feedback_id);
    $stmt->execute();
    $feedback_data = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Restaurant Feedback</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .thank-you-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            padding: 50px;
            text-align: center;
            max-width: 600px;
            margin: 20px;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #28a745;
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
        
        .thank-you-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }
        
        .thank-you-message {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .feedback-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        
        .rating-display {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
            margin-right: 10px;
        }
        
        .rating-label {
            font-weight: 600;
            color: #333;
            min-width: 120px;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            margin: 10px;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-feedback {
            background: transparent;
            border: 2px solid #667eea;
            border-radius: 50px;
            padding: 13px 28px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #667eea;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 10px;
        }
        
        .btn-feedback:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .incentive-section {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .incentive-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #8b4513;
            margin-bottom: 15px;
        }
        
        .incentive-text {
            color: #8b4513;
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            .thank-you-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .thank-you-title {
                font-size: 2rem;
            }
            
            .success-icon {
                font-size: 4rem;
            }
        }
    </style>
</head>
<body>
    <div class="thank-you-container">
        <div class="success-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        
        <h1 class="thank-you-title">Thank You!</h1>
        
        <p class="thank-you-message">
            Your feedback has been successfully submitted and will help us improve our service. 
            We truly appreciate you taking the time to share your experience with us.
        </p>
        
        <?php if ($feedback_data): ?>
        <div class="feedback-summary">
            <h5><i class="bi bi-clipboard-check"></i> Feedback Summary</h5>
            <div class="rating-display">
                <span class="rating-label">Food Quality:</span>
                <span class="rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?= $i <= $feedback_data['food_quality_rating'] ? '★' : '☆' ?>
                    <?php endfor; ?>
                </span>
                <span class="ms-2"><?= $feedback_data['food_quality_rating'] ?>/5</span>
            </div>
            
            <div class="rating-display">
                <span class="rating-label">Service Quality:</span>
                <span class="rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?= $i <= $feedback_data['service_quality_rating'] ? '★' : '☆' ?>
                    <?php endfor; ?>
                </span>
                <span class="ms-2"><?= $feedback_data['service_quality_rating'] ?>/5</span>
            </div>
            
            <div class="rating-display">
                <span class="rating-label">Venue Quality:</span>
                <span class="rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?= $i <= $feedback_data['venue_quality_rating'] ? '★' : '☆' ?>
                    <?php endfor; ?>
                </span>
                <span class="ms-2"><?= $feedback_data['venue_quality_rating'] ?>/5</span>
            </div>
            
            <div class="rating-display">
                <span class="rating-label">Overall Rating:</span>
                <span class="rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?= $i <= $feedback_data['overall_rating'] ? '★' : '☆' ?>
                    <?php endfor; ?>
                </span>
                <span class="ms-2"><?= number_format($feedback_data['overall_rating'], 1) ?>/5</span>
            </div>
            
            <?php if ($feedback_data['table_number']): ?>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="bi bi-table"></i> Table: <?= $feedback_data['table_number'] ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="incentive-section">
            <div class="incentive-title">
                <i class="bi bi-gift"></i> Special Offer
            </div>
            <p class="incentive-text">
                As a thank you for your feedback, show this page to our staff on your next visit 
                and receive a 10% discount on your meal!
            </p>
        </div>
        
        <div class="action-buttons">
            <a href="../ordering/index.php" class="btn-home">
                <i class="bi bi-house"></i> Back to Menu
            </a>
            <a href="index.php" class="btn-feedback">
                <i class="bi bi-chat-heart"></i> Submit Another Feedback
            </a>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                <i class="bi bi-shield-check"></i> 
                Your feedback is secure and will be used to improve our services.
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-redirect after 30 seconds (optional)
        // setTimeout(function() {
        //     window.location.href = '../ordering/index.php';
        // }, 30000);
        
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Animate the success icon
            const icon = document.querySelector('.success-icon');
            icon.style.animation = 'bounce 2s infinite';
            
            // Add a subtle pulse to the thank you title
            const title = document.querySelector('.thank-you-title');
            title.style.animation = 'pulse 2s infinite';
        });
        
        // Add pulse animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

<?php
$conn->close();
?>
