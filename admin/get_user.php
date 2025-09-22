<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if ID parameter is set
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Get user data
    $user = getUserById($conn, $user_id);
    
    if ($user) {
        // Return user data as JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        // User not found
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} else {
    // ID parameter not set
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID not provided']);
}
?>