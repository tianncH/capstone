<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

echo "<h2>📱 Setup Feedback System</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 800px; margin: 0 auto;'>";

try {
    // Create feedback table
    $create_feedback_table = "
    CREATE TABLE IF NOT EXISTS feedback (
        feedback_id INT AUTO_INCREMENT PRIMARY KEY,
        table_id INT,
        customer_name VARCHAR(255),
        customer_email VARCHAR(255),
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        feedback_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (table_id) REFERENCES tables(table_id) ON DELETE SET NULL
    )";
    
    if ($conn->query($create_feedback_table)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Feedback Table Created</h4>";
        echo "<p>Feedback table created successfully!</p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating feedback table: " . $conn->error);
    }
    
    // Create uploads directory for QR codes
    $qr_upload_dir = '../uploads/qr_codes/';
    if (!file_exists($qr_upload_dir)) {
        if (mkdir($qr_upload_dir, 0777, true)) {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ QR Codes Directory Created</h4>";
            echo "<p>QR codes upload directory created successfully!</p>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>";
            echo "<h4>⚠️ Directory Creation Warning</h4>";
            echo "<p>Could not create QR codes directory automatically. Please create it manually.</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>";
        echo "<h4>ℹ️ QR Codes Directory Exists</h4>";
        echo "<p>QR codes directory already exists.</p>";
        echo "</div>";
    }
    
    // Check if tables exist
    $tables_check = $conn->query("SELECT COUNT(*) as count FROM tables");
    $tables_count = $tables_check->fetch_assoc()['count'];
    
    if ($tables_count > 0) {
        echo "<div class='alert alert-info'>";
        echo "<h4>📊 System Status</h4>";
        echo "<p><strong>Tables Found:</strong> {$tables_count}</p>";
        echo "<p><strong>Ready for QR Code Generation:</strong> Yes</p>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ No Tables Found</h4>";
        echo "<p>Please add some tables first before generating QR codes.</p>";
        echo "<a href='table_management.php' class='btn btn-primary'>Manage Tables</a>";
        echo "</div>";
    }
    
    echo "<br><div class='alert alert-success'>";
    echo "<h4>🎉 Setup Complete!</h4>";
    echo "<p>Your feedback system is now ready to use!</p>";
    echo "</div>";
    
    echo "<div class='d-grid gap-2 d-md-flex justify-content-md-center'>";
    echo "<a href='qr_management.php' class='btn btn-primary btn-lg'>Generate QR Codes</a>";
    echo "<a href='feedback_management.php' class='btn btn-success btn-lg'>View Feedback</a>";
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









