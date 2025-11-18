<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

echo "<h2>🔧 Fix Cash Float Database Schema</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto;'>";

try {
    // Check if assigned_to column exists
    $check_column = "SHOW COLUMNS FROM cash_float_sessions LIKE 'assigned_to'";
    $result = $conn->query($check_column);
    
    if ($result->num_rows == 0) {
        // Add assigned_to column
        $add_assigned_to = "ALTER TABLE cash_float_sessions ADD COLUMN assigned_to INT NOT NULL DEFAULT 1 AFTER status";
        if ($conn->query($add_assigned_to)) {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Added assigned_to Column</h4>";
            echo "<p>Successfully added assigned_to column to cash_float_sessions table.</p>";
            echo "</div>";
        } else {
            throw new Exception("Error adding assigned_to column: " . $conn->error);
        }
        
        // Add assigned_by column
        $add_assigned_by = "ALTER TABLE cash_float_sessions ADD COLUMN assigned_by INT AFTER assigned_to";
        if ($conn->query($add_assigned_by)) {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Added assigned_by Column</h4>";
            echo "<p>Successfully added assigned_by column to cash_float_sessions table.</p>";
            echo "</div>";
        } else {
            throw new Exception("Error adding assigned_by column: " . $conn->error);
        }
        
        // Update unique key to include assigned_to
        $drop_unique = "ALTER TABLE cash_float_sessions DROP INDEX unique_shift_date";
        $conn->query($drop_unique); // Ignore error if index doesn't exist
        
        $add_unique = "ALTER TABLE cash_float_sessions ADD UNIQUE KEY unique_counter_shift_date (shift_date, assigned_to)";
        if ($conn->query($add_unique)) {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Updated Unique Key</h4>";
            echo "<p>Successfully updated unique key to include counter assignment.</p>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>";
            echo "<h4>⚠️ Unique Key Warning</h4>";
            echo "<p>Could not update unique key: " . $conn->error . "</p>";
            echo "</div>";
        }
        
        // Add index for assigned_to
        $add_index = "ALTER TABLE cash_float_sessions ADD INDEX idx_assigned_to (assigned_to)";
        if ($conn->query($add_index)) {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Added Index</h4>";
            echo "<p>Successfully added index for assigned_to column.</p>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>";
            echo "<h4>⚠️ Index Warning</h4>";
            echo "<p>Could not add index: " . $conn->error . "</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div class='alert alert-info'>";
        echo "<h4>ℹ️ Schema Already Updated</h4>";
        echo "<p>The assigned_to column already exists in the cash_float_sessions table.</p>";
        echo "</div>";
    }
    
    // Check if session_id column exists in transactions table
    $check_session_id = "SHOW COLUMNS FROM cash_float_transactions LIKE 'session_id'";
    $result2 = $conn->query($check_session_id);
    
    if ($result2->num_rows == 0) {
        // Add session_id column to transactions table
        $add_session_id = "ALTER TABLE cash_float_transactions ADD COLUMN session_id INT AFTER transaction_id";
        if ($conn->query($add_session_id)) {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Added session_id Column</h4>";
            echo "<p>Successfully added session_id column to cash_float_transactions table.</p>";
            echo "</div>";
        } else {
            throw new Exception("Error adding session_id column: " . $conn->error);
        }
        
        // Add foreign key constraint
        $add_fk = "ALTER TABLE cash_float_transactions ADD CONSTRAINT fk_session_id 
                   FOREIGN KEY (session_id) REFERENCES cash_float_sessions(session_id) ON DELETE CASCADE";
        if ($conn->query($add_fk)) {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Added Foreign Key</h4>";
            echo "<p>Successfully added foreign key constraint for session_id.</p>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>";
            echo "<h4>⚠️ Foreign Key Warning</h4>";
            echo "<p>Could not add foreign key: " . $conn->error . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>";
        echo "<h4>ℹ️ Transactions Schema Already Updated</h4>";
        echo "<p>The session_id column already exists in the cash_float_transactions table.</p>";
        echo "</div>";
    }
    
    echo "<br><div class='alert alert-success'>";
    echo "<h4>🎉 Database Schema Fix Complete!</h4>";
    echo "<p>Your cash float system database is now properly configured!</p>";
    echo "</div>";
    
    echo "<div class='d-grid gap-2 d-md-flex justify-content-md-center'>";
    echo "<a href='cash_float_assignment.php' class='btn btn-primary btn-lg'>Cash Float Assignment</a>";
    echo "<a href='cash_float_admin.php' class='btn btn-success btn-lg'>Admin Reports</a>";
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









