<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

header('Content-Type: application/json');

// Get count of pending bookings
$sql = "SELECT COUNT(*) as pending_count FROM reservations WHERE status = 'pending'";
$result = $conn->query($sql);
$pending_count = $result->fetch_assoc()['pending_count'];

// Get recent bookings (last 5)
$sql = "SELECT r.*, v.venue_name 
        FROM reservations r 
        JOIN venues v ON r.venue_id = v.venue_id 
        ORDER BY r.created_at DESC 
        LIMIT 5";
$result = $conn->query($sql);
$recent_bookings = [];

while ($row = $result->fetch_assoc()) {
    $recent_bookings[] = [
        'reservation_id' => $row['reservation_id'],
        'venue_name' => $row['venue_name'],
        'customer_name' => $row['customer_name'],
        'reservation_date' => date('M j, Y', strtotime($row['reservation_date'])),
        'start_time' => date('g:i A', strtotime($row['start_time'])),
        'party_size' => $row['party_size'],
        'reservation_type' => $row['reservation_type'],
        'status' => $row['status'],
        'confirmation_code' => $row['confirmation_code'],
        'created_at' => date('M j, g:i A', strtotime($row['created_at']))
    ];
}

echo json_encode([
    'pending_count' => $pending_count,
    'recent_bookings' => $recent_bookings
]);

$conn->close();
?>


