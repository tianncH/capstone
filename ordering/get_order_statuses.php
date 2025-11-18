<?php
require_once 'includes/db_connection.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check if data is valid
if (!$data || !isset($data['queue_numbers']) || empty($data['queue_numbers'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

// Get today's date
$today = date('Y-m-d');

// Prepare the query
$queue_numbers = $data['queue_numbers'];
$placeholders = str_repeat('?,', count($queue_numbers) - 1) . '?';

$sql = "SELECT o.queue_number, o.status_id, os.name as status_name 
        FROM orders o 
        JOIN order_statuses os ON o.status_id = os.status_id 
        WHERE o.queue_number IN ($placeholders) 
        AND DATE(o.created_at) = ?";

// Prepare statement
$stmt = $conn->prepare($sql);

// Bind parameters
$types = str_repeat('s', count($queue_numbers)) . 's'; // All strings + today's date
$params = array_merge($queue_numbers, [$today]);

// Create array of references for bind_param
$bindParams = [];
$bindParams[] = &$types;
foreach ($params as $key => $value) {
    $bindParams[] = &$params[$key];
}

// Call bind_param with dynamic parameters
call_user_func_array([$stmt, 'bind_param'], $bindParams);

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Process results
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[$row['queue_number']] = [
        'status_id' => $row['status_id'],
        'status_name' => $row['status_name']
    ];
}

// Return response
echo json_encode([
    'success' => true,
    'orders' => $orders
]);

$stmt->close();
$conn->close();
?>