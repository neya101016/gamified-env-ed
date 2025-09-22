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

// Check if email is in session
if (!isset($_SESSION['reset_email'])) {
    // Redirect to forgot password page if no email in session
    header("Location: forgot-password.php");
    exit;
}

$email = $_SESSION['reset_email'];
$message = '';
$message_type = '';
$remaining_seconds = 0;

// Initialize TOTP Auth
$totpAuth = new TOTPAuth();

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get user details
$query = "SELECT u.user_id, u.name, pr.secret_key, pr.expires_at, pr.otp_attempts 
          FROM users u 
          JOIN password_resets pr ON u.user_id = pr.user_id 
          WHERE u.email = :email AND u.is_active = 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    // No valid reset request found
    $_SESSION['error_message'] = "Your OTP request has expired or is invalid. Please try again.";
    unset($_SESSION['reset_email']);
    header("Location: forgot-password.php");
    exit;
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if OTP request has expired
if (strtotime($user['expires_at']) < time()) {
    // Reset request has expired
    $_SESSION['error_message'] = "Your OTP has expired. Please request a new one.";
    unset($_SESSION['reset_email']);
    header("Location: forgot-password.php");
    exit;
}

// Check if max attempts reached (5 attempts)
if ($user['otp_attempts'] >= 5) {
    // Too many attempts
    $_SESSION['error_message'] = "Too many incorrect attempts. Please request a new OTP.";
    unset($_SESSION['reset_email']);
    header("Location: forgot-password.php");
    exit;
}

// Calculate remaining seconds for the current OTP
$remaining_seconds = $totpAuth->getRemainingSeconds();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp']);
    
    // Validate OTP
    if (empty($otp)) {
        $message = 'Please enter the OTP.';
        $message_type = 'danger';
    } elseif (!preg_match('/^\d{6}$/', $otp)) {
        $message = 'OTP must be 6 digits.';
        $message_type = 'danger';
    } else {
        // Increment attempts counter
        $query = "UPDATE password_resets SET otp_attempts = otp_attempts + 1, last_attempt = NOW() 
                  WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user['user_id']);
        $stmt->execute();
        
        // Verify OTP
        if ($totpAuth->verifyOTP($user['secret_key'], $otp)) {
            // OTP is valid, store user_id in session for password reset
            $_SESSION['reset_user_id'] = $user['user_id'];
            
            // Redirect to reset password page
            header("Location: reset-password.php");
            exit;
        } else {
            $message = 'Invalid OTP. Please try again.';
            $message_type = 'danger';
            
            // Refresh user data to get updated attempts
            $query = "SELECT otp_attempts FROM password_resets WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user['user_id']);
            $stmt->execute();
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($updated['otp_attempts'] >= 5) {
                // Too many attempts
                $_SESSION['error_message'] = "Too many incorrect attempts. Please request a new OTP.";
                unset($_SESSION['reset_email']);
                header("Location: forgot-password.php");
                exit;
            }
        }
    }
}

// Handle resend OTP
if (isset($_GET['resend']) && $_GET['resend'] == 1) {
    // Generate a new secret key
    $secretKey = $totpAuth->generateSecretKey();
    
    // Update expiry time (10 minutes)
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Update the record
    $query = "UPDATE password_resets 
              SET secret_key = :secret_key, otp_attempts = 0, last_attempt = NULL, 
              expires_at = :expires_at, created_at = NOW() 
              WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user['user_id']);
    $stmt->bindParam(':secret_key', $secretKey);
    $stmt->bindParam(':expires_at', $expiry);
    
    if ($stmt->execute()) {
        // Generate new OTP
        $otp = $totpAuth->generateOTP($secretKey);
        
        // In a real application, send the OTP via email or SMS
        // For development purposes, we'll log it
        error_log("New OTP for {$email}: {$otp} (valid for 30 seconds)");
        
        $message = 'A new OTP has been sent to your email.';
        $message_type = 'success';
        
        // Refresh user data
        $query = "SELECT secret_key FROM password_resets WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Recalculate remaining seconds
        $remaining_seconds = $totpAuth->getRemainingSeconds();
    } else {
        $message = 'Failed to generate a new OTP. Please try again.';
        $message_type = 'danger';
    }
}

// Generate a new OTP if requested (for development/testing only)
if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    $otp = $totpAuth->generateOTP($user['secret_key']);
    $message = "Current OTP: {$otp} (valid for {$remaining_seconds} seconds)";
    $message_type = 'info';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenQuest - Verify OTP</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .otp-input {
            letter-spacing: 1em;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .countdown {
            font-size: 1.2rem;
            color: #0d6efd;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-lg-5 col-md-7">
                <div class="card shadow-lg border-0 rounded-lg mt-5">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3 class="my-2"><i class="fas fa-leaf me-2"></i>GreenQuest</h3>
                        <p class="mb-0">Verify One-Time Password</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <p class="mb-3">An OTP has been sent to <strong><?php echo htmlspecialchars($email); ?></strong>.</p>
                        <p class="mb-4">Please enter the 6-digit code below to verify your identity.</p>
                        
                        <div class="text-center mb-3">
                            <p class="countdown">OTP expires in: <span id="countdown"><?php echo $remaining_seconds; ?></span> seconds</p>
                        </div>
                        
                        <form method="post" action="">
                            <div class="form-floating mb-4">
                                <input class="form-control otp-input" id="otp" name="otp" type="text" placeholder="123456" 
                                       maxlength="6" pattern="\d{6}" inputmode="numeric" required />
                                <label for="otp">Enter 6-digit OTP</label>
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit" name="verify_otp" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check-circle me-2"></i>Verify OTP
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <p>Didn't receive the OTP? <a href="?resend=1">Resend OTP</a></p>
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
    
    <script>
        // OTP input formatting and validation
        document.getElementById('otp').addEventListener('input', function(e) {
            // Remove non-digits
            this.value = this.value.replace(/\D/g, '');
        });
        
        // Countdown timer
        let remainingSeconds = <?php echo $remaining_seconds; ?>;
        const countdownElement = document.getElementById('countdown');
        
        const countdownInterval = setInterval(() => {
            remainingSeconds--;
            countdownElement.textContent = remainingSeconds;
            
            if (remainingSeconds <= 0) {
                clearInterval(countdownInterval);
                // Add a message that OTP has expired and offer to resend
                const formContainer = document.querySelector('form').parentNode;
                const expiredAlert = document.createElement('div');
                expiredAlert.className = 'alert alert-warning mt-3';
                expiredAlert.innerHTML = '<strong>OTP has expired!</strong> <a href="?resend=1">Click here</a> to request a new one.';
                formContainer.insertBefore(expiredAlert, document.querySelector('form').nextSibling);
            }
        }, 1000);
    </script>
</body>
</html>