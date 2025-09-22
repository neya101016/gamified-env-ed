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

// Check if challenge ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: challenges.php");
    exit;
}

$challenge_id = $_GET['id'];

// Get challenge details
$query = "SELECT c.*, u.name as creator_name 
          FROM challenges c
          LEFT JOIN users u ON c.created_by = u.user_id
          WHERE c.challenge_id = :challenge_id AND c.created_by = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':challenge_id', $challenge_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();

$challenge = $stmt->fetch(PDO::FETCH_ASSOC);

// If challenge doesn't exist or doesn't belong to this NGO, redirect
if (!$challenge) {
    header("Location: challenges.php");
    exit;
}

// Get participation stats
$query = "SELECT 
          COUNT(uc.user_challenge_id) as total_participants,
          SUM(CASE WHEN uc.status = 'enrolled' THEN 1 ELSE 0 END) as enrolled_count,
          SUM(CASE WHEN uc.status = 'submitted' THEN 1 ELSE 0 END) as submitted_count,
          SUM(CASE WHEN uc.status = 'verified' THEN 1 ELSE 0 END) as verified_count,
          SUM(CASE WHEN uc.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
          FROM user_challenges uc
          WHERE uc.challenge_id = :challenge_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':challenge_id', $challenge_id);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get participants
$query = "SELECT uc.*, u.name as student_name, u.profile_pic, u.school_id, s.name as school_name,
          cp.proof_id, cp.proof_url, cp.submitted_at, cp.verifier_id, cp.verdict, cp.verified_at, cp.metadata
          FROM user_challenges uc
          JOIN users u ON uc.user_id = u.user_id
          LEFT JOIN schools s ON u.school_id = s.school_id
          LEFT JOIN challenge_proofs cp ON uc.user_challenge_id = cp.user_challenge_id AND cp.is_latest = 1
          WHERE uc.challenge_id = :challenge_id
          ORDER BY 
            CASE 
                WHEN uc.status = 'submitted' THEN 1
                WHEN uc.status = 'enrolled' THEN 2
                WHEN uc.status = 'verified' THEN 3
                WHEN uc.status = 'rejected' THEN 4
            END,
            cp.submitted_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':challenge_id', $challenge_id);
$stmt->execute();
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get eco-impact of this challenge
$query = "SELECT 
          COUNT(*) as verified_count,
          SUM(c.eco_points) as total_points_awarded
          FROM user_challenges uc
          JOIN challenges c ON uc.challenge_id = c.challenge_id
          WHERE uc.challenge_id = :challenge_id AND uc.status = 'verified'";
$stmt = $db->prepare($query);
$stmt->bindParam(':challenge_id', $challenge_id);
$stmt->execute();
$impact = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challenge Details - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge-status-enrolled { background-color: #f8f9fa; color: #495057; }
        .badge-status-submitted { background-color: #ffc107; color: #212529; }
        .badge-status-verified { background-color: #28a745; color: white; }
        .badge-status-rejected { background-color: #dc3545; color: white; }
        .profile-img-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
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
                        <a class="nav-link active" href="challenges.php">My Challenges</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="challenges.php">My Challenges</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($challenge['title']); ?></li>
                </ol>
            </nav>
            <div>
                <a href="challenges.php?action=edit&id=<?php echo $challenge['challenge_id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Edit Challenge
                </a>
            </div>
        </div>
        
        <!-- Challenge Information -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($challenge['title']); ?></h3>
                        <p class="text-muted mb-3">Created on <?php echo date('F j, Y', strtotime($challenge['created_at'])); ?></p>
                        
                        <div class="mb-3">
                            <?php 
                            $status_badge = '';
                            if ($challenge['is_active'] == 1) {
                                $status_badge = '<span class="badge bg-success">Active</span>';
                            } else {
                                $status_badge = '<span class="badge bg-secondary">Inactive</span>';
                            }
                            
                            $difficulty_badge = '';
                            switch ($challenge['difficulty']) {
                                case 'easy':
                                    $difficulty_badge = '<span class="badge bg-success">Easy</span>';
                                    break;
                                case 'medium':
                                    $difficulty_badge = '<span class="badge bg-warning text-dark">Medium</span>';
                                    break;
                                case 'hard':
                                    $difficulty_badge = '<span class="badge bg-danger">Hard</span>';
                                    break;
                            }
                            
                            echo $status_badge . ' ' . $difficulty_badge;
                            ?>
                            <span class="badge bg-info"><?php echo $challenge['eco_points']; ?> Eco-Points</span>
                        </div>
                        
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($challenge['description'])); ?></p>
                        
                        <h5>Requirements</h5>
                        <p><?php echo nl2br(htmlspecialchars($challenge['requirements'])); ?></p>
                        
                        <?php if (!empty($challenge['resources'])): ?>
                        <h5>Resources</h5>
                        <p><?php echo nl2br(htmlspecialchars($challenge['resources'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-chart-pie me-2 text-primary"></i>Participation Stats</h5>
                        
                        <div class="mb-3">
                            <p class="mb-1">Total Participants: <strong><?php echo $stats['total_participants']; ?></strong></p>
                            
                            <div class="progress mb-2" style="height: 20px;">
                                <?php
                                $total = $stats['total_participants'] > 0 ? $stats['total_participants'] : 1;
                                $enrolled_percent = ($stats['enrolled_count'] / $total) * 100;
                                $submitted_percent = ($stats['submitted_count'] / $total) * 100;
                                $verified_percent = ($stats['verified_count'] / $total) * 100;
                                $rejected_percent = ($stats['rejected_count'] / $total) * 100;
                                ?>
                                <div class="progress-bar bg-light text-dark" role="progressbar" style="width: <?php echo $enrolled_percent; ?>%" 
                                    aria-valuenow="<?php echo $enrolled_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php if ($enrolled_percent > 10): echo $stats['enrolled_count']; endif; ?>
                                </div>
                                <div class="progress-bar bg-warning text-dark" role="progressbar" style="width: <?php echo $submitted_percent; ?>%" 
                                    aria-valuenow="<?php echo $submitted_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php if ($submitted_percent > 10): echo $stats['submitted_count']; endif; ?>
                                </div>
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $verified_percent; ?>%" 
                                    aria-valuenow="<?php echo $verified_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php if ($verified_percent > 10): echo $stats['verified_count']; endif; ?>
                                </div>
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $rejected_percent; ?>%" 
                                    aria-valuenow="<?php echo $rejected_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php if ($rejected_percent > 10): echo $stats['rejected_count']; endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-wrap justify-content-between">
                                <small><span class="badge badge-status-enrolled">Enrolled: <?php echo $stats['enrolled_count']; ?></span></small>
                                <small><span class="badge badge-status-submitted">Submitted: <?php echo $stats['submitted_count']; ?></span></small>
                                <small><span class="badge badge-status-verified">Verified: <?php echo $stats['verified_count']; ?></span></small>
                                <small><span class="badge badge-status-rejected">Rejected: <?php echo $stats['rejected_count']; ?></span></small>
                            </div>
                        </div>
                        
                        <h6 class="border-top pt-3">Eco-Impact</h6>
                        <p class="mb-1">Points Awarded: <strong><?php echo number_format($impact['total_points_awarded'] ?? 0); ?></strong></p>
                        <p class="mb-1">Completion Rate: 
                            <strong>
                                <?php 
                                echo $stats['total_participants'] > 0 ? 
                                    round(($stats['verified_count'] / $stats['total_participants']) * 100) : 0; 
                                ?>%
                            </strong>
                        </p>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-cog me-2 text-secondary"></i>Actions</h5>
                        
                        <div class="d-grid gap-2">
                            <?php if ($challenge['is_active'] == 1): ?>
                            <button type="button" class="btn btn-outline-secondary toggle-status-btn" data-id="<?php echo $challenge['challenge_id']; ?>" data-status="0">
                                <i class="fas fa-pause-circle me-2"></i>Deactivate Challenge
                            </button>
                            <?php else: ?>
                            <button type="button" class="btn btn-outline-success toggle-status-btn" data-id="<?php echo $challenge['challenge_id']; ?>" data-status="1">
                                <i class="fas fa-play-circle me-2"></i>Activate Challenge
                            </button>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-outline-danger delete-challenge-btn" data-id="<?php echo $challenge['challenge_id']; ?>">
                                <i class="fas fa-trash-alt me-2"></i>Delete Challenge
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Participants List -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Participants</h5>
            </div>
            <div class="card-body">
                <?php if (count($participants) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>School</th>
                                <th>Status</th>
                                <th>Enrollment Date</th>
                                <th>Submission Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $participant): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo getProfileImage($participant); ?>" class="profile-img-sm me-2" alt="Profile">
                                        <?php echo htmlspecialchars($participant['student_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($participant['school_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    switch ($participant['status']) {
                                        case 'enrolled':
                                            echo '<span class="badge badge-status-enrolled">Enrolled</span>';
                                            break;
                                        case 'submitted':
                                            echo '<span class="badge badge-status-submitted">Submitted</span>';
                                            break;
                                        case 'verified':
                                            echo '<span class="badge badge-status-verified">Verified</span>';
                                            break;
                                        case 'rejected':
                                            echo '<span class="badge badge-status-rejected">Rejected</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($participant['enrolled_at'])); ?></td>
                                <td>
                                    <?php echo $participant['submitted_at'] ? date('M d, Y', strtotime($participant['submitted_at'])) : 'Not submitted'; ?>
                                </td>
                                <td>
                                    <?php if ($participant['status'] == 'submitted'): ?>
                                    <button class="btn btn-sm btn-primary view-proof-btn" 
                                            data-id="<?php echo $participant['proof_id']; ?>"
                                            data-url="<?php echo $participant['proof_url']; ?>"
                                            data-student="<?php echo htmlspecialchars($participant['student_name']); ?>"
                                            data-challenge="<?php echo htmlspecialchars($challenge['title']); ?>"
                                            data-metadata='<?php echo $participant['metadata']; ?>'>
                                        Review
                                    </button>
                                    <?php elseif ($participant['status'] == 'verified' || $participant['status'] == 'rejected'): ?>
                                    <button class="btn btn-sm btn-outline-secondary view-proof-btn" 
                                            data-id="<?php echo $participant['proof_id']; ?>"
                                            data-url="<?php echo $participant['proof_url']; ?>"
                                            data-student="<?php echo htmlspecialchars($participant['student_name']); ?>"
                                            data-challenge="<?php echo htmlspecialchars($challenge['title']); ?>"
                                            data-metadata='<?php echo $participant['metadata']; ?>'>
                                        View
                                    </button>
                                    <?php else: ?>
                                    <span class="text-muted">No submission</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-muted">No participants have enrolled in this challenge yet.</p>
                </div>
                <?php endif; ?>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger reject-btn">Reject</button>
                    <button type="button" class="btn btn-success approve-btn">Approve</button>
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
                    try {
                        const meta = JSON.parse(metadata);
                        $('#proofDescription').text(meta.description || 'No description provided');
                        $('#submissionDate').text(meta.submission_date);
                        $('#fileType').text(meta.file_type);
                        $('#fileSize').text(formatFileSize(meta.file_size));
                    } catch (e) {
                        $('#proofDescription').text('No description available');
                        $('#submissionDate').text('N/A');
                        $('#fileType').text('N/A');
                        $('#fileSize').text('N/A');
                    }
                }
                
                // Hide approve/reject buttons for already processed proofs
                if ($(this).hasClass('btn-outline-secondary')) {
                    $('.approve-btn, .reject-btn').hide();
                } else {
                    $('.approve-btn, .reject-btn').show();
                }
                
                $('#proofReviewModal').modal('show');
            });
            
            // Format file size
            function formatFileSize(bytes) {
                if (!bytes) return 'Unknown';
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
            
            // Toggle challenge status
            $('.toggle-status-btn').on('click', function() {
                const challengeId = $(this).data('id');
                const newStatus = $(this).data('status');
                const statusText = newStatus == 1 ? 'activate' : 'deactivate';
                
                Swal.fire({
                    title: `${newStatus == 1 ? 'Activate' : 'Deactivate'} Challenge?`,
                    text: `Are you sure you want to ${statusText} this challenge?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: `Yes, ${statusText}`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if(result.isConfirmed) {
                        // Submit AJAX request to update status
                        $.ajax({
                            url: '../api/index.php?action=update_challenge_status',
                            type: 'POST',
                            data: {
                                challenge_id: challengeId,
                                is_active: newStatus
                            },
                            dataType: 'json',
                            success: function(response) {
                                if(response.status === 'success') {
                                    Swal.fire({
                                        title: 'Success',
                                        text: `Challenge ${newStatus == 1 ? 'activated' : 'deactivated'} successfully.`,
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(function() {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: response.message || 'Failed to update challenge status.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Server Error',
                                    text: 'An error occurred while connecting to the server.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            });
            
            // Delete challenge
            $('.delete-challenge-btn').on('click', function() {
                const challengeId = $(this).data('id');
                
                Swal.fire({
                    title: 'Delete Challenge?',
                    text: 'This action cannot be undone. All associated data will be deleted.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545'
                }).then((result) => {
                    if(result.isConfirmed) {
                        // Submit AJAX request to delete challenge
                        $.ajax({
                            url: '../api/index.php?action=delete_challenge',
                            type: 'POST',
                            data: {
                                challenge_id: challengeId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if(response.status === 'success') {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: 'The challenge has been deleted successfully.',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(function() {
                                        window.location.href = 'challenges.php';
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: response.message || 'Failed to delete challenge.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Server Error',
                                    text: 'An error occurred while connecting to the server.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
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
        });
    </script>
</body>
</html>