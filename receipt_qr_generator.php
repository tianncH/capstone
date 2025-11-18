<?php
require_once 'admin/includes/db_connection.php';

/**
 * Receipt QR Code Generator
 * Generates unique QR codes for feedback and venue booking on receipts
 */
class ReceiptQRGenerator {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Generate QR codes for a receipt
     */
    public function generateReceiptQRCodes($order_id, $table_id, $receipt_number) {
        // Generate unique QR codes
        $feedback_qr = 'FB_' . uniqid() . '_' . time();
        $venue_qr = 'VB_' . uniqid() . '_' . time();
        
        // Insert into database
        $sql = "INSERT INTO receipt_qr_codes (order_id, table_id, receipt_number, feedback_qr_code, venue_qr_code) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iisss", $order_id, $table_id, $receipt_number, $feedback_qr, $venue_qr);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'feedback_qr' => $feedback_qr,
                'venue_qr' => $venue_qr,
                'receipt_id' => $this->conn->insert_id
            ];
        } else {
            return [
                'success' => false,
                'error' => $stmt->error
            ];
        }
    }
    
    /**
     * Get receipt QR info
     */
    public function getReceiptQRInfo($receipt_number) {
        $sql = "SELECT * FROM receipt_qr_codes WHERE receipt_number = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $receipt_number);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Mark feedback as used
     */
    public function markFeedbackUsed($receipt_number) {
        $sql = "UPDATE receipt_qr_codes SET feedback_used = 1 WHERE receipt_number = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $receipt_number);
        
        return $stmt->execute();
    }
    
    /**
     * Mark venue booking as used
     */
    public function markVenueUsed($receipt_number) {
        $sql = "UPDATE receipt_qr_codes SET venue_used = 1 WHERE receipt_number = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $receipt_number);
        
        return $stmt->execute();
    }
    
    /**
     * Check if feedback is already used
     */
    public function isFeedbackUsed($receipt_number) {
        $sql = "SELECT feedback_used FROM receipt_qr_codes WHERE receipt_number = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $receipt_number);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? (bool)$result['feedback_used'] : false;
    }
    
    /**
     * Check if venue booking is already used
     */
    public function isVenueUsed($receipt_number) {
        $sql = "SELECT venue_used FROM receipt_qr_codes WHERE receipt_number = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $receipt_number);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? (bool)$result['venue_used'] : false;
    }
}

// Test the system
if (isset($_GET['test'])) {
    echo "<h1>🧾 RECEIPT QR GENERATOR TEST</h1>";
    echo "<style>body { font-family: Arial; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";
    
    $generator = new ReceiptQRGenerator($conn);
    
    // Test data
    $order_id = 1;
    $table_id = 1;
    $receipt_number = 'RCP_' . date('Ymd') . '_' . rand(1000, 9999);
    
    echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🧪 TESTING RECEIPT QR GENERATION</h2>";
    echo "<p><strong>Test Data:</strong></p>";
    echo "<ul>";
    echo "<li>Order ID: {$order_id}</li>";
    echo "<li>Table ID: {$table_id}</li>";
    echo "<li>Receipt Number: {$receipt_number}</li>";
    echo "</ul>";
    echo "</div>";
    
    // Generate QR codes
    $result = $generator->generateReceiptQRCodes($order_id, $table_id, $receipt_number);
    
    if ($result['success']) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>✅ QR CODES GENERATED SUCCESSFULLY</h2>";
        echo "<p><strong>Generated QR Codes:</strong></p>";
        echo "<ul>";
        echo "<li>💬 <strong>Feedback QR:</strong> {$result['feedback_qr']}</li>";
        echo "<li>📅 <strong>Venue QR:</strong> {$result['venue_qr']}</li>";
        echo "<li>🆔 <strong>Receipt ID:</strong> {$result['receipt_id']}</li>";
        echo "</ul>";
        echo "</div>";
        
        // Show URLs
        $server_ip = '192.168.1.2';
        $feedback_url = "http://{$server_ip}/capstone/feedback/receipt_feedback.php?qr={$result['feedback_qr']}";
        $venue_url = "http://{$server_ip}/capstone/reservations/receipt_booking.php?qr={$result['venue_qr']}";
        
        echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>🔗 QR CODE URLs</h2>";
        echo "<p><strong>Feedback URL:</strong></p>";
        echo "<code style='background: #f8f9fa; padding: 10px; display: block; margin: 10px 0; word-break: break-all;'>{$feedback_url}</code>";
        echo "<p><strong>Venue Booking URL:</strong></p>";
        echo "<code style='background: #f8f9fa; padding: 10px; display: block; margin: 10px 0; word-break: break-all;'>{$venue_url}</code>";
        echo "</div>";
        
        // Test usage tracking
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>🔒 TESTING USAGE TRACKING</h2>";
        
        $feedback_used = $generator->isFeedbackUsed($receipt_number);
        $venue_used = $generator->isVenueUsed($receipt_number);
        
        echo "<p>Feedback Used: " . ($feedback_used ? '❌ YES' : '✅ NO') . "</p>";
        echo "<p>Venue Used: " . ($venue_used ? '❌ YES' : '✅ NO') . "</p>";
        
        echo "<p><strong>Simulating feedback usage...</strong></p>";
        $generator->markFeedbackUsed($receipt_number);
        
        $feedback_used_after = $generator->isFeedbackUsed($receipt_number);
        echo "<p>Feedback Used After: " . ($feedback_used_after ? '❌ YES' : '✅ NO') . "</p>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>❌ FAILED TO GENERATE QR CODES</h2>";
        echo "<p>Error: {$result['error']}</p>";
        echo "</div>";
    }
}

// Don't close connection here - let the calling script handle it
?>
