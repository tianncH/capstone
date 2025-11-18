<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

echo "<h2>🔐 Setup Secure QR-Based Ordering System</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto;'>";

try {
    // Create QR sessions table for secure session management
    $create_qr_sessions_table = "
    CREATE TABLE IF NOT EXISTS qr_sessions (
        session_id INT AUTO_INCREMENT PRIMARY KEY,
        table_id INT NOT NULL,
        session_token VARCHAR(64) NOT NULL UNIQUE,
        device_fingerprint VARCHAR(128) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        status ENUM('active', 'locked', 'archived', 'expired') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        confirmed_by_counter BOOLEAN DEFAULT FALSE,
        confirmed_at TIMESTAMP NULL,
        confirmed_by INT NULL,
        notes TEXT,
        INDEX idx_table_id (table_id),
        INDEX idx_session_token (session_token),
        INDEX idx_device_fingerprint (device_fingerprint),
        INDEX idx_status (status),
        INDEX idx_expires_at (expires_at),
        FOREIGN KEY (table_id) REFERENCES tables(table_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_qr_sessions_table)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ QR Sessions Table Created</h4>";
        echo "<p>Table for managing secure QR-based ordering sessions created successfully!</p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating QR sessions table: " . $conn->error);
    }
    
    // Create QR orders table for individual orders within sessions
    $create_qr_orders_table = "
    CREATE TABLE IF NOT EXISTS qr_orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'confirmed', 'preparing', 'ready', 'served', 'cancelled') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        confirmed_at TIMESTAMP NULL,
        cancelled_at TIMESTAMP NULL,
        cancellation_reason TEXT,
        cancellation_approved_by INT NULL,
        cancellation_approved_at TIMESTAMP NULL,
        time_limit_expires TIMESTAMP NULL,
        INDEX idx_session_id (session_id),
        INDEX idx_menu_item_id (menu_item_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        INDEX idx_time_limit (time_limit_expires),
        FOREIGN KEY (session_id) REFERENCES qr_sessions(session_id) ON DELETE CASCADE,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_qr_orders_table)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ QR Orders Table Created</h4>";
        echo "<p>Table for managing individual orders within QR sessions created successfully!</p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating QR orders table: " . $conn->error);
    }
    
    // Create QR session notifications table
    $create_qr_notifications_table = "
    CREATE TABLE IF NOT EXISTS qr_session_notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        notification_type ENUM('new_session', 'bill_request', 'order_update', 'cancellation_request', 'session_expired') NOT NULL,
        message TEXT NOT NULL,
        status ENUM('pending', 'acknowledged', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        acknowledged_at TIMESTAMP NULL,
        acknowledged_by INT,
        data JSON,
        INDEX idx_session_id (session_id),
        INDEX idx_status (status),
        INDEX idx_type (notification_type),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (session_id) REFERENCES qr_sessions(session_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_qr_notifications_table)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ QR Session Notifications Table Created</h4>";
        echo "<p>Table for managing notifications within QR sessions created successfully!</p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating QR session notifications table: " . $conn->error);
    }
    
    // Create QR session archive table for historical data
    $create_qr_archive_table = "
    CREATE TABLE IF NOT EXISTS qr_session_archive (
        archive_id INT AUTO_INCREMENT PRIMARY KEY,
        original_session_id INT NOT NULL,
        table_id INT NOT NULL,
        session_token VARCHAR(64) NOT NULL,
        device_fingerprint VARCHAR(128) NOT NULL,
        total_orders INT NOT NULL DEFAULT 0,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        status_at_archive VARCHAR(20) NOT NULL,
        created_at TIMESTAMP NOT NULL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        archived_by INT,
        notes TEXT,
        INDEX idx_table_id (table_id),
        INDEX idx_archived_at (archived_at),
        INDEX idx_original_session (original_session_id)
    )";
    
    if ($conn->query($create_qr_archive_table)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ QR Session Archive Table Created</h4>";
        echo "<p>Table for archiving completed QR sessions created successfully!</p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating QR session archive table: " . $conn->error);
    }
    
    // Update existing tables to include QR code support
    $check_qr_column = "SHOW COLUMNS FROM tables LIKE 'qr_code'";
    $result = $conn->query($check_qr_column);
    
    if ($result->num_rows == 0) {
        $add_qr_column = "ALTER TABLE tables ADD COLUMN qr_code VARCHAR(10) UNIQUE AFTER table_number";
        if ($conn->query($add_qr_column)) {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Added QR Code Column</h4>";
            echo "<p>Added qr_code column to tables table for unique QR identification.</p>";
            echo "</div>";
        }
    }
    
    // Generate unique QR codes for existing tables
    $tables_sql = "SELECT table_id, table_number FROM tables WHERE is_active = 1";
    $tables_result = $conn->query($tables_sql);
    
    $qr_updated = 0;
    while ($table = $tables_result->fetch_assoc()) {
        $qr_code = 'QR_' . str_pad($table['table_number'], 3, '0', STR_PAD_LEFT);
        
        $update_qr = "UPDATE tables SET qr_code = ? WHERE table_id = ?";
        $update_stmt = $conn->prepare($update_qr);
        $update_stmt->bind_param('si', $qr_code, $table['table_id']);
        
        if ($update_stmt->execute()) {
            $qr_updated++;
        }
        $update_stmt->close();
    }
    
    if ($qr_updated > 0) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Updated Table QR Codes</h4>";
        echo "<p>{$qr_updated} table QR codes updated with unique identifiers.</p>";
        echo "</div>";
    }
    
    // Create indexes for performance
    $indexes = [
        "CREATE INDEX idx_qr_sessions_table_status ON qr_sessions(table_id, status)",
        "CREATE INDEX idx_qr_orders_session_status ON qr_orders(session_id, status)",
        "CREATE INDEX idx_qr_notifications_type_status ON qr_session_notifications(notification_type, status)"
    ];
    
    foreach ($indexes as $index_sql) {
        if ($conn->query($index_sql)) {
            echo "<div class='alert alert-info'>";
            echo "<p>✅ Performance index created successfully</p>";
            echo "</div>";
        }
    }
    
    echo "<br><div class='alert alert-success'>";
    echo "<h4>🎉 Secure QR-Based Ordering System Setup Complete!</h4>";
    echo "<p>Your restaurant now has a comprehensive, secure QR-based ordering system with:</p>";
    echo "<ul>";
    echo "<li>✅ Secure session management with device fingerprinting</li>";
    echo "<li>✅ Time-limited order cancellation</li>";
    echo "<li>✅ Counter confirmation for new sessions</li>";
    echo "<li>✅ Complete audit trail and archiving</li>";
    echo "<li>✅ Real-time notifications and tracking</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='d-grid gap-2 d-md-flex justify-content-md-center'>";
    echo "<a href='qr_session_management.php' class='btn btn-primary btn-lg'>Manage QR Sessions</a>";
    echo "<a href='test_secure_qr.php' class='btn btn-success btn-lg'>Test QR System</a>";
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
