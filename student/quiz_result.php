<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and has student role
requireRole('student');

// Check if quiz ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: lessons.php');
    exit;
}

// Check if quiz result is in session
if (!isset($_SESSION['quiz_result'])) {
    header('Location: quiz.php?id=' . $_GET['id']);
    exit;
}

$quiz_id = intval($_GET['id']);
$quiz_result = $_SESSION['quiz_result'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get quiz details
$quizObj = new Quiz($db);
$quiz = $quizObj->getQuizById($quiz_id);

// If quiz not found, redirect to lessons page
if (!$quiz) {
    header('Location: lessons.php');
    exit;
}

// Calculate percentage
$percentage = ($quiz_result['score'] / $quiz_result['total_marks']) * 100;

// Get badge info if awarded
$badge_awarded = false;
if (isset($quiz_result['badge_awarded']) && $quiz_result['badge_awarded']) {
    $badge_awarded = true;
    $badge_name = $quiz_result['badge_name'] ?? 'Achievement Badge';
    $badge_id = $quiz_result['badge_id'] ?? 0;
    
    // Get badge details
    $query = "SELECT * FROM badges WHERE badge_id = :badge_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':badge_id', $badge_id);
    $stmt->execute();
    $badge = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Clear the quiz result from session
unset($_SESSION['quiz_result']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .result-card {
            border-radius: 15px;
            overflow: hidden;
            border: none;
        }
        .result-header {
            background-color: <?php echo ($percentage >= 70) ? '#28a745' : (($percentage >= 50) ? '#17a2b8' : '#dc3545'); ?>;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .result-body {
            padding: 30px;
        }
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: white;
            color: <?php echo ($percentage >= 70) ? '#28a745' : (($percentage >= 50) ? '#17a2b8' : '#dc3545'); ?>;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .score-percent {
            font-size: 2.5rem;
            font-weight: bold;
            line-height: 1;
        }
        .score-label {
            font-size: 1rem;
            margin-top: 5px;
        }
        .badge-card {
            border-radius: 10px;
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .badge-card:hover {
            transform: translateY(-5px);
        }
        .badge-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f2d74e;
            opacity: 0;
            animation: confetti 5s ease-in-out infinite;
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
                        <a class="nav-link active" href="lessons.php">Lessons</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="challenges.php">Challenges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">Leaderboard</a>
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
    <div class="container py-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="lessons.php">Lessons</a></li>
                <li class="breadcrumb-item"><a href="lesson_details.php?id=<?php echo $quiz['lesson_id']; ?>"><?php echo htmlspecialchars($quiz['lesson_title']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Quiz Results</li>
            </ol>
        </nav>
        
        <!-- Results Card -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg result-card">
                    <div class="result-header">
                        <div class="score-circle mb-3">
                            <div class="score-percent"><?php echo round($percentage); ?>%</div>
                            <div class="score-label">Score</div>
                        </div>
                        <h1 class="mt-4">
                            <?php 
                            if ($percentage >= 90) echo 'Excellent!';
                            elseif ($percentage >= 70) echo 'Good Job!';
                            elseif ($percentage >= 50) echo 'Nice Effort!';
                            else echo 'Keep Practicing!';
                            ?>
                        </h1>
                    </div>
                    <div class="result-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h4>Quiz Results</h4>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Score
                                        <span class="badge bg-primary rounded-pill"><?php echo $quiz_result['score']; ?> / <?php echo $quiz_result['total_marks']; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Percentage
                                        <span class="badge bg-primary rounded-pill"><?php echo round($percentage); ?>%</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Eco-Points Earned
                                        <span class="badge bg-success rounded-pill">+<?php echo $quiz_result['eco_points_earned']; ?></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h4>Feedback</h4>
                                <div class="alert alert-<?php 
                                    if ($percentage >= 90) echo 'success';
                                    elseif ($percentage >= 70) echo 'primary';
                                    elseif ($percentage >= 50) echo 'info';
                                    else echo 'warning';
                                ?>">
                                    <?php 
                                    if ($percentage >= 90) {
                                        echo "<p>Outstanding! You've mastered this topic. Keep up the excellent work!</p>";
                                    } elseif ($percentage >= 70) {
                                        echo "<p>Good job! You've demonstrated a solid understanding of the material.</p>";
                                    } elseif ($percentage >= 50) {
                                        echo "<p>Nice effort! You've grasped the basics, but might want to review some concepts.</p>";
                                    } else {
                                        echo "<p>Keep practicing! We recommend reviewing the lesson material again to strengthen your understanding.</p>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if($badge_awarded && isset($badge)): ?>
                        <!-- Badge Earned -->
                        <div class="badge-earned mt-4">
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="card badge-card text-center">
                                        <div class="card-body p-4">
                                            <div class="badge-icon">
                                                <?php
                                                // Display different icons based on badge name
                                                $icon = 'fas fa-award'; // Default
                                                switch($badge['name'] ?? '') {
                                                    case 'Eco Starter':
                                                        $icon = 'fas fa-seedling';
                                                        break;
                                                    case 'Green Thumb':
                                                        $icon = 'fas fa-leaf';
                                                        break;
                                                    case 'Quiz Master':
                                                        $icon = 'fas fa-graduation-cap';
                                                        break;
                                                    case 'Eco Warrior':
                                                        $icon = 'fas fa-shield-alt';
                                                        break;
                                                }
                                                ?>
                                                <i class="<?php echo $icon; ?> text-success"></i>
                                            </div>
                                            <h5 class="card-title">New Badge Unlocked!</h5>
                                            <h3 class="card-subtitle mb-2"><?php echo htmlspecialchars($badge['name'] ?? $badge_name); ?></h3>
                                            <p class="card-text"><?php echo htmlspecialchars($badge['description'] ?? 'You\'ve earned a new achievement badge for your accomplishments!'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="lesson_details.php?id=<?php echo $quiz['lesson_id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Lesson
                            </a>
                            <a href="quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-primary">
                                <i class="fas fa-redo me-2"></i>Take Quiz Again
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confetti elements for animation (only shows for high scores) -->
    <?php if ($percentage >= 80 || $badge_awarded): ?>
        <div id="confetti-container"></div>
    <?php endif; ?>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        <?php if ($percentage >= 80 || $badge_awarded): ?>
        // Confetti animation for celebrations
        document.addEventListener('DOMContentLoaded', function() {
            const confettiContainer = document.getElementById('confetti-container');
            const colors = ['#f94144', '#f3722c', '#f8961e', '#f9c74f', '#90be6d', '#43aa8b', '#577590'];
            
            // Create confetti pieces
            for (let i = 0; i < 150; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.animationDelay = Math.random() * 5 + 's';
                confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                
                // Set size randomly
                const size = Math.random() * 10 + 5;
                confetti.style.width = size + 'px';
                confetti.style.height = size + 'px';
                
                // Add to container
                confettiContainer.appendChild(confetti);
            }
        });
        <?php endif; ?>
    </script>
    <style>
        @keyframes confetti {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
        #confetti-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
        }
    </style>
</body>
</html>