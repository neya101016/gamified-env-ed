<?php
// Start session
session_start();

// Include database configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/totp_auth.php';

// Check if user is already logged in
if(isLoggedIn()) {
    // Redirect to appropriate dashboard
    $redirectUrl = getRedirectUrl($_SESSION['role_name']);
    header("Location: " . $redirectUrl);
    exit;
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_request'])) {
    $email = trim($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'danger';
    } else {
        // Connect to database
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if email exists
        $query = "SELECT user_id, name FROM users WHERE email = :email AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Initialize TOTP Auth
            $totpAuth = new TOTPAuth();
            
            // Generate a secure secret key for TOTP
            $secretKey = $totpAuth->generateSecretKey();
            
            // Set expiry time (10 minutes)
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Check if a reset request already exists for this user
            $query = "SELECT * FROM password_resets WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user['user_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Update existing entry
                $query = "UPDATE password_resets SET secret_key = :secret_key, otp_attempts = 0, 
                          last_attempt = NULL, expires_at = :expires_at, created_at = NOW() 
                          WHERE user_id = :user_id";
            } else {
                // Create new entry
                $query = "INSERT INTO password_resets (user_id, secret_key, expires_at) 
                          VALUES (:user_id, :secret_key, :expires_at)";
            }
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user['user_id']);
            $stmt->bindParam(':secret_key', $secretKey);
            $stmt->bindParam(':expires_at', $expiry);
            
            if ($stmt->execute()) {
                // Generate OTP code
                $otp = $totpAuth->generateOTP($secretKey);
                
                // In a real application, send the OTP via email or SMS
                // For development purposes, we'll log it
                error_log("OTP for {$email}: {$otp} (valid for 30 seconds)");
                
                // Store email in session for the verification page
                $_SESSION['reset_email'] = $email;
                
                // Redirect to OTP verification page
                header("Location: verify-otp.php");
                exit;
            } else {
                $message = 'An error occurred. Please try again later.';
                $message_type = 'danger';
            }
        } else {
            // Don't reveal if email exists for security
            $message = 'If your email exists in our system, you will receive a one-time password shortly.';
            $message_type = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenQuest - Forgot Password</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-lg-5 col-md-7">
                <div class="card shadow-lg border-0 rounded-lg mt-5">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3 class="my-2"><i class="fas fa-leaf me-2"></i>GreenQuest</h3>
                        <p class="mb-0">Forgot Password</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <p class="mb-3">Enter your email address below and we'll send you a one-time password (OTP) to reset your password.</p>
                        
                        <form method="post" action="">
                            <div class="form-floating mb-3">
                                <input class="form-control" id="email" name="email" type="email" placeholder="name@example.com" required />
                                <label for="email">Email address</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="reset_request" class="btn btn-primary btn-lg">
                                    <i class="fas fa-key me-2"></i>Send OTP
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle me-2"></i>How it works:</h5>
                                <p class="small mb-0">
                                    1. Enter your email address<br>
                                    2. You'll receive a one-time password (OTP)<br>
                                    3. Enter the OTP on the next screen<br>
                                    4. Create a new password
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3 text-center">
                        <div class="small">
                            <a href="login.php">Back to Login</a>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
</body>
</html>