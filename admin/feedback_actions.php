<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$feedback_id = intval($_POST['feedback_id'] ?? 0);

if (!$feedback_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid feedback ID']);
    exit;
}

try {
    switch ($action) {
        case 'mark_reviewed':
            $sql = "UPDATE feedback SET status = 'reviewed', updated_at = NOW() WHERE feedback_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $feedback_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Feedback marked as reviewed']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update feedback status']);
            }
            $stmt->close();
            break;
            
        case 'respond':
            $admin_response = trim($_POST['admin_response'] ?? '');
            
            if (empty($admin_response)) {
                echo json_encode(['success' => false, 'message' => 'Response cannot be empty']);
                exit;
            }
            
            // Update feedback status and add admin response
            $sql = "UPDATE feedback SET status = 'responded', admin_notes = ?, updated_at = NOW() WHERE feedback_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $admin_response, $feedback_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Response sent successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send response']);
            }
            $stmt->close();
            break;
            
        case 'archive':
            $sql = "UPDATE feedback SET status = 'archived', updated_at = NOW() WHERE feedback_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $feedback_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Feedback archived']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to archive feedback']);
            }
            $stmt->close();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>


