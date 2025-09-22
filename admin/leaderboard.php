<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/leaderboard.php';

// Check if user is logged in and is an admin
requireLogin();
requireRole('admin');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create leaderboard object
$leaderboard = new Leaderboard($db);

// Get active period filter
$period = isset($_GET['period']) ? $_GET['period'] : 'all';
$valid_periods = ['daily', 'weekly', 'monthly', 'all'];
if (!in_array($period, $valid_periods)) {
    $period = 'all';
}

// Get global leaderboard data
$global_leaderboard = $leaderboard->getGlobalLeaderboard($period, 20);

// Get top schools
$school_rankings = $leaderboard->getSchoolRankings(20);

// Get user stats
$query = "SELECT 
          COUNT(DISTINCT u.user_id) as total_users,
          SUM(CASE WHEN u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student') THEN 1 ELSE 0 END) as student_count,
          SUM(CASE WHEN u.role_id = (SELECT role_id FROM roles WHERE role_name = 'teacher') THEN 1 ELSE 0 END) as teacher_count,
          SUM(CASE WHEN u.role_id = (SELECT role_id FROM roles WHERE role_name = 'ngo') THEN 1 ELSE 0 END) as ngo_count,
          COUNT(DISTINCT s.school_id) as school_count
          FROM users u
          LEFT JOIN schools s ON u.school_id = s.school_id";
$stmt = $db->prepare($query);
$stmt->execute();
$user_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get eco-points statistics
$query = "SELECT 
          COUNT(point_id) as total_transactions,
          SUM(points) as total_points,
          AVG(points) as avg_points_per_transaction,
          MAX(points) as max_points_transaction
          FROM eco_points";
$stmt = $db->prepare($query);
$stmt->execute();
$points_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get badge stats
$query = "SELECT 
          COUNT(DISTINCT badge_id) as total_badges,
          COUNT(DISTINCT user_badge_id) as total_badges_awarded,
          COUNT(DISTINCT user_id) as users_with_badges
          FROM badges b
          LEFT JOIN user_badges ub ON b.badge_id = ub.badge_id";
$stmt = $db->prepare($query);
$stmt->execute();
$badge_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get activity data for last 30 days
$query = "SELECT 
          DATE_FORMAT(created_at, '%Y-%m-%d') as activity_date,
          COUNT(*) as action_count
          FROM eco_points
          WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          GROUP BY activity_date
          ORDER BY activity_date";
$stmt = $db->prepare($query);
$stmt->execute();
$activity_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Format for Chart.js
$activity_dates = [];
$activity_counts = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $activity_dates[] = date('M j', strtotime($date));
    $activity_counts[] = $activity_data[$date] ?? 0;
}

// Get most active schools this month
$query = "SELECT 
          s.school_id, 
          s.name,
          s.city,
          s.state,
          COUNT(DISTINCT u.user_id) as active_users,
          SUM(ep.points) as total_points
          FROM schools s
          JOIN users u ON s.school_id = u.school_id
          JOIN eco_points ep ON u.user_id = ep.user_id
          WHERE YEAR(ep.created_at) = YEAR(CURDATE()) 
          AND MONTH(ep.created_at) = MONTH(CURDATE())
          GROUP BY s.school_id
          ORDER BY total_points DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$active_schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard Overview - GreenQuest Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <style>
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
        }
        .rank-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            background-color: #f8f9fa;
            color: #333;
            font-weight: bold;
            margin-right: 10px;
        }
        .profile-img-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .badge-count {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: bold;
            line-height: 1;
            color: #fff;
            background-color: #198754;
            border-radius: 0.25rem;
        }
        .period-selector .btn {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        .leaderboard-card {
            border-radius: 10px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-leaf me-2"></i>GreenQuest Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-1"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schools.php">
                            <i class="fas fa-school me-1"></i>Schools
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="content.php">
                            <i class="fas fa-book-open me-1"></i>Content
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="leaderboard.php">
                            <i class="fas fa-trophy me-1"></i>Leaderboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                    </li>
                </ul>
                <div class="dropdown">
                    <a class="btn btn-outline-light dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fas fa-trophy me-2 text-warning"></i>Leaderboard Overview</h2>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="period-selector btn-group">
                    <a href="?period=daily" class="btn btn-<?php echo $period === 'daily' ? 'primary' : 'outline-primary'; ?>">Daily</a>
                    <a href="?period=weekly" class="btn btn-<?php echo $period === 'weekly' ? 'primary' : 'outline-primary'; ?>">Weekly</a>
                    <a href="?period=monthly" class="btn btn-<?php echo $period === 'monthly' ? 'primary' : 'outline-primary'; ?>">Monthly</a>
                    <a href="?period=all" class="btn btn-<?php echo $period === 'all' ? 'primary' : 'outline-primary'; ?>">All Time</a>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-icon bg-primary-subtle text-primary me-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Total Users</h6>
                                <div class="stat-value"><?php echo number_format($user_stats['total_users']); ?></div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="text-center">
                                <div class="fw-bold"><?php echo number_format($user_stats['student_count']); ?></div>
                                <small class="text-muted">Students</small>
                            </div>
                            <div class="text-center">
                                <div class="fw-bold"><?php echo number_format($user_stats['teacher_count']); ?></div>
                                <small class="text-muted">Teachers</small>
                            </div>
                            <div class="text-center">
                                <div class="fw-bold"><?php echo number_format($user_stats['ngo_count']); ?></div>
                                <small class="text-muted">NGOs</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-icon bg-success-subtle text-success me-3">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Total Eco-Points</h6>
                                <div class="stat-value"><?php echo number_format($points_stats['total_points']); ?></div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="text-center">
                                <div class="fw-bold"><?php echo number_format($points_stats['total_transactions']); ?></div>
                                <small class="text-muted">Transactions</small>
                            </div>
                            <div class="text-center">
                                <div class="fw-bold"><?php echo round($points_stats['avg_points_per_transaction']); ?></div>
                                <small class="text-muted">Avg/Transaction</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-icon bg-warning-subtle text-warning me-3">
                                <i class="fas fa-award"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Badges Awarded</h6>
                                <div class="stat-value"><?php echo number_format($badge_stats['total_badges_awarded']); ?></div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="text-center">
                                <div class="fw-bold"><?php echo number_format($badge_stats['total_badges']); ?></div>
                                <small class="text-muted">Badge Types</small>
                            </div>
                            <div class="text-center">
                                <div class="fw-bold"><?php echo number_format($badge_stats['users_with_badges']); ?></div>
                                <small class="text-muted">Users w/ Badges</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-icon bg-info-subtle text-info me-3">
                                <i class="fas fa-school"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Total Schools</h6>
                                <div class="stat-value"><?php echo number_format($user_stats['school_count']); ?></div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center">
                            <a href="schools.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-search me-1"></i>View All Schools
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <!-- Activity Chart -->
            <div class="col-lg-8 mb-4">
                <div class="card leaderboard-card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Activity Trend (Last 30 Days)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="activityChart" width="400" height="250"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Top Schools This Month -->
            <div class="col-lg-4 mb-4">
                <div class="card leaderboard-card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-fire me-2"></i>Most Active Schools This Month</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if (count($active_schools) > 0): ?>
                                <?php foreach ($active_schools as $index => $school): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($school['name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($school['city']); ?>, <?php echo htmlspecialchars($school['state']); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold"><?php echo number_format($school['total_points']); ?> pts</div>
                                            <small class="text-muted"><?php echo $school['active_users']; ?> active users</small>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No data available
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Global Leaderboard -->
            <div class="col-lg-6 mb-4">
                <div class="card leaderboard-card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-globe me-2"></i>Global Leaderboard</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if (count($global_leaderboard) > 0): ?>
                                <?php foreach ($global_leaderboard as $index => $user): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="rank-number"><?php echo $index + 1; ?></span>
                                            <img src="<?php echo getProfileImage($user); ?>" alt="Profile" class="profile-img-sm me-3">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                                                <?php if (!empty($user['school_name'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($user['school_name']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold"><?php echo number_format($user['total_points']); ?></div>
                                            <?php if ($user['badge_count'] > 0): ?>
                                                <span class="badge-count">
                                                    <i class="fas fa-award me-1"></i><?php echo $user['badge_count']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No data available for this period
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Top Schools -->
            <div class="col-lg-6 mb-4">
                <div class="card leaderboard-card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Schools</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if (count($school_rankings) > 0): ?>
                                <?php foreach ($school_rankings as $index => $school): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="rank-number"><?php echo $index + 1; ?></span>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($school['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($school['city']); ?>, <?php echo htmlspecialchars($school['state']); ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold"><?php echo number_format($school['total_points']); ?></div>
                                            <small class="text-muted"><?php echo $school['student_count']; ?> students</small>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No data available
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Leaderboard Management</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <a href="badges.php" class="btn btn-outline-primary">
                                <i class="fas fa-award me-2"></i>Manage Badges
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <a href="points.php" class="btn btn-outline-success">
                                <i class="fas fa-leaf me-2"></i>Manage Point System
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <a href="reports.php?report=leaderboard" class="btn btn-outline-info">
                                <i class="fas fa-file-export me-2"></i>Generate Reports
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <a href="settings.php#leaderboard" class="btn btn-outline-warning">
                                <i class="fas fa-sliders-h me-2"></i>Leaderboard Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-3 mt-auto">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 GreenQuest Admin. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white me-3">Help</a>
                    <a href="#" class="text-white me-3">Privacy Policy</a>
                    <a href="#" class="text-white">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Activity Chart
        const ctx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($activity_dates); ?>,
                datasets: [{
                    label: 'Daily Activities',
                    data: <?php echo json_encode($activity_counts); ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.2)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(13, 110, 253, 1)',
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>