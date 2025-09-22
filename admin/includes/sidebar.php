<?php
// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users me-2"></i> User Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'content.php') ? 'active' : ''; ?>" href="content.php">
                    <i class="fas fa-book me-2"></i> Content Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'analytics.php') ? 'active' : ''; ?>" href="analytics.php">
                    <i class="fas fa-chart-bar me-2"></i> Analytics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-file-alt me-2"></i> Reports
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>System</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'logs.php') ? 'active' : ''; ?>" href="logs.php">
                    <i class="fas fa-clipboard-list me-2"></i> System Logs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'backup.php') ? 'active' : ''; ?>" href="backup.php">
                    <i class="fas fa-database me-2"></i> Backup & Restore
                </a>
            </li>
        </ul>
    </div>
</nav>