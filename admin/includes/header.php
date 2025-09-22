<?php
// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>

<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="dashboard.php">
        <img src="../assets/images/logo-white.png" alt="GreenQuest" height="32" class="me-2"> GreenQuest
    </a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <input class="form-control form-control-dark w-100" type="text" placeholder="Search" aria-label="Search">
    <div class="navbar-nav">
        <div class="nav-item text-nowrap">
            <a class="nav-link px-3" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    3
                    <span class="visually-hidden">unread notifications</span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                <li><h6 class="dropdown-header">Notifications</h6></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-plus text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <p class="mb-0">New user registration</p>
                            <small class="text-muted">2 minutes ago</small>
                        </div>
                    </div>
                </a></li>
                <li><a class="dropdown-item" href="#">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-alt text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <p class="mb-0">New content pending approval</p>
                            <small class="text-muted">10 minutes ago</small>
                        </div>
                    </div>
                </a></li>
                <li><a class="dropdown-item" href="#">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-danger"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <p class="mb-0">System alert: Database backup failed</p>
                            <small class="text-muted">1 hour ago</small>
                        </div>
                    </div>
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
            </ul>
        </div>
        <div class="nav-item text-nowrap">
            <a class="nav-link px-3" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?php echo getProfileImage($_SESSION['user_id']); ?>" alt="User" class="rounded-circle me-2" width="32" height="32">
                <?php echo $_SESSION['name']; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i> Profile</a></li>
                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</header>