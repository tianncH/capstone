<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

header('Content-Type: application/json');

// Get count of pending feedback
$sql = "SELECT COUNT(*) as pending_count FROM feedback WHERE status = 'pending'";
$result = $conn->query($sql);
$pending_count = $result->fetch_assoc()['pending_count'];

// Get recent feedback (last 5)
$sql = "SELECT f.*, t.table_number 
        FROM feedback f 
        LEFT JOIN tables t ON f.table_id = t.table_id 
        ORDER BY f.created_at DESC 
        LIMIT 5";
$result = $conn->query($sql);
$recent_feedback = [];

while ($row = $result->fetch_assoc()) {
    $recent_feedback[] = [
        'feedback_id' => $row['feedback_id'],
        'overall_rating' => $row['overall_rating'],
        'table_number' => $row['table_number'],
        'customer_name' => $row['is_anonymous'] ? 'Anonymous' : ($row['customer_name'] ?? 'Unknown'),
        'status' => $row['status'],
        'created_at' => date('M j, g:i A', strtotime($row['created_at'])),
        'food_rating' => $row['food_quality_rating'],
        'service_rating' => $row['service_quality_rating'],
        'venue_rating' => $row['venue_quality_rating']
    ];
}

echo json_encode([
    'pending_count' => $pending_count,
    'recent_feedback' => $recent_feedback
]);

$conn->close();
?>