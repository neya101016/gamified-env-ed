<?php
// This script creates the password_resets table if it doesn't exist

// Include database configuration
require_once '../includes/config.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// SQL to create the password_resets table
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    secret_key VARCHAR(64) NOT NULL,
    otp_attempts INT DEFAULT 0,
    last_attempt TIMESTAMP NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

try {
    // Execute the query
    $db->exec($sql);
    echo "<h3>Password Resets Table Created Successfully</h3>";
    echo "<p>The password_resets table has been created successfully in the database.</p>";
    echo "<p><a href='../forgot-password.php'>Go to Forgot Password Page</a></p>";
} catch(PDOException $e) {
    echo "<h3>Error Creating Table</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>