<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

echo "<h2>💰 Setup Cash Float System</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto;'>";

try {
    // Create cash float transactions table
    $create_transactions_table = "
    CREATE TABLE IF NOT EXISTS cash_float_transactions (
        transaction_id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_type ENUM('opening', 'closing', 'adjustment', 'sale', 'refund') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        cash_on_hand DECIMAL(10,2) NOT NULL,
        notes TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        shift_date DATE NOT NULL,
        INDEX idx_shift_date (shift_date),
        INDEX idx_created_at (created_at)
    )";
    
    if ($conn->query($create_transactions_table)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Cash Float Transactions Table Created</h4>";
        echo "<p>Table for tracking all cash float movements created successfully!</p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating transactions table: " . $conn->error);
    }
    
    // Create cash float sessions table
    $create_sessions_table = "
    CREATE TABLE IF NOT EXISTS cash_float_sessions (
        session_id INT AUTO_INCREMENT PRIMARY KEY,
        shift_date DATE NOT NULL,
        opening_amount DECIMAL(10,2) DEFAULT 0,
        closing_amount DECIMAL(10,2) DEFAULT NULL,
        total_sales DECIMAL(10,2) DEFAULT 0,
        total_refunds DECIMAL(10,2) DEFAULT 0,
        adjustments DECIMAL(10,2) DEFAULT 0,
        status ENUM('active', 'closed') DEFAULT 'active',
        assigned_to INT NOT NULL,
        assigned_by INT,
        opened_by INT,
        closed_by INT,
        opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        closed_at TIMESTAMP NULL,
        notes TEXT,
        UNIQUE KEY unique_counter_shift_date (shift_date, assigned_to),
        INDEX idx_status (status),
        INDEX idx_assigned_to (assigned_to)
    )";
    
    if ($conn->query($create_sessions_table)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Cash Float Sessions Table Created</h4>";
        echo "<p>Table for managing daily cash float sessions created successfully!</p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating sessions table: " . $conn->error);
    }
    
    // Create cash denominations table
    $create_denominations_table = "
    CREATE TABLE IF NOT EXISTS cash_denominations (
        denomination_id INT AUTO_INCREMENT PRIMARY KEY,
        denomination_name VARCHAR(50) NOT NULL,
        denomination_value DECIMAL(10,2) NOT NULL,
        currency_symbol VARCHAR(10) DEFAULT '₱',
        is_active TINYINT(1) DEFAULT 1,
        display_order INT DEFAULT 0,
        UNIQUE KEY unique_denomination (denomination_value)
    )";
    
    if ($conn->query($create_denominations_table)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Cash Denominations Table Created</h4>";
        echo "<p>Table for managing cash denominations created successfully!</p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating denominations table: " . $conn->error);
    }
    
    // Insert default Philippine peso denominations
    $denominations = [
        ['1000.00', '₱1000', 1000],
        ['500.00', '₱500', 900],
        ['200.00', '₱200', 800],
        ['100.00', '₱100', 700],
        ['50.00', '₱50', 600],
        ['20.00', '₱20', 500],
        ['10.00', '₱10', 400],
        ['5.00', '₱5', 300],
        ['1.00', '₱1', 200],
        ['0.25', '₱0.25', 100],
        ['0.10', '₱0.10', 90],
        ['0.05', '₱0.05', 80],
        ['0.01', '₱0.01', 70]
    ];
    
    $insert_denomination = "INSERT IGNORE INTO cash_denominations (denomination_value, denomination_name, display_order) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_denomination);
    
    $inserted_count = 0;
    foreach ($denominations as $denom) {
        $stmt->bind_param('dsi', $denom[0], $denom[1], $denom[2]);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $inserted_count++;
        }
    }
    $stmt->close();
    
    if ($inserted_count > 0) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Philippine Peso Denominations Added</h4>";
        echo "<p>{$inserted_count} cash denominations added successfully!</p>";
        echo "</div>";
    }
    
    // Check if there are existing tables to link cash float to
    $tables_check = $conn->query("SELECT COUNT(*) as count FROM tables");
    $tables_count = $tables_check->fetch_assoc()['count'];
    
    if ($tables_count > 0) {
        echo "<div class='alert alert-info'>";
        echo "<h4>📊 System Status</h4>";
        echo "<p><strong>Tables Found:</strong> {$tables_count}</p>";
        echo "<p><strong>Cash Float System:</strong> Ready to use</p>";
        echo "</div>";
    }
    
    echo "<br><div class='alert alert-success'>";
    echo "<h4>🎉 Cash Float System Setup Complete!</h4>";
    echo "<p>Your cash float system is now ready for counter and admin use!</p>";
    echo "</div>";
    
    echo "<div class='d-grid gap-2 d-md-flex justify-content-md-center'>";
    echo "<a href='cash_float_counter.php' class='btn btn-primary btn-lg'>Counter Interface</a>";
    echo "<a href='cash_float_admin.php' class='btn btn-success btn-lg'>Admin Management</a>";
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
