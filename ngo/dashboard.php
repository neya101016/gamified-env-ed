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

// Get NGO info
$user = new User($db);
$ngo = $user->getUserById($_SESSION['user_id']);

// Get active challenges created by this NGO
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM user_challenges uc WHERE uc.challenge_id = c.challenge_id) as participant_count,
          (SELECT COUNT(*) FROM user_challenges uc WHERE uc.challenge_id = c.challenge_id AND uc.status = 'verified') as completed_count
          FROM challenges c
          WHERE c.created_by = :user_id
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending verifications
$query = "SELECT cp.*, uc.user_id, uc.challenge_id, uc.status, 
          u.name as student_name, c.title as challenge_title
          FROM challenge_proofs cp
          JOIN user_challenges uc ON cp.user_challenge_id = uc.user_challenge_id
          JOIN users u ON uc.user_id = u.user_id
          JOIN challenges c ON uc.challenge_id = c.challenge_id
          WHERE cp.verdict = 'pending' AND c.created_by = :user_id
          ORDER BY cp.submitted_at ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$pending_verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overall eco-impact stats
$query = "SELECT 
          (SELECT COUNT(*) FROM user_challenges uc 
           JOIN challenges c ON uc.challenge_id = c.challenge_id 
           WHERE c.created_by = :user_id) as total_participations,
          (SELECT COUNT(*) FROM user_challenges uc 
           JOIN challenges c ON uc.challenge_id = c.challenge_id 
           WHERE c.created_by = :user_id AND uc.status = 'verified') as total_completions,
          (SELECT SUM(c.eco_points) FROM user_challenges uc 
           JOIN challenges c ON uc.challenge_id = c.challenge_id 
           WHERE c.created_by = :user_id AND uc.status = 'verified') as total_points_awarded";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$impact_stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGO Dashboard - GreenQuest</title>
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
                        <a class="nav-link" href="challenges.php">My Challenges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="verifications.php">Verifications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="impact.php">Impact Report</a>
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
                                <h4 class="card-title">Welcome, <?php echo htmlspecialchars($ngo['name']); ?>!</h4>
                                <p class="card-text text-muted">Manage your environmental challenges and track their impact.</p>
                            </div>
                            <div class="ms-auto">
                                <a href="challenges.php?action=create" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Create New Challenge
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Eco-Impact Stats -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="display-4 text-primary mb-2"><?php echo number_format($challenges ? count($challenges) : 0); ?></div>
                        <h5 class="card-title">Active Challenges</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="display-4 text-success mb-2"><?php echo number_format($impact_stats['total_completions'] ?? 0); ?></div>
                        <h5 class="card-title">Challenges Completed</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="display-4 text-info mb-2"><?php echo number_format($impact_stats['total_points_awarded'] ?? 0); ?></div>
                        <h5 class="card-title">Eco-Points Awarded</h5>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pending Verifications & Challenges -->
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
                                <a href="verifications.php" class="btn btn-outline-primary">View All Pending Verifications</a>
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
                        <h5 class="card-title mb-0"><i class="fas fa-tasks me-2"></i>Your Challenges</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($challenges) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach(array_slice($challenges, 0, 5) as $challenge): ?>
                                <a href="challenge_details.php?id=<?php echo $challenge['challenge_id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($challenge['title']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo $challenge['participant_count']; ?> participants | 
                                                <?php echo $challenge['completed_count']; ?> completed
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php 
                                            $percent = $challenge['participant_count'] > 0 ? 
                                                ($challenge['completed_count'] / $challenge['participant_count']) * 100 : 0;
                                            
                                            if($percent >= 75) echo 'success';
                                            elseif($percent >= 50) echo 'primary';
                                            elseif($percent >= 25) echo 'warning text-dark';
                                            else echo 'danger';
                                        ?>">
                                            <?php echo $challenge['participant_count'] > 0 ? 
                                                round(($challenge['completed_count'] / $challenge['participant_count']) * 100) : 0; ?>%
                                        </span>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="challenges.php" class="btn btn-outline-primary">View All Challenges</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">You haven't created any challenges yet.</p>
                                <a href="challenges.php?action=create" class="btn btn-outline-primary">Create Challenge</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Challenge Impact -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Challenge Impact</h5>
                        <a href="impact.php" class="btn btn-sm btn-outline-primary">View Full Report</a>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="border rounded p-3">
                                    <h3 class="text-success"><?php echo number_format($impact_stats['total_participations'] ?? 0); ?></h3>
                                    <p class="text-muted mb-0">Total Participations</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="border rounded p-3">
                                    <h3 class="text-primary"><?php 
                                        $completion_rate = $impact_stats['total_participations'] > 0 ? 
                                            ($impact_stats['total_completions'] / $impact_stats['total_participations']) * 100 : 0;
                                        echo round($completion_rate) . '%';
                                    ?></h3>
                                    <p class="text-muted mb-0">Completion Rate</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="border rounded p-3">
                                    <h3 class="text-warning"><?php 
                                        $avg_points = $impact_stats['total_completions'] > 0 ? 
                                            $impact_stats['total_points_awarded'] / $impact_stats['total_completions'] : 0;
                                        echo number_format($avg_points, 1);
                                    ?></h3>
                                    <p class="text-muted mb-0">Avg. Points per Challenge</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h3 class="text-info"><?php echo number_format(count($challenges) ?? 0); ?></h3>
                                    <p class="text-muted mb-0">Active Challenges</p>
                                </div>
                            </div>
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