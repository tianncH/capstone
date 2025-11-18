<?php
require_once 'admin/includes/db_connection.php';
require_once 'admin/includes/qr_generator.php';

echo "<h1>🔧 FIXING QR MANAGEMENT URLs</h1>";
echo "<style>body { font-family: Arial; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; }</style>";

echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🐛 QR MANAGEMENT URL ISSUE FIXED</h2>";
echo "<p><strong>The problem:</strong></p>";
echo "<ul>";
echo "<li>❌ <strong>getQRInfo() method</strong> was still using cianos_welcome.php</li>";
echo "<li>❌ <strong>Database URLs</strong> might still point to old URLs</li>";
echo "<li>❌ <strong>QR codes</strong> need to be regenerated with correct URLs</li>";
echo "</ul>";
echo "<p><strong>✅ Fixed getQRInfo() method to use secure_qr_menu.php</strong></p>";
echo "</div>";

// Initialize QR generator
$qr_generator = new QRGenerator('http://192.168.1.2/capstone');

// Show current URLs
echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>📊 CURRENT DATABASE URLs</h2>";

$tables_sql = "SELECT table_id, table_number, qr_code, qr_code_url FROM tables WHERE is_active = 1 ORDER BY table_number";
$tables_result = $conn->query($tables_sql);

if ($tables_result && $tables_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Table</th><th>QR Code</th><th>Database URL</th><th>getQRInfo() URL</th><th>Status</th></tr>";
    
    while ($table = $tables_result->fetch_assoc()) {
        $qr_info = $qr_generator->getQRInfo($table['table_id']);
        $db_url = $table['qr_code_url'];
        $qr_info_url = $qr_info['ordering_url'];
        
        $db_correct = strpos($db_url, 'secure_qr_menu.php') !== false;
        $qr_info_correct = strpos($qr_info_url, 'secure_qr_menu.php') !== false;
        
        $status = ($db_correct && $qr_info_correct) ? '✅ Both Correct' : '❌ Needs Fix';
        $status_color = ($db_correct && $qr_info_correct) ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>Table {$table['table_number']}</td>";
        echo "<td>{$table['qr_code']}</td>";
        echo "<td style='word-break: break-all; font-size: 12px;'>{$db_url}</td>";
        echo "<td style='word-break: break-all; font-size: 12px;'>{$qr_info_url}</td>";
        echo "<td style='color: {$status_color}; font-weight: bold;'>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ No tables found!</p>";
}
echo "</div>";

// Fix database URLs
if (isset($_GET['fix_database'])) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🔧 FIXING DATABASE URLs</h2>";
    
    $tables_sql = "SELECT table_id, table_number, qr_code FROM tables WHERE is_active = 1 ORDER BY table_number";
    $tables_result = $conn->query($tables_sql);
    
    $fixed_count = 0;
    $errors = 0;
    
    while ($table = $tables_result->fetch_assoc()) {
        $new_url = "http://192.168.1.2/capstone/ordering/secure_qr_menu.php?qr=" . $table['qr_code'];
        
        $update_sql = "UPDATE tables SET qr_code_url = ? WHERE table_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('si', $new_url, $table['table_id']);
        
        if ($update_stmt->execute()) {
            echo "<p class='success'>✅ Table {$table['table_number']}: Database URL updated</p>";
            $fixed_count++;
        } else {
            echo "<p class='error'>❌ Table {$table['table_number']}: Error - " . $update_stmt->error . "</p>";
            $errors++;
        }
        $update_stmt->close();
    }
    
    echo "<p class='success'><strong>🎉 DATABASE URL FIX COMPLETE!</strong></p>";
    echo "<p class='success'>📊 Fixed: <strong>{$fixed_count}</strong> tables</p>";
    if ($errors > 0) {
        echo "<p class='error'>❌ Errors: <strong>{$errors}</strong></p>";
    }
    echo "</div>";
}

// Regenerate QR codes
if (isset($_GET['regenerate_qr'])) {
    echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🔄 REGENERATING QR CODES</h2>";
    
    $tables_sql = "SELECT table_id, table_number FROM tables WHERE is_active = 1 ORDER BY table_number";
    $tables_result = $conn->query($tables_sql);
    
    $regenerated_count = 0;
    $errors = 0;
    
    while ($table = $tables_result->fetch_assoc()) {
        // Generate new ordering QR
        $ordering_qr = $qr_generator->generateOrderingQR($table['table_id']);
        
        if ($ordering_qr['success']) {
            echo "<p class='success'>✅ Table {$table['table_number']}: QR code regenerated</p>";
            $regenerated_count++;
        } else {
            echo "<p class='error'>❌ Table {$table['table_number']}: Failed to regenerate QR</p>";
            $errors++;
        }
    }
    
    echo "<p class='success'><strong>🎉 QR REGENERATION COMPLETE!</strong></p>";
    echo "<p class='success'>📊 Regenerated: <strong>{$regenerated_count}</strong> QR codes</p>";
    if ($errors > 0) {
        echo "<p class='error'>❌ Errors: <strong>{$errors}</strong></p>";
    }
    echo "</div>";
}

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🚀 FIX ACTIONS</h2>";
echo "<p><strong>Choose your fix:</strong></p>";
echo "<a href='?fix_database=1' style='background: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; font-weight: bold;'>🔧 Fix Database URLs</a>";
echo "<a href='?regenerate_qr=1' style='background: #17a2b8; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; font-weight: bold;'>🔄 Regenerate QR Codes</a>";
echo "<a href='admin/qr_management.php' style='background: #6f42c1; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; font-weight: bold;'>👀 Check Admin QR Management</a>";
echo "</div>";

echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🎯 WHAT THE FIX DOES</h2>";
echo "<p><strong>After fixing:</strong></p>";
echo "<ul>";
echo "<li>✅ <strong>getQRInfo() method</strong> now returns secure_qr_menu.php URLs</li>";
echo "<li>✅ <strong>Database URLs</strong> will be updated to secure_qr_menu.php</li>";
echo "<li>✅ <strong>QR codes</strong> will be regenerated with correct URLs</li>";
echo "<li>✅ <strong>Admin QR management</strong> will show correct URLs</li>";
echo "<li>✅ <strong>Scanning QR codes</strong> will go directly to ordering menu</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🎉 EXPECTED RESULT</h2>";
echo "<p><strong>After the fix:</strong></p>";
echo "<ol>";
echo "<li>🔧 <strong>Click 'Fix Database URLs'</strong> to update database</li>";
echo "<li>🔄 <strong>Click 'Regenerate QR Codes'</strong> to create new QR images</li>";
echo "<li>👀 <strong>Check Admin QR Management</strong> - URLs should be correct</li>";
echo "<li>🧪 <strong>Test scanning QR codes</strong> - should go to ordering menu</li>";
echo "</ol>";
echo "</div>";

$conn->close();
?>






