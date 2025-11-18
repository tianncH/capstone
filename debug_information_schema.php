<?php
require_once 'admin/includes/db_connection.php';

echo "<h2>🔍 INFORMATION_SCHEMA DEBUG - FINDING CORRECT COLUMNS!</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1200px; margin: 0 auto; background: #f8f9fa; padding: 20px; border-radius: 10px;'>";

try {
    echo "<h3>📊 KEY_COLUMN_USAGE Table Structure:</h3>";
    
    // Get the structure of information_schema.KEY_COLUMN_USAGE
    $schema_sql = "DESCRIBE information_schema.KEY_COLUMN_USAGE";
    $result = $conn->query($schema_sql);
    
    if ($result->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Column</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Type</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Null</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Key</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Default</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Extra</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px; font-weight: bold;'>{$row['Field']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['Type']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['Null']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['Key']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['Default']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>📊 TABLE_CONSTRAINTS Table Structure:</h3>";
    
    // Get the structure of information_schema.TABLE_CONSTRAINTS
    $constraints_schema_sql = "DESCRIBE information_schema.TABLE_CONSTRAINTS";
    $result = $conn->query($constraints_schema_sql);
    
    if ($result->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Column</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Type</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Null</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Key</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Default</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Extra</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px; font-weight: bold;'>{$row['Field']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['Type']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['Null']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['Key']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['Default']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>🔍 Sample Data from KEY_COLUMN_USAGE:</h3>";
    
    // Get sample data to see what's available
    $sample_sql = "SELECT * FROM information_schema.KEY_COLUMN_USAGE 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME IN ('qr_sessions', 'table_sessions')
                   LIMIT 10";
    $result = $conn->query($sample_sql);
    
    if ($result->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        
        // Get column names
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns = array_keys($row);
            break;
        }
        
        // Display headers
        foreach ($columns as $column) {
            echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>{$column}</th>";
        }
        echo "</tr>";
        
        // Reset result pointer
        $result->data_seek(0);
        
        // Display data
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($columns as $column) {
                $value = $row[$column];
                if (strlen($value) > 20) {
                    $value = substr($value, 0, 20) . '...';
                }
                echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$value}</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No constraints found for qr_sessions or table_sessions.<br>";
    }
    
    echo "<h3>🔍 Sample Data from TABLE_CONSTRAINTS:</h3>";
    
    // Get sample data from TABLE_CONSTRAINTS
    $constraints_sample_sql = "SELECT * FROM information_schema.TABLE_CONSTRAINTS 
                              WHERE TABLE_SCHEMA = DATABASE() 
                              AND TABLE_NAME IN ('qr_sessions', 'table_sessions')
                              LIMIT 10";
    $result = $conn->query($constraints_sample_sql);
    
    if ($result->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        
        // Get column names
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns = array_keys($row);
            break;
        }
        
        // Display headers
        foreach ($columns as $column) {
            echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>{$column}</th>";
        }
        echo "</tr>";
        
        // Reset result pointer
        $result->data_seek(0);
        
        // Display data
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($columns as $column) {
                $value = $row[$column];
                if (strlen($value) > 20) {
                    $value = substr($value, 0, 20) . '...';
                }
                echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$value}</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No constraints found for qr_sessions or table_sessions.<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>






