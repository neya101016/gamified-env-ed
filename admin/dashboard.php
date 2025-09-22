<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get dashboard data
$total_users = getTotalUsers($conn);
$pending_approvals = getPendingApprovals($conn);
$recent_activities = getRecentActivities($conn, 5);
$system_stats = getSystemStats($conn);

// Page title
$page_title = "Admin Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenQuest - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <!-- Admin Header -->
    <?php include_once 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Welcome, <?php echo $_SESSION['name']; ?>!</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                            <i class="fas fa-calendar"></i> This week
                        </button>
                    </div>
                </div>

                <!-- Dashboard Overview Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_users); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Active Challenges</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($system_stats['active_challenges']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Completed Lessons</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($system_stats['completed_lessons']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Approvals</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($pending_approvals['total']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Required Section -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actions Required</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php if ($pending_approvals['challenge_proofs'] > 0): ?>
                                        <a href="content.php?type=verifications" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-clipboard-check text-primary me-2"></i> Challenge proofs waiting for verification
                                            </div>
                                            <span class="badge bg-primary rounded-pill"><?php echo $pending_approvals['challenge_proofs']; ?></span>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($pending_approvals['user_registrations'] > 0): ?>
                                        <a href="users.php?filter=pending" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-user-plus text-warning me-2"></i> New user registrations waiting for approval
                                            </div>
                                            <span class="badge bg-warning rounded-pill"><?php echo $pending_approvals['user_registrations']; ?></span>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($pending_approvals['content_submissions'] > 0): ?>
                                        <a href="content.php?type=submissions" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-file-alt text-info me-2"></i> Content submissions waiting for review
                                            </div>
                                            <span class="badge bg-info rounded-pill"><?php echo $pending_approvals['content_submissions']; ?></span>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($system_stats['reported_content'] > 0): ?>
                                        <a href="reports.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-exclamation-triangle text-danger me-2"></i> Content reported by users
                                            </div>
                                            <span class="badge bg-danger rounded-pill"><?php echo $system_stats['reported_content']; ?></span>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (empty($pending_approvals['challenge_proofs']) && empty($pending_approvals['user_registrations']) && empty($pending_approvals['content_submissions']) && empty($system_stats['reported_content'])): ?>
                                        <div class="list-group-item">
                                            <div class="text-center">
                                                <i class="fas fa-check-circle text-success fa-2x mb-3"></i>
                                                <p class="mb-0">No pending actions at the moment!</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Activities -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Recent Activities</h5>
                                <a href="activities.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php if (!empty($recent_activities)): ?>
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-marker bg-<?php echo getActivityIconClass($activity['activity_type']); ?>">
                                                    <i class="fas fa-<?php echo getActivityIcon($activity['activity_type']); ?>"></i>
                                                </div>
                                                <div class="timeline-content">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($activity['user_name']); ?></h6>
                                                        <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                                                    </div>
                                                    <p class="mb-0"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center p-3">
                                            <p class="mb-0">No recent activities found.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">System Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label d-flex justify-content-between">
                                        <span>Database</span>
                                        <span class="text-success">Healthy</span>
                                    </label>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">25%</div>
                                    </div>
                                    <small class="text-muted">Using 25% of allocated space</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label d-flex justify-content-between">
                                        <span>File Storage</span>
                                        <span class="text-warning">Warning</span>
                                    </label>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 75%;" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">75%</div>
                                    </div>
                                    <small class="text-muted">Using 75% of allocated space</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label d-flex justify-content-between">
                                        <span>System Load</span>
                                        <span class="text-success">Normal</span>
                                    </label>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 30%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100">30%</div>
                                    </div>
                                    <small class="text-muted">Current load: 30%</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label d-flex justify-content-between">
                                        <span>Last Backup</span>
                                        <span class="text-success">Recent</span>
                                    </label>
                                    <p class="mb-0">2023-07-20 03:45 AM</p>
                                    <small class="text-muted">Next scheduled: 2023-07-21 03:45 AM</small>
                                </div>
                                
                                <div class="d-grid gap-2 mt-4">
                                    <a href="backup.php" class="btn btn-primary">Run Manual Backup</a>
                                    <a href="settings.php" class="btn btn-outline-secondary">System Settings</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <?php include_once '../includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>