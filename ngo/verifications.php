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

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$valid_statuses = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($status, $valid_statuses)) {
    $status = 'pending';
}

// Build the query based on filter
$query = "SELECT cp.*, uc.user_id, uc.challenge_id, uc.status, 
          u.name as student_name, c.title as challenge_title, c.eco_points,
          cp.submitted_at, cp.verdict, cp.verified_at, cp.verifier_id
          FROM challenge_proofs cp
          JOIN user_challenges uc ON cp.user_challenge_id = uc.user_challenge_id
          JOIN users u ON uc.user_id = u.user_id
          JOIN challenges c ON uc.challenge_id = c.challenge_id
          WHERE c.created_by = :user_id";

if ($status !== 'all') {
    $query .= " AND cp.verdict = :status";
}

$query .= " ORDER BY cp.submitted_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
if ($status !== 'all') {
    $stmt->bindParam(':status', $status);
}
$stmt->execute();
$verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$query = "SELECT 
          COUNT(*) as total,
          SUM(CASE WHEN cp.verdict = 'pending' THEN 1 ELSE 0 END) as pending,
          SUM(CASE WHEN cp.verdict = 'approved' THEN 1 ELSE 0 END) as approved,
          SUM(CASE WHEN cp.verdict = 'rejected' THEN 1 ELSE 0 END) as rejected
          FROM challenge_proofs cp
          JOIN user_challenges uc ON cp.user_challenge_id = uc.user_challenge_id
          JOIN challenges c ON uc.challenge_id = c.challenge_id
          WHERE c.created_by = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get verifier names
$verifier_ids = array_filter(array_column($verifications, 'verifier_id'));
if (!empty($verifier_ids)) {
    $placeholders = implode(',', array_fill(0, count($verifier_ids), '?'));
    $query = "SELECT user_id, name FROM users WHERE user_id IN ($placeholders)";
    $stmt = $db->prepare($query);
    
    $i = 1;
    foreach($verifier_ids as $id) {
        $stmt->bindValue($i++, $id);
    }
    
    $stmt->execute();
    $verifiers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} else {
    $verifiers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challenge Verifications - GreenQuest</title>
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="challenges.php">My Challenges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="verifications.php">Verifications</a>
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
            <h2>Challenge Verifications</h2>
            <div class="btn-group" role="group">
                <a href="verifications.php?status=pending" class="btn btn-<?php echo $status === 'pending' ? 'primary' : 'outline-primary'; ?>">
                    Pending <span class="badge bg-light text-dark"><?php echo $stats['pending'] ?? 0; ?></span>
                </a>
                <a href="verifications.php?status=approved" class="btn btn-<?php echo $status === 'approved' ? 'success' : 'outline-success'; ?>">
                    Approved <span class="badge bg-light text-dark"><?php echo $stats['approved'] ?? 0; ?></span>
                </a>
                <a href="verifications.php?status=rejected" class="btn btn-<?php echo $status === 'rejected' ? 'danger' : 'outline-danger'; ?>">
                    Rejected <span class="badge bg-light text-dark"><?php echo $stats['rejected'] ?? 0; ?></span>
                </a>
                <a href="verifications.php?status=all" class="btn btn-<?php echo $status === 'all' ? 'secondary' : 'outline-secondary'; ?>">
                    All <span class="badge bg-light text-dark"><?php echo $stats['total'] ?? 0; ?></span>
                </a>
            </div>
        </div>
        
        <?php if (count($verifications) > 0): ?>
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Challenge</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Verified By</th>
                                <th>Points</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($verifications as $verification): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($verification['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($verification['challenge_title']); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($verification['submitted_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($verification['verdict']) {
                                                case 'approved': echo 'success'; break;
                                                case 'rejected': echo 'danger'; break;
                                                default: echo 'warning text-dark';
                                            }
                                        ?>">
                                            <?php echo ucfirst($verification['verdict']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($verification['verifier_id']) {
                                                echo isset($verifiers[$verification['verifier_id']]) ? 
                                                    htmlspecialchars($verifiers[$verification['verifier_id']]) : 'Unknown';
                                                echo '<br><small class="text-muted">' . date('M d, Y', strtotime($verification['verified_at'])) . '</small>';
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($verification['verdict'] === 'approved') {
                                                echo '<span class="text-success">+' . $verification['eco_points'] . '</span>';
                                            } else {
                                                echo $verification['eco_points'];
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary view-proof-btn" 
                                                data-id="<?php echo $verification['proof_id']; ?>"
                                                data-url="<?php echo $verification['proof_url']; ?>"
                                                data-student="<?php echo htmlspecialchars($verification['student_name']); ?>"
                                                data-challenge="<?php echo htmlspecialchars($verification['challenge_title']); ?>"
                                                data-metadata='<?php echo $verification['metadata']; ?>'
                                                data-status="<?php echo $verification['verdict']; ?>">
                                            View
                                        </button>
                                        
                                        <?php if ($verification['verdict'] === 'pending'): ?>
                                            <div class="btn-group btn-group-sm mt-1">
                                                <button class="btn btn-success approve-btn" 
                                                        data-id="<?php echo $verification['proof_id']; ?>">
                                                    Approve
                                                </button>
                                                <button class="btn btn-danger reject-btn"
                                                        data-id="<?php echo $verification['proof_id']; ?>">
                                                    Reject
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-clipboard-check fa-4x text-muted mb-3"></i>
                    <h4>No Verifications Found</h4>
                    <p class="text-muted">
                        <?php 
                            if ($status === 'pending') {
                                echo "There are no pending challenge verifications.";
                            } elseif ($status === 'approved') {
                                echo "No challenges have been approved yet.";
                            } elseif ($status === 'rejected') {
                                echo "No challenges have been rejected.";
                            } else {
                                echo "No challenge verifications found.";
                            }
                        ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
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
                    
                    <div id="statusIndicator" class="d-none alert mb-0 text-center">
                        <strong>Status:</strong> <span id="statusText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="proofId">
                    <input type="hidden" id="proofStatus">
                    
                    <div id="pendingActions">
                        <button type="button" class="btn btn-danger reject-btn">Reject</button>
                        <button type="button" class="btn btn-success approve-btn">Approve</button>
                    </div>
                    
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                const status = $(this).data('status');
                
                $('#proofId').val(id);
                $('#proofStatus').val(status);
                $('#studentName').text(student);
                $('#challengeName').text(challenge);
                $('#proofImage').attr('src', url);
                
                // Show/hide action buttons based on status
                if (status === 'pending') {
                    $('#pendingActions').show();
                    $('#statusIndicator').addClass('d-none');
                } else {
                    $('#pendingActions').hide();
                    $('#statusIndicator').removeClass('d-none');
                    
                    if (status === 'approved') {
                        $('#statusIndicator').removeClass('alert-danger').addClass('alert-success');
                        $('#statusText').text('Approved');
                    } else {
                        $('#statusIndicator').removeClass('alert-success').addClass('alert-danger');
                        $('#statusText').text('Rejected');
                    }
                }
                
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
                const proofId = $(this).data('id') || $('#proofId').val();
                
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
                const proofId = $(this).data('id') || $('#proofId').val();
                
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
        });
    </script>
</body>
</html>