<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

echo "<h2>🔧 Debug QR API</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto;'>";

try {
    // Check if secure QR tables exist
    echo "<div class='alert alert-info'>";
    echo "<h4>Database Tables Check</h4>";
    
    $tables_to_check = ['qr_sessions', 'qr_orders', 'qr_session_notifications'];
    
    foreach ($tables_to_check as $table) {
        $check_sql = "SHOW TABLES LIKE '$table'";
        $result = $conn->query($check_sql);
        
        if ($result->num_rows > 0) {
            echo "<p><strong>$table</strong> table exists</p>";
        } else {
            echo "<p><strong>$table</strong> table missing</p>";
        }
    }
    echo "</div>";
    
    // Check if we have any active sessions
    $sessions_sql = "SELECT COUNT(*) as count FROM qr_sessions";
    $result = $conn->query($sessions_sql);
    $session_count = $result->fetch_assoc()['count'];
    
    echo "<div class='alert alert-info'>";
    echo "<h4>Current QR Sessions</h4>";
    echo "<p>Total QR sessions in database: $session_count</p>";
    echo "</div>";
    
    // Test API endpoint directly
    echo "<div class='alert alert-warning'>";
    echo "<h4>🧪 Test API Endpoint</h4>";
    echo "<p>Testing the secure QR API endpoint...</p>";
    
    // Simulate a test request
    $test_data = [
        'action' => 'test',
        'session_id' => 1,
        'session_token' => 'test_token',
        'device_fingerprint' => 'test_fingerprint'
    ];
    
    // Check if the API file exists
    $api_file = '../ordering/secure_qr_api.php';
    if (file_exists($api_file)) {
        echo "<p>API file exists at: $api_file</p>";
        
        // Test if we can include it
        try {
            // Read the file to check for syntax errors
            $content = file_get_contents($api_file);
            echo "<p>API file is readable</p>";
            
            // Check for basic PHP syntax
            if (strpos($content, '<?php') !== false) {
                echo "<p>API file contains PHP code</p>";
            } else {
                echo "<p>API file doesn't contain PHP code</p>";
            }
            
        } catch (Exception $e) {
            echo "<p>Error reading API file: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>API file not found at: $api_file</p>";
    }
    echo "</div>";
    
    // Show recent error logs if available
    echo "<div class='alert alert-info'>";
    echo "<h4>📝 Recent Errors</h4>";
    
    // Try to get error log information
    $error_log = ini_get('error_log');
    if ($error_log && file_exists($error_log)) {
        echo "<p>Error log location: $error_log</p>";
        $errors = tail($error_log, 10);
        if ($errors) {
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px;'>";
            echo htmlspecialchars($errors);
            echo "</pre>";
        }
    } else {
        echo "<p>No error log found or accessible</p>";
    }
    echo "</div>";
    
    echo "<div class='d-grid gap-2 d-md-flex justify-content-md-center'>";
    echo "<a href='setup_secure_qr_system.php' class='btn btn-warning btn-lg'>Setup Secure QR System</a>";
    echo "<a href='test_secure_qr.php' class='btn btn-primary btn-lg'>Test QR System</a>";
    echo "<a href='index.php' class='btn btn-secondary btn-lg'>Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

function tail($file, $lines = 10) {
    $handle = fopen($file, "r");
    if (!$handle) return false;
    
    $linecounter = $lines;
    $pos = -2;
    $beginning = false;
    $text = array();
    
    while ($linecounter > 0) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        $linecounter--;
        if ($beginning) {
            rewind($handle);
        }
        $text[$lines - $linecounter - 1] = fgets($handle);
        if ($beginning) break;
    }
    fclose($handle);
    return implode("", array_reverse($text));
}

echo "</div>";
?>





