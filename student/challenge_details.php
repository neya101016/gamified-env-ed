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

// Check if challenge ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid challenge ID.";
    header("Location: challenges.php");
    exit;
}

$challenge_id = $_GET['id'];

// Get challenge details
$query = "SELECT c.*, 
          ngo.name as ngo_name, ngo.profile_pic as ngo_profile_pic,
          CASE 
            WHEN uc.status IS NULL THEN 'not_started'
            ELSE uc.status
          END as participation_status,
          uc.completed_at,
          cp.proof_url as proof_image,
          cp.metadata as submission_description,
          cp.verdict as feedback
          FROM challenges c
          JOIN users ngo ON c.created_by = ngo.user_id
          LEFT JOIN user_challenges uc ON c.challenge_id = uc.challenge_id AND uc.user_id = :user_id
          LEFT JOIN challenge_proofs cp ON uc.user_challenge_id = cp.user_challenge_id
          WHERE c.challenge_id = :challenge_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->bindParam(':challenge_id', $challenge_id);
$stmt->execute();
$challenge = $stmt->fetch(PDO::FETCH_ASSOC);

// If challenge doesn't exist
if (!$challenge) {
    $_SESSION['error_message'] = "Challenge not found.";
    header("Location: challenges.php");
    exit;
}

// Check if challenge is expired
$now = new DateTime();
$end_date = new DateTime($challenge['end_date']);
$start_date = new DateTime($challenge['start_date']);
$is_expired = $end_date < $now;
$is_future = $start_date > $now;

// Get statistics about the challenge
$query = "SELECT 
          COUNT(DISTINCT uc.user_id) as total_participants,
          COUNT(DISTINCT CASE WHEN uc.status = 'verified' THEN uc.user_id END) as completed_count
          FROM user_challenges uc
          WHERE uc.challenge_id = :challenge_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':challenge_id', $challenge_id);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent participants (verified submissions)
$query = "SELECT u.user_id, u.name, u.profile_pic, uc.completed_at as submission_date, s.name as school_name
          FROM user_challenges uc
          JOIN users u ON uc.user_id = u.user_id
          LEFT JOIN schools s ON u.school_id = s.school_id
          WHERE uc.challenge_id = :challenge_id AND uc.status = 'verified'
          ORDER BY uc.completed_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':challenge_id', $challenge_id);
$stmt->execute();
$recent_participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle challenge submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_challenge'])) {
    // Check if already submitted
    if ($challenge['participation_status'] != 'not_started' && $challenge['participation_status'] != 'rejected') {
        $_SESSION['error_message'] = "You have already submitted this challenge.";
        header("Location: challenge_details.php?id=$challenge_id");
        exit;
    }
    
    // Check if challenge is active
    if ($is_expired) {
        $_SESSION['error_message'] = "This challenge has expired.";
        header("Location: challenge_details.php?id=$challenge_id");
        exit;
    }
    
    if ($is_future) {
        $_SESSION['error_message'] = "This challenge hasn't started yet.";
        header("Location: challenge_details.php?id=$challenge_id");
        exit;
    }
    
    // Validate submission
    if (empty($_POST['description'])) {
        $_SESSION['error_message'] = "Please provide a description of your challenge completion.";
        header("Location: challenge_details.php?id=$challenge_id");
        exit;
    }
    
    // Handle file upload
    $proof_image = '';
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Check file type
        if (!in_array($_FILES['proof_image']['type'], $allowed_types)) {
            $_SESSION['error_message'] = "Only JPG, JPEG, and PNG files are allowed.";
            header("Location: challenge_details.php?id=$challenge_id");
            exit;
        }
        
        // Check file size
        if ($_FILES['proof_image']['size'] > $max_size) {
            $_SESSION['error_message'] = "File size should not exceed 5MB.";
            header("Location: challenge_details.php?id=$challenge_id");
            exit;
        }
        
        // Generate unique filename
        $extension = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
        $proof_image = uniqid('challenge_') . '_' . time() . '.' . $extension;
        $upload_path = '../uploads/challenge_proofs/' . $proof_image;
        
        // Move the uploaded file
        if (!move_uploaded_file($_FILES['proof_image']['tmp_name'], $upload_path)) {
            $_SESSION['error_message'] = "Error uploading file. Please try again.";
            header("Location: challenge_details.php?id=$challenge_id");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Please upload proof of challenge completion (image).";
        header("Location: challenge_details.php?id=$challenge_id");
        exit;
    }
    
    // Insert or update user challenge
    if ($challenge['participation_status'] == 'rejected') {
        // Update the existing record - first update the user_challenge
        $query = "UPDATE user_challenges SET 
                  status = 'pending',
                  completed_at = NOW()
                  WHERE user_id = :user_id AND challenge_id = :challenge_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':challenge_id', $challenge_id);
        
        if (!$stmt->execute()) {
            $_SESSION['error_message'] = "Error updating challenge. Please try again.";
            header("Location: challenge_details.php?id=$challenge_id");
            exit;
        }
        
        // Get the user_challenge_id
        $query = "SELECT user_challenge_id FROM user_challenges 
                 WHERE user_id = :user_id AND challenge_id = :challenge_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':challenge_id', $challenge_id);
        $stmt->execute();
        $uc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Now update or insert the proof
        $query = "INSERT INTO challenge_proofs 
                 (user_challenge_id, proof_url, metadata, submitted_at, verdict) 
                 VALUES 
                 (:user_challenge_id, :proof_url, :metadata, NOW(), 'pending')
                 ON DUPLICATE KEY UPDATE
                 proof_url = :proof_url, 
                 metadata = :metadata,
                 submitted_at = NOW(),
                 verdict = 'pending'";
        
        $metadata = json_encode(['description' => $_POST['description']]);
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_challenge_id', $uc['user_challenge_id']);
        $stmt->bindParam(':proof_url', $proof_image);
        $stmt->bindParam(':metadata', $metadata);
        
    } else {
        // Insert new user_challenge record
        $query = "INSERT INTO user_challenges 
                 (user_id, challenge_id, status, completed_at) 
                 VALUES 
                 (:user_id, :challenge_id, 'pending', NOW())";
                 
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':challenge_id', $challenge_id);
        
        if (!$stmt->execute()) {
            $_SESSION['error_message'] = "Error submitting challenge. Please try again.";
            header("Location: challenge_details.php?id=$challenge_id");
            exit;
        }
        
        // Get the inserted user_challenge_id
        $user_challenge_id = $db->lastInsertId();
        
        // Now insert the proof
        $query = "INSERT INTO challenge_proofs 
                 (user_challenge_id, proof_url, metadata, submitted_at, verdict) 
                 VALUES 
                 (:user_challenge_id, :proof_url, :metadata, NOW(), 'pending')";
        
        $metadata = json_encode(['description' => $_POST['description']]);
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_challenge_id', $user_challenge_id);
        $stmt->bindParam(':proof_url', $proof_image);
        $stmt->bindParam(':metadata', $metadata);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Challenge submission successful! Your submission is pending verification.";
        header("Location: challenge_details.php?id=$challenge_id");
        exit;
    } else {
        $_SESSION['error_message'] = "Error submitting challenge proof. Please try again.";
        header("Location: challenge_details.php?id=$challenge_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($challenge['title']); ?> - Challenge Details</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .challenge-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }
        .challenge-header {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .ngo-avatar {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
        .status-badge {
            font-size: 1rem;
            padding: 8px 15px;
            border-radius: 20px;
        }
        .countdown-timer {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        .timer-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #dc3545;
        }
        .challenge-description {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .participant-avatar {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
        .submission-form {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .feedback-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 10px 10px 0;
        }
        .submission-result {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .proof-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .details-card {
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .details-card-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .details-card-body {
            padding: 20px;
            background-color: #fff;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background-color: #f8f9fa;
            margin-bottom: 15px;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #28a745;
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
                        <a class="nav-link" href="lessons.php">Lessons</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="challenges.php">Challenges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">Leaderboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="batch.php">My Batch</a>
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
                <h2><i class="fas fa-tasks me-2"></i>Challenge Details</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="challenges.php">Challenges</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($challenge['title']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- Challenge Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="challenge-header shadow-sm">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo !empty($challenge['ngo_profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($challenge['ngo_profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                             alt="NGO Profile" class="ngo-avatar me-3">
                        <div>
                            <h3 class="mb-0"><?php echo htmlspecialchars($challenge['title']); ?></h3>
                            <p class="text-muted mb-0">
                                By <strong><?php echo htmlspecialchars($challenge['ngo_name']); ?></strong> &bull; 
                                <span class="badge bg-secondary"><?php echo isset($challenge['verification_type_id']) ? 'Type ' . $challenge['verification_type_id'] : 'General'; ?></span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>
                                <?php echo date('M d, Y', strtotime($challenge['start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($challenge['end_date'])); ?>
                            </p>
                        </div>
                        <span class="badge bg-success">
                            <i class="fas fa-leaf me-1"></i>
                            <?php echo isset($challenge['eco_points']) ? $challenge['eco_points'] : (isset($challenge['points']) ? $challenge['points'] : 0); ?> Eco-Points
                        </span>
                    </div>
                    
                    <div class="mt-3">
                        <?php
                        // Display appropriate status badge
                        if ($challenge['participation_status'] == 'pending') {
                            echo '<span class="status-badge bg-warning"><i class="fas fa-hourglass-half me-2"></i>Pending Verification</span>';
                        } elseif ($challenge['participation_status'] == 'verified') {
                            echo '<span class="status-badge bg-success"><i class="fas fa-check-circle me-2"></i>Challenge Completed</span>';
                        } elseif ($challenge['participation_status'] == 'rejected') {
                            echo '<span class="status-badge bg-danger"><i class="fas fa-times-circle me-2"></i>Submission Rejected</span>';
                        } else {
                            if ($is_expired) {
                                echo '<span class="status-badge bg-secondary"><i class="fas fa-calendar-times me-2"></i>Challenge Expired</span>';
                            } elseif ($is_future) {
                                echo '<span class="status-badge bg-info text-dark"><i class="fas fa-calendar me-2"></i>Challenge Starts Soon</span>';
                            } else {
                                echo '<span class="status-badge bg-primary"><i class="fas fa-play-circle me-2"></i>Challenge Available</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <div class="mb-4">
                    <img src="<?php echo !empty($challenge['image']) ? '../uploads/challenges/' . htmlspecialchars($challenge['image']) : '../assets/images/default_challenge.jpg'; ?>" 
                         alt="Challenge Image" class="challenge-image shadow-sm">
                </div>
                
                <div class="details-card">
                    <div class="details-card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Challenge Description</h5>
                    </div>
                    <div class="details-card-body">
                        <?php echo nl2br(htmlspecialchars($challenge['description'])); ?>
                        
                        <div class="mt-4">
                            <h6><i class="fas fa-clipboard-list me-2"></i>Challenge Requirements:</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <?php echo isset($challenge['requirements']) ? nl2br(htmlspecialchars($challenge['requirements'])) : 'Complete the challenge as described above.'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($challenge['participation_status'] == 'rejected'): ?>
                    <!-- Feedback from verification -->
                    <div class="feedback-box">
                        <h5><i class="fas fa-comment-dots me-2"></i>Feedback on Your Submission</h5>
                        <p><?php echo isset($challenge['feedback']) ? nl2br(htmlspecialchars($challenge['feedback'])) : 'No specific feedback provided.'; ?></p>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>You can resubmit your challenge with the necessary improvements.
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($challenge['participation_status'] == 'verified'): ?>
                    <!-- Completed challenge details -->
                    <div class="submission-result">
                        <h5 class="text-success"><i class="fas fa-check-circle me-2"></i>Challenge Completed</h5>
                        <p>You successfully completed this challenge on <?php echo date('F d, Y', strtotime($challenge['completed_at'])); ?>.</p>
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Your Submission</h6>
                            </div>
                            <div class="card-body">
                                <img src="../uploads/challenge_proofs/<?php echo htmlspecialchars($challenge['proof_image']); ?>" 
                                     alt="Proof Image" class="proof-image">
                                <?php
                                $description = '';
                                if (isset($challenge['submission_description'])) {
                                    if (is_string($challenge['submission_description'])) {
                                        $jsonData = json_decode($challenge['submission_description'], true);
                                        $description = isset($jsonData['description']) ? $jsonData['description'] : $challenge['submission_description'];
                                    }
                                }
                                ?>
                                <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
                            </div>
                        </div>
                        <div class="alert alert-success">
                            <i class="fas fa-trophy me-2"></i>You earned <strong><?php echo isset($challenge['eco_points']) ? $challenge['eco_points'] : (isset($challenge['points']) ? $challenge['points'] : 0); ?> Eco-Points</strong> for completing this challenge!
                        </div>
                    </div>
                <?php elseif ($challenge['participation_status'] == 'pending'): ?>
                    <!-- Pending verification details -->
                    <div class="submission-result">
                        <h5 class="text-warning"><i class="fas fa-hourglass-half me-2"></i>Submission Pending Verification</h5>
                        <p>You submitted this challenge on <?php echo date('F d, Y', strtotime($challenge['completed_at'])); ?>. Please wait while our team verifies your submission.</p>
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Your Submission</h6>
                            </div>
                            <div class="card-body">
                                <img src="../uploads/challenge_proofs/<?php echo htmlspecialchars($challenge['proof_image']); ?>" 
                                     alt="Proof Image" class="proof-image">
                                <?php
                                $description = '';
                                if (isset($challenge['submission_description'])) {
                                    if (is_string($challenge['submission_description'])) {
                                        $jsonData = json_decode($challenge['submission_description'], true);
                                        $description = isset($jsonData['description']) ? $jsonData['description'] : $challenge['submission_description'];
                                    }
                                }
                                ?>
                                <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Verification usually takes 24-48 hours. Thank you for your patience!
                        </div>
                    </div>
                <?php elseif (!$is_expired && !$is_future): ?>
                    <!-- Challenge submission form -->
                    <div class="submission-form">
                        <h5><i class="fas fa-paper-plane me-2"></i>Submit Your Challenge</h5>
                        <form action="challenge_details.php?id=<?php echo $challenge_id; ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="proof_image" class="form-label">Upload Proof (Image)*</label>
                                <input type="file" class="form-control" id="proof_image" name="proof_image" accept="image/*" required>
                                <small class="text-muted">Upload a clear image as proof of completing the challenge. Max size: 5MB</small>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description*</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Describe how you completed the challenge and what you learned..."></textarea>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirmation" required>
                                    <label class="form-check-label" for="confirmation">
                                        I confirm that I have completed this challenge as per the requirements.
                                    </label>
                                </div>
                            </div>
                            <button type="submit" name="submit_challenge" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Challenge
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <?php if (!$is_expired && !$is_future && $challenge['participation_status'] == 'not_started'): ?>
                    <!-- Countdown timer -->
                    <div class="countdown-timer shadow-sm mb-4">
                        <h5>Time Remaining</h5>
                        <div class="timer-value" id="countdown">
                            <i class="fas fa-clock"></i> Loading...
                        </div>
                        <p class="text-muted mb-0">to complete this challenge</p>
                    </div>
                <?php endif; ?>
                
                <!-- Challenge Statistics -->
                <div class="details-card mb-4">
                    <div class="details-card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Challenge Stats</h5>
                    </div>
                    <div class="details-card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $stats['total_participants'] ?? 0; ?></div>
                                    <div class="stat-label">Participants</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $stats['completed_count'] ?? 0; ?></div>
                                    <div class="stat-label">Completed</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php 
                                $completion_rate = 0;
                                if (($stats['total_participants'] ?? 0) > 0) {
                                    $completion_rate = (($stats['completed_count'] ?? 0) / $stats['total_participants']) * 100;
                                }
                                echo number_format($completion_rate, 0) . '%';
                                ?>
                            </div>
                            <div class="stat-label">Completion Rate</div>
                        </div>
                        
                        <div class="progress mt-2 mb-4" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $completion_rate; ?>%"
                                 aria-valuenow="<?php echo $completion_rate; ?>" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <div><i class="fas fa-calendar-check me-2"></i>Start Date</div>
                            <div><strong><?php echo date('M d, Y', strtotime($challenge['start_date'])); ?></strong></div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><i class="fas fa-calendar-times me-2"></i>End Date</div>
                            <div><strong><?php echo date('M d, Y', strtotime($challenge['end_date'])); ?></strong></div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><i class="fas fa-signal me-2"></i>Difficulty</div>
                            <div>
                                <?php
                                // Determine difficulty based on points
                                $points = isset($challenge['eco_points']) ? $challenge['eco_points'] : (isset($challenge['points']) ? $challenge['points'] : 0);
                                $difficulty = 'Medium';
                                $difficultyClass = 'bg-warning text-dark';
                                
                                if ($points < 50) {
                                    $difficulty = 'Easy';
                                    $difficultyClass = 'bg-success';
                                } elseif ($points >= 100) {
                                    $difficulty = 'Hard';
                                    $difficultyClass = 'bg-danger';
                                }
                                ?>
                                <span class="badge <?php echo $difficultyClass; ?>">
                                    <?php echo $difficulty; ?>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div><i class="fas fa-leaf me-2"></i>Eco-Points</div>
                            <div><strong><?php echo isset($challenge['eco_points']) ? $challenge['eco_points'] : (isset($challenge['points']) ? $challenge['points'] : 0); ?> points</strong></div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Participants -->
                <div class="details-card">
                    <div class="details-card-header">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Recent Participants</h5>
                    </div>
                    <div class="details-card-body">
                        <?php if(count($recent_participants) > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach($recent_participants as $participant): ?>
                                <li class="list-group-item px-0">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($participant['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($participant['profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                                             alt="Participant" class="participant-avatar me-3">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($participant['name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo !empty($participant['school_name']) ? htmlspecialchars($participant['school_name']) : 'No School'; ?> &bull;
                                                <?php echo date('M d, Y', strtotime($participant['submission_date'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <?php if ($stats['completed_count'] > count($recent_participants)): ?>
                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        +<?php echo $stats['completed_count'] - count($recent_participants); ?> more participants
                                    </small>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">No participants yet. Be the first to complete this challenge!</p>
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
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Show success message if redirected from form submission
            <?php if(isset($_SESSION['success_message'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo $_SESSION['success_message']; ?>',
                    confirmButtonColor: '#28a745'
                });
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            // Show error message if there is one
            <?php if(isset($_SESSION['error_message'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '<?php echo $_SESSION['error_message']; ?>',
                    confirmButtonColor: '#dc3545'
                });
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            // Countdown timer
            <?php if (!$is_expired && !$is_future && $challenge['participation_status'] == 'not_started'): ?>
                function updateCountdown() {
                    const now = new Date().getTime();
                    const endTime = new Date("<?php echo $challenge['end_date']; ?>").getTime();
                    const timeLeft = endTime - now;
                    
                    if (timeLeft <= 0) {
                        document.getElementById("countdown").innerHTML = "EXPIRED";
                        return;
                    }
                    
                    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                    
                    document.getElementById("countdown").innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                    
                    // Highlight countdown if less than 24 hours
                    if (timeLeft < 24 * 60 * 60 * 1000) {
                        document.getElementById("countdown").style.color = "#dc3545";
                    }
                }
                
                // Update countdown every second
                updateCountdown();
                setInterval(updateCountdown, 1000);
            <?php endif; ?>
            
            // Image preview for file upload
            $('#proof_image').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        Swal.fire({
                            title: 'Image Preview',
                            text: 'Is this image clear enough to verify your challenge completion?',
                            imageUrl: e.target.result,
                            imageAlt: 'Challenge Proof Preview',
                            confirmButtonText: 'Yes, it\'s clear',
                            showCancelButton: true,
                            cancelButtonText: 'Let me choose another',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#dc3545'
                        }).then((result) => {
                            if (!result.isConfirmed) {
                                $('#proof_image').val('');
                            }
                        });
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>