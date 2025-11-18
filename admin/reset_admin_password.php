<?php
// Include database connection
require_once 'includes/db_connection.php';

// New password to set
$new_password = 'admin123';

// Hash the password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update the admin user's password (assuming admin_id = 1)
$sql = "UPDATE admin_users SET password = ? WHERE admin_id = 1";

if ($stmt = $conn->prepare($sql)) {
    // Bind the hashed password as a parameter
    $stmt->bind_param("s", $hashed_password);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo "Password successfully reset to 'admin123' for the admin user.";
    } else {
        echo "Error updating password: " . $stmt->error;
    }
    
    // Close statement
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error;
}

// Close connection
$conn->close();
?>