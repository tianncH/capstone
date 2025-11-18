<?php
require_once '../admin/includes/db_connection.php';

// QR Code Generator for Feedback System
// This creates QR codes that link to the feedback form with pre-filled information

// Get parameters
$type = $_GET['type'] ?? 'general'; // 'table', 'order', 'general'
$id = $_GET['id'] ?? null;
$table_id = $_GET['table_id'] ?? null;
$order_id = $_GET['order_id'] ?? null;

// Generate feedback URL based on type
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/';
$feedback_url = $base_url . 'index.php';

// Add parameters to URL
$params = [];
if ($table_id) $params[] = 'table_id=' . $table_id;
if ($order_id) $params[] = 'order_id=' . $order_id;
if ($type) $params[] = 'type=' . $type;

if (!empty($params)) {
    $feedback_url .= '?' . implode('&', $params);
}

// QR Code data
$qr_data = $feedback_url;

// Include QR code library (using a simple text-based approach)
// For production, you might want to use a proper QR code library like phpqrcode
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator - Feedback System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .qr-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin: 20px auto;
            max-width: 800px;
            padding: 30px;
        }
        
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        
        .qr-url {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
            font-family: monospace;
        }
        
        .btn-generate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-generate:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .qr-options {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="qr-container">
            <div class="text-center mb-4">
                <h1><i class="bi bi-qr-code"></i> QR Code Generator</h1>
                <p class="text-muted">Generate QR codes for customer feedback collection</p>
            </div>
            
            <!-- QR Code Options -->
            <div class="qr-options">
                <h4><i class="bi bi-gear"></i> Generate QR Code</h4>
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="type" class="form-label">QR Code Type</label>
                                <select class="form-control" id="type" name="type" onchange="updateForm()">
                                    <option value="general" <?= $type === 'general' ? 'selected' : '' ?>>General Feedback</option>
                                    <option value="table" <?= $type === 'table' ? 'selected' : '' ?>>Table-Specific</option>
                                    <option value="order" <?= $type === 'order' ? 'selected' : '' ?>>Order-Specific</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4" id="table_field" style="display: <?= $type === 'table' ? 'block' : 'none' ?>;">
                            <div class="form-group mb-3">
                                <label for="table_id" class="form-label">Table Number</label>
                                <select class="form-control" id="table_id" name="table_id">
                                    <option value="">Select Table</option>
                                    <?php
                                    $tables_sql = "SELECT table_id, table_number FROM tables WHERE is_active = 1 ORDER BY table_number";
                                    $tables_result = $conn->query($tables_sql);
                                    while ($table = $tables_result->fetch_assoc()):
                                    ?>
                                        <option value="<?= $table['table_id'] ?>" <?= $table_id == $table['table_id'] ? 'selected' : '' ?>>
                                            Table <?= $table['table_number'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4" id="order_field" style="display: <?= $type === 'order' ? 'block' : 'none' ?>;">
                            <div class="form-group mb-3">
                                <label for="order_id" class="form-label">Order Number</label>
                                <input type="text" class="form-control" id="order_id" name="order_id" 
                                       placeholder="Enter order number" value="<?= htmlspecialchars($order_id ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-generate">
                            <i class="bi bi-qr-code"></i> Generate QR Code
                        </button>
                    </div>
                </form>
            </div>
            
            <?php if ($qr_data): ?>
            <!-- Generated QR Code -->
            <div class="qr-code">
                <h4><i class="bi bi-qr-code-scan"></i> Generated QR Code</h4>
                
                <!-- QR Code Display -->
                <div class="mb-3">
                    <div id="qrcode" style="display: inline-block; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);"></div>
                </div>
                
                <!-- QR Code URL -->
                <div class="qr-url">
                    <strong>Feedback URL:</strong><br>
                    <small><?= htmlspecialchars($qr_data) ?></small>
                </div>
                
                <!-- Download Options -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <button class="btn btn-outline-primary w-100" onclick="downloadQR()">
                            <i class="bi bi-download"></i> Download QR Code
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-outline-success w-100" onclick="printQR()">
                            <i class="bi bi-printer"></i> Print QR Code
                        </button>
                    </div>
                </div>
                
                <!-- QR Code Info -->
                <div class="mt-4">
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> QR Code Information</h6>
                        <ul class="mb-0">
                            <li><strong>Type:</strong> <?= ucfirst($type) ?> Feedback</li>
                            <?php if ($table_id): ?>
                                <li><strong>Table:</strong> Table <?= $table_id ?></li>
                            <?php endif; ?>
                            <?php if ($order_id): ?>
                                <li><strong>Order:</strong> <?= $order_id ?></li>
                            <?php endif; ?>
                            <li><strong>Purpose:</strong> Customer feedback collection</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Usage Instructions -->
            <div class="mt-5">
                <h4><i class="bi bi-book"></i> How to Use QR Codes</h4>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-table display-4 text-primary mb-3"></i>
                                <h5>Table QR Codes</h5>
                                <p class="small">Place QR codes on tables for easy access to feedback during dining</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-receipt display-4 text-success mb-3"></i>
                                <h5>Receipt QR Codes</h5>
                                <p class="small">Add QR codes to receipts for post-meal feedback collection</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-phone display-4 text-warning mb-3"></i>
                                <h5>Mobile Friendly</h5>
                                <p class="small">Customers can scan with any smartphone camera app</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Generate QR Code
        <?php if ($qr_data): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const qrData = <?= json_encode($qr_data) ?>;
            QRCode.toCanvas(document.getElementById('qrcode'), qrData, {
                width: 200,
                height: 200,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            }, function (error) {
                if (error) console.error(error);
            });
        });
        <?php endif; ?>
        
        // Update form based on type selection
        function updateForm() {
            const type = document.getElementById('type').value;
            const tableField = document.getElementById('table_field');
            const orderField = document.getElementById('order_field');
            
            if (type === 'table') {
                tableField.style.display = 'block';
                orderField.style.display = 'none';
            } else if (type === 'order') {
                tableField.style.display = 'none';
                orderField.style.display = 'block';
            } else {
                tableField.style.display = 'none';
                orderField.style.display = 'none';
            }
        }
        
        // Download QR Code
        function downloadQR() {
            const canvas = document.querySelector('#qrcode canvas');
            if (canvas) {
                const link = document.createElement('a');
                link.download = 'feedback-qr-code.png';
                link.href = canvas.toDataURL();
                link.click();
            }
        }
        
        // Print QR Code
        function printQR() {
            const canvas = document.querySelector('#qrcode canvas');
            if (canvas) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Feedback QR Code</title>
                            <style>
                                body { text-align: center; padding: 20px; font-family: Arial, sans-serif; }
                                .qr-container { display: inline-block; }
                                .qr-info { margin-top: 20px; }
                            </style>
                        </head>
                        <body>
                            <div class="qr-container">
                                <h2>Customer Feedback QR Code</h2>
                                <img src="${canvas.toDataURL()}" style="max-width: 300px;">
                                <div class="qr-info">
                                    <p><strong>Scan to leave feedback</strong></p>
                                    <p>Thank you for dining with us!</p>
                                </div>
                            </div>
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
