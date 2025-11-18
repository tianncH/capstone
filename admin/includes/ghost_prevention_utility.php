<?php
class GhostPreventionUtility {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Check for and clean up duplicate sessions
     */
    public function checkAndCleanupDuplicates() {
        $cleanup_count = 0;
        
        // Check for duplicate QR sessions
        $qr_duplicates_sql = "SELECT table_id, COUNT(*) as count FROM qr_sessions WHERE status = 'active' GROUP BY table_id HAVING COUNT(*) > 1";
        $result = $this->conn->query($qr_duplicates_sql);
        
        while ($row = $result->fetch_assoc()) {
            $table_id = $row['table_id'];
            $count = $row['count'];
            
            // Keep only the most recent session
            $keep_sql = "UPDATE qr_sessions SET status = 'archived' WHERE table_id = ? AND status = 'active' AND session_id NOT IN (SELECT session_id FROM (SELECT session_id FROM qr_sessions WHERE table_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1) as latest)";
            $stmt = $this->conn->prepare($keep_sql);
            $stmt->bind_param("ii", $table_id, $table_id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            $cleanup_count += $affected;
            echo "🧹 Cleaned up {$affected} duplicate QR sessions for table {$table_id}<br>";
        }
        
        // Check for duplicate table sessions
        $table_duplicates_sql = "SELECT table_id, COUNT(*) as count FROM table_sessions WHERE status = 'active' GROUP BY table_id HAVING COUNT(*) > 1";
        $result = $this->conn->query($table_duplicates_sql);
        
        while ($row = $result->fetch_assoc()) {
            $table_id = $row['table_id'];
            $count = $row['count'];
            
            // Keep only the most recent session
            $keep_sql = "UPDATE table_sessions SET status = 'closed' WHERE table_id = ? AND status = 'active' AND session_id NOT IN (SELECT session_id FROM (SELECT session_id FROM table_sessions WHERE table_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1) as latest)";
            $stmt = $this->conn->prepare($keep_sql);
            $stmt->bind_param("ii", $table_id, $table_id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            $cleanup_count += $affected;
            echo "🧹 Cleaned up {$affected} duplicate table sessions for table {$table_id}<br>";
        }
        
        return $cleanup_count;
    }
    
    /**
     * Get system health report
     */
    public function getSystemHealthReport() {
        $report = [];
        
        // Check QR sessions
        $qr_active_sql = "SELECT COUNT(*) as count FROM qr_sessions WHERE status = 'active'";
        $result = $this->conn->query($qr_active_sql);
        $report['qr_active_sessions'] = $result->fetch_assoc()['count'];
        
        // Check table sessions
        $table_active_sql = "SELECT COUNT(*) as count FROM table_sessions WHERE status = 'active'";
        $result = $this->conn->query($table_active_sql);
        $report['table_active_sessions'] = $result->fetch_assoc()['count'];
        
        // Check for duplicates
        $qr_duplicates_sql = "SELECT COUNT(*) as count FROM (SELECT table_id FROM qr_sessions WHERE status = 'active' GROUP BY table_id HAVING COUNT(*) > 1) as duplicates";
        $result = $this->conn->query($qr_duplicates_sql);
        $report['qr_duplicate_tables'] = $result->fetch_assoc()['count'];
        
        $table_duplicates_sql = "SELECT COUNT(*) as count FROM (SELECT table_id FROM table_sessions WHERE status = 'active' GROUP BY table_id HAVING COUNT(*) > 1) as duplicates";
        $result = $this->conn->query($table_duplicates_sql);
        $report['table_duplicate_tables'] = $result->fetch_assoc()['count'];
        
        return $report;
    }
}