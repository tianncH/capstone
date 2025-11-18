<?php
require_once 'includes/db_connection.php';

// Check if we already have tables
$check_sql = "SELECT COUNT(*) as count FROM tables";
$result = $conn->query($check_sql);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo "<h2>Creating table records...</h2>";
    
    // Create some default tables
    $tables = [
        ['1', 'https://example.com/qr/table1.png'],
        ['2', 'https://example.com/qr/table2.png'],
        ['3', 'https://example.com/qr/table3.png'],
        ['4', 'https://example.com/qr/table4.png'],
        ['5', 'https://example.com/qr/table5.png'],
        ['6', 'https://example.com/qr/table6.png'],
        ['7', 'https://example.com/qr/table7.png'],
        ['8', 'https://example.com/qr/table8.png']
    ];
    
    // Insert tables
    $insert_sql = "INSERT INTO tables (table_number, qr_code_url, is_active) VALUES (?, ?, 1)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ss", $table_number, $qr_code_url);
    
    foreach ($tables as $table) {
        $table_number = $table[0];
        $qr_code_url = $table[1];
        $stmt->execute();
        echo "Created table #$table_number<br>";
    }
    
    echo "<h3>Tables created successfully!</h3>";
    echo "<p>You can now use the ordering system.</p>";
    echo "<p><a href='index.php' class='btn btn-primary'>Go to Ordering Page</a></p>";
} else {
    echo "<h2>Tables already exist in the database.</h2>";
    echo "<p>No action needed.</p>";
    echo "<p><a href='index.php' class='btn btn-primary'>Go to Ordering Page</a></p>";
}

$conn->close();
?>