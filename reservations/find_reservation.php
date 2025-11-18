<?php
require_once '../admin/includes/db_connection.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmation_code = trim($_POST['confirmation_code']);
    $customer_email = trim($_POST['customer_email']);
    
    if (empty($confirmation_code) || empty($customer_email)) {
        $error_message = "Please enter both confirmation code and email address.";
    } else {
        // Find reservation
        $sql = "SELECT r.*, v.venue_name 
                FROM reservations r 
                JOIN venues v ON r.venue_id = v.venue_id 
                WHERE r.confirmation_code = ? AND r.customer_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $confirmation_code, $customer_email);
        $stmt->execute();
        $reservation = $stmt->get_result()->fetch_assoc();
        
        if ($reservation) {
            // Redirect to management page
            header("Location: manage_reservation.php?id=" . $reservation['reservation_id'] . "&code=" . $reservation['confirmation_code']);
            exit;
        } else {
            $error_message = "No reservation found with the provided confirmation code and email address.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Your Reservation - Restaurant</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .find-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 50px auto;
            max-width: 600px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 40px;
            text-align: center;
        }
        
        .form-section {
            padding: 40px;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-custom {
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
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
        
        .info-section {
            background: #e3f2fd;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .search-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="find-container">
            <!-- Header Section -->
            <div class="header-section">
                <div class="search-icon">
                    <i class="bi bi-search"></i>
                </div>
                <h1 class="display-5 mb-3">Find Your Reservation</h1>
                <p class="lead">Enter your confirmation code and email to manage your reservation</p>
            </div>
            
            <!-- Form Section -->
            <div class="form-section">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label for="confirmation_code" class="form-label fw-bold">
                            <i class="bi bi-key"></i> Confirmation Code
                        </label>
                        <input type="text" class="form-control form-control-lg" id="confirmation_code" name="confirmation_code" 
                               placeholder="Enter your 8-character confirmation code" 
                               value="<?= htmlspecialchars($_POST['confirmation_code'] ?? '') ?>" required>
                        <small class="form-text text-muted">This is the code you received when you made your reservation</small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="customer_email" class="form-label fw-bold">
                            <i class="bi bi-envelope"></i> Email Address
                        </label>
                        <input type="email" class="form-control form-control-lg" id="customer_email" name="customer_email" 
                               placeholder="Enter the email address used for the reservation" 
                               value="<?= htmlspecialchars($_POST['customer_email'] ?? '') ?>" required>
                        <small class="form-text text-muted">This must match the email address you provided when making the reservation</small>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary-custom btn-custom">
                            <i class="bi bi-search"></i> Find My Reservation
                        </button>
                    </div>
                </form>
                
                <!-- Information Section -->
                <div class="info-section">
                    <h6 class="text-primary">
                        <i class="bi bi-info-circle"></i> Need Help?
                    </h6>
                    <ul class="mb-0">
                        <li>Your confirmation code was sent to your email when you made the reservation</li>
                        <li>Make sure you're using the same email address you provided during booking</li>
                        <li>If you can't find your confirmation code, check your email spam folder</li>
                        <li>For assistance, contact us at <strong>(555) 123-4567</strong></li>
                    </ul>
                </div>
                
                <!-- Back to Reservation -->
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-primary btn-custom">
                        <i class="bi bi-arrow-left"></i> Back to Make Reservation
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus on confirmation code field
        document.getElementById('confirmation_code').focus();
        
        // Convert confirmation code to uppercase
        document.getElementById('confirmation_code').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
