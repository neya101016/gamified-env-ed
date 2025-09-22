<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/leaderboard.php';

// Check if user is logged in
requireLogin();

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get user badges
$query = "SELECT b.badge_id, b.name, b.description, b.image, b.category, b.points_required, ub.awarded_at
          FROM badges b
          LEFT JOIN user_badges ub ON b.badge_id = ub.badge_id AND ub.user_id = :user_id
          ORDER BY CASE WHEN ub.user_badge_id IS NOT NULL THEN 0 ELSE 1 END, b.category, b.points_required";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize badges by category
$badge_categories = [];
foreach ($badges as $badge) {
    $category = $badge['category'];
    if (!isset($badge_categories[$category])) {
        $badge_categories[$category] = [];
    }
    $badge_categories[$category][] = $badge;
}

// Get user's total points
$query = "SELECT SUM(points) as total_points FROM eco_points WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user_points = $stmt->fetch(PDO::FETCH_ASSOC);
$total_points = $user_points['total_points'] ?? 0;

// Count earned badges
$earned_badges = 0;
foreach ($badges as $badge) {
    if ($badge['awarded_at'] !== null) {
        $earned_badges++;
    }
}

// Get recently awarded badges (last 30 days)
$query = "SELECT b.badge_id, b.name, b.description, b.image, ub.awarded_at, u.name as user_name, u.profile_pic
          FROM user_badges ub
          JOIN badges b ON ub.badge_id = b.badge_id
          JOIN users u ON ub.user_id = u.user_id
          WHERE ub.awarded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          ORDER BY ub.awarded_at DESC
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Badges - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge-card {
            border-radius: 15px;
            transition: transform 0.3s;
            height: 100%;
        }
        .badge-card:hover {
            transform: translateY(-5px);
        }
        .badge-image {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            transition: transform 0.3s;
        }
        .badge-earned .badge-image {
            transform: scale(1.05);
        }
        .badge-locked {
            filter: grayscale(100%);
            opacity: 0.5;
        }
        .progress {
            height: 10px;
            border-radius: 5px;
        }
        .badge-progress {
            font-size: 0.8rem;
        }
        .recent-badge-img {
            width: 60px;
            height: 60px;
        }
        .profile-img-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
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
                        <a class="nav-link" href="leaderboard.php">Leaderboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="badges.php">Badges</a>
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
        <!-- Badges Summary -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4><i class="fas fa-award me-2 text-warning"></i>My Badge Collection</h4>
                        <p class="text-muted">Earn badges by completing eco-activities and challenges</p>
                    </div>
                    <div class="col-md-6">
                        <div class="row text-center">
                            <div class="col-4">
                                <h5 class="mb-0"><?php echo $earned_badges; ?></h5>
                                <small class="text-muted">Badges Earned</small>
                            </div>
                            <div class="col-4">
                                <h5 class="mb-0"><?php echo count($badges) - $earned_badges; ?></h5>
                                <small class="text-muted">Badges to Go</small>
                            </div>
                            <div class="col-4">
                                <h5 class="mb-0"><?php echo number_format($total_points); ?></h5>
                                <small class="text-muted">Total Points</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recently Awarded Badges -->
        <?php if (count($recent_badges) > 0): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-bell me-2 text-primary"></i>Recently Awarded Badges</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($recent_badges as $badge): ?>
                    <div class="col-md-4 col-lg-2 text-center mb-3">
                        <img src="../assets/img/badges/<?php echo htmlspecialchars($badge['image']); ?>" alt="<?php echo htmlspecialchars($badge['name']); ?>" class="recent-badge-img mb-2">
                        <div class="d-flex align-items-center justify-content-center mb-1">
                            <img src="<?php echo getProfileImage(['profile_pic' => $badge['profile_pic']]); ?>" alt="Profile" class="profile-img-sm me-2">
                            <span class="text-truncate"><?php echo htmlspecialchars($badge['user_name']); ?></span>
                        </div>
                        <h6 class="mb-0"><?php echo htmlspecialchars($badge['name']); ?></h6>
                        <small class="text-muted"><?php echo date('M j, Y', strtotime($badge['awarded_at'])); ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Badge Categories -->
        <?php foreach ($badge_categories as $category => $category_badges): ?>
        <h4 class="mb-3 mt-4 text-capitalize"><?php echo htmlspecialchars($category); ?> Badges</h4>
        <div class="row mb-4">
            <?php foreach ($category_badges as $badge): ?>
                <?php
                $is_earned = $badge['awarded_at'] !== null;
                $card_class = $is_earned ? 'badge-earned border-success' : 'badge-locked';
                $badge_status = $is_earned ? 
                    '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Earned</span>' : 
                    '<span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Locked</span>';
                ?>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="card badge-card <?php echo $card_class; ?>">
                        <div class="card-body text-center">
                            <img src="../assets/img/badges/<?php echo htmlspecialchars($badge['image']); ?>" alt="<?php echo htmlspecialchars($badge['name']); ?>" class="badge-image mb-3">
                            <h5 class="mb-1"><?php echo htmlspecialchars($badge['name']); ?></h5>
                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($badge['description']); ?></p>
                            <div class="mb-2">
                                <?php echo $badge_status; ?>
                            </div>
                            <?php if (!$is_earned && $badge['points_required']): ?>
                                <div class="badge-progress mt-2">
                                    <?php 
                                    $progress = min(100, ($total_points / $badge['points_required']) * 100);
                                    ?>
                                    <div class="progress mb-1">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted"><?php echo number_format($total_points); ?>/<?php echo number_format($badge['points_required']); ?> points</small>
                                </div>
                            <?php endif; ?>
                            <?php if ($is_earned): ?>
                                <div class="mt-2">
                                    <small class="text-muted">Earned on <?php echo date('M j, Y', strtotime($badge['awarded_at'])); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        
        <!-- How to Earn Badges -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-lightbulb me-2 text-warning"></i>How to Earn Badges</h5>
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
                                <p class="text-muted mb-0">Finish eco-lessons and earn achievement badges for your progress.</p>
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
                                <p class="text-muted mb-0">Complete environmental challenges to earn specialized badges.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-users fa-2x text-info"></i>
                            </div>
                            <div>
                                <h6>Community Participation</h6>
                                <p class="text-muted mb-0">Participate in community events to earn special badges.</p>
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