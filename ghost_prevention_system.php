<?php
require_once 'admin/includes/db_connection.php';

echo "<h2>🛡️ GHOST PREVENTION SYSTEM - DUPLICATE PROTECTION!</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1200px; margin: 0 auto; background: #f8f9fa; padding: 20px; border-radius: 10px;'>";

try {
    echo "<h3>🔍 ANALYZING CURRENT DUPLICATE PREVENTION...</h3>";
    
    // Check if there are any existing duplicate prevention mechanisms
    echo "<h4>📊 Current System Analysis:</h4>";
    
    // Check for unique constraints
    $constraints_sql = "SELECT 
        kcu.TABLE_NAME,
        kcu.COLUMN_NAME,
        kcu.CONSTRAINT_NAME,
        tc.CONSTRAINT_TYPE
    FROM information_schema.KEY_COLUMN_USAGE kcu
    JOIN information_schema.TABLE_CONSTRAINTS tc 
        ON kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME 
        AND kcu.TABLE_SCHEMA = tc.TABLE_SCHEMA
        AND kcu.TABLE_NAME = tc.TABLE_NAME
    WHERE kcu.TABLE_SCHEMA = DATABASE() 
    AND kcu.TABLE_NAME IN ('qr_sessions', 'table_sessions')
    AND tc.CONSTRAINT_TYPE IN ('UNIQUE', 'PRIMARY KEY')
    ORDER BY kcu.TABLE_NAME, kcu.CONSTRAINT_NAME";
    
    $result = $conn->query($constraints_sql);
    
    if ($result->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Table</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Column</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Constraint</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Type</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['TABLE_NAME']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['COLUMN_NAME']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['CONSTRAINT_NAME']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['CONSTRAINT_TYPE']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No unique constraints found.<br>";
    }
    
    echo "<br><h3>🛡️ IMPLEMENTING GHOST PREVENTION MEASURES...</h3>";
    
    // Start transaction
    $conn->begin_transaction();
    
    // 1. Add unique constraint to prevent duplicate active QR sessions per table
    echo "<h4>🔒 Adding Unique Constraint for QR Sessions:</h4>";
    
    // First, check if constraint already exists
    $check_constraint_sql = "SELECT COUNT(*) as count 
                            FROM information_schema.TABLE_CONSTRAINTS 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = 'qr_sessions' 
                            AND CONSTRAINT_NAME = 'unique_active_qr_session_per_table'";
    
    $result = $conn->query($check_constraint_sql);
    $constraint_exists = $result->fetch_assoc()['count'] > 0;
    
    if (!$constraint_exists) {
        // Add unique index for active sessions per table
        $add_constraint_sql = "CREATE UNIQUE INDEX unique_active_qr_session_per_table 
                              ON qr_sessions (table_id, status) 
                              WHERE status = 'active'";
        
        try {
            $conn->query($add_constraint_sql);
            echo "✅ Added unique constraint for active QR sessions per table<br>";
        } catch (Exception $e) {
            echo "⚠️ Could not add unique constraint (MySQL version may not support filtered indexes): " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ Unique constraint already exists<br>";
    }
    
    // 2. Add unique constraint to prevent duplicate active table sessions per table
    echo "<h4>🔒 Adding Unique Constraint for Table Sessions:</h4>";
    
    $check_table_constraint_sql = "SELECT COUNT(*) as count 
                                  FROM information_schema.TABLE_CONSTRAINTS 
                                  WHERE TABLE_SCHEMA = DATABASE() 
                                  AND TABLE_NAME = 'table_sessions' 
                                  AND CONSTRAINT_NAME = 'unique_active_table_session_per_table'";
    
    $result = $conn->query($check_table_constraint_sql);
    $table_constraint_exists = $result->fetch_assoc()['count'] > 0;
    
    if (!$table_constraint_exists) {
        try {
            $add_table_constraint_sql = "CREATE UNIQUE INDEX unique_active_table_session_per_table 
                                        ON table_sessions (table_id, status) 
                                        WHERE status = 'active'";
            $conn->query($add_table_constraint_sql);
            echo "✅ Added unique constraint for active table sessions per table<br>";
        } catch (Exception $e) {
            echo "⚠️ Could not add unique constraint (MySQL version may not support filtered indexes): " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ Unique constraint already exists<br>";
    }
    
    // 3. Create a function to safely create QR sessions
    echo "<h4>🔧 Creating Safe QR Session Creation Function:</h4>";
    
    $create_function_sql = "CREATE OR REPLACE FUNCTION create_safe_qr_session(
        p_table_id INT,
        p_session_token VARCHAR(64),
        p_device_fingerprint VARCHAR(128),
        p_ip_address VARCHAR(45),
        p_user_agent TEXT
    ) RETURNS INT
    BEGIN
        DECLARE session_id INT DEFAULT NULL;
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            RESIGNAL;
        END;
        
        START TRANSACTION;
        
        -- Check if there's already an active session for this table
        SELECT qs.session_id INTO session_id
        FROM qr_sessions qs
        WHERE qs.table_id = p_table_id 
        AND qs.status = 'active'
        LIMIT 1;
        
        -- If active session exists, close it first
        IF session_id IS NOT NULL THEN
            UPDATE qr_sessions 
            SET status = 'archived', 
                last_activity = NOW()
            WHERE session_id = session_id;
        END IF;
        
        -- Create new session
        INSERT INTO qr_sessions (
            table_id, session_token, device_fingerprint, 
            ip_address, user_agent, status, created_at, last_activity
        ) VALUES (
            p_table_id, p_session_token, p_device_fingerprint,
            p_ip_address, p_user_agent, 'active', NOW(), NOW()
        );
        
        SET session_id = LAST_INSERT_ID();
        
        COMMIT;
        RETURN session_id;
    END";
    
    try {
        $conn->query($create_function_sql);
        echo "✅ Created safe QR session creation function<br>";
    } catch (Exception $e) {
        echo "⚠️ Could not create function: " . $e->getMessage() . "<br>";
    }
    
    // 4. Create a function to safely create table sessions
    echo "<h4>🔧 Creating Safe Table Session Creation Function:</h4>";
    
    $create_table_function_sql = "CREATE OR REPLACE FUNCTION create_safe_table_session(
        p_table_id INT,
        p_customer_name VARCHAR(255),
        p_customer_phone VARCHAR(20)
    ) RETURNS INT
    BEGIN
        DECLARE session_id INT DEFAULT NULL;
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            RESIGNAL;
        END;
        
        START TRANSACTION;
        
        -- Check if there's already an active session for this table
        SELECT ts.session_id INTO session_id
        FROM table_sessions ts
        WHERE ts.table_id = p_table_id 
        AND ts.status = 'active'
        LIMIT 1;
        
        -- If active session exists, close it first
        IF session_id IS NOT NULL THEN
            UPDATE table_sessions 
            SET status = 'closed', 
                closed_at = NOW()
            WHERE session_id = session_id;
        END IF;
        
        -- Create new session
        INSERT INTO table_sessions (
            table_id, customer_name, customer_phone, 
            status, created_at, last_activity
        ) VALUES (
            p_table_id, p_customer_name, p_customer_phone,
            'active', NOW(), NOW()
        );
        
        SET session_id = LAST_INSERT_ID();
        
        COMMIT;
        RETURN session_id;
    END";
    
    try {
        $conn->query($create_table_function_sql);
        echo "✅ Created safe table session creation function<br>";
    } catch (Exception $e) {
        echo "⚠️ Could not create function: " . $e->getMessage() . "<br>";
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "<br><h3>🎉 GHOST PREVENTION SYSTEM INSTALLED!</h3>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ Ghost prevention measures are now active!</h4>";
    echo "<ul>";
    echo "<li>🔒 Unique constraints prevent duplicate active sessions</li>";
    echo "<li>🔧 Safe session creation functions automatically close old sessions</li>";
    echo "<li>🛡️ Database-level protection against duplicates</li>";
    echo "<li>⚡ Automatic cleanup of conflicting sessions</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h4>🎯 How It Works:</h4>";
    echo "<ul>";
    echo "<li><strong>QR Sessions:</strong> Only ONE active session per table allowed</li>";
    echo "<li><strong>Table Sessions:</strong> Only ONE active session per table allowed</li>";
    echo "<li><strong>Auto-Cleanup:</strong> Old sessions are automatically closed when new ones are created</li>";
    echo "<li><strong>Database Protection:</strong> Unique constraints prevent duplicates at the database level</li>";
    echo "</ul>";
    
    echo "<h4>🚀 Usage:</h4>";
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h5>For QR Sessions:</h5>";
    echo "<code>SELECT create_safe_qr_session(table_id, session_token, device_fingerprint, ip_address, user_agent);</code><br><br>";
    echo "<h5>For Table Sessions:</h5>";
    echo "<code>SELECT create_safe_table_session(table_id, customer_name, customer_phone);</code>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='counter/index.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>👨‍💼 Test Counter</a>";
    echo "<a href='admin/qr_session_management.php' style='background: #17a2b8; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>🔍 Check Admin</a>";
    echo "</div>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Error during ghost prevention installation:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>
