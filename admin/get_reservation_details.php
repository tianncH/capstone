<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

header('Content-Type: application/json');

$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$confirmation_code = isset($_GET['code']) ? trim($_GET['code']) : '';

$response = ['success' => false, 'message' => 'Invalid request'];

if ($reservation_id && $confirmation_code) {
    try {
        $sql = "SELECT r.*, v.venue_name 
                FROM reservations r 
                JOIN venues v ON r.venue_id = v.venue_id 
                WHERE r.reservation_id = ? AND r.confirmation_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $reservation_id, $confirmation_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($reservation = $result->fetch_assoc()) {
            $response = [
                'success' => true,
                'reservation' => [
                    'reservation_id' => $reservation['reservation_id'],
                    'customer_name' => $reservation['customer_name'],
                    'customer_email' => $reservation['customer_email'],
                    'customer_phone' => $reservation['customer_phone'],
                    'venue_name' => $reservation['venue_name'],
                    'reservation_date' => $reservation['reservation_date'],
                    'start_time' => $reservation['start_time'],
                    'party_size' => $reservation['party_size'],
                    'reservation_type' => $reservation['reservation_type'],
                    'status' => $reservation['status']
                ]
            ];
        } else {
            $response['message'] = 'Reservation not found or invalid confirmation code';
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}

echo json_encode($response);
$conn->close();
?>


