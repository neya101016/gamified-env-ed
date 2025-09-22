<?php
// Start session
session_start();

// Include database configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if(isLoggedIn()) {
    // Redirect to appropriate dashboard
    $redirectUrl = getRedirectUrl($_SESSION['role_name']);
    header("Location: " . $redirectUrl);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenQuest - Login</title>
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
                        <p class="mb-0">Login to Your Account</p>
                    </div>
                    <div class="card-body p-4">
                        <div id="loginAlert" class="alert alert-danger d-none"></div>
                        
                        <form id="loginForm">
                            <div class="form-floating mb-3">
                                <input class="form-control" id="email" name="email" type="email" placeholder="name@example.com" required />
                                <label for="email">Email address</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input class="form-control" id="password" name="password" type="password" placeholder="Password" required />
                                <label for="password">Password</label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" id="remember" name="remember" type="checkbox" />
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Login</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer bg-white py-3 text-center">
                        <div class="small mb-2"><a href="forgot-password.php">Forgot password?</a></div>
                        <div class="small">Don't have an account? <a href="register.php">Register now!</a></div>
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
    <script src="assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Handle login form submission
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                // Hide any previous alerts
                $('#loginAlert').addClass('d-none').text('');
                
                $.ajax({
                    url: 'api/index.php?action=login',
                    type: 'POST',
                    data: {
                        email: $('#email').val(),
                        password: $('#password').val(),
                        remember: $('#remember').is(':checked') ? 1 : 0
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'success') {
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Login Successful',
                                text: 'Welcome back, ' + response.user.name + '!',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(function() {
                                // Redirect to dashboard
                                window.location.href = response.redirect;
                            });
                        } else {
                            // Show error message in alert
                            $('#loginAlert').removeClass('d-none').text(response.message);
                            
                            // Also show in SweetAlert
                            Swal.fire({
                                icon: 'error',
                                title: 'Login Failed',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        // Show error message
                        $('#loginAlert').removeClass('d-none').text('An error occurred while trying to login. Please try again.');
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'An error occurred while trying to login. Please try again.'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>