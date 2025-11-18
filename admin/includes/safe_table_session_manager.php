<?php
class SafeTableSessionManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Create a safe table session - automatically closes existing active sessions
     */
    public function createSafeTableSession($table_id, $customer_name = null, $customer_phone = null) {
        try {
            $this->conn->begin_transaction();
            
            // Check if there's already an active session for this table
            $check_sql = "SELECT session_id FROM table_sessions WHERE table_id = ? AND status = 'active' LIMIT 1";
            $stmt = $this->conn->prepare($check_sql);
            $stmt->bind_param("i", $table_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $existing_session = $result->fetch_assoc();
                $existing_session_id = $existing_session['session_id'];
                
                // Close the existing session
                $close_sql = "UPDATE table_sessions SET status = 'closed', closed_at = NOW() WHERE session_id = ?";
                $close_stmt = $this->conn->prepare($close_sql);
                $close_stmt->bind_param("i", $existing_session_id);
                $close_stmt->execute();
                $close_stmt->close();
                
                echo "🔄 Closed existing table session #{$existing_session_id} for table {$table_id}<br>";
            }
            
            // Create new session
            $create_sql = "INSERT INTO table_sessions (table_id, customer_name, customer_phone, status, created_at, last_activity) VALUES (?, ?, ?, 'active', NOW(), NOW())";
            $create_stmt = $this->conn->prepare($create_sql);
            $create_stmt->bind_param("iss", $table_id, $customer_name, $customer_phone);
            $create_stmt->execute();
            
            $new_session_id = $this->conn->insert_id;
            $create_stmt->close();
            
            $this->conn->commit();
            
            echo "✅ Created new table session #{$new_session_id} for table {$table_id}<br>";
            return $new_session_id;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw new Exception("Failed to create safe table session: " . $e->getMessage());
        }
    }
    
    /**
     * Check if a table has an active table session
     */
    public function hasActiveTableSession($table_id) {
        $check_sql = "SELECT COUNT(*) as count FROM table_sessions WHERE table_id = ? AND status = 'active'";
        $stmt = $this->conn->prepare($check_sql);
        $stmt->bind_param("i", $table_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();
        
        return $count > 0;
    }
    
    /**
     * Get active table session for a table
     */
    public function getActiveTableSession($table_id) {
        $get_sql = "SELECT * FROM table_sessions WHERE table_id = ? AND status = 'active' LIMIT 1";
        $stmt = $this->conn->prepare($get_sql);
        $stmt->bind_param("i", $table_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();
        
        return $session;
    }
}