<?php
// Common header file for GreenQuest application
// Include at the top of all pages for consistent navigation and styling

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determine active page from the current filename
$current_page = basename($_SERVER['PHP_SELF']);

// Get user role if logged in
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Set default page title if not already set
if (!isset($pageTitle)) {
    $pageTitle = "GreenQuest";
}

// Function to check if a page is active for nav highlighting
function isActive($page) {
    global $current_page;
    return ($current_page == $page) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/style.css">
    <?php if (isset($extraCSS)): ?>
        <?php echo $extraCSS; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo getBaseUrl(); ?>index.php">
                <i class="fas fa-leaf me-2"></i>GreenQuest
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (!isLoggedIn()): ?>
                        <!-- Public Navigation -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('index.php'); ?>" href="<?php echo getBaseUrl(); ?>index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('about.php'); ?>" href="<?php echo getBaseUrl(); ?>about.php">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('leaderboard.php'); ?>" href="<?php echo getBaseUrl(); ?>leaderboard.php">Leaderboard</a>
                        </li>
                    <?php elseif ($user_role == 'student'): ?>
                        <!-- Student Navigation -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="<?php echo getBaseUrl(); ?>student/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('lessons.php'); ?>" href="<?php echo getBaseUrl(); ?>student/lessons.php">Lessons</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('badges.php'); ?>" href="<?php echo getBaseUrl(); ?>student/badges.php">Badges</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('leaderboard.php'); ?>" href="<?php echo getBaseUrl(); ?>student/leaderboard.php">Leaderboard</a>
                        </li>
                    <?php elseif ($user_role == 'teacher'): ?>
                        <!-- Teacher Navigation -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="<?php echo getBaseUrl(); ?>teacher/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('lessons.php'); ?>" href="<?php echo getBaseUrl(); ?>teacher/lessons.php">Lessons</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('students.php'); ?>" href="<?php echo getBaseUrl(); ?>teacher/students.php">Students</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('leaderboard.php'); ?>" href="<?php echo getBaseUrl(); ?>teacher/leaderboard.php">Leaderboard</a>
                        </li>
                    <?php elseif ($user_role == 'ngo'): ?>
                        <!-- NGO Navigation -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="<?php echo getBaseUrl(); ?>ngo/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('challenges.php'); ?>" href="<?php echo getBaseUrl(); ?>ngo/challenges.php">Challenges</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('verifications.php'); ?>" href="<?php echo getBaseUrl(); ?>ngo/verifications.php">Verifications</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('impact.php'); ?>" href="<?php echo getBaseUrl(); ?>ngo/impact.php">Impact</a>
                        </li>
                    <?php elseif ($user_role == 'admin'): ?>
                        <!-- Admin Navigation -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="<?php echo getBaseUrl(); ?>admin/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('users.php'); ?>" href="<?php echo getBaseUrl(); ?>admin/users.php">Users</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('content.php'); ?>" href="<?php echo getBaseUrl(); ?>admin/content.php">Content</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('badges.php'); ?>" href="<?php echo getBaseUrl(); ?>admin/badges.php">Badges</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('analytics.php'); ?>" href="<?php echo getBaseUrl(); ?>admin/analytics.php">Analytics</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <div class="d-flex">
                    <?php if (isLoggedIn()): ?>
                        <!-- User Dropdown Menu -->
                        <div class="dropdown">
                            <a class="btn btn-outline-light dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
                                <?php if (isset($_SESSION['eco_points'])): ?>
                                    <span class="badge bg-success ms-2"><?php echo number_format($_SESSION['eco_points']); ?> Points</span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo getBaseUrl() . getUserRolePath() . 'profile.php'; ?>">
                                    <i class="fas fa-id-card me-2"></i>Profile
                                </a></li>
                                <?php if ($user_role == 'student'): ?>
                                    <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>points_history.php">
                                        <i class="fas fa-history me-2"></i>Points History
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Login/Register Buttons -->
                        <a href="<?php echo getBaseUrl(); ?>login.php" class="btn btn-outline-light me-2">Login</a>
                        <a href="<?php echo getBaseUrl(); ?>register.php" class="btn btn-light">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <!-- Main Content Container -->
    <div class="container py-4">
        <!-- Page content will be inserted here -->