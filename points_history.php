<?php
// Include the header
$pageTitle = "Points History";
$currentPage = "points_history";
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize EcoPoints class
require_once 'includes/eco_points.php';
$ecoPoints = new EcoPoints($db);

// Get user ID (either the logged-in user or a student being viewed by a teacher)
$userId = isset($_GET['user_id']) && $_SESSION['role_name'] === 'teacher' ? (int)$_GET['user_id'] : $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Get points history
$pointsHistory = $ecoPoints->getUserPointsHistory($userId, $limit, $offset);

// Get total points records for pagination
$query = "SELECT COUNT(*) FROM eco_points WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Get user details if viewing another student
$userDetails = null;
if (isset($_GET['user_id']) && $_SESSION['role_name'] === 'teacher') {
    $query = "SELECT u.username, COALESCE(s.school_name, 'Independent') AS school_name 
              FROM users u 
              LEFT JOIN students st ON u.user_id = st.user_id
              LEFT JOIN schools s ON st.school_id = s.school_id
              WHERE u.user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get points breakdown by activity type
$pointsByActivity = $ecoPoints->getUserPointsByActivityType($userId);

// Get points earned in different time periods
$weeklyPoints = $ecoPoints->getUserPointsByPeriod($userId, 'week');
$monthlyPoints = $ecoPoints->getUserPointsByPeriod($userId, 'month');
$yearlyPoints = $ecoPoints->getUserPointsByPeriod($userId, 'year');
$totalPoints = $ecoPoints->getUserTotalPoints($userId);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card eco-card mb-4">
                <div class="card-header bg-success text-white">
                    <h2 class="mb-0">
                        <i class="fas fa-leaf"></i> 
                        <?php if ($userDetails): ?>
                            Eco Points History for <?php echo htmlspecialchars($userDetails['username']); ?>
                        <?php else: ?>
                            Your Eco Points History
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="card-body">
                    <?php if ($userDetails): ?>
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle"></i> 
                            You are viewing the eco points history for student <strong><?php echo htmlspecialchars($userDetails['username']); ?></strong>
                            from <strong><?php echo htmlspecialchars($userDetails['school_name']); ?></strong>.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Points Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body text-center">
                                    <h3 class="display-4 mb-0"><?php echo number_format($totalPoints); ?></h3>
                                    <p class="mb-0">Total Points</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body text-center">
                                    <h3 class="display-4 mb-0"><?php echo number_format($weeklyPoints); ?></h3>
                                    <p class="mb-0">This Week</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body text-center">
                                    <h3 class="display-4 mb-0"><?php echo number_format($monthlyPoints); ?></h3>
                                    <p class="mb-0">This Month</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-secondary text-white h-100">
                                <div class="card-body text-center">
                                    <h3 class="display-4 mb-0"><?php echo number_format($yearlyPoints); ?></h3>
                                    <p class="mb-0">This Year</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Points History -->
                        <div class="col-md-8">
                            <h4 class="mb-3">Points History</h4>
                            
                            <?php if (empty($pointsHistory)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No eco points have been earned yet.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Activity</th>
                                                <th>Description</th>
                                                <th>Points</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pointsHistory as $entry): ?>
                                                <tr>
                                                    <td><?php echo date('M j, Y', strtotime($entry['awarded_at'])); ?></td>
                                                    <td>
                                                        <?php 
                                                        $activityIcon = '';
                                                        switch ($entry['activity_type']) {
                                                            case 'lesson':
                                                                $activityIcon = '<i class="fas fa-book-reader text-primary"></i>';
                                                                break;
                                                            case 'quiz':
                                                                $activityIcon = '<i class="fas fa-question-circle text-warning"></i>';
                                                                break;
                                                            case 'challenge':
                                                                $activityIcon = '<i class="fas fa-tasks text-success"></i>';
                                                                break;
                                                            case 'login':
                                                                $activityIcon = '<i class="fas fa-sign-in-alt text-info"></i>';
                                                                break;
                                                            case 'community':
                                                                $activityIcon = '<i class="fas fa-users text-primary"></i>';
                                                                break;
                                                            default:
                                                                $activityIcon = '<i class="fas fa-star text-secondary"></i>';
                                                        }
                                                        echo $activityIcon . ' ' . ucfirst($entry['activity_type']);
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php echo !empty($entry['description']) ? htmlspecialchars($entry['description']) : 
                                                             (!empty($entry['reason_description']) ? htmlspecialchars($entry['reason_description']) : 'N/A'); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-success">+<?php echo $entry['points']; ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <nav aria-label="Points history pagination">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo isset($_GET['user_id']) ? 'user_id=' . $_GET['user_id'] . '&' : ''; ?>page=<?php echo $page - 1; ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            
                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?<?php echo isset($_GET['user_id']) ? 'user_id=' . $_GET['user_id'] . '&' : ''; ?>page=<?php echo $i; ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo isset($_GET['user_id']) ? 'user_id=' . $_GET['user_id'] . '&' : ''; ?>page=<?php echo $page + 1; ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Points Breakdown -->
                        <div class="col-md-4">
                            <h4 class="mb-3">Points Breakdown</h4>
                            
                            <?php if (empty($pointsByActivity)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No eco points have been earned yet.
                                </div>
                            <?php else: ?>
                                <div class="card">
                                    <div class="card-body p-0">
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($pointsByActivity as $activity): ?>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?php 
                                                    $activityIcon = '';
                                                    switch ($activity['activity_type']) {
                                                        case 'lesson':
                                                            $activityIcon = '<i class="fas fa-book-reader text-primary"></i>';
                                                            break;
                                                        case 'quiz':
                                                            $activityIcon = '<i class="fas fa-question-circle text-warning"></i>';
                                                            break;
                                                        case 'challenge':
                                                            $activityIcon = '<i class="fas fa-tasks text-success"></i>';
                                                            break;
                                                        case 'login':
                                                            $activityIcon = '<i class="fas fa-sign-in-alt text-info"></i>';
                                                            break;
                                                        case 'community':
                                                            $activityIcon = '<i class="fas fa-users text-primary"></i>';
                                                            break;
                                                        default:
                                                            $activityIcon = '<i class="fas fa-star text-secondary"></i>';
                                                    }
                                                    
                                                    echo $activityIcon . ' ' . ucfirst($activity['activity_type']);
                                                    ?>
                                                    <span class="badge badge-pill badge-success">
                                                        <?php echo number_format($activity['total_points']); ?> points
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Progress chart visualization -->
                                <div class="card mt-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Points Progress</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($pointsByActivity as $activity): 
                                            $percentage = ($activity['total_points'] / $totalPoints) * 100;
                                        ?>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span><?php echo ucfirst($activity['activity_type']); ?></span>
                                                    <span class="text-muted small">
                                                        <?php echo number_format($activity['total_points']); ?> points
                                                    </span>
                                                </div>
                                                <div class="progress" style="height: 10px;">
                                                    <?php 
                                                    $bgClass = '';
                                                    switch ($activity['activity_type']) {
                                                        case 'lesson':
                                                            $bgClass = 'bg-primary';
                                                            break;
                                                        case 'quiz':
                                                            $bgClass = 'bg-warning';
                                                            break;
                                                        case 'challenge':
                                                            $bgClass = 'bg-success';
                                                            break;
                                                        case 'login':
                                                            $bgClass = 'bg-info';
                                                            break;
                                                        case 'community':
                                                            $bgClass = 'bg-danger';
                                                            break;
                                                        default:
                                                            $bgClass = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <div class="progress-bar <?php echo $bgClass; ?>" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%" 
                                                         aria-valuenow="<?php echo $percentage; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Next Badges -->
                            <div class="card mt-4">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-certificate"></i> Next Badges</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Get next badges based on points
                                    $query = "SELECT b.badge_id, b.name, b.description, b.image, b.points_required 
                                             FROM badges b 
                                             WHERE b.points_required > (
                                                 SELECT COALESCE(SUM(points), 0) 
                                                 FROM eco_points 
                                                 WHERE user_id = :user_id
                                             )
                                             AND b.badge_id NOT IN (
                                                 SELECT badge_id FROM user_badges WHERE user_id = :user_id
                                             )
                                             AND b.points_required IS NOT NULL
                                             ORDER BY b.points_required ASC
                                             LIMIT 3";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(':user_id', $userId);
                                    $stmt->execute();
                                    $nextBadges = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (empty($nextBadges)): 
                                    ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> You've earned all available points-based badges!
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($nextBadges as $badge): 
                                                $pointsNeeded = $badge['points_required'] - $totalPoints;
                                                $progress = ($totalPoints / $badge['points_required']) * 100;
                                                $progress = min(100, max(0, $progress)); // Ensure between 0-100
                                            ?>
                                                <div class="list-group-item p-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="me-3">
                                                            <?php if ($badge['image']): ?>
                                                                <img src="assets/images/badges/<?php echo htmlspecialchars($badge['image']); ?>" 
                                                                     alt="Badge" width="40" height="40">
                                                            <?php else: ?>
                                                                <i class="fas fa-certificate fa-2x text-warning"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($badge['name']); ?></h6>
                                                            <small class="text-muted"><?php echo htmlspecialchars($badge['description']); ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="progress mb-1" style="height: 8px;">
                                                        <div class="progress-bar bg-warning" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $progress; ?>%" 
                                                             aria-valuenow="<?php echo $progress; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100"></div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted"><?php echo number_format($totalPoints); ?> / <?php echo number_format($badge['points_required']); ?> points</small>
                                                        <small class="text-danger"><?php echo number_format($pointsNeeded); ?> points needed</small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
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