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
    header('Location: ../login.php');
    exit();
}

// Initialize variables
$success_message = '';
$error_message = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            // Add new user
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Validate inputs
            if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
                $error_message = "All fields are required.";
            } else {
                // Check if email already exists
                if (isEmailExists($conn, $email)) {
                    $error_message = "Email already exists. Please use a different email.";
                } else {
                    // Hash the password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    if (addUser($conn, $first_name, $last_name, $email, $hashed_password, $role, $is_active)) {
                        $success_message = "User added successfully.";
                    } else {
                        $error_message = "Failed to add user.";
                    }
                }
            }
            break;
            
        case 'edit':
            // Edit existing user
            $user_id = $_POST['user_id'] ?? '';
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Validate inputs
            if (empty($user_id) || empty($first_name) || empty($last_name) || empty($email) || empty($role)) {
                $error_message = "All fields except password are required.";
            } else {
                // Check if email already exists for a different user
                if (isEmailExistsExcept($conn, $email, $user_id)) {
                    $error_message = "Email already exists for another user. Please use a different email.";
                } else {
                    // Update user
                    if (updateUser($conn, $user_id, $first_name, $last_name, $email, $password, $role, $is_active)) {
                        $success_message = "User updated successfully.";
                    } else {
                        $error_message = "Failed to update user.";
                    }
                }
            }
            break;
            
        default:
            $error_message = "Invalid action.";
            break;
    }
}

// Store messages in session
if (!empty($success_message)) {
    $_SESSION['success_message'] = $success_message;
}

if (!empty($error_message)) {
    $_SESSION['error_message'] = $error_message;
}

// Redirect back to users page
header('Location: users.php');
exit();

/**
 * Function to add a new user
 */
function addUser($conn, $first_name, $last_name, $email, $password, $role, $is_active) {
    try {
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $password, $role, $is_active);
        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Function to update an existing user
 */
function updateUser($conn, $user_id, $first_name, $last_name, $email, $password, $role, $is_active) {
    try {
        // If password is provided, update it too
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ?, role = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("sssssii", $first_name, $last_name, $email, $hashed_password, $role, $is_active, $user_id);
        } else {
            // Otherwise, don't update the password
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssssii", $first_name, $last_name, $email, $role, $is_active, $user_id);
        }
        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Function to check if email exists
 */
function isEmailExists($conn, $email) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

/**
 * Function to check if email exists for a user other than the specified one
 */
function isEmailExistsExcept($conn, $email, $user_id) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}
?>