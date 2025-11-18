<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/qr_generator.php';

$success_message = '';
$error_message = '';

// Initialize QR generator
$qr_generator = new QRGenerator();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['generate_all_qr'])) {
            // Generate QR codes for all tables
            $tables_sql = "SELECT * FROM tables WHERE is_active = 1 ORDER BY table_number";
            $tables_result = $conn->query($tables_sql);
            
            $generated_count = 0;
            while ($table = $tables_result->fetch_assoc()) {
                $table_id = $table['table_id'];
                
                // Generate ordering QR
                $ordering_qr = $qr_generator->generateOrderingQR($table_id);
                if ($ordering_qr['success']) {
                    // Update table with ordering QR URL
                    $update_sql = "UPDATE tables SET qr_code_url = ? WHERE table_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param('si', $ordering_qr['url'], $table_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                
                // Generate feedback QR
                $feedback_qr = $qr_generator->generateFeedbackQR($table_id);
                
                $generated_count++;
            }
            
            $success_message = "Generated QR codes for {$generated_count} tables successfully!";
        }
        
        if (isset($_POST['generate_single_qr'])) {
            $table_id = intval($_POST['table_id']);
            
            // Generate ordering QR
            $ordering_qr = $qr_generator->generateOrderingQR($table_id);
            if ($ordering_qr['success']) {
                // Update table with ordering QR URL
                $update_sql = "UPDATE tables SET qr_code_url = ? WHERE table_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('si', $ordering_qr['url'], $table_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                $success_message = "Generated QR code for Table {$table_id} successfully!";
            } else {
                throw new Exception('Failed to generate QR code');
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get all tables with their QR codes
$tables_sql = "SELECT * FROM tables ORDER BY table_number";
$tables_result = $conn->query($tables_sql);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">📱 QR Code Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#generateAllModal">
                <i class="bi bi-qr-code-scan"></i> Generate All QR Codes
            </button>
            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#generateSingleModal">
                <i class="bi bi-plus-circle"></i> Generate Single QR
            </button>
            <button type="button" class="btn btn-info" onclick="printAllQR()">
                <i class="bi bi-printer"></i> Print All QR Codes
            </button>
        </div>
    </div>

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

    <!-- QR Codes Display -->
    <div class="row">
        <?php if ($tables_result && $tables_result->num_rows > 0): ?>
            <?php while ($table = $tables_result->fetch_assoc()): ?>
                <?php
                $qr_info = $qr_generator->getQRInfo($table['table_id']);
                $has_ordering_qr = !empty($table['qr_code_url']);
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-tablet"></i> Table #<?= htmlspecialchars($table['table_number']) ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Ordering QR Code -->
                            <div class="mb-4">
                                <h6 class="text-primary">
                                    <i class="bi bi-cart"></i> Customer Ordering
                                </h6>
                                <?php if ($has_ordering_qr): ?>
                                    <div class="text-center">
                                        <img src="<?= htmlspecialchars($table['qr_code_url']) ?>" 
                                             alt="Ordering QR Code" 
                                             class="img-fluid border rounded"
                                             style="max-width: 150px;"
                                             onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($qr_info['ordering_url']) ?>'">
                                        <p class="small text-muted mt-2">Scan to order</p>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($qr_info['ordering_url']) ?>" 
                                             alt="Ordering QR Code" 
                                             class="img-fluid border rounded"
                                             style="max-width: 150px;">
                                        <p class="small text-muted mt-2">Scan to order</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Table Feedback QR Code -->
                            <div class="mb-3">
                                <h6 class="text-success">
                                    <i class="bi bi-chat-dots"></i> Table Feedback
                                </h6>
                                <div class="text-center">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($qr_info['feedback_url']) ?>" 
                                         alt="Table Feedback QR Code" 
                                         class="img-fluid border rounded"
                                         style="max-width: 150px;"
                                         onerror="this.src='https://quickchart.io/qr?text=<?= urlencode($qr_info['feedback_url']) ?>&size=150'">
                                    <p class="small text-muted mt-2">Scan for table feedback</p>
                                </div>
                            </div>
                            
                            <!-- Venue Feedback QR Code -->
                            <div class="mb-3">
                                <h6 class="text-warning">
                                    <i class="bi bi-building"></i> Venue Feedback
                                </h6>
                                <div class="text-center">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($qr_info['venue_feedback_url']) ?>" 
                                         alt="Venue Feedback QR Code" 
                                         class="img-fluid border rounded"
                                         style="max-width: 150px;"
                                         onerror="this.src='https://quickchart.io/qr?text=<?= urlencode($qr_info['venue_feedback_url']) ?>&size=150'">
                                    <p class="small text-muted mt-2">Scan for venue feedback</p>
                                </div>
                            </div>
                            
                            <!-- URLs for Reference -->
                            <div class="mt-3">
                                <small class="text-muted">
                                    <strong>Ordering URL:</strong><br>
                                    <code class="small"><?= htmlspecialchars($qr_info['ordering_url']) ?></code>
                                </small>
                                <br><br>
                                <small class="text-muted">
                                    <strong>Table Feedback URL:</strong><br>
                                    <code class="small"><?= htmlspecialchars($qr_info['feedback_url']) ?></code>
                                </small>
                                <br><br>
                                <small class="text-muted">
                                    <strong>Venue Feedback URL:</strong><br>
                                    <code class="small"><?= htmlspecialchars($qr_info['venue_feedback_url']) ?></code>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="downloadQR('<?= htmlspecialchars($table['qr_code_url']) ?>', 'table_<?= $table['table_number'] ?>_ordering')">
                                    <i class="bi bi-download"></i> Download
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" 
                                        onclick="regenerateQR(<?= $table['table_id'] ?>)">
                                    <i class="bi bi-arrow-clockwise"></i> Regenerate
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                        onclick="printQR(<?= $table['table_id'] ?>)">
                                    <i class="bi bi-printer"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i>
                    <h4>No Tables Found</h4>
                    <p>Please add some tables first before generating QR codes.</p>
                    <a href="table_management.php" class="btn btn-primary">Manage Tables</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Generate All QR Codes Modal -->
<div class="modal fade" id="generateAllModal" tabindex="-1" aria-labelledby="generateAllModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateAllModalLabel">Generate All QR Codes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>This will generate QR codes for all active tables.</strong>
                        <ul class="mb-0 mt-2">
                            <li>Customer ordering QR codes</li>
                            <li>Feedback submission QR codes</li>
                            <li>Updates existing QR codes if they exist</li>
                        </ul>
                    </div>
                    <p>Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="generate_all_qr" class="btn btn-success">
                        <i class="bi bi-qr-code-scan"></i> Generate All QR Codes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Generate Single QR Code Modal -->
<div class="modal fade" id="generateSingleModal" tabindex="-1" aria-labelledby="generateSingleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateSingleModalLabel">Generate QR Code for Table</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="table_id" class="form-label">Select Table</label>
                        <select class="form-select" id="table_id" name="table_id" required>
                            <option value="">Choose a table...</option>
                            <?php
                            $tables_result->data_seek(0);
                            while ($table = $tables_result->fetch_assoc()):
                            ?>
                                <option value="<?= $table['table_id'] ?>">Table #<?= htmlspecialchars($table['table_number']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="generate_single_qr" class="btn btn-primary">
                        <i class="bi bi-qr-code"></i> Generate QR Code
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function downloadQR(qrUrl, filename) {
    if (!qrUrl) {
        alert('No QR code available for download');
        return;
    }
    
    // Create a temporary link to download the QR code
    const link = document.createElement('a');
    link.href = qrUrl;
    link.download = filename + '.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function regenerateQR(tableId) {
    if (confirm('Are you sure you want to regenerate the QR code for this table?')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="table_id" value="${tableId}">
            <input type="hidden" name="generate_single_qr" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function printQR(tableId) {
    // Get the current base URL
    const baseUrl = window.location.origin + '/capstone';
    
    // Open a clean print window
    const printWindow = window.open('', '_blank', 'width=600,height=800');
    
    // Write clean HTML content
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>QR Codes - Table ${tableId}</title>
            <style>
                @media print {
                    body { margin: 0; padding: 20px; }
                }
                body { 
                    font-family: Arial, sans-serif; 
                    text-align: center; 
                    margin: 0;
                    padding: 20px;
                    background: white;
                }
                .qr-container { 
                    margin: 30px 0; 
                    display: block;
                }
                .qr-title { 
                    font-weight: bold; 
                    margin-bottom: 15px; 
                    font-size: 18px;
                    color: #333;
                }
                .qr-image { 
                    border: 2px solid #333; 
                    margin: 15px auto; 
                    display: block;
                    max-width: 100%;
                    height: auto;
                }
                .instructions {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                    font-size: 14px;
                    color: #666;
                }
                .description {
                    color: #666;
                    font-size: 14px;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #333;
                    margin-bottom: 30px;
                    font-size: 24px;
                }
            </style>
        </head>
        <body>
            <div class="instructions">
                <strong>Instructions:</strong> Place these QR codes on Table ${tableId} for customer use.
            </div>
            
            <h1>Table ${tableId} - QR Codes</h1>
            
            <div class="qr-container">
                <div class="qr-title">🛒 Customer Ordering</div>
                <div class="description">Scan to browse menu and place orders</div>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(baseUrl + '/ordering/secure_qr_menu.php?qr=QR_' + tableId.toString().padStart(3, '0'))}" 
                     class="qr-image" 
                     alt="Ordering QR Code for Table ${tableId}">
            </div>
            
            <div class="qr-container">
                <div class="qr-title">💬 Customer Feedback</div>
                <div class="description">Scan to provide feedback and ratings</div>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(baseUrl + '/feedback/index.php?table=' + tableId)}" 
                     class="qr-image" 
                     alt="Feedback QR Code for Table ${tableId}">
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Wait for images to load, then print
    setTimeout(() => {
        printWindow.print();
    }, 1000);
}

function printAllQR() {
    // Get all table IDs from the page
    const tableCards = document.querySelectorAll('.card');
    const tableIds = [];
    
    tableCards.forEach(card => {
        const header = card.querySelector('.card-header');
        if (header) {
            const tableText = header.textContent;
            const match = tableText.match(/Table #(\d+)/);
            if (match) {
                tableIds.push(match[1]);
            }
        }
    });
    
    if (tableIds.length === 0) {
        alert('No tables found to print QR codes for.');
        return;
    }
    
    // Get the current base URL
    const baseUrl = window.location.origin + '/capstone';
    
    // Create a simple, clean print page
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    // Write clean HTML content
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Restaurant QR Codes</title>
            <style>
                @media print {
                    body { margin: 0; padding: 15px; }
                    .page-break { page-break-before: always; }
                }
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0;
                    padding: 15px;
                    background: white;
                }
                .table-section {
                    margin-bottom: 40px;
                    page-break-inside: avoid;
                }
                .table-header {
                    background: #007bff;
                    color: white;
                    padding: 15px;
                    text-align: center;
                    margin-bottom: 20px;
                    border-radius: 8px;
                    font-size: 18px;
                    font-weight: bold;
                }
                .qr-container {
                    text-align: center;
                    margin: 20px 0;
                }
                .qr-title {
                    font-weight: bold;
                    margin-bottom: 15px;
                    font-size: 16px;
                    color: #333;
                }
                .qr-image {
                    border: 2px solid #333;
                    margin: 10px auto;
                    display: block;
                    max-width: 100%;
                    height: auto;
                }
                .instructions {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                    text-align: center;
                    font-size: 14px;
                    color: #666;
                }
                h1 {
                    text-align: center;
                    color: #333;
                    margin-bottom: 30px;
                    font-size: 24px;
                }
            </style>
        </head>
        <body>
            <div class="instructions">
                <strong>Instructions:</strong> Print these QR codes and place them on each table for customer use.
            </div>
            
            <h1>Restaurant QR Codes</h1>
    `);
    
    // Add each table's QR codes
    tableIds.forEach((tableId, index) => {
        if (index > 0) {
            printWindow.document.write('<div class="page-break"></div>');
        }
        
        printWindow.document.write(`
            <div class="table-section">
                <div class="table-header">
                    Table ${tableId}
                </div>
                
                <div class="qr-container">
                    <div class="qr-title">🛒 Customer Ordering</div>
                    <p style="margin-bottom: 15px; color: #666; font-size: 14px;">Scan to browse menu and place orders</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(baseUrl + '/ordering/secure_qr_menu.php?qr=QR_' + tableId.toString().padStart(3, '0'))}" 
                         class="qr-image" 
                         alt="Ordering QR Code for Table ${tableId}">
                </div>
                
                <div class="qr-container">
                    <div class="qr-title">💬 Customer Feedback</div>
                    <p style="margin-bottom: 15px; color: #666; font-size: 14px;">Scan to provide feedback and ratings</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(baseUrl + '/feedback/index.php?table=' + tableId)}" 
                         class="qr-image" 
                         alt="Feedback QR Code for Table ${tableId}">
                </div>
            </div>
        `);
    });
    
    printWindow.document.write(`
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Wait for images to load, then print
    setTimeout(() => {
        printWindow.print();
    }, 1500);
}
</script>

<style>
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.qr-image {
    transition: transform 0.2s ease;
}

.qr-image:hover {
    transform: scale(1.05);
}
</style>

<?php include 'includes/footer.php'; ?>
