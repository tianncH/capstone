<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/qr_fallback.php';

// Get all tables with their QR codes
$tables_sql = "SELECT * FROM tables WHERE is_active = 1 ORDER BY table_number";
$tables_result = $conn->query($tables_sql);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">📱 Table QR Codes</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="table_management.php" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Tables
            </a>
            <a href="qr_management.php" class="btn btn-primary">
                <i class="bi bi-gear"></i> Manage QR Codes
            </a>
        </div>
    </div>

    <div class="row">
        <?php if ($tables_result->num_rows > 0): ?>
            <?php while ($table = $tables_result->fetch_assoc()): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white text-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-table"></i> Table <?= $table['table_number'] ?>
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($table['qr_code_url']): ?>
                                <!-- Display QR Code -->
                                <div class="mb-3">
                                    <?php
                                    // Generate QR code with fallback system for maximum reliability
                                    $qr_fallback = new QRFallback();
                                    $qr_result = $qr_fallback->generateQR($table['qr_code_url'], 200);
                                    $qr_image_url = $qr_result['url'];
                                    ?>
                                    <img src="<?= htmlspecialchars($qr_image_url) ?>" 
                                         alt="QR Code for Table <?= $table['table_number'] ?>" 
                                         class="img-fluid border rounded"
                                         style="max-width: 200px;">
                                    <?php if ($qr_result['service'] !== 'placeholder'): ?>
                                        <div class="mt-1">
                                            <small class="text-success">
                                                <i class="bi bi-check-circle"></i> 
                                                Generated via <?= ucfirst($qr_result['service']) ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-1">
                                            <small class="text-warning">
                                                <i class="bi bi-exclamation-triangle"></i> 
                                                Using fallback (services unavailable)
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- QR Code Info -->
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-link-45deg"></i> 
                                        <a href="<?= htmlspecialchars($table['qr_code_url']) ?>" 
                                           target="_blank" 
                                           class="text-decoration-none">
                                            View Menu
                                        </a>
                                    </small>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="d-grid gap-2">
                                    <a href="<?= htmlspecialchars($qr_image_url) ?>" 
                                       download="table_<?= $table['table_number'] ?>_qr.png"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-download"></i> Download QR
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-success btn-sm"
                                            onclick="printQR(<?= $table['table_number'] ?>, '<?= htmlspecialchars($qr_image_url) ?>')">
                                        <i class="bi bi-printer"></i> Print QR
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- No QR Code Generated -->
                                <div class="text-center py-4">
                                    <i class="bi bi-qr-code text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">No QR code generated</p>
                                    <a href="qr_management.php" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-plus-circle"></i> Generate QR Code
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-light">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> 
                                Scan to access Table <?= $table['table_number'] ?> menu
                            </small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-table text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-muted">No Tables Found</h4>
                    <p class="text-muted">No active tables are available to display QR codes.</p>
                    <a href="table_management.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Tables
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Print Modal -->
    <div class="modal fade" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="printModalLabel">Print QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="printContent">
                        <!-- Print content will be inserted here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printQR(tableNumber, qrImageUrl) {
    // Convert to QR Server API URL for printing
    const qrServerUrl = qrImageUrl.replace('https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=', 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=').replace('&format=png', '&format=png');
    
    const printContent = `
        <div style="text-align: center; padding: 20px;">
            <h3>Table ${tableNumber} - Digital Menu</h3>
            <img src="${qrServerUrl}" alt="QR Code for Table ${tableNumber}" style="max-width: 300px;">
            <p style="margin-top: 20px; font-size: 14px; color: #666;">
                Scan this QR code to access the digital menu for Table ${tableNumber}
            </p>
            <p style="font-size: 12px; color: #999;">
                Generated on ${new Date().toLocaleDateString()}
            </p>
        </div>
    `;
    
    document.getElementById('printContent').innerHTML = printContent;
    
    const printModal = new bootstrap.Modal(document.getElementById('printModal'));
    printModal.show();
}

// Auto-refresh QR codes every 30 seconds
setInterval(function() {
    // Only refresh if no modal is open
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 30000);
</script>

<style>
@media print {
    .modal-dialog {
        max-width: none;
        margin: 0;
    }
    .modal-content {
        border: none;
        box-shadow: none;
    }
    .modal-header,
    .modal-footer {
        display: none;
    }
    .modal-body {
        padding: 0;
    }
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<?php include 'includes/footer.php'; ?>
