<?php
// Include the header
$pageTitle = "Eco Leaderboard";
$currentPage = "leaderboard";
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Time periods for filtering
$timePeriods = [
    'all' => 'All Time',
    'month' => 'This Month',
    'week' => 'This Week'
];

// Default time period is all time
$selectedPeriod = isset($_GET['period']) && array_key_exists($_GET['period'], $timePeriods) 
    ? $_GET['period'] 
    : 'all';

// Set up the time filter for the SQL query
$timeFilter = '';
if ($selectedPeriod === 'month') {
    $timeFilter = "AND ep.awarded_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')";
} elseif ($selectedPeriod === 'week') {
    $timeFilter = "AND YEARWEEK(ep.awarded_at, 1) = YEARWEEK(NOW(), 1)";
}

// Query to get the leaderboard data
$query = "
    SELECT 
        u.user_id,
        u.username,
        COALESCE(s.school_name, 'Independent') AS school_name,
        u.profile_image,
        SUM(ep.points) AS total_points,
        COUNT(DISTINCT ub.badge_id) AS badge_count
    FROM 
        users u
    LEFT JOIN 
        eco_points ep ON u.user_id = ep.user_id
    LEFT JOIN 
        user_badges ub ON u.user_id = ub.user_id
    LEFT JOIN
        students st ON u.user_id = st.user_id
    LEFT JOIN
        schools s ON st.school_id = s.school_id
    WHERE 
        u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
        $timeFilter
    GROUP BY 
        u.user_id
    ORDER BY 
        total_points DESC, badge_count DESC
    LIMIT 100
";

$stmt = $db->prepare($query);
$stmt->execute();
$leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's position in leaderboard if logged in
$userPosition = null;
$userStats = null;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Get user's stats
    $userQuery = "
        SELECT 
            u.username,
            COALESCE(s.school_name, 'Independent') AS school_name,
            u.profile_image,
            SUM(COALESCE(ep.points, 0)) AS total_points,
            COUNT(DISTINCT ub.badge_id) AS badge_count
        FROM 
            users u
        LEFT JOIN 
            eco_points ep ON u.user_id = ep.user_id $timeFilter
        LEFT JOIN 
            user_badges ub ON u.user_id = ub.user_id
        LEFT JOIN
            students st ON u.user_id = st.user_id
        LEFT JOIN
            schools s ON st.school_id = s.school_id
        WHERE 
            u.user_id = :user_id
        GROUP BY 
            u.user_id
    ";
    
    $stmt = $db->prepare($userQuery);
    $stmt->bindParam(":user_id", $userId);
    $stmt->execute();
    $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userStats && $userStats['total_points'] > 0) {
        // Get user's position
        $positionQuery = "
            SELECT position
            FROM (
                SELECT 
                    u.user_id, 
                    ROW_NUMBER() OVER (ORDER BY SUM(COALESCE(ep.points, 0)) DESC, COUNT(DISTINCT ub.badge_id) DESC) as position
                FROM 
                    users u
                LEFT JOIN 
                    eco_points ep ON u.user_id = ep.user_id $timeFilter
                LEFT JOIN 
                    user_badges ub ON u.user_id = ub.user_id
                WHERE 
                    u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
                GROUP BY 
                    u.user_id
            ) as rankings
            WHERE user_id = :user_id
        ";
        
        $stmt = $db->prepare($positionQuery);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        $positionResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($positionResult) {
            $userPosition = $positionResult['position'];
        }
    }
}

// Get top schools leaderboard
$schoolQuery = "
    SELECT 
        s.school_id,
        s.school_name,
        s.logo,
        COUNT(DISTINCT st.user_id) AS student_count,
        SUM(ep.points) AS total_points,
        ROUND(SUM(ep.points) / COUNT(DISTINCT st.user_id)) AS points_per_student
    FROM 
        schools s
    JOIN 
        students st ON s.school_id = st.school_id
    JOIN 
        users u ON st.user_id = u.user_id
    LEFT JOIN 
        eco_points ep ON u.user_id = ep.user_id
    WHERE 
        u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
        $timeFilter
    GROUP BY 
        s.school_id
    HAVING 
        student_count > 0
    ORDER BY 
        total_points DESC
    LIMIT 10
";

$stmt = $db->prepare($schoolQuery);
$stmt->execute();
$schoolLeaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent badges awarded
$recentBadgesQuery = "
    SELECT 
        u.user_id,
        u.username,
        u.profile_image,
        b.name AS badge_name,
        b.description AS badge_description,
        b.image AS badge_image,
        ub.awarded_at
    FROM 
        user_badges ub
    JOIN 
        users u ON ub.user_id = u.user_id
    JOIN 
        badges b ON ub.badge_id = b.badge_id
    WHERE 
        u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
    ORDER BY 
        ub.awarded_at DESC
    LIMIT 10
";

$stmt = $db->prepare($recentBadgesQuery);
$stmt->execute();
$recentBadges = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card eco-card mb-4">
                <div class="card-header bg-success text-white">
                    <h2 class="mb-0"><i class="fas fa-trophy"></i> Eco Leaderboard</h2>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <p class="lead">See who's making the biggest environmental impact in our community!</p>
                        
                        <!-- Time period filter -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="btn-group">
                                <?php foreach ($timePeriods as $period => $label): ?>
                                <a href="?period=<?php echo $period; ?>" 
                                   class="btn <?php echo $selectedPeriod === $period ? 'btn-success' : 'btn-outline-success'; ?>">
                                    <?php echo $label; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($userPosition && $userStats): ?>
                            <div class="alert alert-info mb-0">
                                <strong>Your Position:</strong> #<?php echo $userPosition; ?> with <?php echo $userStats['total_points'] ?? 0; ?> points
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Top students leaderboard -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col">Rank</th>
                                    <th scope="col">User</th>
                                    <th scope="col">School</th>
                                    <th scope="col">Badges</th>
                                    <th scope="col">Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leaderboard)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No eco points recorded yet. Complete activities to earn points!</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($leaderboard as $index => $user): ?>
                                    <tr <?php echo (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['user_id']) ? 'class="table-success"' : ''; ?>>
                                        <th scope="row"><?php echo $index + 1; ?></th>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="mr-2">
                                                    <?php if ($user['profile_image']): ?>
                                                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                                             alt="Profile" class="rounded-circle" width="40" height="40">
                                                    <?php else: ?>
                                                        <img src="assets/images/default-avatar.png" 
                                                             alt="Default Profile" class="rounded-circle" width="40" height="40">
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                    <?php if ($index < 3): ?>
                                                        <?php if ($index === 0): ?>
                                                            <i class="fas fa-crown text-warning ml-1" title="1st Place"></i>
                                                        <?php elseif ($index === 1): ?>
                                                            <i class="fas fa-award text-secondary ml-1" title="2nd Place"></i>
                                                        <?php elseif ($index === 2): ?>
                                                            <i class="fas fa-medal text-danger ml-1" title="3rd Place"></i>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['school_name']); ?></td>
                                        <td>
                                            <span class="badge badge-pill badge-info">
                                                <i class="fas fa-certificate"></i> <?php echo $user['badge_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($user['total_points']); ?></strong>
                                            <i class="fas fa-leaf text-success ml-1"></i>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Top schools leaderboard -->
        <div class="col-md-6">
            <div class="card eco-card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-school"></i> Top Schools</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($schoolLeaderboard)): ?>
                        <div class="alert alert-info">No school data available yet.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($schoolLeaderboard as $index => $school): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-1">
                                                <?php if ($index === 0): ?>
                                                    <i class="fas fa-trophy text-warning mr-1"></i>
                                                <?php endif; ?>
                                                <?php echo ($index + 1) . '. ' . htmlspecialchars($school['school_name']); ?>
                                            </h5>
                                            <p class="mb-1">
                                                <span class="badge badge-light">
                                                    <i class="fas fa-users"></i> <?php echo $school['student_count']; ?> students
                                                </span>
                                                <span class="badge badge-light">
                                                    <i class="fas fa-leaf"></i> <?php echo number_format($school['points_per_student']); ?> points per student
                                                </span>
                                            </p>
                                        </div>
                                        <div>
                                            <span class="h5"><?php echo number_format($school['total_points']); ?> points</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent badges -->
        <div class="col-md-6">
            <div class="card eco-card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h3 class="mb-0"><i class="fas fa-certificate"></i> Recent Achievements</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recentBadges)): ?>
                        <div class="alert alert-info">No badges awarded yet.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recentBadges as $badge): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <?php if ($badge['badge_image']): ?>
                                                <img src="assets/images/badges/<?php echo htmlspecialchars($badge['badge_image']); ?>" 
                                                     alt="Badge" width="50" height="50">
                                            <?php else: ?>
                                                <i class="fas fa-certificate fa-3x text-warning"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center mb-1">
                                                <?php if ($badge['profile_image']): ?>
                                                    <img src="<?php echo htmlspecialchars($badge['profile_image']); ?>" 
                                                         alt="Profile" class="rounded-circle mr-1" width="24" height="24">
                                                <?php else: ?>
                                                    <img src="assets/images/default-avatar.png" 
                                                         alt="Default Profile" class="rounded-circle mr-1" width="24" height="24">
                                                <?php endif; ?>
                                                <strong><?php echo htmlspecialchars($badge['username']); ?></strong>
                                                <span class="text-muted ml-2">earned</span>
                                            </div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($badge['badge_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($badge['badge_description']); ?>
                                                <span class="ml-1">
                                                    (<?php echo date('M j, Y', strtotime($badge['awarded_at'])); ?>)
                                                </span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- How to earn points -->
    <div class="row">
        <div class="col-12">
            <div class="card eco-card mb-4">
                <div class="card-header bg-info text-white">
                    <h3 class="mb-0"><i class="fas fa-info-circle"></i> How to Earn Eco Points</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-book-reader fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">Complete Lessons</h5>
                                    <p class="card-text">Finish eco lessons to learn about environmental topics and earn points.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border-primary">
                                <div class="card-body text-center">
                                    <i class="fas fa-tasks fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Take on Challenges</h5>
                                    <p class="card-text">Complete real-world environmental challenges to make an impact.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-question-circle fa-3x text-warning mb-3"></i>
                                    <h5 class="card-title">Pass Quizzes</h5>
                                    <p class="card-text">Test your knowledge with quizzes and earn points for correct answers.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
require_once 'includes/footer.php';
?>