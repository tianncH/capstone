<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

        // Get feedback data
        $feedback_sql = "SELECT f.*, t.table_number, v.venue_name, r.confirmation_code
                         FROM feedback f 
                         LEFT JOIN tables t ON f.table_id = t.table_id 
                         LEFT JOIN reservations r ON f.reservation_id = r.reservation_id
                         LEFT JOIN venues v ON r.venue_id = v.venue_id
                         ORDER BY f.created_at DESC";
        $feedback_result = $conn->query($feedback_sql);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">💬 Customer Feedback</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="feedback_export.php" class="btn btn-outline-primary">
                <i class="bi bi-download"></i> Export Feedback
            </a>
        </div>
    </div>

    <!-- Feedback Statistics -->
    <div class="row mb-4">
        <?php
        // Calculate statistics using multi-category ratings
        $stats_sql = "SELECT 
                        COUNT(*) as total_feedback,
                        AVG(overall_rating) as avg_rating,
                        COUNT(CASE WHEN overall_rating >= 4 THEN 1 END) as positive_feedback,
                        AVG(food_quality_rating) as avg_food_rating,
                        AVG(service_quality_rating) as avg_service_rating,
                        AVG(venue_quality_rating) as avg_venue_rating
                      FROM feedback";
        $stats_result = $conn->query($stats_sql);
        $stats = $stats_result->fetch_assoc();
        ?>
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Feedback</h5>
                    <h2 class="display-6"><?= $stats['total_feedback'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Overall Rating</h5>
                    <h2 class="display-6"><?= number_format($stats['avg_rating'], 1) ?>/5</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Food Quality</h5>
                    <h2 class="display-6"><?= number_format($stats['avg_food_rating'], 1) ?>/5</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Service Quality</h5>
                    <h2 class="display-6"><?= number_format($stats['avg_service_rating'], 1) ?>/5</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Venue Quality</h5>
                    <h2 class="display-6"><?= number_format($stats['avg_venue_rating'], 1) ?>/5</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Positive (4+ Stars)</h5>
                    <h2 class="display-6"><?= $stats['positive_feedback'] ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Recent Feedback</h5>
        </div>
        <div class="card-body">
            <?php if ($feedback_result && $feedback_result->num_rows > 0): ?>
                <div class="row">
                    <?php while ($feedback = $feedback_result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <?php if ($feedback['table_number']): ?>
                                                <span class="badge bg-primary">Table #<?= htmlspecialchars($feedback['table_number']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($feedback['venue_name']): ?>
                                                <span class="badge bg-success"><?= htmlspecialchars($feedback['venue_name']) ?></span>
                                                <?php if ($feedback['confirmation_code']): ?>
                                                    <span class="badge bg-info"><?= htmlspecialchars($feedback['confirmation_code']) ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if (!$feedback['table_number'] && !$feedback['venue_name']): ?>
                                                <span class="badge bg-secondary">General</span>
                                            <?php endif; ?>
                                            <?php if ($feedback['is_anonymous']): ?>
                                                <span class="badge bg-warning">Anonymous</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="rating">
                                            <strong>Overall: </strong>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?= $i <= $feedback['overall_rating'] ? '-fill' : '' ?> text-warning"></i>
                                            <?php endfor; ?>
                                            <small class="text-muted">(<?= number_format($feedback['overall_rating'], 1) ?>)</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Category Ratings -->
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <small class="text-muted">Food</small><br>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?= $i <= $feedback['food_quality_rating'] ? '-fill' : '' ?> text-warning" style="font-size: 0.8rem;"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Service</small><br>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?= $i <= $feedback['service_quality_rating'] ? '-fill' : '' ?> text-warning" style="font-size: 0.8rem;"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Venue</small><br>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?= $i <= $feedback['venue_quality_rating'] ? '-fill' : '' ?> text-warning" style="font-size: 0.8rem;"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Comments -->
                                    <?php if ($feedback['food_quality_comments']): ?>
                                        <div class="mb-2">
                                            <strong class="text-warning">Food:</strong> <?= htmlspecialchars($feedback['food_quality_comments']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($feedback['service_quality_comments']): ?>
                                        <div class="mb-2">
                                            <strong class="text-primary">Service:</strong> <?= htmlspecialchars($feedback['service_quality_comments']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($feedback['venue_quality_comments']): ?>
                                        <div class="mb-2">
                                            <strong class="text-success">Venue:</strong> <?= htmlspecialchars($feedback['venue_quality_comments']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($feedback['reservation_comments']): ?>
                                        <div class="mb-2">
                                            <strong class="text-info">Reservation:</strong> <?= htmlspecialchars($feedback['reservation_comments']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Customer Info -->
                                    <?php if ($feedback['customer_name'] && !$feedback['is_anonymous']): ?>
                                        <p class="card-text mt-2">
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> <?= htmlspecialchars($feedback['customer_name']) ?>
                                            </small>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($feedback['customer_email'] && !$feedback['is_anonymous']): ?>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <i class="bi bi-envelope"></i> <?= htmlspecialchars($feedback['customer_email']) ?>
                                            </small>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <!-- Status -->
                                    <div class="mt-2">
                                        <span class="badge bg-<?= $feedback['status'] == 'pending' ? 'warning' : ($feedback['status'] == 'reviewed' ? 'info' : ($feedback['status'] == 'responded' ? 'success' : 'secondary')) ?>">
                                            <?= ucfirst($feedback['status']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?>
                                        </small>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewFeedbackDetails(<?= $feedback['feedback_id'] ?>)">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <?php if ($feedback['status'] == 'pending'): ?>
                                                <button class="btn btn-outline-success" onclick="markAsReviewed(<?= $feedback['feedback_id'] ?>)">
                                                    <i class="bi bi-check"></i> Review
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($feedback['status'] == 'reviewed'): ?>
                                                <button class="btn btn-outline-info" onclick="respondToFeedback(<?= $feedback['feedback_id'] ?>)">
                                                    <i class="bi bi-reply"></i> Respond
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-chat-dots text-muted" style="font-size: 3rem;"></i>
                    <h4 class="mt-3 text-muted">No Feedback Yet</h4>
                    <p class="text-muted">Customer feedback will appear here once customers start submitting reviews.</p>
                    <a href="qr_management.php" class="btn btn-primary">
                        <i class="bi bi-qr-code"></i> Generate QR Codes
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Feedback Details Modal -->
<div class="modal fade" id="feedbackDetailsModal" tabindex="-1" aria-labelledby="feedbackDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackDetailsModalLabel">Feedback Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="feedbackDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="responseModalLabel">Respond to Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="responseForm">
                <div class="modal-body">
                    <input type="hidden" id="response_feedback_id" name="feedback_id">
                    <div class="mb-3">
                        <label for="admin_response" class="form-label">Your Response</label>
                        <textarea class="form-control" id="admin_response" name="admin_response" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewFeedbackDetails(feedbackId) {
    // Load feedback details via AJAX
    fetch(`feedback_details.php?id=${feedbackId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('feedbackDetailsContent').innerHTML = data;
            new bootstrap.Modal(document.getElementById('feedbackDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading feedback details');
        });
}

function markAsReviewed(feedbackId) {
    if (confirm('Mark this feedback as reviewed?')) {
        fetch('feedback_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=mark_reviewed&feedback_id=${feedbackId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating feedback status');
        });
    }
}

function respondToFeedback(feedbackId) {
    document.getElementById('response_feedback_id').value = feedbackId;
    new bootstrap.Modal(document.getElementById('responseModal')).show();
}

// Handle response form submission
document.getElementById('responseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'respond');
    
    fetch('feedback_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('responseModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending response');
    });
});
</script>

<?php include 'includes/footer.php'; ?>