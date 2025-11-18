<?php
require_once 'admin/includes/db_connection.php';

echo "<h1>🐛 DEBUGGING COUNTER LOGIN</h1>";
echo "<style>body { font-family: Arial; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; }</style>";

echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🐛 COUNTER LOGIN DEBUG</h2>";
echo "<p><strong>Let's debug why the counter login isn't working!</strong></p>";
echo "</div>";

// Test 1: Check if counter_users table exists and has data
echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🗄️ TEST 1: DATABASE CHECK</h2>";

$check_table = "SHOW TABLES LIKE 'counter_users'";
$result = $conn->query($check_table);

if ($result->num_rows > 0) {
    echo "<p class='success'>✅ counter_users table exists!</p>";
    
    // Check for users
    $users_sql = "SELECT counter_id, username, password, is_active FROM counter_users";
    $users_result = $conn->query($users_sql);
    
    if ($users_result && $users_result->num_rows > 0) {
        echo "<p class='success'>✅ Found " . $users_result->num_rows . " counter user(s)</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Password Hash</th><th>Status</th></tr>";
        
        while ($user = $users_result->fetch_assoc()) {
            $status = $user['is_active'] ? 'Active' : 'Inactive';
            $status_color = $user['is_active'] ? 'green' : 'red';
            $password_preview = substr($user['password'], 0, 20) . '...';
            echo "<tr>";
            echo "<td>{$user['counter_id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$password_preview}</td>";
            echo "<td style='color: {$status_color};'>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No counter users found!</p>";
    }
} else {
    echo "<p class='error'>❌ counter_users table doesn't exist!</p>";
}
echo "</div>";

// Test 2: Test password verification
echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🔑 TEST 2: PASSWORD VERIFICATION</h2>";

$test_username = 'counter';
$test_password = 'counter123';

echo "<p><strong>Testing login for:</strong></p>";
echo "<ul>";
echo "<li>Username: {$test_username}</li>";
echo "<li>Password: {$test_password}</li>";
echo "</ul>";

// Get user from database
$user_sql = "SELECT counter_id, username, password, is_active FROM counter_users WHERE username = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("s", $test_username);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    echo "<p class='success'>✅ User '{$test_username}' found in database!</p>";
    echo "<p class='info'>User ID: {$user['counter_id']}</p>";
    echo "<p class='info'>User Status: " . ($user['is_active'] ? 'Active' : 'Inactive') . "</p>";
    
    // Test password verification
    if (password_verify($test_password, $user['password'])) {
        echo "<p class='success'>✅ Password verification successful!</p>";
    } else {
        echo "<p class='error'>❌ Password verification failed!</p>";
        echo "<p class='warning'>⚠️ The password hash might be incorrect. Let's regenerate it...</p>";
        
        // Regenerate password hash
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE counter_users SET password = ? WHERE counter_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_hash, $user['counter_id']);
        
        if ($update_stmt->execute()) {
            echo "<p class='success'>✅ Password hash regenerated successfully!</p>";
            echo "<p class='info'>New hash: " . substr($new_hash, 0, 20) . "...</p>";
        } else {
            echo "<p class='error'>❌ Failed to regenerate password hash!</p>";
        }
    }
} else {
    echo "<p class='error'>❌ User '{$test_username}' not found in database!</p>";
}
echo "</div>";

// Test 3: Test session functionality
echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🔐 TEST 3: SESSION FUNCTIONALITY</h2>";

session_start();

echo "<p><strong>Session Status:</strong></p>";
echo "<ul>";
echo "<li>Session ID: " . session_id() . "</li>";
echo "<li>Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</li>";
echo "<li>Session Name: " . session_name() . "</li>";
echo "</ul>";

// Test setting session variables
$_SESSION["test_var"] = "test_value";
if (isset($_SESSION["test_var"])) {
    echo "<p class='success'>✅ Session variables can be set and read!</p>";
} else {
    echo "<p class='error'>❌ Session variables cannot be set or read!</p>";
}

// Clean up test session
unset($_SESSION["test_var"]);
echo "</div>";

// Test 4: Simulate login process
echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🧪 TEST 4: SIMULATE LOGIN PROCESS</h2>";

$simulate_username = 'counter';
$simulate_password = 'counter123';

echo "<p><strong>Simulating login process...</strong></p>";

// Step 1: Check if user exists
$check_user_sql = "SELECT counter_id, username, password, is_active FROM counter_users WHERE username = ?";
$check_stmt = $conn->prepare($check_user_sql);
$check_stmt->bind_param("s", $simulate_username);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 1) {
    echo "<p class='success'>✅ Step 1: User exists in database</p>";
    
    $user_data = $check_result->fetch_assoc();
    
    // Step 2: Check if user is active
    if ($user_data['is_active']) {
        echo "<p class='success'>✅ Step 2: User is active</p>";
        
        // Step 3: Verify password
        if (password_verify($simulate_password, $user_data['password'])) {
            echo "<p class='success'>✅ Step 3: Password verification successful</p>";
            
            // Step 4: Set session variables
            $_SESSION["counter_loggedin"] = true;
            $_SESSION["counter_user_id"] = $user_data['counter_id'];
            $_SESSION["counter_username"] = $user_data['username'];
            
            echo "<p class='success'>✅ Step 4: Session variables set</p>";
            echo "<p class='info'>Session data:</p>";
            echo "<ul>";
            echo "<li>counter_loggedin: " . ($_SESSION["counter_loggedin"] ? 'true' : 'false') . "</li>";
            echo "<li>counter_user_id: " . $_SESSION["counter_user_id"] . "</li>";
            echo "<li>counter_username: " . $_SESSION["counter_username"] . "</li>";
            echo "</ul>";
            
            echo "<p class='success'><strong>🎉 LOGIN SIMULATION SUCCESSFUL!</strong></p>";
            
        } else {
            echo "<p class='error'>❌ Step 3: Password verification failed</p>";
        }
    } else {
        echo "<p class='error'>❌ Step 2: User is inactive</p>";
    }
} else {
    echo "<p class='error'>❌ Step 1: User not found or multiple users found</p>";
}
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🚀 NEXT STEPS</h2>";
echo "<p><strong>Based on the debug results above:</strong></p>";
echo "<ol>";
echo "<li>🔍 <strong>Check the results</strong> of each test above</li>";
echo "<li>🔧 <strong>Fix any issues</strong> found in the tests</li>";
echo "<li>🧪 <strong>Test the login again</strong> with the same credentials</li>";
echo "<li>📞 <strong>Report back</strong> what the debug shows</li>";
echo "</ol>";
echo "</div>";

$conn->close();
?>






