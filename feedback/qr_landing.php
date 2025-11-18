<?php
require_once '../admin/includes/db_connection.php';

// QR Code Landing Page
// This page is shown when customers scan QR codes for feedback

$type = $_GET['type'] ?? 'general';
$table_id = $_GET['table_id'] ?? null;
$order_id = $_GET['order_id'] ?? null;

// Get table information if table_id is provided
$table_info = null;
if ($table_id) {
    $sql = "SELECT * FROM tables WHERE table_id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $table_id);
    $stmt->execute();
    $table_info = $stmt->get_result()->fetch_assoc();
}

// Get order information if order_id is provided
$order_info = null;
if ($order_id) {
    $sql = "SELECT o.*, t.table_number FROM orders o 
            LEFT JOIN tables t ON o.table_id = t.table_id 
            WHERE o.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order_info = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Your Feedback - Restaurant Experience</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .landing-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin: 20px auto;
            max-width: 600px;
            padding: 40px;
            text-align: center;
        }
        
        .welcome-icon {
            font-size: 4rem;
            color: #667eea;
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
        
        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }
        
        .welcome-message {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .context-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        
        .btn-feedback {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            margin: 10px;
        }
        
        .btn-feedback:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-skip {
            background: transparent;
            border: 2px solid #6c757d;
            border-radius: 50px;
            padding: 13px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #6c757d;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 10px;
        }
        
        .btn-skip:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
        }
        
        .benefits {
            background: #e3f2fd;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        
        .benefit-item i {
            color: #2196f3;
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        @media (max-width: 768px) {
            .landing-container {
                margin: 10px;
                padding: 30px 20px;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .welcome-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="landing-container">
            <div class="welcome-icon">
                <i class="bi bi-chat-heart"></i>
            </div>
            
            <h1 class="welcome-title">Welcome!</h1>
            
            <p class="welcome-message">
                Thank you for dining with us! We'd love to hear about your experience. 
                Your feedback helps us improve and serve you better.
            </p>
            
            <?php if ($table_info || $order_info): ?>
            <div class="context-info">
                <h5><i class="bi bi-info-circle"></i> Your Visit Details</h5>
                <?php if ($table_info): ?>
                    <p><strong>Table:</strong> Table <?= $table_info['table_number'] ?></p>
                <?php endif; ?>
                <?php if ($order_info): ?>
                    <p><strong>Order:</strong> <?= $order_info['queue_number'] ?></p>
                    <?php if ($order_info['table_number']): ?>
                        <p><strong>Table:</strong> Table <?= $order_info['table_number'] ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <p><strong>Date:</strong> <?= date('M d, Y') ?></p>
            </div>
            <?php endif; ?>
            
            <div class="benefits">
                <h5><i class="bi bi-gift"></i> Why Share Your Feedback?</h5>
                <div class="benefit-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Help us improve our service</span>
                </div>
                <div class="benefit-item">
                    <i class="bi bi-star"></i>
                    <span>Quick 2-minute survey</span>
                </div>
                <div class="benefit-item">
                    <i class="bi bi-shield-check"></i>
                    <span>Anonymous option available</span>
                </div>
                <div class="benefit-item">
                    <i class="bi bi-percent"></i>
                    <span>Get 10% off your next visit</span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="index.php<?= $table_id ? '?table_id=' . $table_id : '' ?><?= $order_id ? ($table_id ? '&' : '?') . 'order_id=' . $order_id : '' ?>" 
                   class="btn-feedback">
                    <i class="bi bi-star"></i> Share Your Feedback
                </a>
                
                <a href="javascript:history.back()" class="btn-skip">
                    <i class="bi bi-arrow-left"></i> Maybe Later
                </a>
            </div>
            
            <div class="mt-4">
                <small class="text-muted">
                    <i class="bi bi-clock"></i> Takes less than 2 minutes
                </small>
            </div>
            
            <div class="mt-3">
                <small class="text-muted">
                    <i class="bi bi-shield-check"></i> 
                    Your feedback is secure and will be used to improve our services.
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Animate the welcome icon
            const icon = document.querySelector('.welcome-icon');
            icon.style.animation = 'bounce 2s infinite';
            
            // Add click tracking (optional)
            document.querySelector('.btn-feedback').addEventListener('click', function() {
                // You can add analytics tracking here
                console.log('Customer clicked feedback button');
            });
        });
        
        // Auto-redirect after 30 seconds (optional)
        // setTimeout(function() {
        //     if (confirm('Would you like to share your feedback now?')) {
        //         window.location.href = 'index.php';
        //     }
        // }, 30000);
    </script>
</body>
</html>

<?php
$conn->close();
?>
