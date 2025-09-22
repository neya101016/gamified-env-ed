<?php
// Start session
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .error-container {
            padding: 60px 0;
            text-center: center;
        }
        .error-icon {
            font-size: 120px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #6c757d;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center error-container">
                <div class="error-icon">
                    <i class="fas fa-user-lock"></i>
                </div>
                <h1 class="mb-4">Unauthorized Access</h1>
                <p class="lead mb-4">You don't have permission to access this page.</p>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <p class="mb-5">Your account doesn't have the required role to access this section. Please contact an administrator if you believe this is an error.</p>
                    
                    <div class="d-flex justify-content-center mb-5">
                        <a href="<?php echo getRedirectUrl($_SESSION['role_name']); ?>" class="btn btn-primary me-3">
                            <i class="fas fa-home me-2"></i>Go to Dashboard
                        </a>
                        <button onclick="window.history.back()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Go Back
                        </button>
                    </div>
                <?php else: ?>
                    <p class="mb-5">You need to log in with an account that has the appropriate permissions.</p>
                    
                    <div class="d-flex justify-content-center mb-5">
                        <a href="login.php" class="btn btn-primary me-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-home me-2"></i>Go to Homepage
                        </a>
                    </div>
                <?php endif; ?>
                
                <p class="text-muted">
                    <small>If you believe this is a mistake, please contact the administrator.</small>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>