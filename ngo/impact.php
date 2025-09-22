<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and has NGO role
requireRole('ngo');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get current NGO ID
$ngo_id = $_SESSION['user_id'];

// Get time period filter (default: last 6 months)
$period = isset($_GET['period']) ? $_GET['period'] : '6months';

switch ($period) {
    case 'month':
        $date_filter = "AND cp.verified_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        $period_label = "Last Month";
        break;
    case '3months':
        $date_filter = "AND cp.verified_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        $period_label = "Last 3 Months";
        break;
    case 'year':
        $date_filter = "AND cp.verified_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        $period_label = "Last Year";
        break;
    case 'all':
        $date_filter = "";
        $period_label = "All Time";
        break;
    default: // 6months
        $date_filter = "AND cp.verified_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        $period_label = "Last 6 Months";
}

// Get overall impact statistics
$query = "SELECT 
    COUNT(DISTINCT uc.user_id) as students_engaged,
    COUNT(DISTINCT c.challenge_id) as challenges_verified,
    SUM(c.eco_points) as total_points_awarded,
    SUM(c.co2_reduction) as total_co2_saved
FROM challenge_proofs cp
JOIN user_challenges uc ON cp.user_challenge_id = uc.user_challenge_id
JOIN challenges c ON uc.challenge_id = c.challenge_id
WHERE c.created_by = :ngo_id 
AND cp.verdict = 'approved' 
$date_filter";

$stmt = $db->prepare($query);
$stmt->bindParam(':ngo_id', $ngo_id);
$stmt->execute();
$overall_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get challenge-specific statistics
$query = "SELECT 
    c.challenge_id,
    c.title,
    c.eco_points,
    c.co2_reduction,
    COUNT(cp.proof_id) as completions,
    SUM(c.eco_points) as total_points,
    SUM(c.co2_reduction) as total_co2_saved
FROM challenges c
LEFT JOIN user_challenges uc ON c.challenge_id = uc.challenge_id
LEFT JOIN challenge_proofs cp ON uc.user_challenge_id = cp.user_challenge_id 
    AND cp.verdict = 'approved' 
    $date_filter
WHERE c.created_by = :ngo_id
GROUP BY c.challenge_id
ORDER BY completions DESC, c.title ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':ngo_id', $ngo_id);
$stmt->execute();
$challenge_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly completion trend
$query = "SELECT 
    DATE_FORMAT(cp.verified_at, '%Y-%m') as month,
    COUNT(*) as completions,
    SUM(c.eco_points) as points_awarded,
    SUM(c.co2_reduction) as co2_saved
FROM challenge_proofs cp
JOIN user_challenges uc ON cp.user_challenge_id = uc.user_challenge_id
JOIN challenges c ON uc.challenge_id = c.challenge_id
WHERE c.created_by = :ngo_id 
AND cp.verdict = 'approved'
AND cp.verified_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(cp.verified_at, '%Y-%m')
ORDER BY month ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':ngo_id', $ngo_id);
$stmt->execute();
$monthly_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get most engaged students
$query = "SELECT 
    u.user_id,
    u.name,
    COUNT(cp.proof_id) as challenges_completed,
    SUM(c.eco_points) as points_earned
FROM users u
JOIN user_challenges uc ON u.user_id = uc.user_id
JOIN challenge_proofs cp ON uc.user_challenge_id = cp.user_challenge_id
JOIN challenges c ON uc.challenge_id = c.challenge_id
WHERE c.created_by = :ngo_id 
AND cp.verdict = 'approved'
$date_filter
GROUP BY u.user_id
ORDER BY challenges_completed DESC, points_earned DESC
LIMIT 10";

$stmt = $db->prepare($query);
$stmt->bindParam(':ngo_id', $ngo_id);
$stmt->execute();
$top_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impact Report - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.0.0/dist/chart.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stat-card {
            transition: transform 0.3s ease;
            border-radius: 10px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 3rem;
            opacity: 0.8;
        }
        .impact-chart-container {
            position: relative;
            height: 300px;
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
                        <a class="nav-link" href="challenges.php">My Challenges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="verifications.php">Verifications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="impact.php">Impact Report</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-line me-2"></i>Environmental Impact Report</h2>
            <div class="btn-group">
                <a href="impact.php?period=month" class="btn btn-outline-primary <?php echo $period === 'month' ? 'active' : ''; ?>">Month</a>
                <a href="impact.php?period=3months" class="btn btn-outline-primary <?php echo $period === '3months' ? 'active' : ''; ?>">3 Months</a>
                <a href="impact.php?period=6months" class="btn btn-outline-primary <?php echo $period === '6months' ? 'active' : ''; ?>">6 Months</a>
                <a href="impact.php?period=year" class="btn btn-outline-primary <?php echo $period === 'year' ? 'active' : ''; ?>">Year</a>
                <a href="impact.php?period=all" class="btn btn-outline-primary <?php echo $period === 'all' ? 'active' : ''; ?>">All Time</a>
            </div>
        </div>
        
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Impact Overview: <?php echo $period_label; ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card h-100 stat-card border-primary bg-primary text-white">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <h2 class="mb-0"><?php echo number_format($overall_stats['students_engaged'] ?? 0); ?></h2>
                                    <div>Students Engaged</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100 stat-card border-success bg-success text-white">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <h2 class="mb-0"><?php echo number_format($overall_stats['challenges_verified'] ?? 0); ?></h2>
                                    <div>Challenges Completed</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100 stat-card border-warning bg-warning text-dark">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div>
                                    <h2 class="mb-0"><?php echo number_format($overall_stats['total_points_awarded'] ?? 0); ?></h2>
                                    <div>Eco-Points Awarded</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100 stat-card border-info bg-info text-white">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <i class="fas fa-tree"></i>
                                </div>
                                <div>
                                    <h2 class="mb-0"><?php echo number_format($overall_stats['total_co2_saved'] ?? 0); ?></h2>
                                    <div>kg CO2 Saved</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Monthly Completion Trend</h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary active" id="showCompletions">Completions</button>
                            <button type="button" class="btn btn-outline-primary" id="showPoints">Eco-Points</button>
                            <button type="button" class="btn btn-outline-primary" id="showCO2">CO2 Saved</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="impact-chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Most Engaged Students</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($top_students) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Challenges</th>
                                            <th>Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_students as $index => $student): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                                <td><?php echo number_format($student['challenges_completed']); ?></td>
                                                <td><?php echo number_format($student['points_earned']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p>No student data available for this period.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Challenge Performance</h5>
            </div>
            <div class="card-body">
                <?php if (count($challenge_stats) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Challenge</th>
                                    <th>Completions</th>
                                    <th>Eco-Points per Challenge</th>
                                    <th>Total Eco-Points</th>
                                    <th>CO2 Reduction (kg)</th>
                                    <th>Total CO2 Saved (kg)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($challenge_stats as $challenge): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($challenge['title']); ?></td>
                                        <td><?php echo number_format($challenge['completions']); ?></td>
                                        <td><?php echo number_format($challenge['eco_points']); ?></td>
                                        <td><?php echo number_format($challenge['total_points']); ?></td>
                                        <td><?php echo number_format($challenge['co2_reduction']); ?></td>
                                        <td><?php echo number_format($challenge['total_co2_saved']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                        <p>No challenge data available yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Export Options</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body text-center">
                                <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                                <h5>Excel Report</h5>
                                <p class="text-muted">Download detailed impact data in Excel format</p>
                                <button class="btn btn-outline-success" id="exportExcel">
                                    <i class="fas fa-download me-2"></i>Download Excel
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body text-center">
                                <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                                <h5>PDF Report</h5>
                                <p class="text-muted">Generate printable PDF report with charts</p>
                                <button class="btn btn-outline-danger" id="exportPDF">
                                    <i class="fas fa-download me-2"></i>Download PDF
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body text-center">
                                <i class="fas fa-share-alt fa-3x text-primary mb-3"></i>
                                <h5>Share Report</h5>
                                <p class="text-muted">Share this impact report with others</p>
                                <button class="btn btn-outline-primary" id="shareReport">
                                    <i class="fas fa-share me-2"></i>Share Report
                                </button>
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.0.0/dist/chart.umd.min.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Chart data
            const chartLabels = <?php echo json_encode(array_map(function($item) {
                $date = new DateTime($item['month'] . '-01');
                return $date->format('M Y');
            }, $monthly_trend)); ?>;
            
            const completionsData = <?php echo json_encode(array_map(function($item) {
                return (int)$item['completions'];
            }, $monthly_trend)); ?>;
            
            const pointsData = <?php echo json_encode(array_map(function($item) {
                return (int)$item['points_awarded'];
            }, $monthly_trend)); ?>;
            
            const co2Data = <?php echo json_encode(array_map(function($item) {
                return (int)$item['co2_saved'];
            }, $monthly_trend)); ?>;
            
            // Create chart
            const ctx = document.getElementById('trendChart').getContext('2d');
            let activeDataset = 'completions';
            
            const trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Challenge Completions',
                        data: completionsData,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
            
            // Switch between chart datasets
            $('#showCompletions').click(function() {
                $(this).addClass('active').siblings().removeClass('active');
                updateChart('completions', completionsData, 'Challenge Completions', 'rgba(54, 162, 235, 1)', 'rgba(54, 162, 235, 0.2)');
            });
            
            $('#showPoints').click(function() {
                $(this).addClass('active').siblings().removeClass('active');
                updateChart('points', pointsData, 'Eco-Points Awarded', 'rgba(255, 159, 64, 1)', 'rgba(255, 159, 64, 0.2)');
            });
            
            $('#showCO2').click(function() {
                $(this).addClass('active').siblings().removeClass('active');
                updateChart('co2', co2Data, 'CO2 Saved (kg)', 'rgba(75, 192, 192, 1)', 'rgba(75, 192, 192, 0.2)');
            });
            
            function updateChart(type, data, label, borderColor, backgroundColor) {
                activeDataset = type;
                trendChart.data.datasets[0].data = data;
                trendChart.data.datasets[0].label = label;
                trendChart.data.datasets[0].borderColor = borderColor;
                trendChart.data.datasets[0].backgroundColor = backgroundColor;
                trendChart.update();
            }
            
            // Export buttons
            $('#exportExcel').click(function() {
                alert('Excel export functionality will be implemented soon!');
            });
            
            $('#exportPDF').click(function() {
                alert('PDF export functionality will be implemented soon!');
            });
            
            $('#shareReport').click(function() {
                alert('Report sharing functionality will be implemented soon!');
            });
        });
    </script>
</body>
</html>