<?php
/**
 * QR Code Generation System
 * Simple and effective QR code generation for ordering and feedback
 */

class QRGenerator {
    private $base_url;
    private $qr_size;
    
    public function __construct($base_url = 'http://localhost/capstone', $size = 200) {
        $this->base_url = rtrim($base_url, '/');
        $this->qr_size = $size;
    }
    
    /**
     * Generate QR code for table ordering (Secure QR System)
     */
    public function generateOrderingQR($table_id) {
        // Get the QR code for this table
        global $conn;
        $qr_sql = "SELECT qr_code FROM tables WHERE table_id = ?";
        $stmt = $conn->prepare($qr_sql);
        $stmt->bind_param('i', $table_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result && !empty($result['qr_code'])) {
            $url = $this->base_url . "/ordering/secure_qr_menu.php?qr=" . urlencode($result['qr_code']);
        } else {
            // Use secure system with table_id fallback
            $url = $this->base_url . "/ordering/secure_qr_menu.php?table=" . $table_id;
        }
        
        return $this->generateQRCode($url, "table_{$table_id}_ordering");
    }
    
    /**
     * Generate QR code for feedback submission
     */
    public function generateFeedbackQR($table_id = null) {
        // For now, use the general feedback page since receipt-based feedback is the new system
        $url = $this->base_url . "/feedback/index.php";
        if ($table_id) {
            $url .= "?table=" . $table_id;
        }
        return $this->generateQRCode($url, "table_" . ($table_id ?? 'general') . "_feedback");
    }
    
    /**
     * Generate QR code using multiple fallback methods
     */
    private function generateQRCode($data, $filename) {
        // Generate unique filename
        $qr_filename = $filename . "_" . time() . ".png";
        $qr_path = "../uploads/qr_codes/" . $qr_filename;
        
        // Create directory if it doesn't exist
        $upload_dir = "../uploads/qr_codes/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Try multiple QR code generation methods
        $qr_urls = [
            // Method 1: QR Server API
            "https://api.qrserver.com/v1/create-qr-code/?size={$this->qr_size}x{$this->qr_size}&data=" . urlencode($data),
            
            // Method 2: Google Charts API (alternative format)
            "https://chart.googleapis.com/chart?chs={$this->qr_size}x{$this->qr_size}&cht=qr&choe=UTF-8&chl=" . urlencode($data),
            
            // Method 3: QR Code API
            "https://quickchart.io/qr?text=" . urlencode($data) . "&size={$this->qr_size}",
        ];
        
        foreach ($qr_urls as $qr_url) {
            // Try to download QR code
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $qr_image = @file_get_contents($qr_url, false, $context);
            
            if ($qr_image !== false && strlen($qr_image) > 100) { // Basic validation
                if (file_put_contents($qr_path, $qr_image)) {
                    return [
                        'success' => true,
                        'filename' => $qr_filename,
                        'path' => $qr_path,
                        'url' => '/capstone/uploads/qr_codes/' . $qr_filename,
                        'data' => $data,
                        'method' => 'api_generated'
                    ];
                }
            }
        }
        
        // Fallback: Generate a simple QR code placeholder
        return $this->generateQRPlaceholder($data, $filename);
    }
    
    /**
     * Generate a simple QR code placeholder when APIs fail
     */
    private function generateQRPlaceholder($data, $filename) {
        $qr_filename = $filename . "_" . time() . ".png";
        $qr_path = "../uploads/qr_codes/" . $qr_filename;
        
        // Create a simple QR code using a basic method
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size={$this->qr_size}x{$this->qr_size}&data=" . urlencode($data);
        
        // Return the direct URL instead of trying to save the file
        return [
            'success' => true,
            'filename' => $qr_filename,
            'path' => $qr_path,
            'url' => $qr_url, // Use direct URL instead of local file
            'data' => $data,
            'method' => 'direct_url'
        ];
    }
    
    /**
     * Generate QR code using local PHP library (alternative method)
     */
    public function generateQRCodeLocal($data, $filename) {
        // This would use a local QR code library like phpqrcode
        // For now, we'll use the Google Charts method above
        return $this->generateQRCode($data, $filename);
    }
    
    /**
     * Get QR code info for display
     */
    public function getQRInfo($table_id) {
        // Get the QR code for this table
        global $conn;
        $qr_sql = "SELECT qr_code FROM tables WHERE table_id = ?";
        $stmt = $conn->prepare($qr_sql);
        $stmt->bind_param('i', $table_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $ordering_url = $this->base_url . "/ordering/index.php?table=" . $table_id;
        if ($result && !empty($result['qr_code'])) {
            $ordering_url = $this->base_url . "/ordering/secure_qr_menu.php?qr=" . urlencode($result['qr_code']);
        }
        
        return [
            'ordering_url' => $ordering_url,
            'feedback_url' => $this->base_url . "/feedback/index.php?table=" . $table_id,
            'venue_feedback_url' => $this->base_url . "/feedback/index.php?table=" . $table_id . "&type=venue",
            'table_id' => $table_id
        ];
    }
}
?>
