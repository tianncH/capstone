<?php
class SafeQRSessionManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Create a safe QR session - automatically closes existing active sessions
     */
    public function createSafeQRSession($table_id, $session_token, $device_fingerprint, $ip_address, $user_agent) {
        try {
            $this->conn->begin_transaction();
            
            // Check if there's already an active session for this table
            $check_sql = "SELECT session_id FROM qr_sessions WHERE table_id = ? AND status = 'active' LIMIT 1";
            $stmt = $this->conn->prepare($check_sql);
            $stmt->bind_param("i", $table_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $existing_session = $result->fetch_assoc();
                $existing_session_id = $existing_session['session_id'];
                
                // Close the existing session
                $close_sql = "UPDATE qr_sessions SET status = 'archived', last_activity = NOW() WHERE session_id = ?";
                $close_stmt = $this->conn->prepare($close_sql);
                $close_stmt->bind_param("i", $existing_session_id);
                $close_stmt->execute();
                $close_stmt->close();
                
                echo "🔄 Closed existing QR session #{$existing_session_id} for table {$table_id}<br>";
            }
            
            // Create new session
            $create_sql = "INSERT INTO qr_sessions (table_id, session_token, device_fingerprint, ip_address, user_agent, status, created_at, last_activity) VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())";
            $create_stmt = $this->conn->prepare($create_sql);
            $create_stmt->bind_param("issss", $table_id, $session_token, $device_fingerprint, $ip_address, $user_agent);
            $create_stmt->execute();
            
            $new_session_id = $this->conn->insert_id;
            $create_stmt->close();
            
            $this->conn->commit();
            
            echo "✅ Created new QR session #{$new_session_id} for table {$table_id}<br>";
            return $new_session_id;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw new Exception("Failed to create safe QR session: " . $e->getMessage());
        }
    }
    
    /**
     * Check if a table has an active QR session
     */
    public function hasActiveQRSession($table_id) {
        $check_sql = "SELECT COUNT(*) as count FROM qr_sessions WHERE table_id = ? AND status = 'active'";
        $stmt = $this->conn->prepare($check_sql);
        $stmt->bind_param("i", $table_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();
        
        return $count > 0;
    }
    
    /**
     * Get active QR session for a table
     */
    public function getActiveQRSession($table_id) {
        $get_sql = "SELECT * FROM qr_sessions WHERE table_id = ? AND status = 'active' LIMIT 1";
        $stmt = $this->conn->prepare($get_sql);
        $stmt->bind_param("i", $table_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();
        
        return $session;
    }
}