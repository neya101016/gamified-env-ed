<?php
// Sample data generator for eco points and badges
require_once '../includes/config.php';
require_once '../includes/eco_points.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize EcoPoints class
$ecoPoints = new EcoPoints($db);

// Make sure only administrators can run this
session_start();
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'admin') {
    echo "Access denied. Only administrators can run this utility.";
    exit;
}

// Handle POST request to generate data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_data'])) {
        // Get all student users
        $query = "SELECT user_id FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'student') LIMIT 20";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get some challenges
        $query = "SELECT challenge_id, title FROM challenges LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get some lessons
        $query = "SELECT lesson_id, title FROM lessons LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate random eco points for each student
        $activityTypes = ['lesson', 'quiz', 'challenge', 'login', 'community'];
        $reasonKeys = ['lesson_complete', 'quiz_perfect', 'quiz_pass', 'challenge_complete', 'daily_login', 'content_contribution', 'community_activity', 'profile_complete'];
        $descriptions = [
            'Completed Renewable Energy lesson',
            'Perfect score on Water Conservation quiz',
            'Completed Recycling Challenge',
            'Participated in Tree Planting Event',
            'Completed Carbon Footprint lesson',
            'Passed Sustainable Agriculture quiz',
            'Daily login bonus',
            'Participated in community discussion',
            'Shared educational content',
            'Completed profile information'
        ];
        
        $pointsAdded = 0;
        $badgesAwarded = 0;
        
        foreach ($students as $student) {
            $userId = $student['user_id'];
            $pointEntries = rand(5, 20); // Random number of point entries per student
            
            // Generate points over the last 3 months
            $endDate = time();
            $startDate = strtotime('-3 months');
            
            for ($i = 0; $i < $pointEntries; $i++) {
                $activityType = $activityTypes[array_rand($activityTypes)];
                $reasonKey = $reasonKeys[array_rand($reasonKeys)];
                $description = $descriptions[array_rand($descriptions)];
                $points = rand(5, 50);
                
                // Set random activity ID based on activity type
                $activityId = null;
                if ($activityType === 'lesson' && !empty($lessons)) {
                    $lesson = $lessons[array_rand($lessons)];
                    $activityId = $lesson['lesson_id'];
                    $description = "Completed " . $lesson['title'];
                } else if ($activityType === 'challenge' && !empty($challenges)) {
                    $challenge = $challenges[array_rand($challenges)];
                    $activityId = $challenge['challenge_id'];
                    $description = "Completed " . $challenge['title'] . " challenge";
                }
                
                // Set random date between start and end dates
                $randomTimestamp = rand($startDate, $endDate);
                $date = date('Y-m-d H:i:s', $randomTimestamp);
                
                // Add points directly to database with custom date
                $query = "INSERT INTO eco_points (user_id, points, activity_type, activity_id, description, awarded_at) 
                          VALUES (:user_id, :points, :activity_type, :activity_id, :description, :awarded_at)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':points', $points);
                $stmt->bindParam(':activity_type', $activityType);
                $stmt->bindParam(':activity_id', $activityId);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':awarded_at', $date);
                
                if ($stmt->execute()) {
                    $pointsAdded++;
                }
            }
            
            // Check for badges after adding all points
            $awardedBadges = $ecoPoints->checkForBadges($userId);
            $badgesAwarded += count($awardedBadges);
        }
        
        $successMessage = "Sample data generated successfully. Added $pointsAdded point entries and awarded $badgesAwarded badges.";
    }
    
    // Handle clear data request
    if (isset($_POST['clear_data'])) {
        // Clear eco points
        $query = "DELETE FROM eco_points";
        $stmt = $db->prepare($query);
        if ($stmt->execute()) {
            $pointsDeleted = $stmt->rowCount();
        }
        
        // Clear user badges
        $query = "DELETE FROM user_badges";
        $stmt = $db->prepare($query);
        if ($stmt->execute()) {
            $badgesDeleted = $stmt->rowCount();
        }
        
        $successMessage = "Data cleared successfully. Removed $pointsDeleted point entries and $badgesDeleted badge awards.";
    }
}

// Get current data statistics
$query = "SELECT COUNT(*) AS count FROM eco_points";
$stmt = $db->prepare($query);
$stmt->execute();
$pointsCount = $stmt->fetchColumn();

$query = "SELECT COUNT(*) AS count FROM user_badges";
$stmt = $db->prepare($query);
$stmt->execute();
$badgesCount = $stmt->fetchColumn();

$query = "SELECT COUNT(*) AS count FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'student')";
$stmt = $db->prepare($query);
$stmt->execute();
$studentsCount = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eco Points Data Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .alert {
            margin-top: 20px;
        }
        .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            border-radius: 10px 10px 0 0;
        }
        .stats-card {
            text-align: center;
            padding: 20px;
        }
        .stats-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .btn-action {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">
            <i class="fas fa-leaf text-success"></i> Eco Points Data Generator
        </h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white stats-card">
                    <div class="card-body">
                        <h5><i class="fas fa-users"></i> Students</h5>
                        <div class="stats-value"><?php echo number_format($studentsCount); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary text-white stats-card">
                    <div class="card-body">
                        <h5><i class="fas fa-leaf"></i> Eco Points</h5>
                        <div class="stats-value"><?php echo number_format($pointsCount); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark stats-card">
                    <div class="card-body">
                        <h5><i class="fas fa-award"></i> Badges Awarded</h5>
                        <div class="stats-value"><?php echo number_format($badgesCount); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Generate Sample Data</h4>
                    </div>
                    <div class="card-body">
                        <p>
                            This will generate random eco points for students and award badges based on their achievements.
                            Points will be distributed across different activity types with realistic timestamps.
                        </p>
                        <form method="post" action="">
                            <div class="d-grid">
                                <button type="submit" name="generate_data" class="btn btn-success btn-lg btn-action">
                                    <i class="fas fa-magic me-2"></i>Generate Sample Data
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="fas fa-trash-alt"></i> Clear Existing Data</h4>
                    </div>
                    <div class="card-body">
                        <p>
                            <strong>Warning:</strong> This will delete all eco points and badge awards from the database.
                            This action cannot be undone. Badge definitions will remain intact.
                        </p>
                        <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete all eco points and badge awards? This cannot be undone.')">
                            <div class="d-grid">
                                <button type="submit" name="clear_data" class="btn btn-danger btn-lg btn-action">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Clear All Data
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 text-center">
            <a href="../admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Return to Admin Dashboard
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>