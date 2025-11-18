<?php
require_once 'includes/db_connection.php';
require_once 'includes/cash_float_functions.php';

// Start session to check admin authentication
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            // Set new cash float
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['date']) || !isset($input['amount'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Date and amount are required']);
                exit;
            }
            
            $date = $input['date'];
            $amount = floatval($input['amount']);
            $notes = isset($input['notes']) ? $input['notes'] : '';
            $admin_id = $_SESSION['admin_id'];
            
            if ($amount < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Amount cannot be negative']);
                exit;
            }
            
            $result = setCashFloat($conn, $date, $amount, $admin_id, $notes);
            echo json_encode($result);
            break;
            
        case 'GET':
            if (isset($_GET['date'])) {
                // Get cash float for specific date
                $date = $_GET['date'];
                $result = getCashFloat($conn, $date);
                echo json_encode($result);
            } elseif (isset($_GET['history'])) {
                // Get cash float history
                $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
                $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 30;
                
                $result = getCashFloatHistory($conn, $start_date, $end_date, $limit);
                echo json_encode($result);
            } elseif (isset($_GET['variance'])) {
                // Calculate cash variance for a date
                $date = $_GET['variance'];
                $result = calculateCashVariance($conn, $date);
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
            }
            break;
            
        case 'PUT':
            // Update existing cash float
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['float_id']) || !isset($input['amount'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Float ID and amount are required']);
                exit;
            }
            
            $float_id = intval($input['float_id']);
            $amount = floatval($input['amount']);
            $notes = isset($input['notes']) ? $input['notes'] : '';
            $admin_id = $_SESSION['admin_id'];
            
            if ($amount < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Amount cannot be negative']);
                exit;
            }
            
            $result = updateCashFloat($conn, $float_id, $amount, $admin_id, $notes);
            echo json_encode($result);
            break;
            
        case 'PATCH':
            // Close cash float
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['float_id']) || !isset($input['closing_amount'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Float ID and closing amount are required']);
                exit;
            }
            
            $float_id = intval($input['float_id']);
            $closing_amount = floatval($input['closing_amount']);
            $notes = isset($input['notes']) ? $input['notes'] : '';
            $admin_id = $_SESSION['admin_id'];
            
            if ($closing_amount < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Closing amount cannot be negative']);
                exit;
            }
            
            $result = closeCashFloat($conn, $float_id, $closing_amount, $admin_id, $notes);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>