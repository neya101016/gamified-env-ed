<?php
// Start session
session_start();

// Check if session exists and destroy it
if(isset($_SESSION['user_id'])) {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}

// Redirect to login page
header("Location: login.php");
exit;
?>