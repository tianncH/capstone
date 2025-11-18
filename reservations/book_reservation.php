<?php
require_once '../admin/includes/db_connection.php';

$success_message = '';
$error_message = '';
$reservation_id = null;
$confirmation_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and validate input
        $venue_id = (int)$_POST['venue_id'];
        $customer_name = trim($_POST['customer_name']);
        $customer_email = trim($_POST['customer_email']);
        $customer_phone = !empty($_POST['customer_phone']) ? trim($_POST['customer_phone']) : null;
        $reservation_date = $_POST['reservation_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $party_size = (int)$_POST['party_size'];
        $reservation_type = $_POST['reservation_type'];
        $special_requests = !empty($_POST['special_requests']) ? trim($_POST['special_requests']) : null;
        
        // Validate required fields
        if (empty($venue_id) || empty($customer_name) || empty($customer_email) || empty($reservation_date) || empty($start_time) || empty($end_time) || empty($party_size)) {
            throw new Exception("All required fields must be filled.");
        }
        
        // Validate email
        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Validate date
        if (strtotime($reservation_date) < strtotime(date('Y-m-d'))) {
            throw new Exception("Reservation date cannot be in the past.");
        }
        
        // Validate time
        if (strtotime($start_time) >= strtotime($end_time)) {
            throw new Exception("End time must be after start time.");
        }
        
        // Get venue details
        $venue_sql = "SELECT * FROM venues WHERE venue_id = ? AND is_active = 1";
        $venue_stmt = $conn->prepare($venue_sql);
        $venue_stmt->bind_param('i', $venue_id);
        $venue_stmt->execute();
        $venue = $venue_stmt->get_result()->fetch_assoc();
        
        if (!$venue) {
            throw new Exception("Selected venue is not available.");
        }
        
        // Check party size against venue capacity
        if ($party_size > $venue['max_capacity']) {
            throw new Exception("Party size exceeds venue capacity of " . $venue['max_capacity'] . " people.");
        }
        
        if ($party_size < $venue['min_party_size']) {
            throw new Exception("Party size is below minimum requirement of " . $venue['min_party_size'] . " people.");
        }
        
        // Check venue operating hours
        $venue_open = strtotime($venue['opening_time']);
        $venue_close = strtotime($venue['closing_time']);
        $res_start = strtotime($start_time);
        $res_end = strtotime($end_time);
        
        if ($res_start < $venue_open || $res_end > $venue_close) {
            throw new Exception("Reservation time is outside venue operating hours (" . date('g:i A', $venue_open) . " - " . date('g:i A', $venue_close) . ").");
        }
        
        // Check for conflicts with existing reservations
        $conflict_sql = "SELECT COUNT(*) as count FROM reservations 
                         WHERE venue_id = ? AND reservation_date = ? 
                         AND status IN ('pending', 'confirmed') 
                         AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))";
        $conflict_stmt = $conn->prepare($conflict_sql);
        $conflict_stmt->bind_param('isssss', $venue_id, $reservation_date, $end_time, $start_time, $end_time, $start_time);
        $conflict_stmt->execute();
        $conflict_result = $conflict_stmt->get_result()->fetch_assoc();
        
        if ($conflict_result['count'] > 0) {
            throw new Exception("Time slot is no longer available. Please choose a different time.");
        }
        
        // Check for venue restrictions
        $restriction_sql = "SELECT COUNT(*) as count FROM venue_restrictions 
                            WHERE venue_id = ? AND start_date <= ? AND (end_date >= ? OR end_date IS NULL) 
                            AND is_active = 1 
                            AND ((start_time IS NULL OR start_time <= ?) AND (end_time IS NULL OR end_time >= ?))";
        $restriction_stmt = $conn->prepare($restriction_sql);
        $restriction_stmt->bind_param('issss', $venue_id, $reservation_date, $reservation_date, $end_time, $start_time);
        $restriction_stmt->execute();
        $restriction_result = $restriction_stmt->get_result()->fetch_assoc();
        
        if ($restriction_result['count'] > 0) {
            throw new Exception("Selected time slot is restricted. Please choose a different time.");
        }
        
        // Generate confirmation code
        $confirmation_code = strtoupper(substr(md5(uniqid()), 0, 8));
        
        // Insert reservation
        $insert_sql = "INSERT INTO reservations (
            venue_id, customer_name, customer_email, customer_phone, 
            reservation_date, start_time, end_time, party_size, 
            reservation_type, special_requests, confirmation_code, 
            status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NULL)";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('isssssiisss', 
            $venue_id, $customer_name, $customer_email, $customer_phone,
            $reservation_date, $start_time, $end_time, $party_size,
            $reservation_type, $special_requests, $confirmation_code
        );
        
        if ($insert_stmt->execute()) {
            $reservation_id = $conn->insert_id;
            $success_message = "Reservation submitted successfully!";
            
            // Redirect to confirmation page
            header("Location: confirmation.php?id=" . $reservation_id . "&code=" . $confirmation_code);
            exit;
        } else {
            throw new Exception("Failed to submit reservation. Please try again.");
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// If we get here, there was an error - redirect back to booking page
if ($error_message) {
    header("Location: index.php?error=" . urlencode($error_message));
    exit;
}
?>
