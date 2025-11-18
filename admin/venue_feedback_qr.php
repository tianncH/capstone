<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

// Get all reservations that need venue feedback QR codes
$reservations_sql = "SELECT r.*, v.venue_name, v.description as venue_description
                     FROM reservations r
                     JOIN venues v ON r.venue_id = v.venue_id
                     WHERE r.status IN ('confirmed', 'completed')
                     ORDER BY r.reservation_date DESC, r.start_time DESC";
$reservations_result = $conn->query($reservations_sql);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-building"></i> Venue Feedback QR Codes
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print All
                </button>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        <strong>Staff Instructions:</strong> Print these QR codes and show them to customers <strong>after their venue event</strong> to collect feedback about their venue experience.
    </div>

    <?php if ($reservations_result && $reservations_result->num_rows > 0): ?>
        <div class="row">
            <?php while ($reservation = $reservations_result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-building"></i> <?= htmlspecialchars($reservation['venue_name']) ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Reservation Details -->
                            <div class="mb-3">
                                <h6 class="text-primary">Reservation Details</h6>
                                <p class="mb-1"><strong>Date:</strong> <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?></p>
                                <p class="mb-1"><strong>Time:</strong> <?= date('g:i A', strtotime($reservation['start_time'])) ?></p>
                                <p class="mb-1"><strong>Party Size:</strong> <?= $reservation['party_size'] ?> people</p>
                                <p class="mb-1"><strong>Confirmation:</strong> <?= $reservation['confirmation_code'] ?></p>
                            </div>
                            
                            <!-- Venue Feedback QR Code -->
                            <div class="text-center mb-3">
                                <h6 class="text-warning">Venue Feedback QR</h6>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode('http://localhost/capstone/feedback/index.php?reservation_id=' . $reservation['reservation_id'] . '&confirmation_code=' . $reservation['confirmation_code'] . '&venue_id=' . $reservation['venue_id']) ?>" 
                                     alt="Venue Feedback QR Code" 
                                     class="img-fluid border rounded"
                                     style="max-width: 200px;">
                                <p class="small text-muted mt-2">
                                    <strong>Show this QR to customer after their venue event</strong>
                                </p>
                            </div>
                            
                            <!-- Customer Info -->
                            <div class="mb-3">
                                <h6 class="text-info">Customer Info</h6>
                                <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($reservation['customer_name']) ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($reservation['customer_email']) ?></p>
                                <?php if ($reservation['customer_phone']): ?>
                                    <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($reservation['customer_phone']) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Special Requests -->
                            <?php if ($reservation['special_requests']): ?>
                                <div class="mb-3">
                                    <h6 class="text-secondary">Special Requests</h6>
                                    <p class="small"><?= htmlspecialchars($reservation['special_requests']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="downloadQR('<?= $reservation['reservation_id'] ?>', 'venue_feedback_<?= $reservation['confirmation_code'] ?>')">
                                    <i class="bi bi-download"></i> Download
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                        onclick="printQR('<?= $reservation['reservation_id'] ?>')">
                                    <i class="bi bi-printer"></i> Print
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" 
                                        onclick="markCompleted('<?= $reservation['reservation_id'] ?>')">
                                    <i class="bi bi-check-circle"></i> Mark Done
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            No venue reservations found that need feedback QR codes.
        </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .btn-toolbar, .card-footer, .alert {
        display: none !important;
    }
    
    .card {
        break-inside: avoid;
        margin-bottom: 20px;
    }
    
    .card-header {
        background: #f8f9fa !important;
        color: #000 !important;
        border: 1px solid #dee2e6 !important;
    }
}
</style>

<script>
function downloadQR(reservationId, filename) {
    const qrImg = document.querySelector(`img[alt="Venue Feedback QR Code"]`);
    const link = document.createElement('a');
    link.href = qrImg.src;
    link.download = filename + '.png';
    link.click();
}

function printQR(reservationId) {
    const card = document.querySelector(`button[onclick="printQR('${reservationId}')"]`).closest('.card');
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Venue Feedback QR - ${reservationId}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .qr-container { text-align: center; margin: 20px 0; }
                    .qr-container img { max-width: 300px; }
                    .reservation-info { margin: 20px 0; }
                </style>
            </head>
            <body>
                <h2>Venue Feedback QR Code</h2>
                <div class="qr-container">
                    ${card.innerHTML}
                </div>
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function markCompleted(reservationId) {
    if (confirm('Mark this venue feedback as completed?')) {
        // Here you could add AJAX to mark the reservation as feedback completed
        alert('Venue feedback marked as completed!');
        // Optionally reload the page or remove the card
        location.reload();
    }
}
</script>

<?php include 'includes/footer.php'; ?>

