<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

echo "<h2>🍽️ Setup Table Session System</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto;'>";

try {
    // Create table sessions table
    $create_sessions_table = "
    CREATE TABLE IF NOT EXISTS table_sessions (
        session_id INT AUTO_INCREMENT PRIMARY KEY,
        table_id INT NOT NULL,
        session_token VARCHAR(64) NOT NULL UNIQUE,
        status ENUM('active', 'closed', 'paid') DEFAULT 'active',
        total_amount DECIMAL(10,2) DEFAULT 0,
        items_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        closed_at TIMESTAMP NULL,
        paid_at TIMESTAMP NULL,
        notes TEXT,
        INDEX idx_table_id (table_id),
        INDEX idx_session_token (session_token),
        INDEX idx_status (status),
        FOREIGN KEY (table_id) REFERENCES tables(table_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_sessions_table)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Table Sessions Table Created</h4>";
        echo "<p>Table for managing ongoing table dining sessions created successfully!</p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating table sessions table: " . $conn->error);
    }
    
    // Create table session items table
    $create_session_items_table = "
    CREATE TABLE IF NOT EXISTS table_session_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        notes TEXT,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'confirmed', 'preparing', 'ready', 'served') DEFAULT 'pending',
        INDEX idx_session_id (session_id),
        INDEX idx_menu_item_id (menu_item_id),
        INDEX idx_status (status),
        FOREIGN KEY (session_id) REFERENCES table_sessions(session_id) ON DELETE CASCADE,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_session_items_table)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Table Session Items Table Created</h4>";
        echo "<p>Table for managing individual items within table sessions created successfully!</p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating table session items table: " . $conn->error);
    }
    
    // Create table session notifications table
    $create_notifications_table = "
    CREATE TABLE IF NOT EXISTS table_session_notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        notification_type ENUM('bill_request', 'service_request', 'order_update') NOT NULL,
        message TEXT NOT NULL,
        status ENUM('pending', 'acknowledged', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        acknowledged_at TIMESTAMP NULL,
        acknowledged_by INT,
        INDEX idx_session_id (session_id),
        INDEX idx_status (status),
        INDEX idx_type (notification_type),
        FOREIGN KEY (session_id) REFERENCES table_sessions(session_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_notifications_table)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Table Session Notifications Table Created</h4>";
        echo "<p>Table for managing waiter notifications and service requests created successfully!</p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating notifications table: " . $conn->error);
    }
    
    // Update existing tables to include session support
    $check_qr_column = "SHOW COLUMNS FROM tables LIKE 'qr_code_url'";
    $result = $conn->query($check_qr_column);
    
    if ($result->num_rows == 0) {
        $add_qr_column = "ALTER TABLE tables ADD COLUMN qr_code_url VARCHAR(255) AFTER table_number";
        if ($conn->query($add_qr_column)) {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Added QR Code Column</h4>";
            echo "<p>Added qr_code_url column to tables table for session-based ordering.</p>";
            echo "</div>";
        }
    }
    
    // Generate QR codes for existing tables
    $tables_sql = "SELECT table_id, table_number FROM tables WHERE is_active = 1";
    $tables_result = $conn->query($tables_sql);
    
    $qr_updated = 0;
    while ($table = $tables_result->fetch_assoc()) {
        $base_url = "http://localhost/capstone/ordering/table_menu.php";
        $qr_url = "{$base_url}?table={$table['table_number']}";
        
        $update_qr = "UPDATE tables SET qr_code_url = ? WHERE table_id = ?";
        $update_stmt = $conn->prepare($update_qr);
        $update_stmt->bind_param('si', $qr_url, $table['table_id']);
        
        if ($update_stmt->execute()) {
            $qr_updated++;
        }
        $update_stmt->close();
    }
    
    if ($qr_updated > 0) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Updated Table QR Codes</h4>";
        echo "<p>{$qr_updated} table QR codes updated for session-based ordering.</p>";
        echo "</div>";
    }
    
    echo "<br><div class='alert alert-success'>";
    echo "<h4>🎉 Table Session System Setup Complete!</h4>";
    echo "<p>Your restaurant now supports premium table-based dining sessions!</p>";
    echo "</div>";
    
    echo "<div class='d-grid gap-2 d-md-flex justify-content-md-center'>";
    echo "<a href='table_management.php' class='btn btn-primary btn-lg'>Manage Tables</a>";
    echo "<a href='table_sessions.php' class='btn btn-success btn-lg'>View Active Sessions</a>";
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









