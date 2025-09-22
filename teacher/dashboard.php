<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and has teacher role
requireRole('teacher');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get teacher info
$user = new User($db);
$teacher = $user->getUserById($_SESSION['user_id']);

// Get pending verifications
$query = "SELECT cp.*, uc.user_id, uc.challenge_id, uc.status, 
          u.name as student_name, c.title as challenge_title
          FROM challenge_proofs cp
          JOIN user_challenges uc ON cp.user_challenge_id = uc.user_challenge_id
          JOIN users u ON uc.user_id = u.user_id
          JOIN challenges c ON uc.challenge_id = c.challenge_id
          WHERE cp.verdict = 'pending'
          AND u.school_id = :school_id
          ORDER BY cp.submitted_at ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':school_id', $teacher['school_id']);
$stmt->execute();
$pending_verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent lessons
$query = "SELECT l.*, u.name as creator_name
          FROM lessons l
          LEFT JOIN users u ON l.created_by = u.user_id
          ORDER BY l.created_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get school stats
$query = "SELECT 
          (SELECT COUNT(*) FROM users WHERE school_id = :school_id AND role_id = 1) as student_count,
          (SELECT SUM(points) FROM eco_points ep JOIN users u ON ep.user_id = u.user_id WHERE u.school_id = :school_id) as total_points,
          (SELECT COUNT(*) FROM user_challenges uc JOIN users u ON uc.user_id = u.user_id WHERE u.school_id = :school_id AND uc.status = 'verified') as completed_challenges,
          (SELECT COUNT(*) FROM user_badges ub JOIN users u ON ub.user_id = u.user_id WHERE u.school_id = :school_id) as earned_badges";
$stmt = $db->prepare($query);
$stmt->bindParam(':school_id', $teacher['school_id']);
$stmt->execute();
$school_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get leaderboard for school
$leaderboard = new Leaderboard($db);
$school_leaderboard = $leaderboard->getSchoolLeaderboard($teacher['school_id'], 'all', 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
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
                        <a class="nav-link" href="lessons.php">Manage Lessons</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="challenges.php">Verify Challenges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Reports</a>
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
        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <h4 class="card-title">Welcome, <?php echo htmlspecialchars($teacher['name']); ?>!</h4>
                                <p class="card-text text-muted">Manage lessons, verify challenges, and track student progress.</p>
                            </div>
                            <div class="ms-auto text-center">
                                <h3 class="text-primary mb-0"><?php echo $teacher['school_name']; ?></h3>
                                <p class="mb-0">School</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- School Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="display-4 text-primary mb-2"><?php echo number_format($school_stats['student_count'] ?? 0); ?></div>
                        <h5 class="card-title">Students</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="display-4 text-success mb-2"><?php echo number_format($school_stats['total_points'] ?? 0); ?></div>
                        <h5 class="card-title">Eco-Points</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="display-4 text-info mb-2"><?php echo number_format($school_stats['completed_challenges'] ?? 0); ?></div>
                        <h5 class="card-title">Challenges</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="display-4 text-warning mb-2"><?php echo number_format($school_stats['earned_badges'] ?? 0); ?></div>
                        <h5 class="card-title">Badges</h5>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pending Verifications & Leaderboard -->
        <div class="row mb-4">
            <div class="col-md-7 mb-4 mb-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-clipboard-check me-2"></i>Pending Challenge Verifications</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($pending_verifications) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Challenge</th>
                                            <th>Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($pending_verifications as $verification): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($verification['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($verification['challenge_title']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($verification['submitted_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary view-proof-btn" 
                                                        data-id="<?php echo $verification['proof_id']; ?>"
                                                        data-url="<?php echo $verification['proof_url']; ?>"
                                                        data-student="<?php echo htmlspecialchars($verification['student_name']); ?>"
                                                        data-challenge="<?php echo htmlspecialchars($verification['challenge_title']); ?>"
                                                        data-metadata='<?php echo $verification['metadata']; ?>'>
                                                    Review
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if(count($pending_verifications) > 5): ?>
                            <div class="text-center mt-3">
                                <a href="challenges.php" class="btn btn-outline-primary">View All Pending Verifications</a>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">No pending challenge verifications.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-trophy me-2"></i>School Leaderboard</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($school_leaderboard) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach($school_leaderboard as $index => $student): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <span class="badge rounded-pill bg-<?php 
                                                if($index === 0) echo 'warning text-dark';
                                                elseif($index === 1) echo 'secondary';
                                                elseif($index === 2) echo 'danger';
                                                else echo 'primary';
                                            ?> fs-5">
                                                <?php echo $index + 1; ?>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($student['name']); ?></h6>
                                            <small class="text-muted"><?php echo $student['badge_count']; ?> badges</small>
                                        </div>
                                        <div class="text-end">
                                            <h5 class="mb-0 text-primary"><?php echo number_format($student['total_points']); ?></h5>
                                            <small class="text-muted">points</small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="reports.php" class="btn btn-outline-primary">View Full Report</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">No student data available yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Lessons & Quick Actions -->
        <div class="row">
            <div class="col-md-7 mb-4 mb-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-book me-2"></i>Recent Lessons</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($recent_lessons) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach($recent_lessons as $lesson): ?>
                                <a href="edit_lesson.php?id=<?php echo $lesson['lesson_id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($lesson['title']); ?></h6>
                                            <p class="mb-1 small text-muted">
                                                <?php echo htmlspecialchars(substr($lesson['summary'], 0, 100)) . (strlen($lesson['summary']) > 100 ? '...' : ''); ?>
                                            </p>
                                            <small class="text-muted">
                                                Created by: <?php echo htmlspecialchars($lesson['creator_name']); ?> | 
                                                <?php echo date('M d, Y', strtotime($lesson['created_at'])); ?>
                                            </small>
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
                                <a href="lessons.php" class="btn btn-outline-primary">Manage All Lessons</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">No lessons available yet.</p>
                                <a href="create_lesson.php" class="btn btn-outline-primary">Create New Lesson</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="create_lesson.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Create New Lesson
                            </a>
                            <a href="create_challenge.php" class="btn btn-success">
                                <i class="fas fa-tasks me-2"></i>Create New Challenge
                            </a>
                            <a href="export_data.php" class="btn btn-info">
                                <i class="fas fa-file-export me-2"></i>Export Student Data
                            </a>
                            <a href="student_progress.php" class="btn btn-warning">
                                <i class="fas fa-chart-line me-2"></i>View Student Progress
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Proof Review Modal -->
    <div class="modal fade" id="proofReviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Challenge Proof</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Student:</strong> <span id="studentName"></span></p>
                            <p><strong>Challenge:</strong> <span id="challengeName"></span></p>
                            <p><strong>Description:</strong> <span id="proofDescription"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Submitted:</strong> <span id="submissionDate"></span></p>
                            <p><strong>File Type:</strong> <span id="fileType"></span></p>
                            <p><strong>File Size:</strong> <span id="fileSize"></span></p>
                        </div>
                    </div>
                    
                    <div class="text-center mb-3">
                        <img id="proofImage" class="img-fluid rounded" alt="Challenge Proof">
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="proofId">
                    <button type="button" class="btn btn-danger reject-btn">Reject</button>
                    <button type="button" class="btn btn-success approve-btn">Approve</button>
                </div>
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
            // View proof details
            $('.view-proof-btn').on('click', function() {
                const id = $(this).data('id');
                const url = $(this).data('url');
                const student = $(this).data('student');
                const challenge = $(this).data('challenge');
                const metadata = $(this).data('metadata');
                
                $('#proofId').val(id);
                $('#studentName').text(student);
                $('#challengeName').text(challenge);
                $('#proofImage').attr('src', url);
                
                // Parse metadata JSON
                if(metadata) {
                    const meta = JSON.parse(metadata);
                    $('#proofDescription').text(meta.description || 'No description provided');
                    $('#submissionDate').text(meta.submission_date);
                    $('#fileType').text(meta.file_type);
                    $('#fileSize').text(formatFileSize(meta.file_size));
                }
                
                $('#proofReviewModal').modal('show');
            });
            
            // Format file size
            function formatFileSize(bytes) {
                if(bytes < 1024) return bytes + ' bytes';
                if(bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
                return (bytes / 1048576).toFixed(2) + ' MB';
            }
            
            // Approve proof
            $('.approve-btn').on('click', function() {
                const proofId = $('#proofId').val();
                
                Swal.fire({
                    title: 'Approve Challenge?',
                    text: 'This will award eco-points to the student. Continue?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Approve',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if(result.isConfirmed) {
                        verifyProof(proofId, 'approved');
                    }
                });
            });
            
            // Reject proof
            $('.reject-btn').on('click', function() {
                const proofId = $('#proofId').val();
                
                Swal.fire({
                    title: 'Reject Challenge?',
                    text: 'The student will need to submit a new proof. Continue?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Reject',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if(result.isConfirmed) {
                        verifyProof(proofId, 'rejected');
                    }
                });
            });
            
            // Verify proof API call
            function verifyProof(proofId, verdict) {
                $.ajax({
                    url: '../api/index.php?action=verify_challenge',
                    type: 'POST',
                    data: {
                        proof_id: proofId,
                        verdict: verdict
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#proofReviewModal').modal('hide');
                        
                        if(response.status === 'success') {
                            let title, text, icon;
                            
                            if(verdict === 'approved') {
                                title = 'Challenge Approved';
                                text = 'The student has been awarded eco-points for completing this challenge.';
                                icon = 'success';
                                
                                // Check if badge was awarded
                                if(response.badge_awarded) {
                                    text += ` The student has also earned the "${response.badge_name}" badge!`;
                                }
                            } else {
                                title = 'Challenge Rejected';
                                text = 'The student will need to submit a new proof for this challenge.';
                                icon = 'info';
                            }
                            
                            Swal.fire({
                                title: title,
                                text: text,
                                icon: icon,
                                confirmButtonText: 'OK'
                            }).then(function() {
                                // Reload page to update list
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: 'Failed to process the verification. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        $('#proofReviewModal').modal('hide');
                        
                        Swal.fire({
                            title: 'Server Error',
                            text: 'An error occurred while connecting to the server. Please try again later.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
            
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