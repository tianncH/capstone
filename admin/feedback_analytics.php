<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Get date range for analytics
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Get feedback analytics
$feedback_sql = "
    SELECT 
        DATE(created_at) as feedback_date,
        COUNT(*) as total_feedback,
        AVG(rating) as avg_overall_rating,
        AVG(food_quality) as avg_food_quality,
        AVG(service_quality) as avg_service_quality,
        AVG(place_quality) as avg_place_quality,
        COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_feedback,
        COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_feedback,
        COUNT(CASE WHEN device_type = 'mobile' THEN 1 END) as mobile_feedback,
        COUNT(CASE WHEN device_type = 'tablet' THEN 1 END) as tablet_feedback,
        COUNT(CASE WHEN device_type = 'desktop' THEN 1 END) as desktop_feedback
    FROM feedback 
    WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    GROUP BY DATE(created_at)
    ORDER BY feedback_date DESC
    LIMIT 30
";

$feedback_stmt = $conn->prepare($feedback_sql);
$feedback_stmt->bind_param("ss", $start_date, $end_date);
$feedback_stmt->execute();
$feedback_data = $feedback_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get venue analytics
$venue_sql = "
    SELECT 
        venue_type,
        COUNT(*) as total_ratings,
        AVG(venue_reservation_quality) as avg_reservation_quality,
        AVG(venue_quality_rating) as avg_venue_quality,
        COUNT(CASE WHEN venue_quality_rating >= 4 THEN 1 END) as positive_ratings,
        COUNT(CASE WHEN venue_quality_rating <= 2 THEN 1 END) as negative_ratings
    FROM venue_ratings 
    WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    GROUP BY venue_type
    ORDER BY avg_venue_quality DESC
";

$venue_stmt = $conn->prepare($venue_sql);
$venue_stmt->bind_param("ss", $start_date, $end_date);
$venue_stmt->execute();
$venue_data = $venue_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get overall statistics
$stats_sql = "
    SELECT 
        (SELECT COUNT(*) FROM feedback WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY) as total_feedback,
        (SELECT COUNT(*) FROM venue_ratings WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY) as total_venue_ratings,
        (SELECT AVG(rating) FROM feedback WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY) as avg_overall_rating,
        (SELECT AVG(venue_quality_rating) FROM venue_ratings WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY) as avg_venue_rating
";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("ssssssss", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-graph-up"></i> Feedback Analytics</h1>
                <div class="d-flex gap-2">
                    <input type="date" class="form-control" id="start_date" value="<?= $start_date ?>" style="width: auto;">
                    <input type="date" class="form-control" id="end_date" value="<?= $end_date ?>" style="width: auto;">
                    <button class="btn btn-primary" onclick="updateDateRange()">
                        <i class="bi bi-search"></i> Update
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= number_format($stats['total_feedback'] ?? 0) ?></h4>
                            <p class="mb-0">Total Feedback</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-chat-dots fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= number_format($stats['total_venue_ratings'] ?? 0) ?></h4>
                            <p class="mb-0">Venue Ratings</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-building fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= number_format($stats['avg_overall_rating'] ?? 0, 1) ?>/5</h4>
                            <p class="mb-0">Avg Overall Rating</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-star-fill fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= number_format($stats['avg_venue_rating'] ?? 0, 1) ?>/5</h4>
                            <p class="mb-0">Avg Venue Rating</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-building fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Analytics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-chart-line"></i> Daily Feedback Analytics</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($feedback_data)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mt-2">No feedback data available for the selected date range.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Total Feedback</th>
                                        <th>Avg Overall</th>
                                        <th>Avg Food</th>
                                        <th>Avg Service</th>
                                        <th>Avg Place</th>
                                        <th>Positive (4-5★)</th>
                                        <th>Negative (1-2★)</th>
                                        <th>Mobile</th>
                                        <th>Tablet</th>
                                        <th>Desktop</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($feedback_data as $row): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($row['feedback_date'])) ?></td>
                                            <td><span class="badge bg-primary"><?= $row['total_feedback'] ?></span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?= number_format($row['avg_overall_rating'], 1) ?></span>
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?= $i <= $row['avg_overall_rating'] ? '-fill' : '' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?= number_format($row['avg_food_quality'], 1) ?></span>
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?= $i <= $row['avg_food_quality'] ? '-fill' : '' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?= number_format($row['avg_service_quality'], 1) ?></span>
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?= $i <= $row['avg_service_quality'] ? '-fill' : '' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?= number_format($row['avg_place_quality'], 1) ?></span>
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?= $i <= $row['avg_place_quality'] ? '-fill' : '' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-success"><?= $row['positive_feedback'] ?></span></td>
                                            <td><span class="badge bg-danger"><?= $row['negative_feedback'] ?></span></td>
                                            <td><span class="badge bg-info"><?= $row['mobile_feedback'] ?></span></td>
                                            <td><span class="badge bg-secondary"><?= $row['tablet_feedback'] ?></span></td>
                                            <td><span class="badge bg-dark"><?= $row['desktop_feedback'] ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Venue Analytics -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-building"></i> Venue Rating Analytics</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($venue_data)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-building fs-1 text-muted"></i>
                            <p class="text-muted mt-2">No venue rating data available for the selected date range.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Venue Type</th>
                                        <th>Total Ratings</th>
                                        <th>Avg Reservation Quality</th>
                                        <th>Avg Venue Quality</th>
                                        <th>Positive Ratings</th>
                                        <th>Negative Ratings</th>
                                        <th>Overall Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($venue_data as $row): ?>
                                        <?php 
                                        $overall_score = ($row['avg_reservation_quality'] + $row['avg_venue_quality']) / 2;
                                        $score_class = $overall_score >= 4 ? 'success' : ($overall_score >= 3 ? 'warning' : 'danger');
                                        ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['venue_type']) ?></strong></td>
                                            <td><span class="badge bg-primary"><?= $row['total_ratings'] ?></span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?= number_format($row['avg_reservation_quality'], 1) ?></span>
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?= $i <= $row['avg_reservation_quality'] ? '-fill' : '' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?= number_format($row['avg_venue_quality'], 1) ?></span>
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?= $i <= $row['avg_venue_quality'] ? '-fill' : '' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-success"><?= $row['positive_ratings'] ?></span></td>
                                            <td><span class="badge bg-danger"><?= $row['negative_ratings'] ?></span></td>
                                            <td>
                                                <span class="badge bg-<?= $score_class ?>">
                                                    <?= number_format($overall_score, 1) ?>/5
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateDateRange() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    if (startDate && endDate) {
        window.location.href = `feedback_analytics.php?start_date=${startDate}&end_date=${endDate}`;
    } else {
        alert('Please select both start and end dates.');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>






