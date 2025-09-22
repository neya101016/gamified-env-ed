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

$quiz_id = intval($_GET['id']);

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Quiz class
$quizObj = new Quiz($db);
$quiz = $quizObj->getQuizById($quiz_id);

// If quiz not found, redirect to lessons page
if (!$quiz) {
    header('Location: lessons.php');
    exit;
}

// Get previous quiz attempts
$query = "SELECT * FROM quiz_attempts 
          WHERE user_id = :user_id AND quiz_id = :quiz_id
          ORDER BY submitted_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->bindParam(':quiz_id', $quiz_id);
$stmt->execute();
$previous_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if quiz is being submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    // Process quiz submission
    $answers = [];
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'question_') === 0) {
            $question_id = intval(str_replace('question_', '', $key));
            $option_id = intval($value);
            
            $answers[] = [
                'question_id' => $question_id,
                'option_id' => $option_id
            ];
        }
    }
    
    // Set quiz ID in the Quiz object
    $quizObj->quiz_id = $quiz_id;
    
    // Submit the quiz
    $result = $quizObj->submitAttempt($_SESSION['user_id'], $answers);
    
    if ($result) {
        // Redirect to results page with success message
        $_SESSION['quiz_result'] = $result;
        header('Location: quiz_result.php?id=' . $quiz_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz: <?php echo htmlspecialchars($quiz['title']); ?> - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .quiz-header {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .question-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 20px;
        }
        .option-label {
            display: block;
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .option-label:hover {
            background-color: #f8f9fa;
        }
        .option-input:checked + .option-label {
            background-color: #e7f1ff;
            border-color: #0d6efd;
        }
        .option-input {
            position: absolute;
            opacity: 0;
        }
        .quiz-timer {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .previous-attempts {
            max-height: 300px;
            overflow-y: auto;
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
                <li class="breadcrumb-item"><a href="lesson_details.php?id=<?php echo $quiz['lesson_id']; ?>"><?php echo htmlspecialchars($quiz['lesson_title']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Quiz</li>
            </ol>
        </nav>
        
        <div class="row">
            <div class="col-md-8">
                <!-- Quiz Header -->
                <div class="quiz-header shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
                            <p class="text-muted mb-0">
                                <i class="fas fa-star me-1"></i> Total Marks: <?php echo $quiz['total_marks']; ?>
                                <i class="fas fa-clock ms-3 me-1"></i> Time Limit: <?php echo $quiz['time_limit_minutes']; ?> minutes
                            </p>
                        </div>
                        <div class="quiz-timer" id="quiz-timer">
                            <i class="fas fa-hourglass-half me-2"></i><span id="time-remaining"><?php echo $quiz['time_limit_minutes']; ?>:00</span>
                        </div>
                    </div>
                </div>
                
                <!-- Quiz Form -->
                <form id="quiz-form" method="post" action="">
                    <?php if (isset($quiz['questions']) && !empty($quiz['questions'])): ?>
                        <?php foreach ($quiz['questions'] as $index => $question): ?>
                            <div class="card shadow-sm question-card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        Question <?php echo $index + 1; ?> 
                                        <span class="float-end text-muted"><?php echo $question['marks']; ?> mark<?php echo ($question['marks'] > 1) ? 's' : ''; ?></span>
                                    </h5>
                                    <p class="card-text"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                    
                                    <div class="options mt-3">
                                        <?php foreach ($question['options'] as $option): ?>
                                            <div class="option">
                                                <input type="radio" name="question_<?php echo $question['question_id']; ?>" 
                                                       id="option_<?php echo $option['option_id']; ?>" 
                                                       value="<?php echo $option['option_id']; ?>" 
                                                       class="option-input" required>
                                                <label for="option_<?php echo $option['option_id']; ?>" class="option-label">
                                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
                            <a href="lesson_details.php?id=<?php echo $quiz['lesson_id']; ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" name="submit_quiz" class="btn btn-primary" id="submit-quiz">Submit Quiz</button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <p>No questions are available for this quiz yet. Please try again later or contact your instructor.</p>
                            <a href="lesson_details.php?id=<?php echo $quiz['lesson_id']; ?>" class="btn btn-primary mt-2">Back to Lesson</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="col-md-4">
                <!-- Quiz Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Quiz Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Questions
                                <span class="badge bg-primary rounded-pill"><?php echo count($quiz['questions'] ?? []); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Total Marks
                                <span class="badge bg-primary rounded-pill"><?php echo $quiz['total_marks']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Time Limit
                                <span class="badge bg-primary rounded-pill"><?php echo $quiz['time_limit_minutes']; ?> min</span>
                            </li>
                        </ul>
                        
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-lightbulb me-2"></i>Tips:</h6>
                            <ul class="mb-0">
                                <li>Answer all questions before submitting</li>
                                <li>You can change your answers before submitting</li>
                                <li>Submit before the timer runs out</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Previous Attempts -->
                <?php if (!empty($previous_attempts)): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Previous Attempts</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush previous-attempts">
                            <?php foreach ($previous_attempts as $attempt): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Score: <?php echo $attempt['score']; ?> / <?php echo $quiz['total_marks']; ?></h6>
                                            <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($attempt['submitted_at'])); ?></small>
                                        </div>
                                        <span class="badge bg-<?php 
                                            $percentage = ($attempt['score'] / $quiz['total_marks']) * 100;
                                            if($percentage >= 80) echo 'success';
                                            elseif($percentage >= 60) echo 'primary';
                                            elseif($percentage >= 40) echo 'warning text-dark';
                                            else echo 'danger';
                                        ?>">
                                            <?php echo round($percentage); ?>%
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Leave Quiz Confirmation Modal -->
    <div class="modal fade" id="leaveQuizModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Leave Quiz?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to leave this quiz? Your progress will not be saved.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Stay on Quiz</button>
                    <a href="lesson_details.php?id=<?php echo $quiz['lesson_id']; ?>" class="btn btn-primary" id="confirm-leave">Leave Quiz</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Time's Up Modal -->
    <div class="modal fade" id="timesUpModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Time's Up!</h5>
                </div>
                <div class="modal-body">
                    <p>Your time for this quiz has expired. Your answers will be submitted automatically.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="submit-on-timeout">Submit Answers</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Quiz timer
            var timeLimit = <?php echo $quiz['time_limit_minutes']; ?> * 60; // Convert minutes to seconds
            var timer = timeLimit;
            var timerInterval;
            
            function startTimer() {
                timerInterval = setInterval(function() {
                    timer--;
                    
                    var minutes = Math.floor(timer / 60);
                    var seconds = timer % 60;
                    
                    // Format time as MM:SS
                    var timeString = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');
                    $('#time-remaining').text(timeString);
                    
                    // Change color as time decreases
                    if (timer <= 60) { // Last minute
                        $('#quiz-timer').addClass('text-danger').removeClass('text-warning');
                    } else if (timer <= 300) { // Last 5 minutes
                        $('#quiz-timer').addClass('text-warning');
                    }
                    
                    // Show time's up modal when timer reaches zero
                    if (timer <= 0) {
                        clearInterval(timerInterval);
                        $('#timesUpModal').modal('show');
                    }
                }, 1000);
            }
            
            // Start the timer when the page loads
            startTimer();
            
            // Confirm before leaving the page
            window.onbeforeunload = function() {
                // Don't prompt if submitting the form
                if (!isSubmitting) {
                    return "Are you sure you want to leave? Your quiz progress will be lost.";
                }
            };
            
            var isSubmitting = false;
            
            // Set isSubmitting when form is submitted
            $('#quiz-form').on('submit', function() {
                isSubmitting = true;
            });
            
            // Leave quiz button
            $('a[href="lesson_details.php"]').on('click', function(e) {
                e.preventDefault();
                $('#leaveQuizModal').modal('show');
            });
            
            // Time's up submission
            $('#submit-on-timeout').on('click', function() {
                isSubmitting = true;
                $('#quiz-form').submit();
            });
            
            // Form validation before submission
            $('#quiz-form').on('submit', function(e) {
                var allQuestionsAnswered = true;
                
                // Check if all questions are answered
                $('input[type="radio"]').each(function() {
                    var name = $(this).attr('name');
                    if ($('input[name="' + name + '"]:checked').length === 0) {
                        allQuestionsAnswered = false;
                    }
                });
                
                if (!allQuestionsAnswered && !isSubmitting) {
                    e.preventDefault();
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Incomplete Quiz',
                        text: 'You have not answered all questions. Are you sure you want to submit?',
                        showCancelButton: true,
                        confirmButtonText: 'Submit Anyway',
                        cancelButtonText: 'Continue Quiz'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            isSubmitting = true;
                            $('#quiz-form').submit();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>