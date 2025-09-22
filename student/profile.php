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

// Get school info if available
$school_name = 'Not associated with a school';
if (!empty($student['school_id'])) {
    $query = "SELECT name, city, state FROM schools WHERE school_id = :school_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':school_id', $student['school_id']);
    $stmt->execute();
    $school = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($school) {
        $school_name = $school['name'] . (!empty($school['city']) ? ', ' . $school['city'] : '') . 
                       (!empty($school['state']) ? ', ' . $school['state'] : '');
    }
}

// Get total eco points
$query = "SELECT SUM(points) as total_points FROM eco_points WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$points = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent eco points (last 10 entries)
$query = "SELECT ep.*, epr.reason_key, epr.description as reason_description
          FROM eco_points ep
          LEFT JOIN eco_point_reasons epr ON ep.reason_id = epr.reason_id
          WHERE ep.user_id = :user_id
          ORDER BY ep.awarded_at DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$recent_points = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process profile update
$update_success = false;
$update_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($name)) {
        $update_error = 'Name is required';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update_error = 'Valid email is required';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $update_error = 'New passwords do not match';
    } elseif (!empty($new_password) && empty($current_password)) {
        $update_error = 'Current password is required to set a new password';
    } elseif (!empty($new_password) && strlen($new_password) < 8) {
        $update_error = 'New password must be at least 8 characters long';
    } else {
        // Verify current password if changing password
        if (!empty($new_password)) {
            // Get current password hash
            $query = "SELECT password_hash FROM users WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $current_hash = $stmt->fetch(PDO::FETCH_ASSOC)['password_hash'];
            
            if (!password_verify($current_password, $current_hash)) {
                $update_error = 'Current password is incorrect';
            }
        }
        
        // Check if email exists for another user
        if (empty($update_error) && $email !== $student['email']) {
            $query = "SELECT user_id FROM users WHERE email = :email AND user_id != :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $update_error = 'Email is already in use by another account';
            }
        }
        
        // Process profile picture if uploaded
        $profile_pic = $student['profile_pic'];
        
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['profile_pic']['type'], $allowed_types)) {
                $update_error = 'Profile picture must be a JPEG, PNG, or GIF image';
            } elseif ($_FILES['profile_pic']['size'] > $max_size) {
                $update_error = 'Profile picture size cannot exceed 2MB';
            } else {
                $upload_dir = '../uploads/profile_pics/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir) && !is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $filename = uniqid() . '_' . basename($_FILES['profile_pic']['name']);
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                    $profile_pic = $filename;
                    
                    // Delete old profile picture if it exists and is not the default
                    if (!empty($student['profile_pic']) && file_exists($upload_dir . $student['profile_pic'])) {
                        @unlink($upload_dir . $student['profile_pic']);
                    }
                } else {
                    $update_error = 'Failed to upload profile picture';
                }
            }
        }
        
        // Update profile if no errors
        if (empty($update_error)) {
            // Prepare update query
            $query = "UPDATE users SET name = :name, email = :email";
            $params = [
                ':name' => $name,
                ':email' => $email,
                ':user_id' => $_SESSION['user_id']
            ];
            
            // Add password update if provided
            if (!empty($new_password)) {
                $query .= ", password_hash = :password_hash";
                $params[':password_hash'] = password_hash($new_password, PASSWORD_BCRYPT);
            }
            
            // Add profile picture update if changed
            if ($profile_pic !== $student['profile_pic']) {
                $query .= ", profile_pic = :profile_pic";
                $params[':profile_pic'] = $profile_pic;
            }
            
            $query .= " WHERE user_id = :user_id";
            
            // Execute update
            $stmt = $db->prepare($query);
            if ($stmt->execute($params)) {
                $update_success = true;
                
                // Update session variables
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                
                // Refresh student data
                $student = $user->getUserById($_SESSION['user_id']);
            } else {
                $update_error = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// Get badges
$badge = new Badge($db);
$badges = $badge->getUserBadges($_SESSION['user_id']);

// Get activity statistics
$query = "SELECT 
            (SELECT COUNT(*) FROM quiz_attempts WHERE user_id = :user_id) as quiz_count,
            (SELECT COUNT(*) FROM user_challenges WHERE user_id = :user_id) as challenge_count,
            (SELECT COUNT(*) FROM user_challenges WHERE user_id = :user_id AND status = 'verified') as completed_challenge_count,
            (SELECT COUNT(*) FROM user_badges WHERE user_id = :user_id) as badge_count";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-header {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .profile-badge {
            width: 40px;
            height: 40px;
            margin-right: 5px;
        }
        .stat-box {
            background-color: #fff;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            height: 100%;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .stat-box:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #28a745;
        }
        .eco-points-history {
            max-height: 300px;
            overflow-y: auto;
        }
        .nav-tabs .nav-link.active {
            border-color: #28a745 #dee2e6 #fff;
            border-top-width: 2px;
        }
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
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
                        <a class="nav-link" href="challenges.php">Challenges</a>
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
                        <li><a class="dropdown-item active" href="profile.php"><i class="fas fa-id-card me-2"></i>Profile</a></li>
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
                <h2><i class="fas fa-user-circle me-2"></i>My Profile</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Profile</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <?php if ($update_success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> Your profile has been updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($update_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $update_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Profile Header -->
        <div class="profile-header shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img src="<?php echo !empty($student['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($student['profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                         alt="Profile Picture" class="profile-picture mb-3">
                </div>
                <div class="col-md-5">
                    <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                    <p class="text-muted mb-2"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($student['email']); ?></p>
                    <p class="text-muted mb-2"><i class="fas fa-school me-2"></i><?php echo htmlspecialchars($school_name); ?></p>
                    <p class="text-muted mb-2"><i class="fas fa-calendar-alt me-2"></i>Joined: <?php echo date('F j, Y', strtotime($student['join_date'])); ?></p>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <div class="mb-3">
                        <span class="badge bg-success p-2 fs-6">
                            <i class="fas fa-leaf me-1"></i>
                            <?php echo number_format($points['total_points'] ?? 0); ?> Eco-Points
                        </span>
                    </div>
                    <div>
                        <?php if (count($badges) > 0): ?>
                            <?php foreach(array_slice($badges, 0, 5) as $badge): ?>
                                <span class="badge bg-warning text-dark p-2 mb-1" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($badge['name']); ?>">
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
                                    <i class="<?php echo $icon; ?>"></i>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($badges) > 5): ?>
                                <span class="badge bg-secondary p-2 mb-1">+<?php echo count($badges) - 5; ?> more</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary p-2">No badges yet</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Content -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <!-- Statistics -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-bar me-2"></i>Activity Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stat-box">
                                    <div class="stat-icon"><i class="fas fa-book-reader"></i></div>
                                    <h5><?php echo $stats['quiz_count'] ?? 0; ?></h5>
                                    <p class="text-muted mb-0">Quizzes Taken</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box">
                                    <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                                    <h5><?php echo $stats['completed_challenge_count'] ?? 0; ?></h5>
                                    <p class="text-muted mb-0">Challenges Completed</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box">
                                    <div class="stat-icon"><i class="fas fa-award"></i></div>
                                    <h5><?php echo $stats['badge_count'] ?? 0; ?></h5>
                                    <p class="text-muted mb-0">Badges Earned</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box">
                                    <div class="stat-icon"><i class="fas fa-leaf"></i></div>
                                    <h5><?php echo number_format($points['total_points'] ?? 0); ?></h5>
                                    <p class="text-muted mb-0">Total Eco-Points</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Eco-Points -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Recent Eco-Points</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="eco-points-history">
                            <ul class="list-group list-group-flush">
                                <?php if(count($recent_points) > 0): ?>
                                    <?php foreach($recent_points as $point): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-success">+<?php echo $point['points']; ?></span>
                                                <small class="text-muted ms-2"><?php echo date('M d, Y', strtotime($point['awarded_at'])); ?></small>
                                            </div>
                                            <?php
                                            $icon = 'circle';
                                            switch($point['reason_key']) {
                                                case 'challenge':
                                                    $icon = 'tasks';
                                                    break;
                                                case 'quiz':
                                                    $icon = 'question-circle';
                                                    break;
                                                case 'bonus':
                                                    $icon = 'gift';
                                                    break;
                                            }
                                            ?>
                                            <i class="fas fa-<?php echo $icon; ?> text-muted"></i>
                                        </div>
                                        <p class="mb-0 small"><?php echo htmlspecialchars($point['note'] ?? $point['reason_description'] ?? 'Points awarded'); ?></p>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center py-3">
                                        <p class="text-muted mb-0">No eco-points history yet.</p>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php if(count($recent_points) > 0): ?>
                        <div class="card-footer bg-light text-center">
                            <a href="../points_history.php" class="btn btn-sm btn-outline-primary">View Full History</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Profile Tabs -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light p-0">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="edit-tab" data-bs-toggle="tab" href="#edit" role="tab" aria-controls="edit" aria-selected="true">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="security-tab" data-bs-toggle="tab" href="#security" role="tab" aria-controls="security" aria-selected="false">
                                    <i class="fas fa-lock me-2"></i>Security
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="preferences-tab" data-bs-toggle="tab" href="#preferences" role="tab" aria-controls="preferences" aria-selected="false">
                                    <i class="fas fa-cog me-2"></i>Preferences
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Edit Profile Tab -->
                            <div class="tab-pane fade show active" id="edit" role="tabpanel" aria-labelledby="edit-tab">
                                <form action="" method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="profile_pic" class="form-label">Profile Picture</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="profile_pic" name="profile_pic" accept="image/*">
                                            <label class="input-group-text" for="profile_pic">Upload</label>
                                        </div>
                                        <div class="form-text">Max file size: 2MB. Allowed formats: JPEG, PNG, GIF</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="school" class="form-label">School</label>
                                        <input type="text" class="form-control" id="school" value="<?php echo htmlspecialchars($school_name); ?>" disabled>
                                        <div class="form-text">School information can only be updated by an administrator.</div>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Security Tab -->
                            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                                <form action="" method="post">
                                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($student['name']); ?>">
                                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">Password must be at least 8 characters long.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Password Tips:</strong>
                                            <ul class="mb-0">
                                                <li>Use at least 8 characters</li>
                                                <li>Include uppercase and lowercase letters</li>
                                                <li>Add numbers and special characters</li>
                                                <li>Avoid using personal information</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Preferences Tab -->
                            <div class="tab-pane fade" id="preferences" role="tabpanel" aria-labelledby="preferences-tab">
                                <form action="" method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Email Notifications</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="notify_challenges" checked>
                                            <label class="form-check-label" for="notify_challenges">New challenge notifications</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="notify_badges" checked>
                                            <label class="form-check-label" for="notify_badges">Badge achievement notifications</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="notify_points">
                                            <label class="form-check-label" for="notify_points">Eco-points updates</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="notify_leaderboard">
                                            <label class="form-check-label" for="notify_leaderboard">Leaderboard position changes</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Privacy Settings</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="privacy_profile" checked>
                                            <label class="form-check-label" for="privacy_profile">Show my profile to other students</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="privacy_badges" checked>
                                            <label class="form-check-label" for="privacy_badges">Show my badges on leaderboards</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="privacy_challenges" checked>
                                            <label class="form-check-label" for="privacy_challenges">Show my challenge completions</label>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Preferences functionality is coming soon. These settings are currently not active.
                                    </div>
                                    
                                    <button type="button" class="btn btn-primary" disabled>
                                        <i class="fas fa-save me-2"></i>Save Preferences
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Profile picture preview
            const profilePicInput = document.getElementById('profile_pic');
            if (profilePicInput) {
                profilePicInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.querySelector('.profile-picture').src = e.target.result;
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
            
            // Password strength validation
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (newPasswordInput && confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    if (this.value !== newPasswordInput.value) {
                        this.setCustomValidity('Passwords do not match');
                    } else {
                        this.setCustomValidity('');
                    }
                });
                
                newPasswordInput.addEventListener('input', function() {
                    if (this.value.length < 8) {
                        this.setCustomValidity('Password must be at least 8 characters long');
                    } else {
                        this.setCustomValidity('');
                        if (confirmPasswordInput.value !== this.value) {
                            confirmPasswordInput.setCustomValidity('Passwords do not match');
                        } else {
                            confirmPasswordInput.setCustomValidity('');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>