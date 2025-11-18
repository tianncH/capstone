<?php
require_once 'includes/db_connection.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Get the last check timestamp from the session or set a default
session_start();
$last_check = isset($_SESSION['last_ready_check']) ? $_SESSION['last_ready_check'] : date('Y-m-d H:i:s', strtotime('-1 minute'));

// Update the last check timestamp
$_SESSION['last_ready_check'] = date('Y-m-d H:i:s');

// Check for new ready orders since the last check
$sql = "SELECT COUNT(*) as count FROM orders 
        WHERE status_id = 4 
        AND updated_at > ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $last_check);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

// Return JSON response
echo json_encode([
    'hasNewReadyOrders' => ($data['count'] > 0),
    'count' => $data['count']
]);

$conn->close();
?>