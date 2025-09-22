<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and has admin role
requireRole('admin');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Set page title
$pageTitle = "Analytics Dashboard";

// Time period filters
$period = isset($_GET['period']) ? $_GET['period'] : 'week';
$periods = [
    'day' => 'Today',
    'week' => 'This Week',
    'month' => 'This Month',
    'year' => 'This Year',
    'all' => 'All Time'
];

// Calculate date range based on selected period
$endDate = date('Y-m-d');
switch ($period) {
    case 'day':
        $startDate = date('Y-m-d');
        break;
    case 'week':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'year':
        $startDate = date('Y-m-d', strtotime('-365 days'));
        break;
    default:
        $startDate = '2000-01-01'; // All time
}

// Get user registrations per day for the selected period
$query = "SELECT DATE(created_at) as date, COUNT(*) as count 
          FROM users 
          WHERE created_at >= :start_date AND created_at <= :end_date
          GROUP BY DATE(created_at)
          ORDER BY date ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $startDate);
$stmt->bindParam(':end_date', $endDate . ' 23:59:59');
$stmt->execute();
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare registration data for charts
$registrationDates = [];
$registrationCounts = [];
foreach ($registrations as $reg) {
    $registrationDates[] = date('M d', strtotime($reg['date']));
    $registrationCounts[] = $reg['count'];
}

// Get user counts by role
$query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$stmt = $db->prepare($query);
$stmt->execute();
$userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total users
$query = "SELECT COUNT(*) as total FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get active users (logged in within last 7 days)
$query = "SELECT COUNT(*) as total FROM users WHERE last_login >= :last_week";
$stmt = $db->prepare($query);
$lastWeek = date('Y-m-d H:i:s', strtotime('-7 days'));
$stmt->bindParam(':last_week', $lastWeek);
$stmt->execute();
$activeUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get top lessons by completion
$query = "SELECT l.lesson_id, l.title, COUNT(DISTINCT ul.user_id) as completions
          FROM lessons l
          LEFT JOIN user_lessons ul ON l.lesson_id = ul.lesson_id
          WHERE ul.completed = 1
          GROUP BY l.lesson_id
          ORDER BY completions DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$topLessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top challenges by participation
$query = "SELECT c.challenge_id, c.title, COUNT(DISTINCT uc.user_id) as participations
          FROM challenges c
          LEFT JOIN user_challenges uc ON c.challenge_id = uc.challenge_id
          GROUP BY c.challenge_id
          ORDER BY participations DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$topChallenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get eco points distribution
$query = "SELECT 
            CASE 
                WHEN total_points < 100 THEN '0-99'
                WHEN total_points BETWEEN 100 AND 499 THEN '100-499'
                WHEN total_points BETWEEN 500 AND 999 THEN '500-999'
                WHEN total_points BETWEEN 1000 AND 4999 THEN '1k-4.9k'
                ELSE '5k+'
            END as point_range,
            COUNT(*) as user_count
          FROM users
          GROUP BY point_range
          ORDER BY MIN(total_points)";
$stmt = $db->prepare($query);
$stmt->execute();
$pointsDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get badges awarded over time
$query = "SELECT DATE_FORMAT(awarded_at, '%Y-%m') as month, COUNT(*) as count
          FROM user_badges
          WHERE awarded_at >= :start_date
          GROUP BY month
          ORDER BY month ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $startDate);
$stmt->execute();
$badgesAwarded = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare badges data for charts
$badgeMonths = [];
$badgeCounts = [];
foreach ($badgesAwarded as $badge) {
    $badgeMonths[] = date('M Y', strtotime($badge['month'] . '-01'));
    $badgeCounts[] = $badge['count'];
}

// Get recent activities
$query = "SELECT a.activity_id, a.user_id, a.activity_type, a.entity_id, a.created_at, u.name, u.email
          FROM activities a
          JOIN users u ON a.user_id = u.user_id
          ORDER BY a.created_at DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include admin header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-chart-line me-2"></i>Analytics Dashboard</h2>
    <div class="btn-group">
        <?php foreach ($periods as $key => $label): ?>
            <a href="?period=<?php echo $key; ?>" class="btn btn-<?php echo $period == $key ? 'primary' : 'outline-primary'; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Key Metrics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-left-primary h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalUsers); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm border-left-success h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Users (7d)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($activeUsers); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm border-left-info h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Lessons</div>
                        <?php
                        // Count total lessons
                        $query = "SELECT COUNT(*) as count FROM lessons";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $totalLessons = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalLessons); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm border-left-warning h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Challenges</div>
                        <?php
                        // Count total challenges
                        $query = "SELECT COUNT(*) as count FROM challenges";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $totalChallenges = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalChallenges); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- User Registration Chart -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="m-0 font-weight-bold">User Registrations (<?php echo $periods[$period]; ?>)</h6>
            </div>
            <div class="card-body">
                <canvas id="registrationChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- User Roles Chart -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="m-0 font-weight-bold">User Roles Distribution</h6>
            </div>
            <div class="card-body">
                <canvas id="userRolesChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Second Row of Charts -->
<div class="row mb-4">
    <!-- Eco Points Distribution -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h6 class="m-0 font-weight-bold">Eco Points Distribution</h6>
            </div>
            <div class="card-body">
                <canvas id="pointsDistributionChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Badges Awarded -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h6 class="m-0 font-weight-bold">Badges Awarded</h6>
            </div>
            <div class="card-body">
                <canvas id="badgesChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Content Performance Row -->
<div class="row mb-4">
    <!-- Top Lessons -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="m-0 font-weight-bold">Top Lessons by Completion</h6>
            </div>
            <div class="card-body">
                <?php if (count($topLessons) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Lesson</th>
                                    <th>Completions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topLessons as $lesson): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lesson['title']); ?></td>
                                        <td><?php echo $lesson['completions']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No lesson completion data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top Challenges -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h6 class="m-0 font-weight-bold">Top Challenges by Participation</h6>
            </div>
            <div class="card-body">
                <?php if (count($topChallenges) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Challenge</th>
                                    <th>Participants</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topChallenges as $challenge): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($challenge['title']); ?></td>
                                        <td><?php echo $challenge['participations']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No challenge participation data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="m-0 font-weight-bold">Recent User Activities</h6>
    </div>
    <div class="card-body">
        <?php if (count($recentActivities) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Activity</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivities as $activity): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($activity['name']); ?>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($activity['email']); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $activityText = '';
                                    switch ($activity['activity_type']) {
                                        case 'login':
                                            $activityText = 'Logged in to the system';
                                            break;
                                        case 'register':
                                            $activityText = 'Registered a new account';
                                            break;
                                        case 'lesson_complete':
                                            $activityText = 'Completed a lesson';
                                            break;
                                        case 'challenge_join':
                                            $activityText = 'Joined a challenge';
                                            break;
                                        case 'challenge_complete':
                                            $activityText = 'Completed a challenge';
                                            break;
                                        case 'badge_earned':
                                            $activityText = 'Earned a new badge';
                                            break;
                                        case 'quiz_complete':
                                            $activityText = 'Completed a quiz';
                                            break;
                                        default:
                                            $activityText = 'Performed activity: ' . $activity['activity_type'];
                                    }
                                    echo $activityText;
                                    ?>
                                </td>
                                <td><?php echo time_elapsed_string($activity['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">No recent activities found.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // User Registration Chart
    const registrationCtx = document.getElementById('registrationChart').getContext('2d');
    const registrationChart = new Chart(registrationCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($registrationDates); ?>,
            datasets: [{
                label: 'New Registrations',
                data: <?php echo json_encode($registrationCounts); ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 3,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: 'rgba(78, 115, 223, 1)',
                pointHoverRadius: 5,
                pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                pointHitRadius: 10,
                pointBorderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                tooltip: {
                    backgroundColor: 'rgb(255, 255, 255)',
                    bodyColor: '#858796',
                    titleMarginBottom: 10,
                    titleColor: '#6e707e',
                    titleFont: {
                        size: 14
                    },
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    padding: 15,
                    displayColors: false
                }
            }
        }
    });

    // User Roles Chart
    const userRolesCtx = document.getElementById('userRolesChart').getContext('2d');
    const userRolesChart = new Chart(userRolesCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($userRoles, 'role')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($userRoles, 'count')); ?>,
                backgroundColor: [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc',
                    '#f6c23e',
                    '#e74a3b'
                ],
                hoverBackgroundColor: [
                    '#2e59d9',
                    '#17a673',
                    '#2c9faf',
                    '#dda20a',
                    '#be2617'
                ],
                hoverBorderColor: 'rgba(234, 236, 244, 1)',
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    backgroundColor: 'rgb(255, 255, 255)',
                    bodyColor: '#858796',
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    padding: 15,
                    displayColors: false
                }
            },
            cutout: '70%'
        }
    });

    // Eco Points Distribution Chart
    const pointsDistributionCtx = document.getElementById('pointsDistributionChart').getContext('2d');
    const pointsDistributionChart = new Chart(pointsDistributionCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($pointsDistribution, 'point_range')); ?>,
            datasets: [{
                label: 'Users',
                data: <?php echo json_encode(array_column($pointsDistribution, 'user_count')); ?>,
                backgroundColor: [
                    'rgba(28, 200, 138, 0.2)',
                    'rgba(28, 200, 138, 0.3)',
                    'rgba(28, 200, 138, 0.5)',
                    'rgba(28, 200, 138, 0.7)',
                    'rgba(28, 200, 138, 0.9)'
                ],
                borderColor: [
                    'rgb(28, 200, 138)',
                    'rgb(28, 200, 138)',
                    'rgb(28, 200, 138)',
                    'rgb(28, 200, 138)',
                    'rgb(28, 200, 138)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                tooltip: {
                    backgroundColor: 'rgb(255, 255, 255)',
                    bodyColor: '#858796',
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    padding: 15,
                    displayColors: false
                }
            }
        }
    });

    // Badges Awarded Chart
    const badgesCtx = document.getElementById('badgesChart').getContext('2d');
    const badgesChart = new Chart(badgesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($badgeMonths); ?>,
            datasets: [{
                label: 'Badges Awarded',
                data: <?php echo json_encode($badgeCounts); ?>,
                backgroundColor: 'rgba(54, 185, 204, 0.05)',
                borderColor: 'rgba(54, 185, 204, 1)',
                pointRadius: 3,
                pointBackgroundColor: 'rgba(54, 185, 204, 1)',
                pointBorderColor: 'rgba(54, 185, 204, 1)',
                pointHoverRadius: 5,
                pointHoverBackgroundColor: 'rgba(54, 185, 204, 1)',
                pointHoverBorderColor: 'rgba(54, 185, 204, 1)',
                pointHitRadius: 10,
                pointBorderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                tooltip: {
                    backgroundColor: 'rgb(255, 255, 255)',
                    bodyColor: '#858796',
                    titleMarginBottom: 10,
                    titleColor: '#6e707e',
                    titleFont: {
                        size: 14
                    },
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    padding: 15,
                    displayColors: false
                }
            }
        }
    });
</script>

<?php
// Helper function to format time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Include admin footer
include 'includes/footer.php';
?>
