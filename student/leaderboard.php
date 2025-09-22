<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
requireLogin();

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get active period filter
$period = isset($_GET['period']) ? $_GET['period'] : 'all';
$valid_periods = ['daily', 'weekly', 'monthly', 'all'];
if (!in_array($period, $valid_periods)) {
    $period = 'all';
}

// Create leaderboard object
$leaderboard = new Leaderboard($db);

// Get global leaderboard
$global_leaderboard = $leaderboard->getGlobalLeaderboard($period, 10);

// Get school leaderboard if user is associated with a school
$school_leaderboard = [];
if (isset($_SESSION['school_id']) && $_SESSION['school_id']) {
    $school_leaderboard = $leaderboard->getSchoolLeaderboard($_SESSION['school_id'], $period, 10);
    
    // Get school info
    $query = "SELECT * FROM schools WHERE school_id = :school_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':school_id', $_SESSION['school_id']);
    $stmt->execute();
    $school_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get top schools
$school_rankings = $leaderboard->getSchoolRankings(10);

// Get user's rank
$query = "SELECT 
          user_id, 
          @rank := @rank + 1 as rank
          FROM 
          (SELECT u.user_id, SUM(IFNULL(ep.points, 0)) as total_points
           FROM users u
           LEFT JOIN eco_points ep ON u.user_id = ep.user_id
           WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
           GROUP BY u.user_id
           ORDER BY total_points DESC) as ranked_users,
          (SELECT @rank := 0) as init
          ORDER BY rank ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$user_ranks = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$user_global_rank = $user_ranks[$_SESSION['user_id']] ?? 'N/A';

// If user is in a school, get their school rank
$user_school_rank = 'N/A';
if (isset($_SESSION['school_id']) && $_SESSION['school_id']) {
    $query = "SELECT 
              user_id, 
              @srank := @srank + 1 as rank
              FROM 
              (SELECT u.user_id, SUM(IFNULL(ep.points, 0)) as total_points
               FROM users u
               LEFT JOIN eco_points ep ON u.user_id = ep.user_id
               WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
               AND u.school_id = :school_id
               GROUP BY u.user_id
               ORDER BY total_points DESC) as ranked_users,
              (SELECT @srank := 0) as init
              ORDER BY rank ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':school_id', $_SESSION['school_id']);
    $stmt->execute();
    $school_user_ranks = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $user_school_rank = $school_user_ranks[$_SESSION['user_id']] ?? 'N/A';
}

// Get user's total points
$query = "SELECT SUM(points) as total_points FROM eco_points WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user_points = $stmt->fetch(PDO::FETCH_ASSOC);
$total_points = $user_points['total_points'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        <a class="nav-link" href="lessons.php">Lessons</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="challenges.php">Challenges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="leaderboard.php">Leaderboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="badges.php">Badges</a>
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
        <!-- Your Stats -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4><i class="fas fa-star me-2 text-warning"></i>Your Leaderboard Stats</h4>
                        <p class="text-muted">See how you compare to other eco-warriors</p>
                    </div>
                    <div class="col-md-6">
                        <div class="row text-center">
                            <div class="col-4">
                                <h5 class="mb-0"><?php echo number_format($total_points); ?></h5>
                                <small class="text-muted">Eco-Points</small>
                            </div>
                            <div class="col-4">
                                <h5 class="mb-0"><?php echo $user_global_rank; ?></h5>
                                <small class="text-muted">Global Rank</small>
                            </div>
                            <div class="col-4">
                                <h5 class="mb-0"><?php echo $user_school_rank; ?></h5>
                                <small class="text-muted">School Rank</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Period Selector -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Leaderboards</h2>
            <div class="period-selector btn-group">
                <a href="?period=daily" class="btn btn-<?php echo $period === 'daily' ? 'primary' : 'outline-primary'; ?>">Daily</a>
                <a href="?period=weekly" class="btn btn-<?php echo $period === 'weekly' ? 'primary' : 'outline-primary'; ?>">Weekly</a>
                <a href="?period=monthly" class="btn btn-<?php echo $period === 'monthly' ? 'primary' : 'outline-primary'; ?>">Monthly</a>
                <a href="?period=all" class="btn btn-<?php echo $period === 'all' ? 'primary' : 'outline-primary'; ?>">All Time</a>
            </div>
        </div>
        
        <div class="row mb-4">
            <!-- Global Leaderboard -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm leaderboard-card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-globe me-2"></i>Global Leaderboard</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if (count($global_leaderboard) > 0): ?>
                                <?php foreach ($global_leaderboard as $index => $user): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $index < 3 ? 'top-3 rank-' . ($index+1) : ''; ?>">
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
            
            <!-- School Leaderboard or Top Schools -->
            <div class="col-lg-6 mb-4">
                <?php if (isset($_SESSION['school_id']) && $_SESSION['school_id']): ?>
                    <div class="card shadow-sm leaderboard-card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-school me-2"></i><?php echo htmlspecialchars($school_info['name'] ?? 'School'); ?> Leaderboard</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (count($school_leaderboard) > 0): ?>
                                    <?php foreach ($school_leaderboard as $index => $user): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $index < 3 ? 'top-3 rank-' . ($index+1) : ''; ?>">
                                            <div class="d-flex align-items-center">
                                                <span class="rank-number"><?php echo $index + 1; ?></span>
                                                <img src="<?php echo getProfileImage($user); ?>" alt="Profile" class="profile-img-sm me-3">
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
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
                <?php else: ?>
                    <div class="card shadow-sm leaderboard-card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Schools</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (count($school_rankings) > 0): ?>
                                    <?php foreach ($school_rankings as $index => $school): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $index < 3 ? 'top-3 rank-' . ($index+1) : ''; ?>">
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
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Achievement Badges Section -->
        <div class="card shadow-sm leaderboard-card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-award me-2"></i>Top Badge Earners</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    // Query to get top badge earners
                    $query = "SELECT u.user_id, u.name, u.profile_pic, s.name as school_name, COUNT(ub.badge_id) as badge_count
                              FROM users u
                              JOIN user_badges ub ON u.user_id = ub.user_id
                              LEFT JOIN schools s ON u.school_id = s.school_id
                              GROUP BY u.user_id
                              ORDER BY badge_count DESC, u.name ASC
                              LIMIT 6";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $badge_earners = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($badge_earners as $earner):
                    ?>
                    <div class="col-md-4 col-lg-2 text-center mb-3">
                        <img src="<?php echo getProfileImage($earner); ?>" alt="Profile" class="img-fluid rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                        <h6><?php echo htmlspecialchars($earner['name']); ?></h6>
                        <div class="badge bg-success mb-1">
                            <i class="fas fa-award me-1"></i><?php echo $earner['badge_count']; ?> badges
                        </div>
                        <?php if (!empty($earner['school_name'])): ?>
                            <div><small class="text-muted"><?php echo htmlspecialchars($earner['school_name']); ?></small></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Helpful Tips -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-lightbulb me-2 text-warning"></i>How to Climb the Leaderboard</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-book-reader fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h6>Complete Lessons</h6>
                                <p class="text-muted mb-0">Finish lessons and ace quizzes to earn eco-points.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-tasks fa-2x text-success"></i>
                            </div>
                            <div>
                                <h6>Take on Challenges</h6>
                                <p class="text-muted mb-0">Participate in and complete environmental challenges.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-certificate fa-2x text-warning"></i>
                            </div>
                            <div>
                                <h6>Earn Badges</h6>
                                <p class="text-muted mb-0">Collect achievement badges to boost your profile.</p>
                            </div>
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
</body>
</html>