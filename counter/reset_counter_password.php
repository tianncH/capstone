<?php
// Include database connection
require_once 'includes/db_connection.php';

// New password to set
$new_password = 'counter123';

// Hash the password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update the counter user's password (assuming counter_user_id = 1 or using username)
$sql = "UPDATE counter_users SET password = ? WHERE counter_user_id = 1";

if ($stmt = $conn->prepare($sql)) {
    // Bind the hashed password as a parameter
    $stmt->bind_param("s", $hashed_password);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;'>";
        echo "<h2 style='color: #28a745;'>Password Reset Successful</h2>";
        echo "<p>Password has been successfully reset to '<strong>counter123</strong>' for the counter user.</p>";
        echo "<p>Please delete this file from your server immediately for security reasons.</p>";
        echo "<p><a href='counter_login.php' style='display: inline-block; background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Go to Login Page</a></p>";
        echo "</div>";
    } else {
        echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; background-color: #f8d7da;'>";
        echo "<h2 style='color: #721c24;'>Error</h2>";
        echo "<p>Error updating password: " . $stmt->error . "</p>";
        echo "</div>";
    }
    
    // Close statement
    $stmt->close();
} else {
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; background-color: #f8d7da;'>";
    echo "<h2 style='color: #721c24;'>Error</h2>";
    echo "<p>Error preparing statement: " . $conn->error . "</p>";
    echo "</div>";
}

// Close connection
$conn->close();
?>