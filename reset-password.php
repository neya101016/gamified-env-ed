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
$user_verified = false;
$user_id = null;

// Check if user has been verified via OTP
if (isset($_SESSION['reset_user_id'])) {
    $user_id = $_SESSION['reset_user_id'];
    $user_verified = true;
    
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify that the reset request still exists and is valid
    $query = "SELECT pr.*, u.email 
              FROM password_resets pr
              JOIN users u ON pr.user_id = u.user_id
              WHERE pr.user_id = :user_id AND pr.expires_at > NOW()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $user_verified = false;
        unset($_SESSION['reset_user_id']);
        $message = 'Your verification has expired. Please restart the password reset process.';
        $message_type = 'danger';
    }
} else {
    $message = 'Please verify your identity with a one-time password first.';
    $message_type = 'warning';
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $user_verified) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate passwords
    if (empty($password)) {
        $message = 'Please enter a new password.';
        $message_type = 'danger';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $message_type = 'danger';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'danger';
    } else {
        // Update user password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        $query = "UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            // Delete the used reset request
            $query = "DELETE FROM password_resets WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Clear session variables
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_email']);
            
            $message = 'Your password has been reset successfully. You can now log in with your new password.';
            $message_type = 'success';
            $user_verified = false; // Hide the form after successful reset
        } else {
            $message = 'An error occurred. Please try again later.';
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenQuest - Reset Password</title>
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
                        <p class="mb-0">Reset Password</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($user_verified): ?>
                            <p class="mb-3">Please enter your new password below.</p>
                            
                            <form method="post" action="" id="resetPasswordForm">
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="password" name="password" type="password" placeholder="New password" required />
                                    <label for="password">New Password</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="confirm_password" name="confirm_password" type="password" placeholder="Confirm new password" required />
                                    <label for="confirm_password">Confirm New Password</label>
                                </div>
                                <div class="mb-3">
                                    <div class="password-strength-meter">
                                        <label>Password Strength:</label>
                                        <div class="progress">
                                            <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small id="password-strength-text" class="form-text">Password must be at least 8 characters long</small>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="reset_password" class="btn btn-primary btn-lg">
                                        <i class="fas fa-key me-2"></i>Reset Password
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-lock fa-4x text-muted mb-3"></i>
                                <p>Please complete the verification process to reset your password.</p>
                                <a href="forgot-password.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Password Reset
                                </a>
                            </div>
                        <?php endif; ?>
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
        $(document).ready(function() {
            // Password strength validation
            $('#password').on('input', function() {
                var password = $(this).val();
                var strength = 0;
                var feedback = '';
                
                // Update strength based on criteria
                if (password.length >= 8) {
                    strength += 20;
                    feedback = 'Minimum length met';
                } else {
                    feedback = 'Password must be at least 8 characters long';
                }
                
                // Check for uppercase
                if (password.match(/[A-Z]/)) {
                    strength += 20;
                }
                
                // Check for lowercase
                if (password.match(/[a-z]/)) {
                    strength += 20;
                }
                
                // Check for numbers
                if (password.match(/[0-9]/)) {
                    strength += 20;
                }
                
                // Check for special characters
                if (password.match(/[^A-Za-z0-9]/)) {
                    strength += 20;
                }
                
                // Update strength meter
                var $strengthBar = $('#password-strength-bar');
                var $strengthText = $('#password-strength-text');
                
                $strengthBar.css('width', strength + '%');
                
                // Update bar color and text based on strength
                if (strength <= 20) {
                    $strengthBar.removeClass('bg-warning bg-success').addClass('bg-danger');
                    $strengthText.text(feedback || 'Very weak');
                } else if (strength <= 60) {
                    $strengthBar.removeClass('bg-danger bg-success').addClass('bg-warning');
                    $strengthText.text(feedback || 'Moderate');
                } else {
                    $strengthBar.removeClass('bg-danger bg-warning').addClass('bg-success');
                    $strengthText.text(feedback || 'Strong password');
                }
            });
            
            // Confirm password validation
            $('#confirm_password').on('input', function() {
                var password = $('#password').val();
                var confirm_password = $(this).val();
                
                if (password === confirm_password) {
                    $(this).removeClass('border-danger').addClass('border-success');
                } else {
                    $(this).removeClass('border-success').addClass('border-danger');
                }
            });
            
            // Form submission validation
            $('#resetPasswordForm').on('submit', function(e) {
                var password = $('#password').val();
                var confirm_password = $('#confirm_password').val();
                
                if (password.length < 8) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Password Too Weak',
                        text: 'Your password must be at least 8 characters long.'
                    });
                } else if (password !== confirm_password) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Passwords Do Not Match',
                        text: 'Please make sure your passwords match.'
                    });
                }
            });
        });
    </script>
</body>
</html>