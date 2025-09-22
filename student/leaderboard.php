<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and has student role
requireRole('student');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get user info
$user = new User($db);
$student = $user->getUserById($_SESSION['user_id']);

// Get leaderboard type (default: individual)
$type = isset($_GET['type']) ? $_GET['type'] : 'individual';
$period = isset($_GET['period']) ? $_GET['period'] : 'all-time';
$school_id = isset($_GET['school_id']) ? $_GET['school_id'] : (isset($student['school_id']) ? $student['school_id'] : 0);

// Set time period filter
$period_filter = '';
if ($period == 'this-month') {
    $period_filter = 'AND MONTH(ep.date_earned) = MONTH(CURRENT_DATE()) AND YEAR(ep.date_earned) = YEAR(CURRENT_DATE())';
} elseif ($period == 'this-week') {
    $period_filter = 'AND YEARWEEK(ep.date_earned, 1) = YEARWEEK(CURRENT_DATE(), 1)';
} elseif ($period == 'today') {
    $period_filter = 'AND DATE(ep.date_earned) = CURRENT_DATE()';
}

// Get current user's rank and points
$user_rank = 0;
$user_points = 0;

// Get individual leaderboard
$individual_leaderboard = [];
if ($type == 'individual') {
    // Get top 10 users
    $query = "SET @rank := 0";
    $db->query($query);
    
    // Get leaderboard with ranks
    $query = "SELECT 
              @rank := @rank + 1 as rank,
              u.user_id, u.name, u.profile_pic, 
              s.name as school_name,
              SUM(ep.points) as total_points,
              COUNT(DISTINCT ub.badge_id) as badge_count,
              COUNT(DISTINCT uc.challenge_id) as challenges_completed
              FROM users u
              LEFT JOIN eco_points ep ON u.user_id = ep.user_id $period_filter
              LEFT JOIN user_badges ub ON u.user_id = ub.user_id
              LEFT JOIN user_challenges uc ON u.user_id = uc.user_id AND uc.status = 'verified'
              LEFT JOIN schools s ON u.school_id = s.school_id
              JOIN roles r ON u.role_id = r.role_id
              WHERE r.role_name = 'student'
              GROUP BY u.user_id
              ORDER BY total_points DESC, badge_count DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $individual_leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current user's rank
    $query = "SELECT user_rank, total_points FROM (
              SELECT 
              u.user_id,
              @rank := @rank + 1 as user_rank,
              SUM(ep.points) as total_points
              FROM users u
              LEFT JOIN eco_points ep ON u.user_id = ep.user_id $period_filter
              JOIN roles r ON u.role_id = r.role_id
              WHERE r.role_name = 'student'
              GROUP BY u.user_id
              ORDER BY total_points DESC, u.name
              ) as ranks
              WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $db->query("SET @rank := 0");
    $stmt->execute();
    $user_ranking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_ranking) {
        $user_rank = $user_ranking['user_rank'];
        $user_points = $user_ranking['total_points'];
    }
}

// Get school leaderboard
$school_leaderboard = [];
if ($type == 'school') {
    // Get top 10 schools
    $query = "SET @rank := 0";
    $db->query($query);
    
    // Get leaderboard with ranks
    $query = "SELECT 
              @rank := @rank + 1 as rank,
              s.school_id, s.name, s.logo,
              s.city, s.state,
              COUNT(DISTINCT u.user_id) as student_count,
              SUM(ep.points) as total_points,
              SUM(ep.points) / COUNT(DISTINCT u.user_id) as avg_points_per_student
              FROM schools s
              JOIN users u ON s.school_id = u.school_id
              LEFT JOIN eco_points ep ON u.user_id = ep.user_id $period_filter
              JOIN roles r ON u.role_id = r.role_id
              WHERE r.role_name = 'student'
              GROUP BY s.school_id
              ORDER BY total_points DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $school_leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's school rank
    if (!empty($student['school_id'])) {
        $query = "SELECT school_rank, total_points FROM (
                 SELECT 
                 s.school_id,
                 @rank := @rank + 1 as school_rank,
                 SUM(ep.points) as total_points
                 FROM schools s
                 JOIN users u ON s.school_id = u.school_id
                 LEFT JOIN eco_points ep ON u.user_id = ep.user_id $period_filter
                 JOIN roles r ON u.role_id = r.role_id
                 WHERE r.role_name = 'student'
                 GROUP BY s.school_id
                 ORDER BY total_points DESC
                 ) as ranks
                 WHERE school_id = :school_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':school_id', $student['school_id']);
        $db->query("SET @rank := 0");
        $stmt->execute();
        $school_ranking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($school_ranking) {
            $user_rank = $school_ranking['school_rank'];
            $user_points = $school_ranking['total_points'];
        }
    }
}

// Get batch/class leaderboard
$batch_leaderboard = [];
if ($type == 'batch' && !empty($student['school_id'])) {
    // Get top 10 users from the same school
    $query = "SET @rank := 0";
    $db->query($query);
    
    // Get leaderboard with ranks
    $query = "SELECT 
              @rank := @rank + 1 as rank,
              u.user_id, u.name, u.profile_pic,
              SUM(ep.points) as total_points,
              COUNT(DISTINCT ub.badge_id) as badge_count,
              COUNT(DISTINCT uc.challenge_id) as challenges_completed
              FROM users u
              LEFT JOIN eco_points ep ON u.user_id = ep.user_id $period_filter
              LEFT JOIN user_badges ub ON u.user_id = ub.user_id
              LEFT JOIN user_challenges uc ON u.user_id = uc.user_id AND uc.status = 'verified'
              JOIN roles r ON u.role_id = r.role_id
              WHERE r.role_name = 'student' AND u.school_id = :school_id
              GROUP BY u.user_id
              ORDER BY total_points DESC, badge_count DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':school_id', $student['school_id']);
    $stmt->execute();
    $batch_leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current user's rank in batch
    $query = "SELECT user_rank, total_points FROM (
              SELECT 
              u.user_id,
              @rank := @rank + 1 as user_rank,
              SUM(ep.points) as total_points
              FROM users u
              LEFT JOIN eco_points ep ON u.user_id = ep.user_id $period_filter
              JOIN roles r ON u.role_id = r.role_id
              WHERE r.role_name = 'student' AND u.school_id = :school_id
              GROUP BY u.user_id
              ORDER BY total_points DESC, u.name
              ) as ranks
              WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':school_id', $student['school_id']);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $db->query("SET @rank := 0");
    $stmt->execute();
    $user_batch_ranking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_batch_ranking) {
        $user_rank = $user_batch_ranking['user_rank'];
        $user_points = $user_batch_ranking['total_points'];
    }
}

// Get challenge leaderboard
$challenge_leaderboard = [];
if ($type == 'challenge') {
    // Get top 10 users by challenge completion
    $query = "SET @rank := 0";
    $db->query($query);
    
    // Get leaderboard with ranks
    $query = "SELECT 
              @rank := @rank + 1 as rank,
              u.user_id, u.name, u.profile_pic, 
              s.name as school_name,
              COUNT(DISTINCT uc.challenge_id) as challenges_completed,
              SUM(c.points) as challenge_points
              FROM users u
              JOIN user_challenges uc ON u.user_id = uc.user_id
              JOIN challenges c ON uc.challenge_id = c.challenge_id
              LEFT JOIN schools s ON u.school_id = s.school_id
              WHERE uc.status = 'verified'
              GROUP BY u.user_id
              ORDER BY challenges_completed DESC, challenge_points DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $challenge_leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current user's challenge rank
    $query = "SELECT 
              COUNT(DISTINCT uc.challenge_id) as challenges_completed,
              SUM(c.points) as challenge_points
              FROM users u
              LEFT JOIN user_challenges uc ON u.user_id = uc.user_id AND uc.status = 'verified'
              LEFT JOIN challenges c ON uc.challenge_id = c.challenge_id
              WHERE u.user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user_challenge_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_challenge_stats) {
        $user_points = $user_challenge_stats['challenges_completed'] ?? 0;
        
        // Get rank
        $query = "SELECT COUNT(*) + 1 as rank FROM (
                 SELECT u.user_id, COUNT(DISTINCT uc.challenge_id) as count
                 FROM users u
                 JOIN user_challenges uc ON u.user_id = uc.user_id
                 WHERE uc.status = 'verified'
                 GROUP BY u.user_id
                 HAVING count > :user_count
                 ) as counts";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_count', $user_points);
        $stmt->execute();
        $rank_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_rank = $rank_result['rank'] ?? 'N/A';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eco Leaderboard - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .filter-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .leaderboard-table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .profile-pic {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
        .school-logo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 8px;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #28a745;
        }
        .top-three {
            text-align: center;
            margin-bottom: 30px;
        }
        .top-position {
            position: relative;
            display: inline-block;
        }
        .position-1 {
            margin-bottom: 30px;
        }
        .position-2, .position-3 {
            margin-top: 30px;
        }
        .medal {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        .medal-1 {
            background-color: #ffc107;
        }
        .medal-2 {
            background-color: #adb5bd;
        }
        .medal-3 {
            background-color: #cd7f32;
        }
        .top-avatar {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin: 0 auto 10px;
            border: 3px solid #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .position-1 .top-avatar {
            width: 100px;
            height: 100px;
        }
        .top-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .top-school {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .top-points {
            background-color: #28a745;
            color: white;
            border-radius: 20px;
            padding: 3px 10px;
            display: inline-block;
            font-size: 0.9rem;
        }
        .user-rank-card {
            background-color: #e9f7ef;
            border-left: 4px solid #28a745;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        .tab-content {
            background-color: #fff;
            border-radius: 0 0 10px 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .nav-tabs {
            border-bottom: none;
        }
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        .badge-small {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 3px;
        }
        .progress-bar-label {
            position: absolute;
            right: 10px;
            color: white;
            font-weight: bold;
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
                        <a class="nav-link" href="batch.php">My Batch</a>
                    </li>
                </ul>
                <div class="dropdown">
                    <a class="btn btn-outline-light dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="badges.php"><i class="fas fa-award me-2"></i>My Badges</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container py-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-trophy me-2"></i>Eco Leaderboard</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Leaderboard</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-card shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="form-label">Leaderboard Type</label>
                    <select id="leaderboard-type" class="form-select">
                        <option value="individual" <?php echo $type == 'individual' ? 'selected' : ''; ?>>Individual</option>
                        <option value="school" <?php echo $type == 'school' ? 'selected' : ''; ?>>Schools</option>
                        <option value="batch" <?php echo $type == 'batch' ? 'selected' : ''; ?>>My Batch</option>
                        <option value="challenge" <?php echo $type == 'challenge' ? 'selected' : ''; ?>>Challenge Champions</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="form-label">Time Period</label>
                    <select id="time-period" class="form-select">
                        <option value="all-time" <?php echo $period == 'all-time' ? 'selected' : ''; ?>>All Time</option>
                        <option value="this-month" <?php echo $period == 'this-month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="this-week" <?php echo $period == 'this-week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="today" <?php echo $period == 'today' ? 'selected' : ''; ?>>Today</option>
                    </select>
                </div>
                <div class="col-md-4 text-end d-flex align-items-end justify-content-end">
                    <button id="refresh-btn" class="btn btn-primary">
                        <i class="fas fa-sync-alt me-2"></i>Refresh Leaderboard
                    </button>
                </div>
            </div>
        </div>
        
        <!-- User's Current Rank -->
        <div class="user-rank-card shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-1 text-center">
                    <h4 class="mb-0">#<?php echo $user_rank; ?></h4>
                    <small class="text-muted">Your Rank</small>
                </div>
                <div class="col-md-2 text-center">
                    <img src="<?php echo !empty($student['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($student['profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                         alt="Your Profile" class="profile-pic">
                    <div class="mt-1"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                </div>
                <div class="col-md-7">
                    <div class="progress" style="height: 20px;">
                        <?php 
                        // Calculate percentage for progress bar
                        $percentage = 0;
                        $next_milestone = 0;
                        
                        if ($type == 'individual' || $type == 'batch' || $type == 'school') {
                            // For points-based leaderboards
                            if ($user_points > 0) {
                                $milestones = [100, 250, 500, 1000, 2500, 5000, 10000, 25000, 50000, 100000];
                                foreach ($milestones as $milestone) {
                                    if ($user_points < $milestone) {
                                        $next_milestone = $milestone;
                                        $percentage = ($user_points / $milestone) * 100;
                                        break;
                                    }
                                }
                                
                                if ($next_milestone == 0) {
                                    $percentage = 100;
                                    $next_milestone = $milestones[count($milestones)-1];
                                }
                            }
                        } else {
                            // For challenge-based leaderboard
                            $challenge_milestones = [1, 3, 5, 10, 15, 25, 50, 75, 100];
                            foreach ($challenge_milestones as $milestone) {
                                if ($user_points < $milestone) {
                                    $next_milestone = $milestone;
                                    $percentage = ($user_points / $milestone) * 100;
                                    break;
                                }
                            }
                            
                            if ($next_milestone == 0) {
                                $percentage = 100;
                                $next_milestone = $challenge_milestones[count($challenge_milestones)-1];
                            }
                        }
                        ?>
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%">
                            <span class="progress-bar-label">
                                <?php 
                                if ($type == 'challenge') {
                                    echo $user_points . ' / ' . $next_milestone . ' Challenges';
                                } else {
                                    echo number_format($user_points) . ' / ' . number_format($next_milestone) . ' Points';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="text-end mt-1">
                        <small class="text-muted">
                            <?php
                            if ($type == 'challenge') {
                                echo 'Next milestone: ' . $next_milestone . ' challenges completed';
                            } else {
                                echo 'Next milestone: ' . number_format($next_milestone) . ' eco-points';
                            }
                            ?>
                        </small>
                    </div>
                </div>
                <div class="col-md-2 text-center">
                    <h4 class="mb-0">
                        <?php
                        if ($type == 'challenge') {
                            echo $user_points;
                            echo '<small class="text-muted d-block">Challenges</small>';
                        } else {
                            echo number_format($user_points);
                            echo '<small class="text-muted d-block">Eco-Points</small>';
                        }
                        ?>
                    </h4>
                </div>
            </div>
        </div>
        
        <!-- Leaderboard Tabs -->
        <ul class="nav nav-tabs mt-4" id="leaderboardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="table-tab" data-bs-toggle="tab" data-bs-target="#table-view" type="button" role="tab">
                    <i class="fas fa-table me-2"></i>Table View
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="podium-tab" data-bs-toggle="tab" data-bs-target="#podium-view" type="button" role="tab">
                    <i class="fas fa-medal me-2"></i>Top Winners
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="leaderboardTabContent">
            <!-- Table View -->
            <div class="tab-pane fade show active" id="table-view" role="tabpanel">
                <?php if ($type == 'individual' && !empty($individual_leaderboard)): ?>
                    <table class="table table-hover leaderboard-table">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">Rank</th>
                                <th width="40%">Student</th>
                                <th width="20%">School</th>
                                <th width="10%">Badges</th>
                                <th width="10%">Challenges</th>
                                <th width="10%">Eco-Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($individual_leaderboard as $user): ?>
                            <tr class="<?php echo $user['user_id'] == $_SESSION['user_id'] ? 'table-success' : ''; ?>">
                                <td class="text-center">
                                    <?php if ($user['rank'] <= 3): ?>
                                        <i class="fas fa-trophy 
                                            <?php echo $user['rank'] == 1 ? 'text-warning' : ($user['rank'] == 2 ? 'text-secondary' : 'text-danger'); ?>">
                                        </i>
                                    <?php else: ?>
                                        <?php echo $user['rank']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($user['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($user['profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                                             alt="Profile" class="profile-pic me-2">
                                        <div><?php echo htmlspecialchars($user['name']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo !empty($user['school_name']) ? htmlspecialchars($user['school_name']) : 'No School'; ?></td>
                                <td class="text-center"><?php echo $user['badge_count'] ?? 0; ?></td>
                                <td class="text-center"><?php echo $user['challenges_completed'] ?? 0; ?></td>
                                <td class="text-center"><strong><?php echo number_format($user['total_points'] ?? 0); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($type == 'school' && !empty($school_leaderboard)): ?>
                    <table class="table table-hover leaderboard-table">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">Rank</th>
                                <th width="40%">School</th>
                                <th width="15%">Location</th>
                                <th width="10%">Students</th>
                                <th width="15%">Avg. Points</th>
                                <th width="10%">Total Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($school_leaderboard as $school): ?>
                            <tr class="<?php echo $school['school_id'] == $student['school_id'] ? 'table-success' : ''; ?>">
                                <td class="text-center">
                                    <?php if ($school['rank'] <= 3): ?>
                                        <i class="fas fa-trophy 
                                            <?php echo $school['rank'] == 1 ? 'text-warning' : ($school['rank'] == 2 ? 'text-secondary' : 'text-danger'); ?>">
                                        </i>
                                    <?php else: ?>
                                        <?php echo $school['rank']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($school['logo'])): ?>
                                            <img src="../uploads/school_logos/<?php echo htmlspecialchars($school['logo']); ?>" 
                                                 alt="School Logo" class="school-logo me-2">
                                        <?php else: ?>
                                            <div class="school-logo me-2">
                                                <i class="fas fa-school"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div><?php echo htmlspecialchars($school['name']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $location = [];
                                    if (!empty($school['city'])) $location[] = $school['city'];
                                    if (!empty($school['state'])) $location[] = $school['state'];
                                    echo !empty($location) ? htmlspecialchars(implode(', ', $location)) : 'N/A';
                                    ?>
                                </td>
                                <td class="text-center"><?php echo $school['student_count'] ?? 0; ?></td>
                                <td class="text-center"><?php echo number_format($school['avg_points_per_student'] ?? 0, 0); ?></td>
                                <td class="text-center"><strong><?php echo number_format($school['total_points'] ?? 0); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($type == 'batch' && !empty($batch_leaderboard)): ?>
                    <table class="table table-hover leaderboard-table">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">Rank</th>
                                <th width="40%">Student</th>
                                <th width="20%">Badges</th>
                                <th width="15%">Challenges</th>
                                <th width="15%">Eco-Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($batch_leaderboard as $user): ?>
                            <tr class="<?php echo $user['user_id'] == $_SESSION['user_id'] ? 'table-success' : ''; ?>">
                                <td class="text-center">
                                    <?php if ($user['rank'] <= 3): ?>
                                        <i class="fas fa-trophy 
                                            <?php echo $user['rank'] == 1 ? 'text-warning' : ($user['rank'] == 2 ? 'text-secondary' : 'text-danger'); ?>">
                                        </i>
                                    <?php else: ?>
                                        <?php echo $user['rank']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($user['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($user['profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                                             alt="Profile" class="profile-pic me-2">
                                        <div><?php echo htmlspecialchars($user['name']); ?></div>
                                    </div>
                                </td>
                                <td class="text-center"><?php echo $user['badge_count'] ?? 0; ?></td>
                                <td class="text-center"><?php echo $user['challenges_completed'] ?? 0; ?></td>
                                <td class="text-center"><strong><?php echo number_format($user['total_points'] ?? 0); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($type == 'challenge' && !empty($challenge_leaderboard)): ?>
                    <table class="table table-hover leaderboard-table">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">Rank</th>
                                <th width="40%">Student</th>
                                <th width="25%">School</th>
                                <th width="10%">Challenges</th>
                                <th width="15%">Points Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($challenge_leaderboard as $user): ?>
                            <tr class="<?php echo $user['user_id'] == $_SESSION['user_id'] ? 'table-success' : ''; ?>">
                                <td class="text-center">
                                    <?php if ($user['rank'] <= 3): ?>
                                        <i class="fas fa-trophy 
                                            <?php echo $user['rank'] == 1 ? 'text-warning' : ($user['rank'] == 2 ? 'text-secondary' : 'text-danger'); ?>">
                                        </i>
                                    <?php else: ?>
                                        <?php echo $user['rank']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($user['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($user['profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                                             alt="Profile" class="profile-pic me-2">
                                        <div><?php echo htmlspecialchars($user['name']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo !empty($user['school_name']) ? htmlspecialchars($user['school_name']) : 'No School'; ?></td>
                                <td class="text-center"><strong><?php echo $user['challenges_completed'] ?? 0; ?></strong></td>
                                <td class="text-center"><?php echo number_format($user['challenge_points'] ?? 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No leaderboard data available for the selected criteria.
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Podium View -->
            <div class="tab-pane fade" id="podium-view" role="tabpanel">
                <div class="top-three">
                    <div class="row">
                        <?php 
                        $top_three = [];
                        
                        if ($type == 'individual' && !empty($individual_leaderboard)) {
                            $top_three = array_slice($individual_leaderboard, 0, 3);
                        } elseif ($type == 'school' && !empty($school_leaderboard)) {
                            $top_three = array_slice($school_leaderboard, 0, 3);
                        } elseif ($type == 'batch' && !empty($batch_leaderboard)) {
                            $top_three = array_slice($batch_leaderboard, 0, 3);
                        } elseif ($type == 'challenge' && !empty($challenge_leaderboard)) {
                            $top_three = array_slice($challenge_leaderboard, 0, 3);
                        }
                        
                        // Check if we have a second place
                        if (count($top_three) >= 2): 
                        ?>
                        <div class="col-md-4">
                            <div class="position-2 top-position">
                                <div class="medal medal-2">
                                    <i class="fas fa-medal"></i>
                                </div>
                                <?php if ($type == 'school'): ?>
                                    <?php if (!empty($top_three[1]['logo'])): ?>
                                        <img src="../uploads/school_logos/<?php echo htmlspecialchars($top_three[1]['logo']); ?>" 
                                             alt="School Logo" class="top-avatar">
                                    <?php else: ?>
                                        <div class="top-avatar d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-school text-success" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <img src="<?php echo !empty($top_three[1]['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($top_three[1]['profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                                         alt="Profile" class="top-avatar">
                                <?php endif; ?>
                                
                                <div class="top-name"><?php echo htmlspecialchars($top_three[1]['name']); ?></div>
                                
                                <?php if ($type != 'school' && $type != 'batch'): ?>
                                    <div class="top-school">
                                        <?php echo !empty($top_three[1]['school_name']) ? htmlspecialchars($top_three[1]['school_name']) : 'No School'; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="top-points">
                                    <?php 
                                    if ($type == 'challenge') {
                                        echo $top_three[1]['challenges_completed'] . ' Challenges';
                                    } else {
                                        echo number_format($top_three[1]['total_points']) . ' Points';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- First place (always in middle) -->
                        <?php if (!empty($top_three)): ?>
                        <div class="col-md-4">
                            <div class="position-1 top-position">
                                <div class="medal medal-1">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <?php if ($type == 'school'): ?>
                                    <?php if (!empty($top_three[0]['logo'])): ?>
                                        <img src="../uploads/school_logos/<?php echo htmlspecialchars($top_three[0]['logo']); ?>" 
                                             alt="School Logo" class="top-avatar">
                                    <?php else: ?>
                                        <div class="top-avatar d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-school text-success" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <img src="<?php echo !empty($top_three[0]['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($top_three[0]['profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                                         alt="Profile" class="top-avatar">
                                <?php endif; ?>
                                
                                <div class="top-name"><?php echo htmlspecialchars($top_three[0]['name']); ?></div>
                                
                                <?php if ($type != 'school' && $type != 'batch'): ?>
                                    <div class="top-school">
                                        <?php echo !empty($top_three[0]['school_name']) ? htmlspecialchars($top_three[0]['school_name']) : 'No School'; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="top-points">
                                    <?php 
                                    if ($type == 'challenge') {
                                        echo $top_three[0]['challenges_completed'] . ' Challenges';
                                    } else {
                                        echo number_format($top_three[0]['total_points']) . ' Points';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Check if we have a third place -->
                        <?php if (count($top_three) >= 3): ?>
                        <div class="col-md-4">
                            <div class="position-3 top-position">
                                <div class="medal medal-3">
                                    <i class="fas fa-medal"></i>
                                </div>
                                <?php if ($type == 'school'): ?>
                                    <?php if (!empty($top_three[2]['logo'])): ?>
                                        <img src="../uploads/school_logos/<?php echo htmlspecialchars($top_three[2]['logo']); ?>" 
                                             alt="School Logo" class="top-avatar">
                                    <?php else: ?>
                                        <div class="top-avatar d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-school text-success" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <img src="<?php echo !empty($top_three[2]['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($top_three[2]['profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                                         alt="Profile" class="top-avatar">
                                <?php endif; ?>
                                
                                <div class="top-name"><?php echo htmlspecialchars($top_three[2]['name']); ?></div>
                                
                                <?php if ($type != 'school' && $type != 'batch'): ?>
                                    <div class="top-school">
                                        <?php echo !empty($top_three[2]['school_name']) ? htmlspecialchars($top_three[2]['school_name']) : 'No School'; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="top-points">
                                    <?php 
                                    if ($type == 'challenge') {
                                        echo $top_three[2]['challenges_completed'] . ' Challenges';
                                    } else {
                                        echo number_format($top_three[2]['total_points']) . ' Points';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (empty($top_three)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>No leaderboard data available for the selected criteria.
                    </div>
                <?php endif; ?>
                
                <!-- Leaderboard Info -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Leaderboard Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>How to Earn Eco-Points:</h6>
                                <ul>
                                    <li>Complete environmental lessons and quizzes</li>
                                    <li>Participate in and complete eco-challenges</li>
                                    <li>Earn badges for environmental activities</li>
                                    <li>Invite friends to join GreenQuest</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Leaderboard Updates:</h6>
                                <ul>
                                    <li>Individual rankings are updated in real-time</li>
                                    <li>School rankings are calculated daily</li>
                                    <li>Weekly and monthly winners are announced on the first day of each new period</li>
                                    <li>Top performers may receive special recognition and rewards</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Handle leaderboard refresh
            $('#refresh-btn').click(function() {
                const type = $('#leaderboard-type').val();
                const period = $('#time-period').val();
                window.location.href = `leaderboard.php?type=${type}&period=${period}`;
            });
            
            // Update leaderboard on select change
            $('#leaderboard-type, #time-period').change(function() {
                $('#refresh-btn').click();
            });
        });
    </script>
</body>
</html>