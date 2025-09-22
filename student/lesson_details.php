<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and has student role
requireRole('student');

// Check if lesson ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: lessons.php');
    exit;
}

$lesson_id = intval($_GET['id']);

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get lesson details with contents
$lesson = new Lesson($db);
$lesson_data = $lesson->getLessonById($lesson_id);

// If lesson not found, redirect to lessons page
if (!$lesson_data) {
    header('Location: lessons.php');
    exit;
}

// Check if student has completed this lesson (has quiz attempt)
$query = "SELECT qa.*, q.total_marks 
          FROM quiz_attempts qa
          JOIN quizzes q ON qa.quiz_id = q.quiz_id
          WHERE qa.user_id = :user_id 
          AND q.lesson_id = :lesson_id
          ORDER BY qa.submitted_at DESC
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->bindParam(':lesson_id', $lesson_id);
$stmt->execute();
$quiz_attempt = $stmt->fetch(PDO::FETCH_ASSOC);

// Get the quiz for this lesson
$query = "SELECT quiz_id, title, total_marks, time_limit_minutes
          FROM quizzes 
          WHERE lesson_id = :lesson_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':lesson_id', $lesson_id);
$stmt->execute();
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

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
    <title><?php echo htmlspecialchars($lesson_data['title']); ?> - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .lesson-content {
            line-height: 1.7;
        }
        .lesson-navigation {
            position: sticky;
            top: 20px;
        }
        .nav-link.active {
            background-color: #f8f9fa;
            border-left: 3px solid #0d6efd;
            font-weight: bold;
        }
        .content-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .content-section:last-child {
            border-bottom: none;
        }
        .quiz-card {
            border-left: 5px solid #0d6efd;
        }
        .embed-responsive {
            position: relative;
            display: block;
            width: 100%;
            padding: 0;
            overflow: hidden;
        }
        .embed-responsive::before {
            display: block;
            content: "";
            padding-top: 56.25%;
        }
        .embed-responsive iframe {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
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
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="lessons.php">Lessons</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($lesson_data['title']); ?></li>
            </ol>
        </nav>
        
        <!-- Lesson Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-md-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="card-title"><?php echo htmlspecialchars($lesson_data['title']); ?></h2>
                                <p class="text-muted mb-md-0">
                                    <span class="badge bg-<?php 
                                        switch($lesson_data['difficulty']) {
                                            case 1: echo 'success'; break;
                                            case 2: echo 'primary'; break;
                                            case 3: echo 'warning text-dark'; break;
                                            case 4: 
                                            case 5: echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?> me-2">
                                        <?php echo $difficulty_labels[$lesson_data['difficulty']] ?? 'Unknown'; ?>
                                    </span>
                                    <i class="fas fa-user me-1"></i> Created by <?php echo htmlspecialchars($lesson_data['creator_name'] ?? 'Admin'); ?>
                                </p>
                            </div>
                            <?php if ($quiz_attempt): ?>
                                <div class="text-center">
                                    <span class="badge bg-<?php 
                                        $percentage = ($quiz_attempt['score'] / $quiz_attempt['total_marks']) * 100;
                                        if($percentage >= 80) echo 'success';
                                        elseif($percentage >= 60) echo 'primary';
                                        elseif($percentage >= 40) echo 'warning text-dark';
                                        else echo 'danger';
                                    ?> fs-6 p-2">
                                        Quiz Score: <?php echo $quiz_attempt['score']; ?>/<?php echo $quiz_attempt['total_marks']; ?>
                                        (<?php echo round($percentage); ?>%)
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lesson Content -->
        <div class="row">
            <!-- Navigation Sidebar -->
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="lesson-navigation">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Contents</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush" id="lesson-nav">
                                <a class="list-group-item list-group-item-action active" href="#overview">Overview</a>
                                
                                <?php if(isset($lesson_data['contents']) && !empty($lesson_data['contents'])): ?>
                                    <?php foreach($lesson_data['contents'] as $index => $content): ?>
                                        <a class="list-group-item list-group-item-action" href="#section-<?php echo $index + 1; ?>">
                                            <?php echo htmlspecialchars($content['title']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if($quiz): ?>
                                    <a class="list-group-item list-group-item-action" href="#quiz">
                                        <i class="fas fa-question-circle me-1"></i> Quiz
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($quiz): ?>
                        <div class="mt-3">
                            <a href="quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-primary w-100">
                                <?php echo $quiz_attempt ? 'Retake Quiz' : 'Take Quiz'; ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-body lesson-content">
                        <!-- Overview Section -->
                        <div class="content-section" id="overview">
                            <h3>Overview</h3>
                            <p><?php echo htmlspecialchars($lesson_data['summary']); ?></p>
                        </div>
                        
                        <!-- Content Sections -->
                        <?php if(isset($lesson_data['contents']) && !empty($lesson_data['contents'])): ?>
                            <?php foreach($lesson_data['contents'] as $index => $content): ?>
                                <div class="content-section" id="section-<?php echo $index + 1; ?>">
                                    <h3><?php echo htmlspecialchars($content['title']); ?></h3>
                                    
                                    <?php 
                                    // Display content based on type
                                    switch($content['content_type_name']) {
                                        case 'text':
                                            echo htmlspecialchars_decode($content['body']);
                                            break;
                                        case 'image':
                                            echo '<img src="' . htmlspecialchars($content['external_url']) . '" alt="' . htmlspecialchars($content['title']) . '" class="img-fluid mb-3">';
                                            if (!empty($content['body'])) {
                                                echo '<p>' . htmlspecialchars_decode($content['body']) . '</p>';
                                            }
                                            break;
                                        case 'video':
                                            // Extract video ID for embedding
                                            $video_url = $content['external_url'];
                                            $video_id = '';
                                            
                                            if (strpos($video_url, 'youtube.com') !== false) {
                                                parse_str(parse_url($video_url, PHP_URL_QUERY), $params);
                                                $video_id = $params['v'] ?? '';
                                            } elseif (strpos($video_url, 'youtu.be') !== false) {
                                                $video_id = basename(parse_url($video_url, PHP_URL_PATH));
                                            }
                                            
                                            if (!empty($video_id)) {
                                                echo '<div class="embed-responsive mb-3">';
                                                echo '<iframe src="https://www.youtube.com/embed/' . $video_id . '" allowfullscreen></iframe>';
                                                echo '</div>';
                                            } else {
                                                echo '<div class="alert alert-warning">Video could not be embedded. <a href="' . htmlspecialchars($video_url) . '" target="_blank">Watch video</a></div>';
                                            }
                                            
                                            if (!empty($content['body'])) {
                                                echo '<p>' . htmlspecialchars_decode($content['body']) . '</p>';
                                            }
                                            break;
                                        case 'link':
                                            echo '<p>' . htmlspecialchars_decode($content['body']) . '</p>';
                                            echo '<p><a href="' . htmlspecialchars($content['external_url']) . '" target="_blank" class="btn btn-outline-primary"><i class="fas fa-external-link-alt me-2"></i>Visit Resource</a></p>';
                                            break;
                                        default:
                                            echo htmlspecialchars_decode($content['body']);
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <p>No content sections available for this lesson yet.</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Quiz Section -->
                        <?php if($quiz): ?>
                            <div class="content-section" id="quiz">
                                <h3>Quiz: <?php echo htmlspecialchars($quiz['title']); ?></h3>
                                <div class="card quiz-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h5 class="card-title mb-0">Test Your Knowledge</h5>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-clock me-1"></i> <?php echo $quiz['time_limit_minutes']; ?> minutes
                                                    <i class="fas fa-star ms-3 me-1"></i> <?php echo $quiz['total_marks']; ?> marks
                                                </p>
                                            </div>
                                            <?php if($quiz_attempt): ?>
                                                <div>
                                                    <span class="badge bg-<?php 
                                                        $percentage = ($quiz_attempt['score'] / $quiz_attempt['total_marks']) * 100;
                                                        if($percentage >= 80) echo 'success';
                                                        elseif($percentage >= 60) echo 'primary';
                                                        elseif($percentage >= 40) echo 'warning text-dark';
                                                        else echo 'danger';
                                                    ?>">
                                                        Last Score: <?php echo $quiz_attempt['score']; ?>/<?php echo $quiz_attempt['total_marks']; ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <p class="card-text">Complete this quiz to test your understanding of the lesson and earn eco-points!</p>
                                        <a href="quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-primary">
                                            <?php echo $quiz_attempt ? 'Retake Quiz' : 'Take Quiz'; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
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
            // Smooth scrolling for navigation links
            $('#lesson-nav a').on('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links
                $('#lesson-nav a').removeClass('active');
                
                // Add active class to clicked link
                $(this).addClass('active');
                
                // Get the target section
                var targetId = $(this).attr('href');
                var $target = $(targetId);
                
                // Scroll to target section with offset for navbar
                $('html, body').animate({
                    scrollTop: $target.offset().top - 20
                }, 300);
            });
            
            // Update active navigation item on scroll
            $(window).on('scroll', function() {
                var scrollPosition = $(window).scrollTop();
                
                // Check each section
                $('.content-section').each(function() {
                    var currentSection = $(this);
                    var sectionTop = currentSection.offset().top - 100;
                    var sectionBottom = sectionTop + currentSection.outerHeight();
                    
                    if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                        var id = currentSection.attr('id');
                        var $navItem = $('#lesson-nav a[href="#' + id + '"]');
                        
                        if (!$navItem.hasClass('active')) {
                            $('#lesson-nav a').removeClass('active');
                            $navItem.addClass('active');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>