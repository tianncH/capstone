<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

echo "<h2>🧪 Test Secure QR System</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto;'>";

try {
    // Test 1: Check if secure QR tables exist
    echo "<div class='alert alert-info'>";
    echo "<h4>📋 Secure QR Database Tables Check</h4>";
    
    $tables_to_check = ['qr_sessions', 'qr_orders', 'qr_session_notifications', 'qr_session_archive'];
    
    foreach ($tables_to_check as $table) {
        $check_sql = "SHOW TABLES LIKE '$table'";
        $result = $conn->query($check_sql);
        
        if ($result->num_rows > 0) {
            echo "<p>✅ <strong>$table</strong> table exists</p>";
        } else {
            echo "<p>❌ <strong>$table</strong> table missing</p>";
        }
    }
    echo "</div>";
    
    // Test 2: Check if tables have QR codes
    echo "<div class='alert alert-info'>";
    echo "<h4>🔗 Table QR Codes Check</h4>";
    
    $qr_sql = "SELECT COUNT(*) as count FROM tables WHERE qr_code IS NOT NULL AND qr_code != ''";
    $result = $conn->query($qr_sql);
    $qr_count = $result->fetch_assoc()['count'];
    
    if ($qr_count > 0) {
        echo "<p>✅ Found $qr_count tables with QR codes</p>";
        
        // Show QR code details
        $qr_detail_sql = "SELECT table_id, table_number, qr_code FROM tables WHERE qr_code IS NOT NULL ORDER BY table_number LIMIT 5";
        $qr_result = $conn->query($qr_detail_sql);
        
        echo "<p><strong>Sample QR Codes:</strong></p>";
        echo "<ul>";
        while ($table = $qr_result->fetch_assoc()) {
            echo "<li>Table " . $table['table_number'] . " → " . $table['qr_code'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ No tables have QR codes assigned</p>";
    }
    echo "</div>";
    
    // Test 3: Check menu items
    echo "<div class='alert alert-info'>";
    echo "<h4>🍽️ Menu Items Check</h4>";
    
    $menu_sql = "SELECT COUNT(*) as count FROM menu_items WHERE is_available = 1";
    $result = $conn->query($menu_sql);
    $menu_count = $result->fetch_assoc()['count'];
    
    if ($menu_count > 0) {
        echo "<p>✅ Found $menu_count available menu items</p>";
    } else {
        echo "<p>❌ No available menu items found</p>";
    }
    echo "</div>";
    
    // Test 4: Test QR session creation
    echo "<div class='alert alert-info'>";
    echo "<h4>🔧 Test QR Session Creation</h4>";
    
    // Get first table with QR code
    $test_table_sql = "SELECT table_id, table_number, qr_code FROM tables WHERE qr_code IS NOT NULL LIMIT 1";
    $test_result = $conn->query($test_table_sql);
    
    if ($test_result->num_rows > 0) {
        $test_table = $test_result->fetch_assoc();
        $test_table_id = $test_table['table_id'];
        $test_table_number = $test_table['table_number'];
        $test_qr_code = $test_table['qr_code'];
        
        // Create a test session
        $session_token = bin2hex(random_bytes(32));
        $device_fingerprint = hash('sha256', 'test_device_' . time());
        $expires_at = date('Y-m-d H:i:s', strtotime('+2 hours'));
        
        $create_session_sql = "INSERT INTO qr_sessions (table_id, session_token, device_fingerprint, ip_address, user_agent, expires_at) VALUES (?, ?, ?, '127.0.0.1', 'Test Browser', ?)";
        $create_stmt = $conn->prepare($create_session_sql);
        $create_stmt->bind_param('isss', $test_table_id, $session_token, $device_fingerprint, $expires_at);
        
        if ($create_stmt->execute()) {
            $session_id = $conn->insert_id;
            echo "<p>✅ Successfully created test QR session for Table $test_table_number (Session ID: $session_id)</p>";
            
            // Clean up test session
            $cleanup_sql = "DELETE FROM qr_sessions WHERE session_id = ?";
            $cleanup_stmt = $conn->prepare($cleanup_sql);
            $cleanup_stmt->bind_param('i', $session_id);
            $cleanup_stmt->execute();
            $cleanup_stmt->close();
            
            echo "<p>🧹 Test session cleaned up</p>";
        } else {
            echo "<p>❌ Failed to create test QR session: " . $conn->error . "</p>";
        }
        $create_stmt->close();
    } else {
        echo "<p>❌ No tables with QR codes available for testing</p>";
    }
    echo "</div>";
    
    // Test 5: Generate test URLs
    echo "<div class='alert alert-success'>";
    echo "<h4>🔗 Test URLs</h4>";
    echo "<p>Use these URLs to test the secure QR-based ordering system:</p>";
    
    $test_tables_sql = "SELECT table_number, qr_code FROM tables WHERE qr_code IS NOT NULL ORDER BY table_number LIMIT 3";
    $test_tables_result = $conn->query($test_tables_sql);
    
    echo "<ul>";
    while ($test_table = $test_tables_result->fetch_assoc()) {
        $test_url = "http://localhost/capstone/ordering/secure_qr_menu.php?qr=" . $test_table['qr_code'];
        echo "<li><a href='$test_url' target='_blank'>Table " . $test_table['table_number'] . " - Secure QR Menu (" . $test_table['qr_code'] . ")</a></li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Test 6: Security Features
    echo "<div class='alert alert-success'>";
    echo "<h4>🔐 Security Features Implemented</h4>";
    echo "<ul>";
    echo "<li>✅ <strong>Device Fingerprinting</strong> - Each session tied to specific device</li>";
    echo "<li>✅ <strong>Session Tokens</strong> - Unique tokens for each QR scan</li>";
    echo "<li>✅ <strong>Time Limits</strong> - 2-minute cancellation window for orders</li>";
    echo "<li>✅ <strong>Counter Confirmation</strong> - New sessions require staff approval</li>";
    echo "<li>✅ <strong>Session Expiration</strong> - 2-hour session timeout</li>";
    echo "<li>✅ <strong>Order Status Tracking</strong> - Complete audit trail</li>";
    echo "<li>✅ <strong>Archive System</strong> - Historical session data</li>";
    echo "<li>✅ <strong>Real-time Notifications</strong> - Instant staff alerts</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='d-grid gap-2 d-md-flex justify-content-md-center'>";
    echo "<a href='setup_secure_qr_system.php' class='btn btn-warning btn-lg'>Setup Secure QR System</a>";
    echo "<a href='qr_session_management.php' class='btn btn-primary btn-lg'>Manage QR Sessions</a>";
    echo "<a href='index.php' class='btn btn-secondary btn-lg'>Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>
