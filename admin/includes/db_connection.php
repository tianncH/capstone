<?php
// Database connection settings
$servername = "localhost";
$username = "root";  // Change this to your database username if different
$password = "";      // Change this to your database password if any
$dbname = "restaurant_ordering_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone to match your local timezone (Philippines)
date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");
?>
