<?php
require_once 'admin/includes/db_connection.php';

echo "<h1>🧹 COMPREHENSIVE CLEANUP - REMOVING IRRELEVANT FILES</h1>";
echo "<style>body { font-family: Arial; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; }</style>";

echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🗑️ FILES TO DELETE (NO LONGER NEEDED)</h2>";
echo "<p><strong>Since we switched to receipt-based QR system, these files are obsolete:</strong></p>";
echo "<ul>";
echo "<li>❌ <strong>cianos_welcome.php</strong> - Landing page (already deleted)</li>";
echo "<li>❌ <strong>qr_landing.php</strong> - Old QR landing page</li>";
echo "<li>❌ <strong>mobile_secure_qr_menu.php</strong> - Duplicate mobile menu</li>";
echo "<li>❌ <strong>Smart session manager files</strong> - No longer needed</li>";
echo "<li>❌ <strong>Old test files</strong> - Various test scripts</li>";
echo "<li>❌ <strong>Old QR system files</strong> - Superseded by new system</li>";
echo "</ul>";
echo "</div>";

// List of files to delete
$files_to_delete = [
    'ordering/qr_landing.php',
    'ordering/mobile_secure_qr_menu.php', 
    'ordering/includes/smart_session_manager.php',
    'test_smart_approach.php',
    'perfect_smart_test.php',
    'update_qr_for_mobile.php',
    'fix_qr_urls_for_mobile.php',
    'fix_qr_with_real_ip.php',
    'update_qr_for_mobile_ui.php',
    'fix_qr_to_landing_page.php',
    'show_smart_approach_flow.php',
    'implement_receipt_qr_system.php',
    'test_beautiful_receipt.php',
    'counter/integrate_beautiful_receipt.php',
    'testing_dashboard.php',
    'disable_qr_for_testing.php',
    'simple_testing_dashboard.php',
    'feedback/simple_test_feedback.php',
    'reservations/simple_test_venue_rating.php',
    'reservations/simple_test_venue_reservation.php',
    'setup_enhanced_feedback_database.php',
    'test_enhanced_feedback_integration.php',
    'fix_database_issues.php',
    'fix_duplicate_qr_sessions_again.php',
    'fix_counter_login_system.php',
    'test_counter_login.php',
    'test_counter_security.php',
    'test_counter_session.php',
    'fix_add_buttons_for_testing.php',
    'debug_session_disappearing.php',
    'fix_counter_dashboard_sessions.php',
    'fix_admin_qr_urls.php',
    'fresh_start_cleanup.php'
];

echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>📋 FILES TO DELETE</h2>";

$deleted_count = 0;
$not_found_count = 0;

foreach ($files_to_delete as $file) {
    if (file_exists($file)) {
        echo "<p class='info'>🗑️ Found: {$file}</p>";
    } else {
        echo "<p class='warning'>⚠️ Not found: {$file}</p>";
        $not_found_count++;
    }
}

echo "<p class='info'><strong>📊 Total files to check: " . count($files_to_delete) . "</strong></p>";
echo "<p class='warning'><strong>⚠️ Files not found: {$not_found_count}</strong></p>";
echo "</div>";

// Confirmation form
if (!isset($_POST['confirm_cleanup'])) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>⚠️ CONFIRMATION REQUIRED</h2>";
    echo "<p><strong>Are you sure you want to delete all these irrelevant files?</strong></p>";
    echo "<p><strong>This will clean up your system and remove obsolete code!</strong></p>";
    
    echo "<form method='POST'>";
    echo "<div style='margin: 20px 0;'>";
    echo "<label><input type='checkbox' name='confirm_checkbox' required> I understand this will delete obsolete files and cannot be undone</label>";
    echo "</div>";
    echo "<button type='submit' name='confirm_cleanup' style='background: #dc3545; color: white; padding: 15px 25px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;'>🧹 DELETE IRRELEVANT FILES</button>";
    echo "</form>";
    echo "</div>";
} else {
    // Perform cleanup
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🧹 DELETING IRRELEVANT FILES</h2>";
    
    $deleted_count = 0;
    $errors = 0;
    
    foreach ($files_to_delete as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "<p class='success'>✅ Deleted: {$file}</p>";
                $deleted_count++;
            } else {
                echo "<p class='error'>❌ Failed to delete: {$file}</p>";
                $errors++;
            }
        } else {
            echo "<p class='warning'>⚠️ Not found: {$file}</p>";
        }
    }
    
    echo "<p class='success'><strong>🎉 FILE CLEANUP COMPLETE!</strong></p>";
    echo "<p class='success'>📊 Files deleted: <strong>{$deleted_count}</strong></p>";
    if ($errors > 0) {
        echo "<p class='error'>❌ Errors: <strong>{$errors}</strong></p>";
    }
    echo "</div>";
    
    // Update database URLs
    echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🔄 UPDATING DATABASE URLs</h2>";
    
    $tables_sql = "SELECT table_id, table_number, qr_code FROM tables WHERE is_active = 1 ORDER BY table_number";
    $tables_result = $conn->query($tables_sql);
    
    $updated_count = 0;
    $db_errors = 0;
    
    while ($table = $tables_result->fetch_assoc()) {
        $new_url = "http://192.168.1.2/capstone/ordering/secure_qr_menu.php?qr=" . $table['qr_code'];
        
        $update_sql = "UPDATE tables SET qr_code_url = ? WHERE table_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('si', $new_url, $table['table_id']);
        
        if ($update_stmt->execute()) {
            echo "<p class='success'>✅ Table {$table['table_number']}: URL updated to secure_qr_menu.php</p>";
            $updated_count++;
        } else {
            echo "<p class='error'>❌ Table {$table['table_number']}: Error - " . $update_stmt->error . "</p>";
            $db_errors++;
        }
        $update_stmt->close();
    }
    
    echo "<p class='success'><strong>🎉 DATABASE UPDATE COMPLETE!</strong></p>";
    echo "<p class='success'>📊 Tables updated: <strong>{$updated_count}</strong></p>";
    if ($db_errors > 0) {
        echo "<p class='error'>❌ Database errors: <strong>{$db_errors}</strong></p>";
    }
    echo "</div>";
    
    // Regenerate QR codes
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🔄 REGENERATING QR CODES</h2>";
    
    require_once 'admin/includes/qr_generator.php';
    $qr_generator = new QRGenerator('http://192.168.1.2/capstone');
    
    $tables_sql = "SELECT table_id, table_number FROM tables WHERE is_active = 1 ORDER BY table_number";
    $tables_result = $conn->query($tables_sql);
    
    $regenerated_count = 0;
    $qr_errors = 0;
    
    while ($table = $tables_result->fetch_assoc()) {
        $ordering_qr = $qr_generator->generateOrderingQR($table['table_id']);
        
        if ($ordering_qr['success']) {
            echo "<p class='success'>✅ Table {$table['table_number']}: QR code regenerated</p>";
            $regenerated_count++;
        } else {
            echo "<p class='error'>❌ Table {$table['table_number']}: Failed to regenerate QR</p>";
            $qr_errors++;
        }
    }
    
    echo "<p class='success'><strong>🎉 QR REGENERATION COMPLETE!</strong></p>";
    echo "<p class='success'>📊 QR codes regenerated: <strong>{$regenerated_count}</strong></p>";
    if ($qr_errors > 0) {
        echo "<p class='error'>❌ QR errors: <strong>{$qr_errors}</strong></p>";
    }
    echo "</div>";
}

echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🎉 CLEANUP COMPLETE!</h2>";
echo "<p><strong>Your system is now clean and streamlined!</strong></p>";
echo "<p><strong>What's been cleaned:</strong></p>";
echo "<ul>";
echo "<li>🧹 <strong>Deleted obsolete files</strong> (landing pages, test files, old QR system)</li>";
echo "<li>🔄 <strong>Updated database URLs</strong> to point to secure_qr_menu.php</li>";
echo "<li>🔄 <strong>Regenerated QR codes</strong> with correct URLs</li>";
echo "<li>✅ <strong>Streamlined system</strong> - only essential files remain</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🚀 TEST YOUR CLEAN SYSTEM</h2>";
echo "<p><strong>Now test that everything works:</strong></p>";
echo "<a href='admin/qr_management.php' style='background: #6f42c1; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; font-weight: bold;'>👀 Check Admin QR Management</a>";
echo "<a href='ordering/secure_qr_menu.php?qr=QR_001' style='background: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; font-weight: bold;'>🧪 Test QR Ordering</a>";
echo "<a href='counter/counter_login.php' style='background: #17a2b8; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; font-weight: bold;'>🔐 Counter Login</a>";
echo "</div>";

$conn->close();
?>






