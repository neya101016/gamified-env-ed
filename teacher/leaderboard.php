<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/leaderboard.php';

// Check if user is logged in and is a teacher
requireLogin();
requireRole('teacher');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create leaderboard object
$leaderboard = new Leaderboard($db);

// Get teacher's school ID
$school_id = $_SESSION['school_id'] ?? 0;

// Get school info if school ID exists
$school_info = null;
if ($school_id) {
    $query = "SELECT * FROM schools WHERE school_id = :school_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':school_id', $school_id);
    $stmt->execute();
    $school_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get active period filter
$period = isset($_GET['period']) ? $_GET['period'] : 'all';
$valid_periods = ['daily', 'weekly', 'monthly', 'all'];
if (!in_array($period, $valid_periods)) {
    $period = 'all';
}

// Get school leaderboard if teacher has a school
$school_leaderboard = [];
if ($school_id) {
    $school_leaderboard = $leaderboard->getSchoolLeaderboard($school_id, $period, 20);
}

// Get top performing students in teacher's classes
$class_leaderboard = [];
if ($school_id) {
    $query = "SELECT 
              u.user_id, 
              u.name, 
              u.profile_pic,
              c.class_name,
              COALESCE(SUM(ep.points), 0) as total_points,
              (SELECT COUNT(*) FROM user_badges ub WHERE ub.user_id = u.user_id) as badge_count
              FROM users u
              JOIN class_enrollments ce ON u.user_id = ce.user_id
              JOIN classes c ON ce.class_id = c.class_id
              LEFT JOIN eco_points ep ON u.user_id = ep.user_id
              WHERE c.teacher_id = :teacher_id
              AND u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
              GROUP BY u.user_id, c.class_id
              ORDER BY total_points DESC
              LIMIT 20";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':teacher_id', $_SESSION['user_id']);
    $stmt->execute();
    $class_leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get badge statistics for teacher's students
$query = "SELECT b.name, COUNT(ub.badge_id) as award_count
          FROM badges b
          JOIN user_badges ub ON b.badge_id = ub.badge_id
          JOIN users u ON ub.user_id = u.user_id
          JOIN class_enrollments ce ON u.user_id = ce.user_id
          JOIN classes c ON ce.class_id = c.class_id
          WHERE c.teacher_id = :teacher_id
          GROUP BY b.badge_id
          ORDER BY award_count DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':teacher_id', $_SESSION['user_id']);
$stmt->execute();
$badge_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get activity statistics
$query = "SELECT 
          DATE_FORMAT(ep.created_at, '%Y-%m-%d') as activity_date,
          SUM(ep.points) as daily_points
          FROM eco_points ep
          JOIN users u ON ep.user_id = u.user_id
          JOIN class_enrollments ce ON u.user_id = ce.user_id
          JOIN classes c ON ce.class_id = c.class_id
          WHERE c.teacher_id = :teacher_id
          AND ep.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
          GROUP BY activity_date
          ORDER BY activity_date";
$stmt = $db->prepare($query);
$stmt->bindParam(':teacher_id', $_SESSION['user_id']);
$stmt->execute();
$activity_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Format for Chart.js
$activity_dates = [];
$activity_points = [];
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $activity_dates[] = date('M j', strtotime($date));
    $activity_points[] = $activity_stats[$date] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Leaderboard - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .leaderboard-card {
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .leaderboard-card:hover {
            transform: translateY(-5px);
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
        .top-3 .rank-number {
            width: 36px;
            height: 36px;
            line-height: 36px;
            font-size: 1.2rem;
        }
        .rank-1 .rank-number {
            background-color: gold;
            color: #333;
        }
        .rank-2 .rank-number {
            background-color: silver;
            color: #333;
        }
        .rank-3 .rank-number {
            background-color: #cd7f32; /* bronze */
            color: white;
        }
        .profile-img-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .top-3 .profile-img-sm {
            width: 50px;
            height: 50px;
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
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-leaf me-2"></i>GreenQuest</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="classes.php">My Classes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assignments.php">Assignments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="leaderboard.php">Leaderboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Reports</a>
                    </li>
                </ul>
                <div class="dropdown">
                    <a class="btn btn-outline-light dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
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
    <div class="container py-4">
        <!-- School Info -->
        <?php if ($school_info): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4><i class="fas fa-school me-2 text-primary"></i><?php echo htmlspecialchars($school_info['name']); ?></h4>
                        <p class="text-muted mb-0">
                            <?php echo htmlspecialchars($school_info['city']); ?>, 
                            <?php echo htmlspecialchars($school_info['state']); ?>
                        </p>
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
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <!-- Activity Chart -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm leaderboard-card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Student Activity (Last 14 Days)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="activityChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Top Badge Stats -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm leaderboard-card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-award me-2"></i>Top Badges Earned</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($badge_stats) > 0): ?>
                            <?php foreach ($badge_stats as $badge): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($badge['name']); ?></h6>
                                    </div>
                                    <div>
                                        <span class="badge bg-success">
                                            <i class="fas fa-award me-1"></i><?php echo $badge['award_count']; ?> awarded
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-info-circle me-2"></i>No badges awarded yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Class Leaderboard -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm leaderboard-card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>My Classes Leaderboard</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if (count($class_leaderboard) > 0): ?>
                                <?php foreach ($class_leaderboard as $index => $student): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $index < 3 ? 'top-3 rank-' . ($index+1) : ''; ?>">
                                        <div class="d-flex align-items-center">
                                            <span class="rank-number"><?php echo $index + 1; ?></span>
                                            <img src="<?php echo getProfileImage($student); ?>" alt="Profile" class="profile-img-sm me-3">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($student['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($student['class_name']); ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold"><?php echo number_format($student['total_points']); ?></div>
                                            <?php if ($student['badge_count'] > 0): ?>
                                                <span class="badge-count">
                                                    <i class="fas fa-award me-1"></i><?php echo $student['badge_count']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No student data available
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- School Leaderboard -->
            <?php if ($school_id && count($school_leaderboard) > 0): ?>
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm leaderboard-card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-school me-2"></i>School Leaderboard</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($school_leaderboard as $index => $student): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $index < 3 ? 'top-3 rank-' . ($index+1) : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <span class="rank-number"><?php echo $index + 1; ?></span>
                                        <img src="<?php echo getProfileImage($student); ?>" alt="Profile" class="profile-img-sm me-3">
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($student['name']); ?></div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold"><?php echo number_format($student['total_points']); ?></div>
                                        <?php if ($student['badge_count'] > 0): ?>
                                            <span class="badge-count">
                                                <i class="fas fa-award me-1"></i><?php echo $student['badge_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Download Reports -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-file-export me-2 text-primary"></i>Export Reports</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="d-grid">
                            <a href="reports.php?export=class_performance" class="btn btn-outline-primary">
                                <i class="fas fa-users me-2"></i>Class Performance Report
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="d-grid">
                            <a href="reports.php?export=student_badges" class="btn btn-outline-warning">
                                <i class="fas fa-award me-2"></i>Student Badges Report
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="d-grid">
                            <a href="reports.php?export=activity_summary" class="btn btn-outline-success">
                                <i class="fas fa-chart-bar me-2"></i>Activity Summary Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-light py-3 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 GreenQuest. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-decoration-none text-muted me-3">Privacy Policy</a>
                    <a href="#" class="text-decoration-none text-muted">Terms of Service</a>
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
                    label: 'Daily Points Earned',
                    data: <?php echo json_encode($activity_points); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                    pointRadius: 4
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