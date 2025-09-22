<?php
// Database Connection Configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'ecoedu';
    private $username = 'root'; // Change to your MySQL username
    private $password = ''; // Change to your MySQL password
    private $conn;
    
    // Get database connection
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            // Don't display error details in production
            die("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }
    
    // Close database connection
    public function closeConnection() {
        $this->conn = null;
    }
}
?>