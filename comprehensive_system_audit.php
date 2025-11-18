<?php
require_once 'admin/includes/db_connection.php';

echo "=== COMPREHENSIVE SYSTEM AUDIT ===\n\n";

// 1. Check all table structures
echo "1. DATABASE TABLE STRUCTURES:\n";
$tables = ['qr_sessions', 'qr_orders', 'tables', 'menu_items', 'categories', 'orders', 'order_items', 'order_statuses'];

foreach ($tables as $table) {
    echo "\n--- {$table} ---\n";
    $result = $conn->query("SHOW COLUMNS FROM {$table}");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }
    } else {
        echo "❌ Table {$table} not found or error: " . $conn->error . "\n";
    }
}

echo "\n\n2. CHECKING ALL PHP FILES FOR COLUMN REFERENCE ISSUES:\n";

// 2. Check all PHP files for potential column reference issues
$php_files = [
    'counter/qr_order_details.php',
    'counter/index.php', 
    'ordering/secure_qr_menu.php',
    'admin/qr_session_management.php',
    'admin/qr_session_details.php',
    'admin/table_sessions.php',
    'admin/order_management.php',
    'admin/order_management_new.php'
];

foreach ($php_files as $file) {
    if (file_exists($file)) {
        echo "\n--- {$file} ---\n";
        $content = file_get_contents($file);
        
        // Check for common column reference issues
        $issues = [];
        
        // Check for qr_session_id references
        if (preg_match_all('/qr_session_id/', $content, $matches)) {
            $issues[] = "Found " . count($matches[0]) . " references to 'qr_session_id' (should be 'session_id')";
        }
        
        // Check for qr_order_id references
        if (preg_match_all('/qr_order_id/', $content, $matches)) {
            $issues[] = "Found " . count($matches[0]) . " references to 'qr_order_id' (should be 'order_id')";
        }
        
        // Check for status_id in qr_orders
        if (preg_match_all('/qo\.status_id/', $content, $matches)) {
            $issues[] = "Found " . count($matches[0]) . " references to 'qo.status_id' (should be 'qo.status')";
        }
        
        // Check for closed_at references
        if (preg_match_all('/closed_at/', $content, $matches)) {
            $issues[] = "Found " . count($matches[0]) . " references to 'closed_at' (check if column exists)";
        }
        
        if (empty($issues)) {
            echo "✅ No obvious column reference issues found\n";
        } else {
            foreach ($issues as $issue) {
                echo "⚠️ {$issue}\n";
            }
        }
    } else {
        echo "❌ File {$file} not found\n";
    }
}

echo "\n\n3. CHECKING SQL QUERIES FOR SYNTAX ISSUES:\n";

// 3. Test common SQL queries
$test_queries = [
    "SELECT * FROM qr_sessions WHERE session_id = 1" => "qr_sessions basic query",
    "SELECT * FROM qr_orders WHERE session_id = 1" => "qr_orders basic query", 
    "SELECT qs.*, t.table_number FROM qr_sessions qs JOIN tables t ON qs.table_id = t.table_id" => "qr_sessions with tables join",
    "SELECT qo.*, mi.name FROM qr_orders qo JOIN menu_items mi ON qo.menu_item_id = mi.item_id" => "qr_orders with menu_items join"
];

foreach ($test_queries as $query => $description) {
    echo "\n--- {$description} ---\n";
    $result = $conn->query($query);
    if ($result) {
        echo "✅ Query successful\n";
    } else {
        echo "❌ Query failed: " . $conn->error . "\n";
    }
}

echo "\n\n4. CHECKING FOREIGN KEY RELATIONSHIPS:\n";

// 4. Check foreign key relationships
$fk_checks = [
    "SELECT COUNT(*) as count FROM qr_sessions qs JOIN tables t ON qs.table_id = t.table_id" => "qr_sessions -> tables",
    "SELECT COUNT(*) as count FROM qr_orders qo JOIN qr_sessions qs ON qo.session_id = qs.session_id" => "qr_orders -> qr_sessions",
    "SELECT COUNT(*) as count FROM qr_orders qo JOIN menu_items mi ON qo.menu_item_id = mi.item_id" => "qr_orders -> menu_items"
];

foreach ($fk_checks as $query => $description) {
    echo "\n--- {$description} ---\n";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Relationship works, {$row['count']} matching records\n";
    } else {
        echo "❌ Relationship failed: " . $conn->error . "\n";
    }
}

echo "\n\n5. CHECKING CURRENT DATA INTEGRITY:\n";

// 5. Check data integrity
$integrity_checks = [
    "SELECT COUNT(*) as count FROM qr_sessions WHERE status = 'active'" => "Active QR sessions",
    "SELECT COUNT(*) as count FROM qr_sessions WHERE confirmed_by_counter = TRUE" => "Confirmed QR sessions",
    "SELECT COUNT(*) as count FROM qr_orders" => "Total QR orders",
    "SELECT COUNT(*) as count FROM tables WHERE is_active = 1" => "Active tables"
];

foreach ($integrity_checks as $query => $description) {
    echo "\n--- {$description} ---\n";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Count: {$row['count']}\n";
    } else {
        echo "❌ Query failed: " . $conn->error . "\n";
    }
}

$conn->close();
?>






