<?php
// Initialize the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION["counter_loggedin"]) || $_SESSION["counter_loggedin"] !== true){
    header("location: counter_login.php");
    exit;
}
?>