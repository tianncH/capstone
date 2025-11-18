<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

$feedback_id = intval($_GET['id'] ?? 0);

if (!$feedback_id) {
    echo '<div class="alert alert-danger">Invalid feedback ID.</div>';
    exit;
}

// Get feedback details
$sql = "SELECT f.*, t.table_number 
        FROM feedback f 
        LEFT JOIN tables t ON f.table_id = t.table_id 
        WHERE f.feedback_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $feedback_id);
$stmt->execute();
$feedback = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$feedback) {
    echo '<div class="alert alert-danger">Feedback not found.</div>';
    exit;
}
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-primary">Customer Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Name:</strong></td>
                <td><?= $feedback['is_anonymous'] ? 'Anonymous' : htmlspecialchars($feedback['customer_name'] ?? 'Not provided') ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td><?= $feedback['is_anonymous'] ? 'Hidden' : htmlspecialchars($feedback['customer_email'] ?? 'Not provided') ?></td>
            </tr>
            <tr>
                <td><strong>Phone:</strong></td>
                <td><?= $feedback['is_anonymous'] ? 'Hidden' : htmlspecialchars($feedback['customer_phone'] ?? 'Not provided') ?></td>
            </tr>
            <tr>
                <td><strong>Table:</strong></td>
                <td><?= $feedback['table_number'] ? 'Table #' . htmlspecialchars($feedback['table_number']) : 'General' ?></td>
            </tr>
            <tr>
                <td><strong>Submitted:</strong></td>
                <td><?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <span class="badge bg-<?= $feedback['status'] == 'pending' ? 'warning' : ($feedback['status'] == 'reviewed' ? 'info' : ($feedback['status'] == 'responded' ? 'success' : 'secondary')) ?>">
                        <?= ucfirst($feedback['status']) ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary">Ratings Summary</h6>
        <div class="row text-center">
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-warning">Food Quality</h6>
                        <div class="mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?= $i <= $feedback['food_quality_rating'] ? '-fill' : '' ?> text-warning"></i>
                            <?php endfor; ?>
                        </div>
                        <strong><?= $feedback['food_quality_rating'] ?>/5</strong>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-primary">Service Quality</h6>
                        <div class="mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?= $i <= $feedback['service_quality_rating'] ? '-fill' : '' ?> text-warning"></i>
                            <?php endfor; ?>
                        </div>
                        <strong><?= $feedback['service_quality_rating'] ?>/5</strong>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-success">Venue Quality</h6>
                        <div class="mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?= $i <= $feedback['venue_quality_rating'] ? '-fill' : '' ?> text-warning"></i>
                            <?php endfor; ?>
                        </div>
                        <strong><?= $feedback['venue_quality_rating'] ?>/5</strong>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-3 text-center">
            <h6>Overall Rating: <?= number_format($feedback['overall_rating'], 1) ?>/5</h6>
            <div>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?= $i <= $feedback['overall_rating'] ? '-fill' : '' ?> text-warning" style="font-size: 1.5rem;"></i>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <h6 class="text-primary">Detailed Comments</h6>
    
    <?php if ($feedback['food_quality_comments']): ?>
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <strong><i class="bi bi-egg-fried"></i> Food Quality Comments</strong>
            </div>
            <div class="card-body">
                <?= nl2br(htmlspecialchars($feedback['food_quality_comments'])) ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($feedback['service_quality_comments']): ?>
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <strong><i class="bi bi-person-heart"></i> Service Quality Comments</strong>
            </div>
            <div class="card-body">
                <?= nl2br(htmlspecialchars($feedback['service_quality_comments'])) ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($feedback['venue_quality_comments']): ?>
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <strong><i class="bi bi-building"></i> Venue Quality Comments</strong>
            </div>
            <div class="card-body">
                <?= nl2br(htmlspecialchars($feedback['venue_quality_comments'])) ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($feedback['reservation_comments']): ?>
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <strong><i class="bi bi-calendar-check"></i> Reservation Experience Comments</strong>
            </div>
            <div class="card-body">
                <?= nl2br(htmlspecialchars($feedback['reservation_comments'])) ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($feedback['admin_notes']): ?>
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white">
                <strong><i class="bi bi-sticky"></i> Admin Notes</strong>
            </div>
            <div class="card-body">
                <?= nl2br(htmlspecialchars($feedback['admin_notes'])) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
?>