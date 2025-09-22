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

// Get analytics data
$total_users = getTotalUsers($conn);
$users_by_role = getUsersByRole($conn);
$recent_activities = getRecentActivities($conn, 10);
$content_statistics = getContentStatistics($conn);
$challenge_completions = getChallengeCompletions($conn);
$daily_logins = getDailyLogins($conn, 30); // Last 30 days

// Page title
$page_title = "Analytics Dashboard";
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <h1 class="h2"><?php echo $page_title; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportReport">
                                <i class="fas fa-download"></i> Export Report
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-calendar"></i> Time Range
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="?period=week">Last Week</a></li>
                                <li><a class="dropdown-item" href="?period=month">Last Month</a></li>
                                <li><a class="dropdown-item" href="?period=quarter">Last Quarter</a></li>
                                <li><a class="dropdown-item" href="?period=year">Last Year</a></li>
                                <li><a class="dropdown-item" href="?period=all">All Time</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Analytics Overview Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Users</h6>
                                        <h2 class="mb-0"><?php echo number_format($total_users); ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-users fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <p class="card-text mt-3 mb-0"><i class="fas fa-arrow-up"></i> 12% increase since last month</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Completed Challenges</h6>
                                        <h2 class="mb-0"><?php echo number_format($challenge_completions['total']); ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-tasks fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <p class="card-text mt-3 mb-0"><i class="fas fa-arrow-up"></i> 8% increase since last month</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Content Items</h6>
                                        <h2 class="mb-0"><?php echo number_format($content_statistics['total']); ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-book fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <p class="card-text mt-3 mb-0"><i class="fas fa-arrow-up"></i> 15% increase since last month</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Active Sessions</h6>
                                        <h2 class="mb-0"><?php echo number_format(getActiveSessionCount($conn)); ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-clock fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <p class="card-text mt-3 mb-0"><i class="fas fa-arrow-up"></i> 5% increase since yesterday</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">User Activity Trends</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="userActivityChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">User Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="userDistributionChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Row of Charts -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Content Statistics</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="contentStatisticsChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Challenge Completion Rate</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="challengeCompletionChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Table -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Activities</h5>
                        <a href="activities.php" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Activity</th>
                                        <th>Details</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_activities)): ?>
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo getProfileImage($activity['user_id']); ?>" alt="User" class="rounded-circle me-2" width="32" height="32">
                                                        <?php echo htmlspecialchars($activity['user_name']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo getActivityBadgeClass($activity['activity_type']); ?>">
                                                        <?php echo htmlspecialchars($activity['activity_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                                <td><?php echo timeAgo($activity['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No recent activities found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <?php include_once '../includes/footer.php'; ?>

    <!-- JavaScript for Charts -->
    <script>
        // User Activity Chart
        const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');
        const userActivityChart = new Chart(userActivityCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_logins, 'date')); ?>,
                datasets: [{
                    label: 'User Logins',
                    data: <?php echo json_encode(array_column($daily_logins, 'count')); ?>,
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Logins'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });

        // User Distribution Chart
        const userDistributionCtx = document.getElementById('userDistributionChart').getContext('2d');
        const userDistributionChart = new Chart(userDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($users_by_role, 'role')); ?>,
                datasets: [{
                    label: 'Users by Role',
                    data: <?php echo json_encode(array_column($users_by_role, 'count')); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Content Statistics Chart
        const contentStatisticsCtx = document.getElementById('contentStatisticsChart').getContext('2d');
        const contentStatisticsChart = new Chart(contentStatisticsCtx, {
            type: 'bar',
            data: {
                labels: ['Lessons', 'Challenges', 'Quizzes'],
                datasets: [
                    {
                        label: 'Approved',
                        data: [
                            <?php echo $content_statistics['lessons_approved']; ?>,
                            <?php echo $content_statistics['challenges_approved']; ?>,
                            <?php echo $content_statistics['quizzes_approved']; ?>
                        ],
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Pending',
                        data: [
                            <?php echo $content_statistics['lessons_pending']; ?>,
                            <?php echo $content_statistics['challenges_pending']; ?>,
                            <?php echo $content_statistics['quizzes_pending']; ?>
                        ],
                        backgroundColor: 'rgba(255, 206, 86, 0.7)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Rejected',
                        data: [
                            <?php echo $content_statistics['lessons_rejected']; ?>,
                            <?php echo $content_statistics['challenges_rejected']; ?>,
                            <?php echo $content_statistics['quizzes_rejected']; ?>
                        ],
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Count'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Content Type'
                        }
                    }
                }
            }
        });

        // Challenge Completion Chart
        const challengeCompletionCtx = document.getElementById('challengeCompletionChart').getContext('2d');
        const challengeCompletionChart = new Chart(challengeCompletionCtx, {
            type: 'pie',
            data: {
                labels: ['Completed', 'In Progress', 'Not Started'],
                datasets: [{
                    label: 'Challenge Completion',
                    data: [
                        <?php echo $challenge_completions['completed']; ?>,
                        <?php echo $challenge_completions['in_progress']; ?>,
                        <?php echo $challenge_completions['not_started']; ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(201, 203, 207, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(201, 203, 207, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Export Report
        document.getElementById('exportReport').addEventListener('click', function() {
            // In a real application, this would generate a PDF or CSV report
            alert('This feature would generate and download a detailed analytics report in a real application.');
        });
    </script>

    <!-- Scripts -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>