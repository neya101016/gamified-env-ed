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

// Filtering options
$difficulty = isset($_GET['difficulty']) ? intval($_GET['difficulty']) : null;

// Get all lessons
$lesson = new Lesson($db);
$lessons = $lesson->getAllLessons();

// Filter by difficulty if specified
if ($difficulty !== null) {
    $lessons = array_filter($lessons, function($lesson) use ($difficulty) {
        return $lesson['difficulty'] == $difficulty;
    });
}

// Get completed lessons by the student
$query = "SELECT DISTINCT l.lesson_id
          FROM quiz_attempts qa
          JOIN quizzes q ON qa.quiz_id = q.quiz_id
          JOIN lessons l ON q.lesson_id = l.lesson_id
          WHERE qa.user_id = :user_id AND qa.score > 0";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$completed_lessons = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'lesson_id');

// Difficulty levels
$difficulty_labels = [
    1 => 'Beginner',
    2 => 'Easy',
    3 => 'Intermediate',
    4 => 'Advanced',
    5 => 'Expert'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lessons - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .lesson-card {
            transition: transform 0.3s;
            height: 100%;
        }
        .lesson-card:hover {
            transform: translateY(-5px);
        }
        .card-footer {
            background-color: rgba(0, 0, 0, 0.03);
        }
        .completed-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
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
    <div class="container py-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-book-open me-2"></i>Environmental Lessons</h2>
                <p class="text-muted">Explore our collection of environmental education lessons. Complete lessons to earn eco-points and badges!</p>
            </div>
        </div>
        
        <!-- Filter Options -->
        <div class="row mb-4">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-filter me-2"></i>Filter Lessons</h5>
                        <form action="" method="get" class="row g-3">
                            <div class="col-md-6">
                                <label for="difficulty" class="form-label">Difficulty Level</label>
                                <select class="form-select" id="difficulty" name="difficulty">
                                    <option value="">All Levels</option>
                                    <?php foreach($difficulty_labels as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($difficulty === $key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div>
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    <a href="lessons.php" class="btn btn-outline-secondary ms-2">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lessons Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if(count($lessons) > 0): ?>
                <?php foreach($lessons as $lesson): ?>
                <div class="col">
                    <div class="card lesson-card shadow-sm">
                        <?php if(in_array($lesson['lesson_id'], $completed_lessons)): ?>
                        <div class="completed-badge">
                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Completed</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($lesson['title']); ?></h5>
                            <span class="badge bg-<?php 
                                switch($lesson['difficulty']) {
                                    case 1: echo 'success'; break;
                                    case 2: echo 'primary'; break;
                                    case 3: echo 'warning text-dark'; break;
                                    case 4: 
                                    case 5: echo 'danger'; break;
                                    default: echo 'secondary';
                                }
                            ?> mb-2">
                                <?php echo $difficulty_labels[$lesson['difficulty']] ?? 'Unknown'; ?>
                            </span>
                            <p class="card-text">
                                <?php echo htmlspecialchars(substr($lesson['summary'], 0, 150)) . (strlen($lesson['summary']) > 150 ? '...' : ''); ?>
                            </p>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Created by <?php echo htmlspecialchars($lesson['creator_name'] ?? 'Admin'); ?>
                            </small>
                            <a href="lesson_details.php?id=<?php echo $lesson['lesson_id']; ?>" class="btn btn-sm btn-primary">
                                <?php echo in_array($lesson['lesson_id'], $completed_lessons) ? 'Review Lesson' : 'Start Lesson'; ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <p class="mb-0 text-center">No lessons found matching your filters. Please try different filters or check back later.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination (for future implementation) -->
        <!-- <div class="row mt-4">
            <div class="col">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div> -->
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Add any custom JavaScript here
    </script>
</body>
</html>