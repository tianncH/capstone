<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

echo "<h2>🧪 Test Table Sessions System</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto;'>";

try {
    // Test 1: Check if tables exist
    echo "<div class='alert alert-info'>";
    echo "<h4>📋 Database Tables Check</h4>";
    
    $tables_to_check = ['table_sessions', 'table_session_items', 'table_session_notifications'];
    
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
    
    // Test 2: Check if we have active tables
    echo "<div class='alert alert-info'>";
    echo "<h4>🪑 Active Tables Check</h4>";
    
    $tables_sql = "SELECT COUNT(*) as count FROM tables WHERE is_active = 1";
    $result = $conn->query($tables_sql);
    $table_count = $result->fetch_assoc()['count'];
    
    if ($table_count > 0) {
        echo "<p>✅ Found $table_count active tables</p>";
        
        // Show table details
        $tables_detail_sql = "SELECT table_id, table_number FROM tables WHERE is_active = 1 ORDER BY table_number";
        $tables_result = $conn->query($tables_detail_sql);
        
        echo "<p><strong>Available Tables:</strong></p>";
        echo "<ul>";
        while ($table = $tables_result->fetch_assoc()) {
            echo "<li>Table " . $table['table_number'] . " (ID: " . $table['table_id'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ No active tables found</p>";
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
    
    // Test 4: Test table session creation
    echo "<div class='alert alert-info'>";
    echo "<h4>🔧 Test Session Creation</h4>";
    
    // Get first active table
    $test_table_sql = "SELECT table_id, table_number FROM tables WHERE is_active = 1 LIMIT 1";
    $test_result = $conn->query($test_table_sql);
    
    if ($test_result->num_rows > 0) {
        $test_table = $test_result->fetch_assoc();
        $test_table_id = $test_table['table_id'];
        $test_table_number = $test_table['table_number'];
        
        // Create a test session
        $session_token = bin2hex(random_bytes(32));
        $create_session_sql = "INSERT INTO table_sessions (table_id, session_token, status) VALUES (?, ?, 'active')";
        $create_stmt = $conn->prepare($create_session_sql);
        $create_stmt->bind_param('is', $test_table_id, $session_token);
        
        if ($create_stmt->execute()) {
            $session_id = $conn->insert_id;
            echo "<p>✅ Successfully created test session for Table $test_table_number (Session ID: $session_id)</p>";
            
            // Clean up test session
            $cleanup_sql = "DELETE FROM table_sessions WHERE session_id = ?";
            $cleanup_stmt = $conn->prepare($cleanup_sql);
            $cleanup_stmt->bind_param('i', $session_id);
            $cleanup_stmt->execute();
            $cleanup_stmt->close();
            
            echo "<p>🧹 Test session cleaned up</p>";
        } else {
            echo "<p>❌ Failed to create test session: " . $conn->error . "</p>";
        }
        $create_stmt->close();
    } else {
        echo "<p>❌ No tables available for testing</p>";
    }
    echo "</div>";
    
    // Test 5: Generate test URLs
    echo "<div class='alert alert-success'>";
    echo "<h4>🔗 Test URLs</h4>";
    echo "<p>Use these URLs to test the new table-based ordering system:</p>";
    
    $test_tables_sql = "SELECT table_number FROM tables WHERE is_active = 1 ORDER BY table_number LIMIT 3";
    $test_tables_result = $conn->query($test_tables_sql);
    
    echo "<ul>";
    while ($test_table = $test_tables_result->fetch_assoc()) {
        $test_url = "http://localhost/capstone/ordering/table_menu.php?table=" . $test_table['table_number'];
        echo "<li><a href='$test_url' target='_blank'>Table " . $test_table['table_number'] . " - New Ordering System</a></li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='d-grid gap-2 d-md-flex justify-content-md-center'>";
    echo "<a href='setup_table_sessions.php' class='btn btn-warning btn-lg'>Setup Database Tables</a>";
    echo "<a href='table_sessions.php' class='btn btn-primary btn-lg'>View Table Sessions</a>";
    echo "<a href='index.php' class='btn btn-secondary btn-lg'>Admin Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>









