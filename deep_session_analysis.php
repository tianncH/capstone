<?php
require_once 'admin/includes/db_connection.php';

echo "<h1>🔍 DEEP SESSION ANALYSIS</h1>";
echo "<style>body { font-family: Arial; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } .debug { background: #f8f9fa; padding: 10px; border-left: 4px solid #007bff; margin: 10px 0; }</style>";

echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🎯 THOROUGH SESSION FLOW ANALYSIS</h2>";
echo "<p><strong>Let's analyze the complete QR session lifecycle step by step:</strong></p>";
echo "<ol>";
echo "<li>🔍 <strong>Current Database State</strong> - What sessions exist and their status</li>";
echo "<li>🔍 <strong>Customer Page Logic</strong> - How it detects session status</li>";
echo "<li>🔍 <strong>Counter Dashboard Logic</strong> - How it displays sessions</li>";
echo "<li>🔍 <strong>Confirmation Process</strong> - How confirmation updates the database</li>";
echo "<li>🔍 <strong>Real-time Updates</strong> - How status changes propagate</li>";
echo "</ol>";
echo "</div>";

// 1. ANALYZE CURRENT DATABASE STATE
echo "<div class='debug'>";
echo "<h2>📊 1. CURRENT DATABASE STATE</h2>";

// Check all QR sessions
$sessions_sql = "SELECT * FROM qr_sessions ORDER BY created_at DESC LIMIT 10";
$sessions_result = $conn->query($sessions_sql);

echo "<h3>🔍 All QR Sessions (Last 10):</h3>";
if ($sessions_result && $sessions_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
    echo "<tr><th>Session ID</th><th>Table ID</th><th>Status</th><th>Confirmed by Counter</th><th>Created At</th><th>Expires At</th><th>Session Token</th></tr>";
    
    while ($session = $sessions_result->fetch_assoc()) {
        $confirmed_status = $session['confirmed_by_counter'] ? '✅ TRUE' : '❌ FALSE';
        $status_color = $session['status'] === 'active' ? 'green' : 'red';
        $confirmed_color = $session['confirmed_by_counter'] ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>{$session['session_id']}</td>";
        echo "<td>{$session['table_id']}</td>";
        echo "<td style='color: {$status_color};'>{$session['status']}</td>";
        echo "<td style='color: {$confirmed_color};'>{$confirmed_status}</td>";
        echo "<td>{$session['created_at']}</td>";
        echo "<td>{$session['expires_at']}</td>";
        echo "<td style='font-family: monospace;'>{$session['session_token']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ No QR sessions found!</p>";
}

// Check QR session notifications
$notifications_sql = "SELECT * FROM qr_session_notifications ORDER BY created_at DESC LIMIT 10";
$notifications_result = $conn->query($notifications_sql);

echo "<h3>🔍 QR Session Notifications (Last 10):</h3>";
if ($notifications_result && $notifications_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
    echo "<tr><th>Notification ID</th><th>Session ID</th><th>Type</th><th>Status</th><th>Message</th><th>Created At</th></tr>";
    
    while ($notification = $notifications_result->fetch_assoc()) {
        $status_color = $notification['status'] === 'read' ? 'green' : 'orange';
        
        echo "<tr>";
        echo "<td>{$notification['notification_id']}</td>";
        echo "<td>{$notification['session_id']}</td>";
        echo "<td>{$notification['notification_type']}</td>";
        echo "<td style='color: {$status_color};'>{$notification['status']}</td>";
        echo "<td>{$notification['message']}</td>";
        echo "<td>{$notification['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ No QR session notifications found!</p>";
}

// Check tables with QR codes
$tables_sql = "SELECT table_id, table_number, qr_code, qr_code_url FROM tables WHERE is_active = 1 ORDER BY table_number";
$tables_result = $conn->query($tables_sql);

echo "<h3>🔍 Active Tables with QR Codes:</h3>";
if ($tables_result && $tables_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
    echo "<tr><th>Table ID</th><th>Table Number</th><th>QR Code</th><th>QR Code URL</th></tr>";
    
    while ($table = $tables_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$table['table_id']}</td>";
        echo "<td>{$table['table_number']}</td>";
        echo "<td>{$table['qr_code']}</td>";
        echo "<td style='word-break: break-all;'>{$table['qr_code_url']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ No active tables found!</p>";
}
echo "</div>";

// 2. ANALYZE CUSTOMER PAGE LOGIC
echo "<div class='debug'>";
echo "<h2>🔍 2. CUSTOMER PAGE LOGIC ANALYSIS</h2>";

// Read the customer page file and analyze the session detection logic
$customer_file = 'ordering/secure_qr_menu.php';
if (file_exists($customer_file)) {
    $customer_content = file_get_contents($customer_file);
    
    echo "<h3>🔍 Session Detection Query in Customer Page:</h3>";
    
    // Find the session query
    if (preg_match('/SELECT.*FROM qr_sessions.*WHERE.*table_id.*ORDER BY.*LIMIT 1/s', $customer_content, $matches)) {
        echo "<p class='info'><strong>Found session query:</strong></p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($matches[0]) . "</pre>";
    } else {
        echo "<p class='error'>❌ Could not find session query in customer page!</p>";
    }
    
    echo "<h3>🔍 JavaScript Button Logic:</h3>";
    
    // Find JavaScript that handles button enabling/disabling
    if (preg_match('/isConfirmed.*=.*true|false/s', $customer_content, $matches)) {
        echo "<p class='info'><strong>Found isConfirmed logic:</strong></p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($matches[0]) . "</pre>";
    } else {
        echo "<p class='error'>❌ Could not find isConfirmed logic!</p>";
    }
    
    // Find setInterval for real-time updates
    if (preg_match('/setInterval.*function.*\{.*\}/s', $customer_content, $matches)) {
        echo "<p class='info'><strong>Found setInterval for updates:</strong></p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($matches[0]) . "</pre>";
    } else {
        echo "<p class='error'>❌ Could not find setInterval for updates!</p>";
    }
    
} else {
    echo "<p class='error'>❌ Customer page file not found: {$customer_file}</p>";
}
echo "</div>";

// 3. ANALYZE COUNTER DASHBOARD LOGIC
echo "<div class='debug'>";
echo "<h2>🔍 3. COUNTER DASHBOARD LOGIC ANALYSIS</h2>";

$counter_file = 'counter/index.php';
if (file_exists($counter_file)) {
    $counter_content = file_get_contents($counter_file);
    
    echo "<h3>🔍 QR Session Queries in Counter Dashboard:</h3>";
    
    // Find queries for unconfirmed sessions
    if (preg_match('/SELECT.*FROM qr_sessions.*WHERE.*confirmed_by_counter.*FALSE/s', $counter_content, $matches)) {
        echo "<p class='info'><strong>Found unconfirmed sessions query:</strong></p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($matches[0]) . "</pre>";
    } else {
        echo "<p class='error'>❌ Could not find unconfirmed sessions query!</p>";
    }
    
    // Find queries for confirmed sessions
    if (preg_match('/SELECT.*FROM qr_sessions.*WHERE.*confirmed_by_counter.*TRUE/s', $counter_content, $matches)) {
        echo "<p class='info'><strong>Found confirmed sessions query:</strong></p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($matches[0]) . "</pre>";
    } else {
        echo "<p class='warning'>⚠️ Could not find confirmed sessions query - this explains why confirmed sessions disappear!</p>";
    }
    
    // Find confirmation handler
    if (preg_match('/confirm_qr_session.*POST/s', $counter_content, $matches)) {
        echo "<p class='info'><strong>Found confirmation handler:</strong></p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($matches[0]) . "</pre>";
    } else {
        echo "<p class='error'>❌ Could not find confirmation handler!</p>";
    }
    
} else {
    echo "<p class='error'>❌ Counter dashboard file not found: {$counter_file}</p>";
}
echo "</div>";

// 4. TEST SESSION STATUS FOR TABLE 1
echo "<div class='debug'>";
echo "<h2>🔍 4. TEST SESSION STATUS FOR TABLE 1</h2>";

$table_1_sql = "SELECT * FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1";
$table_1_result = $conn->query($table_1_sql);

if ($table_1_result && $table_1_result->num_rows > 0) {
    $table_1_session = $table_1_result->fetch_assoc();
    
    echo "<h3>🔍 Table 1 Current Session:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Value</th><th>Analysis</th></tr>";
    
    $fields = ['session_id', 'table_id', 'status', 'confirmed_by_counter', 'created_at', 'expires_at', 'session_token'];
    foreach ($fields as $field) {
        $value = $table_1_session[$field];
        $analysis = '';
        
        if ($field === 'confirmed_by_counter') {
            $analysis = $value ? '✅ Confirmed - Customer should be able to order' : '❌ Not confirmed - Customer should see pending message';
        } elseif ($field === 'status') {
            $analysis = $value === 'active' ? '✅ Active session' : '❌ Inactive session';
        } elseif ($field === 'expires_at') {
            $expires = strtotime($value);
            $now = time();
            $analysis = $expires > $now ? '✅ Not expired' : '❌ Expired';
        }
        
        echo "<tr>";
        echo "<td><strong>{$field}</strong></td>";
        echo "<td>{$value}</td>";
        echo "<td>{$analysis}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test what the customer page query would return
    echo "<h3>🔍 What Customer Page Query Returns:</h3>";
    $customer_query = "SELECT * FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1";
    $customer_result = $conn->query($customer_query);
    
    if ($customer_result && $customer_result->num_rows > 0) {
        $customer_session = $customer_result->fetch_assoc();
        echo "<p class='success'>✅ Customer page WOULD find this session</p>";
        echo "<p class='info'>Session ID: {$customer_session['session_id']}</p>";
        echo "<p class='info'>Confirmed: " . ($customer_session['confirmed_by_counter'] ? 'YES' : 'NO') . "</p>";
    } else {
        echo "<p class='error'>❌ Customer page would NOT find any session!</p>";
    }
    
} else {
    echo "<p class='error'>❌ No active session found for Table 1!</p>";
}
echo "</div>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🎯 ANALYSIS COMPLETE</h2>";
echo "<p><strong>Based on this deep analysis, we can now identify the exact issues and fix them systematically.</strong></p>";
echo "<p><strong>Next steps will be based on what we found in this analysis.</strong></p>";
echo "</div>";

$conn->close();
?>






