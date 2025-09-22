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

// Get student info
$user = new User($db);
$student = $user->getUserById($_SESSION['user_id']);

// Get badges
$badge = new Badge($db);
$badges = $badge->getUserBadges($_SESSION['user_id']);

// Get total eco points
$query = "SELECT SUM(points) as total_points FROM eco_points WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$points = $stmt->fetch(PDO::FETCH_ASSOC);

// Get enrolled challenges
$query = "SELECT c.*, uc.status, uc.enrolled_at, uc.completed_at, uc.user_challenge_id,
          v.name as verification_type
          FROM user_challenges uc
          JOIN challenges c ON uc.challenge_id = c.challenge_id
          JOIN verification_types v ON c.verification_type_id = v.verification_type_id
          WHERE uc.user_id = :user_id
          ORDER BY uc.enrolled_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent quiz attempts
$query = "SELECT qa.*, q.title as quiz_title, q.total_marks, l.title as lesson_title
          FROM quiz_attempts qa
          JOIN quizzes q ON qa.quiz_id = q.quiz_id
          JOIN lessons l ON q.lesson_id = l.lesson_id
          WHERE qa.user_id = :user_id
          ORDER BY qa.submitted_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available lessons
$lesson = new Lesson($db);
$lessons = $lesson->getAllLessons();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge-card {
            border-radius: 10px;
            transition: transform 0.3s;
        }
        .badge-card:hover {
            transform: translateY(-5px);
        }
        .badge-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .challenge-card {
            border-left: 5px solid #28a745;
        }
        .challenge-card.completed {
            border-left-color: #ffc107;
        }
        .challenge-card.verified {
            border-left-color: #17a2b8;
        }
        .challenge-card.rejected {
            border-left-color: #dc3545;
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lessons.php">Lessons</a>
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
        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <h4 class="card-title">Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h4>
                                <p class="card-text text-muted">Continue your eco-friendly journey and earn points!</p>
                            </div>
                            <div class="ms-auto text-center">
                                <h3 class="text-primary mb-0"><?php echo number_format($points['total_points'] ?? 0); ?></h3>
                                <p class="mb-0">Eco-Points</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats & Badges -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Your Stats</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Lessons Completed
                                <span class="badge bg-primary rounded-pill">
                                    <?php 
                                    $query = "SELECT COUNT(DISTINCT qa.quiz_id) as count FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.quiz_id WHERE qa.user_id = :user_id";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $result['count'] ?? 0;
                                    ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Challenges Completed
                                <span class="badge bg-success rounded-pill">
                                    <?php 
                                    $query = "SELECT COUNT(*) as count FROM user_challenges WHERE user_id = :user_id AND status IN ('verified')";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $result['count'] ?? 0;
                                    ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Badges Earned
                                <span class="badge bg-info rounded-pill"><?php echo count($badges); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Global Rank
                                <span class="badge bg-warning text-dark rounded-pill">
                                    <?php 
                                    $query = "SELECT user_id, @rank := @rank + 1 as rank
                                              FROM (
                                                SELECT u.user_id, SUM(ep.points) as total_points
                                                FROM users u
                                                LEFT JOIN eco_points ep ON u.user_id = ep.user_id
                                                GROUP BY u.user_id
                                                ORDER BY total_points DESC
                                              ) ranked_users,
                                              (SELECT @rank := 0) r";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute();
                                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    $rank = 'N/A';
                                    foreach($results as $result) {
                                        if($result['user_id'] == $_SESSION['user_id']) {
                                            $rank = '#' . $result['rank'];
                                            break;
                                        }
                                    }
                                    echo $rank;
                                    ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-award me-2"></i>Your Latest Badges</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if(count($badges) > 0): ?>
                                <?php foreach(array_slice($badges, 0, 3) as $badge): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card badge-card text-center shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="badge-icon">
                                                <?php
                                                // Display different icons based on badge name
                                                $icon = 'fas fa-award'; // Default
                                                switch($badge['name']) {
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
                                            <h6 class="card-title"><?php echo htmlspecialchars($badge['name']); ?></h6>
                                            <p class="card-text small text-muted">Earned on <?php echo date('M d, Y', strtotime($badge['awarded_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if(count($badges) > 3): ?>
                                <div class="col-md-12 text-center mt-2">
                                    <a href="badges.php" class="btn btn-sm btn-outline-primary">View All Badges</a>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-3">
                                    <p class="text-muted">You haven't earned any badges yet. Complete lessons and challenges to earn badges!</p>
                                    <a href="challenges.php" class="btn btn-sm btn-outline-primary">Find Challenges</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Active Challenges -->
        <div class="row mb-4">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-tasks me-2"></i>Your Challenges</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($challenges) > 0): ?>
                            <?php foreach(array_slice($challenges, 0, 3) as $challenge): ?>
                            <div class="card mb-3 challenge-card <?php echo $challenge['status']; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title"><?php echo htmlspecialchars($challenge['title']); ?></h5>
                                        <span class="badge bg-<?php 
                                            switch($challenge['status']) {
                                                case 'pending': echo 'secondary'; break;
                                                case 'completed': echo 'warning text-dark'; break;
                                                case 'verified': echo 'success'; break;
                                                case 'rejected': echo 'danger'; break;
                                            }
                                        ?>">
                                            <?php echo ucfirst($challenge['status']); ?>
                                        </span>
                                    </div>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($challenge['description'], 0, 150)) . (strlen($challenge['description']) > 150 ? '...' : ''); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">Enrolled: <?php echo date('M d, Y', strtotime($challenge['enrolled_at'])); ?></small>
                                            <?php if($challenge['completed_at']): ?>
                                            <small class="text-muted ms-3">Completed: <?php echo date('M d, Y', strtotime($challenge['completed_at'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if($challenge['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-outline-primary submit-proof-btn" 
                                                data-id="<?php echo $challenge['user_challenge_id']; ?>"
                                                data-title="<?php echo htmlspecialchars($challenge['title']); ?>"
                                                data-type="<?php echo $challenge['verification_type']; ?>">
                                            Submit Proof
                                        </button>
                                        <?php elseif($challenge['status'] == 'verified'): ?>
                                        <span class="text-success"><i class="fas fa-check-circle me-1"></i> +<?php echo $challenge['eco_points']; ?> points</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if(count($challenges) > 3): ?>
                            <div class="text-center mt-3">
                                <a href="challenges.php" class="btn btn-outline-primary">View All Challenges</a>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">You're not enrolled in any challenges yet.</p>
                                <a href="challenges.php" class="btn btn-outline-primary">Find Challenges</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Quizzes & Recommended Lessons -->
        <div class="row">
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Recent Quiz Attempts</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($quizzes) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach($quizzes as $quiz): ?>
                                <a href="lesson.php?id=<?php echo $quiz['quiz_id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($quiz['quiz_title']); ?></h6>
                                            <p class="mb-1 small text-muted"><?php echo htmlspecialchars($quiz['lesson_title']); ?></p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?php 
                                                $percentage = ($quiz['score'] / $quiz['total_marks']) * 100;
                                                if($percentage >= 80) echo 'success';
                                                elseif($percentage >= 60) echo 'primary';
                                                elseif($percentage >= 40) echo 'warning text-dark';
                                                else echo 'danger';
                                            ?>">
                                                <?php echo $quiz['score']; ?>/<?php echo $quiz['total_marks']; ?>
                                            </span>
                                            <p class="mb-0 small text-muted"><?php echo date('M d, Y', strtotime($quiz['submitted_at'])); ?></p>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">You haven't taken any quizzes yet.</p>
                                <a href="lessons.php" class="btn btn-outline-primary">Browse Lessons</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-book-open me-2"></i>Recommended Lessons</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($lessons) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach(array_slice($lessons, 0, 5) as $lesson): ?>
                                <a href="lesson.php?id=<?php echo $lesson['lesson_id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($lesson['title']); ?></h6>
                                            <p class="mb-1 small text-muted">
                                                <?php echo htmlspecialchars(substr($lesson['summary'], 0, 100)) . (strlen($lesson['summary']) > 100 ? '...' : ''); ?>
                                            </p>
                                        </div>
                                        <span class="badge bg-<?php 
                                            switch($lesson['difficulty']) {
                                                case 1: echo 'success'; break;
                                                case 2: echo 'primary'; break;
                                                case 3: echo 'warning text-dark'; break;
                                                case 4: 
                                                case 5: echo 'danger'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>">
                                            <?php 
                                            $difficulty_labels = [
                                                1 => 'Beginner',
                                                2 => 'Easy',
                                                3 => 'Intermediate',
                                                4 => 'Advanced',
                                                5 => 'Expert'
                                            ];
                                            echo $difficulty_labels[$lesson['difficulty']] ?? 'Unknown';
                                            ?>
                                        </span>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="lessons.php" class="btn btn-outline-primary">View All Lessons</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">No lessons available yet. Check back soon!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Submit Proof Modal -->
    <div class="modal fade" id="submitProofModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Challenge Proof</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="proofForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="user_challenge_id" name="user_challenge_id">
                        
                        <div class="mb-3">
                            <label for="proof_file" class="form-label">Upload Proof</label>
                            <input type="file" class="form-control" id="proof_file" name="proof_file" accept="image/*" required>
                            <div class="form-text">Please upload a clear photo as proof of your challenge completion.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Proof</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Session Timeout Modal -->
    <div class="modal fade" id="sessionTimeoutModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Session Timeout</h5>
                </div>
                <div class="modal-body">
                    <p>Your session is about to expire due to inactivity.</p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                    </div>
                    <p class="mt-3">You will be logged out in <span id="countdown">30</span> seconds.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="stayLoggedInBtn">Stay Logged In</button>
                    <button type="button" class="btn btn-secondary" id="logoutNowBtn">Logout Now</button>
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
            // Submit proof modal
            $('.submit-proof-btn').on('click', function() {
                const id = $(this).data('id');
                const title = $(this).data('title');
                const type = $(this).data('type');
                
                $('#user_challenge_id').val(id);
                $('.modal-title').text('Submit Proof: ' + title);
                
                $('#submitProofModal').modal('show');
            });
            
            // Submit proof form
            $('#proofForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                $.ajax({
                    url: '../api/index.php?action=submit_challenge_proof',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        $('#submitProofModal').modal('hide');
                        
                        if(response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Proof Submitted!',
                                text: 'Your challenge proof has been submitted for verification.',
                                confirmButtonText: 'OK'
                            }).then(function() {
                                // Reload page to update challenge status
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Submission Failed',
                                text: response.message || 'An error occurred while submitting your proof.',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        $('#submitProofModal').modal('hide');
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'An error occurred while connecting to the server. Please try again later.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
            
            // Session timeout handling
            let timeoutTimer;
            let countdownTimer;
            let countdownValue;
            
            function startSessionTimer() {
                clearTimeout(timeoutTimer);
                timeoutTimer = setTimeout(showTimeoutWarning, 25 * 60 * 1000); // 25 minutes
            }
            
            function showTimeoutWarning() {
                $('#sessionTimeoutModal').modal('show');
                countdownValue = 30;
                $('#countdown').text(countdownValue);
                
                clearInterval(countdownTimer);
                countdownTimer = setInterval(function() {
                    countdownValue--;
                    $('#countdown').text(countdownValue);
                    
                    // Update progress bar
                    const percentage = (countdownValue / 30) * 100;
                    $('.progress-bar').css('width', percentage + '%');
                    
                    if(countdownValue <= 0) {
                        clearInterval(countdownTimer);
                        window.location.href = '../logout.php';
                    }
                }, 1000);
            }
            
            $('#stayLoggedInBtn').on('click', function() {
                clearInterval(countdownTimer);
                $('#sessionTimeoutModal').modal('hide');
                
                // Reset session timer by making an AJAX call
                $.ajax({
                    url: '../api/index.php?action=check_session',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'active') {
                            startSessionTimer();
                        } else {
                            window.location.href = '../login.php';
                        }
                    }
                });
            });
            
            $('#logoutNowBtn').on('click', function() {
                clearInterval(countdownTimer);
                window.location.href = '../logout.php';
            });
            
            // Check for activity to reset timer
            $(document).on('click keypress', function() {
                startSessionTimer();
            });
            
            // Start the initial timer
            startSessionTimer();
            
            // Periodically check session status
            setInterval(function() {
                $.ajax({
                    url: '../api/index.php?action=check_session',
                    type: 'GET',
                    dataType: 'json'
                });
            }, 5 * 60 * 1000); // Every 5 minutes
        });
    </script>
</body>
</html>