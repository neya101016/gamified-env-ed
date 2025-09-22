<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Forbidden - GreenQuest</title>
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
            text-align: center;
        }
        .error-code {
            font-size: 140px;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 20px;
            line-height: 1;
        }
        .error-icon {
            font-size: 100px;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-ban"></i>
            </div>
            <div class="error-code">403</div>
            <h1 class="mb-4">Access Forbidden</h1>
            <p class="lead mb-4">You don't have permission to access this page.</p>
            <p class="mb-5">This area might be restricted to certain users or roles.</p>
            
            <div class="d-flex justify-content-center mb-5">
                <a href="/ECO/" class="btn btn-primary me-3">
                    <i class="fas fa-home me-2"></i>Go to Homepage
                </a>
                <a href="/ECO/login.php" class="btn btn-success me-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
                <button onclick="window.history.back()" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Go Back
                </button>
            </div>
            
            <p class="text-muted">
                <small>If you believe this is a mistake, please contact the administrator.</small>
            </p>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>