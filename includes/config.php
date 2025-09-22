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

// Simple Captcha Configuration
class SimpleCaptcha {
    // Generate a random captcha code
    public static function generateCode($length = 6) {
        // Characters to use (no ambiguous characters like 0, O, 1, l, I)
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        
        // Store the code in session for verification
        $_SESSION['captcha_code'] = $code;
        
        return $code;
    }
    
    // Verify the captcha code
    public static function verify($userInput) {
        if (empty($userInput) || empty($_SESSION['captcha_code'])) {
            return false;
        }
        
        // Case-insensitive comparison
        $result = strtoupper($userInput) === $_SESSION['captcha_code'];
        
        // Clear the session captcha code after verification
        unset($_SESSION['captcha_code']);
        
        return $result;
    }
    
    // Generate text-based captcha HTML
    public static function generateCaptchaHTML($code) {
        // Split the code into individual characters
        $characters = str_split($code);
        
        // Start the HTML output
        $html = '<div class="text-captcha-container d-flex justify-content-center" style="background-color: #f0f0f0; padding: 10px; border-radius: 5px; letter-spacing: 3px;">';
        
        // Add each character with random styling
        foreach ($characters as $char) {
            // Generate random style variations
            $color = sprintf('#%02X%02X%02X', mt_rand(30, 150), mt_rand(30, 150), mt_rand(30, 150));
            $size = mt_rand(22, 28);
            $rotation = mt_rand(-15, 15);
            $margin = mt_rand(2, 6);
            
            // Create the styled character
            $html .= '<span style="color: ' . $color . '; 
                           font-size: ' . $size . 'px; 
                           font-weight: bold; 
                           display: inline-block; 
                           transform: rotate(' . $rotation . 'deg);
                           margin: 0 ' . $margin . 'px;
                           text-shadow: 1px 1px 1px #ccc;">' . $char . '</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
?>