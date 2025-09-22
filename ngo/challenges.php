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

// Get action from query parameters
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Handle challenge creation if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $eco_points = intval($_POST['eco_points'] ?? 0);
    $verification_type_id = intval($_POST['verification_type_id'] ?? 0);
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    
    if (empty($title) || empty($description) || $eco_points <= 0 || $verification_type_id <= 0) {
        $error = "All fields are required";
    } else {
        // Create challenge
        $query = "INSERT INTO challenges (title, description, eco_points, verification_type_id, 
                  start_date, end_date, created_by, created_at)
                  VALUES (:title, :description, :eco_points, :verification_type_id, 
                  :start_date, :end_date, :created_by, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':eco_points', $eco_points);
        $stmt->bindParam(':verification_type_id', $verification_type_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            // Redirect to challenges list with success message
            $_SESSION['success_message'] = "Challenge created successfully!";
            header('Location: challenges.php');
            exit();
        } else {
            $error = "Failed to create challenge. Please try again.";
        }
    }
}

// Handle challenge editing if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    $challenge_id = intval($_POST['challenge_id'] ?? 0);
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $eco_points = intval($_POST['eco_points'] ?? 0);
    $verification_type_id = intval($_POST['verification_type_id'] ?? 0);
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    
    if (empty($title) || empty($description) || $eco_points <= 0 || $verification_type_id <= 0 || $challenge_id <= 0) {
        $error = "All fields are required";
    } else {
        // Update challenge
        $query = "UPDATE challenges 
                  SET title = :title, description = :description, eco_points = :eco_points, 
                  verification_type_id = :verification_type_id, start_date = :start_date, end_date = :end_date
                  WHERE challenge_id = :challenge_id AND created_by = :created_by";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':eco_points', $eco_points);
        $stmt->bindParam(':verification_type_id', $verification_type_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':challenge_id', $challenge_id);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            // Redirect to challenges list with success message
            $_SESSION['success_message'] = "Challenge updated successfully!";
            header('Location: challenges.php');
            exit();
        } else {
            $error = "Failed to update challenge. Please try again.";
        }
    }
}

// Get verification types for form
$query = "SELECT * FROM verification_types ORDER BY verification_type_id";
$stmt = $db->prepare($query);
$stmt->execute();
$verification_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all challenges created by this NGO
$query = "SELECT c.*, v.name as verification_type,
          (SELECT COUNT(*) FROM user_challenges uc WHERE uc.challenge_id = c.challenge_id) as participant_count,
          (SELECT COUNT(*) FROM user_challenges uc WHERE uc.challenge_id = c.challenge_id AND uc.status = 'verified') as completed_count
          FROM challenges c
          LEFT JOIN verification_types v ON c.verification_type_id = v.verification_type_id
          WHERE c.created_by = :user_id
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If edit action, get challenge details
$challenge = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $challenge_id = intval($_GET['id']);
    $query = "SELECT * FROM challenges WHERE challenge_id = :challenge_id AND created_by = :created_by";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':challenge_id', $challenge_id);
    $stmt->bindParam(':created_by', $_SESSION['user_id']);
    $stmt->execute();
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$challenge) {
        // Challenge not found or doesn't belong to this NGO
        $_SESSION['error_message'] = "Challenge not found or you don't have permission to edit it.";
        header('Location: challenges.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGO Challenges - GreenQuest</title>
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
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <!-- Create/Edit Challenge Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-<?php echo $action === 'create' ? 'plus-circle' : 'edit'; ?> me-2"></i>
                        <?php echo $action === 'create' ? 'Create New Challenge' : 'Edit Challenge'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="challenges.php?action=<?php echo $action; ?><?php echo $action === 'edit' ? '&id=' . $challenge['challenge_id'] : ''; ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="challenge_id" value="<?php echo $challenge['challenge_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="title" class="form-label">Challenge Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       value="<?php echo $action === 'edit' ? htmlspecialchars($challenge['title']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo $action === 'edit' ? htmlspecialchars($challenge['description']) : ''; ?></textarea>
                                <div class="form-text">Provide clear instructions on what students need to do to complete this challenge.</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="eco_points" class="form-label">Eco Points <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="eco_points" name="eco_points" min="1" max="100" required
                                       value="<?php echo $action === 'edit' ? $challenge['eco_points'] : '10'; ?>">
                                <div class="form-text">Points awarded upon successful completion (1-100).</div>
                            </div>
                            
                            <div class="col-md-8">
                                <label for="verification_type_id" class="form-label">Verification Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="verification_type_id" name="verification_type_id" required>
                                    <option value="">Select verification method</option>
                                    <?php foreach ($verification_types as $type): ?>
                                        <option value="<?php echo $type['verification_type_id']; ?>" 
                                                <?php echo $action === 'edit' && $challenge['verification_type_id'] == $type['verification_type_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?> - <?php echo htmlspecialchars($type['description']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">How students will prove they've completed the challenge.</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date"
                                       value="<?php echo $action === 'edit' && $challenge['start_date'] ? date('Y-m-d', strtotime($challenge['start_date'])) : date('Y-m-d'); ?>">
                                <div class="form-text">When students can start participating in this challenge.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date"
                                       value="<?php echo $action === 'edit' && $challenge['end_date'] ? date('Y-m-d', strtotime($challenge['end_date'])) : ''; ?>">
                                <div class="form-text">Optional. Leave blank for an ongoing challenge.</div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="challenges.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $action === 'create' ? 'Create Challenge' : 'Update Challenge'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Challenges List -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>My Challenges</h2>
                <a href="challenges.php?action=create" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Create New Challenge
                </a>
            </div>
            
            <?php if (count($challenges) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Eco Points</th>
                                <th>Verification</th>
                                <th>Duration</th>
                                <th>Participants</th>
                                <th>Completion Rate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($challenges as $challenge): ?>
                                <tr>
                                    <td>
                                        <a href="challenge_details.php?id=<?php echo $challenge['challenge_id']; ?>" class="fw-bold text-decoration-none">
                                            <?php echo htmlspecialchars($challenge['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $challenge['eco_points']; ?></td>
                                    <td><?php echo htmlspecialchars($challenge['verification_type']); ?></td>
                                    <td>
                                        <?php 
                                            $start = $challenge['start_date'] ? date('M d, Y', strtotime($challenge['start_date'])) : 'Any time';
                                            $end = $challenge['end_date'] ? date('M d, Y', strtotime($challenge['end_date'])) : 'Ongoing';
                                            echo $start . ' to ' . $end;
                                        ?>
                                    </td>
                                    <td><?php echo $challenge['participant_count']; ?></td>
                                    <td>
                                        <?php 
                                            $percent = $challenge['participant_count'] > 0 ? 
                                                ($challenge['completed_count'] / $challenge['participant_count']) * 100 : 0;
                                            
                                            echo '<div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                    style="width: ' . $percent . '%;" 
                                                    aria-valuenow="' . $percent . '" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100"></div>
                                            </div>';
                                            echo '<small class="text-muted mt-1">' . round($percent) . '% (' . $challenge['completed_count'] . '/' . $challenge['participant_count'] . ')</small>';
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="challenge_details.php?id=<?php echo $challenge['challenge_id']; ?>" class="btn btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="challenges.php?action=edit&id=<?php echo $challenge['challenge_id']; ?>" class="btn btn-outline-secondary" title="Edit Challenge">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger delete-challenge-btn" 
                                                    data-id="<?php echo $challenge['challenge_id']; ?>"
                                                    data-title="<?php echo htmlspecialchars($challenge['title']); ?>"
                                                    title="Delete Challenge">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-tasks fa-4x text-muted mb-3"></i>
                        <h4>No Challenges Yet</h4>
                        <p class="text-muted">You haven't created any environmental challenges yet.</p>
                        <a href="challenges.php?action=create" class="btn btn-primary mt-2">
                            <i class="fas fa-plus-circle me-2"></i>Create Your First Challenge
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Delete Challenge Confirmation Modal -->
    <div class="modal fade" id="deleteChallengeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the challenge: <strong id="challenge-title"></strong>?</p>
                    <p class="text-danger">This action cannot be undone. Any enrolled students will lose access to this challenge.</p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="../api/index.php?action=delete_challenge">
                        <input type="hidden" id="delete-challenge-id" name="challenge_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Challenge</button>
                    </form>
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
            // Set up delete challenge modal
            $('.delete-challenge-btn').on('click', function() {
                const id = $(this).data('id');
                const title = $(this).data('title');
                
                $('#challenge-title').text(title);
                $('#delete-challenge-id').val(id);
                
                $('#deleteChallengeModal').modal('show');
            });
            
            // Date validation
            $('#end_date').on('change', function() {
                const startDate = new Date($('#start_date').val());
                const endDate = new Date($(this).val());
                
                if (endDate < startDate) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Date Range',
                        text: 'End date cannot be earlier than start date.'
                    });
                    $(this).val('');
                }
            });
            
            $('#start_date').on('change', function() {
                const startDate = new Date($(this).val());
                const endDateInput = $('#end_date');
                
                if (endDateInput.val()) {
                    const endDate = new Date(endDateInput.val());
                    
                    if (endDate < startDate) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Date Range',
                            text: 'End date cannot be earlier than start date.'
                        });
                        endDateInput.val('');
                    }
                }
            });
        });
    </script>
</body>
</html>