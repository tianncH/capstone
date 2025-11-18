<?php
require_once 'admin/includes/db_connection.php';

echo "<h2>🔍 QR SESSIONS TABLE SCHEMA DEBUG</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto; background: #f8f9fa; padding: 20px; border-radius: 10px;'>";

try {
    echo "<h3>📊 QR Sessions Table Structure:</h3>";
    
    // Get table structure
    $schema_sql = "DESCRIBE qr_sessions";
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
    
    echo "<h3>📋 Sample QR Sessions Data:</h3>";
    
    // Get sample data
    $sample_sql = "SELECT * FROM qr_sessions ORDER BY created_at DESC LIMIT 5";
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
        echo "No QR sessions found.<br>";
    }
    
    echo "<h3>🔗 Tables Table Structure (for reference):</h3>";
    
    // Get tables table structure
    $tables_schema_sql = "DESCRIBE tables";
    $result = $conn->query($tables_schema_sql);
    
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
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>






