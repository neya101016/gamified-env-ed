<?php
// Include the header
$pageTitle = "My Profile";
$currentPage = "profile";
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get user ID (either the logged-in user or a profile being viewed)
$userId = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

// Check if the user exists
$userQuery = "
    SELECT 
        u.user_id,
        u.username,
        u.email,
        u.profile_image,
        u.bio,
        u.join_date as created_at,
        r.role_name,
        COALESCE(s.school_name, 'Independent') AS school_name,
        s.school_id
    FROM 
        users u
    JOIN 
        roles r ON u.role_id = r.role_id
    LEFT JOIN 
        students st ON u.user_id = st.user_id
    LEFT JOIN 
        schools s ON st.school_id = s.school_id
    WHERE 
        u.user_id = :user_id
";

$stmt = $db->prepare($userQuery);
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user doesn't exist, redirect to home
if (!$user) {
    $_SESSION['error'] = "User not found";
    header("Location: index.php");
    exit;
}

// Check if this is the profile owner
$isProfileOwner = ($_SESSION['user_id'] == $userId);

// Get user stats
$statsQuery = "
    SELECT 
        COALESCE(SUM(ep.points), 0) AS total_points,
        COUNT(DISTINCT ub.badge_id) AS badge_count,
        (
            SELECT COUNT(*) 
            FROM challenge_participation cp 
            WHERE cp.user_id = :user_id AND cp.status = 'completed'
        ) AS completed_challenges,
        (
            SELECT COUNT(*) 
            FROM lesson_progress lp 
            WHERE lp.user_id = :user_id AND lp.status = 'completed'
        ) AS completed_lessons
    FROM 
        users u
    LEFT JOIN 
        eco_points ep ON u.user_id = ep.user_id
    LEFT JOIN 
        user_badges ub ON u.user_id = ub.user_id
    WHERE 
        u.user_id = :user_id
";

$stmt = $db->prepare($statsQuery);
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's rank
$rankQuery = "
    SELECT 
        COUNT(*) + 1 AS rank
    FROM 
        (
            SELECT 
                u.user_id, 
                COALESCE(SUM(ep.points), 0) AS total_points
            FROM 
                users u
            LEFT JOIN 
                eco_points ep ON u.user_id = ep.user_id
            GROUP BY 
                u.user_id
            HAVING 
                total_points > (
                    SELECT COALESCE(SUM(points), 0)
                    FROM eco_points
                    WHERE user_id = :user_id
                )
        ) AS higher_ranked
";

$stmt = $db->prepare($rankQuery);
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
$rankResult = $stmt->fetch(PDO::FETCH_ASSOC);
$rank = $rankResult['rank'];

// Get user's badges
$badgesQuery = "
    SELECT 
        b.badge_id,
        b.name,
        b.description,
        b.image,
        b.category,
        ub.awarded_at
    FROM 
        user_badges ub
    JOIN 
        badges b ON ub.badge_id = b.badge_id
    WHERE 
        ub.user_id = :user_id
    ORDER BY 
        ub.awarded_at DESC
";

$stmt = $db->prepare($badgesQuery);
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
$badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's recent activity
$activityQuery = "
    SELECT 
        'points' AS activity_type,
        ep.points,
        ep.description,
        ep.activity_type AS subtype,
        ep.awarded_at AS activity_date,
        NULL AS badge_id,
        NULL AS badge_name,
        NULL AS badge_image,
        NULL AS challenge_id,
        NULL AS challenge_title,
        NULL AS lesson_id,
        NULL AS lesson_title
    FROM 
        eco_points ep
    WHERE 
        ep.user_id = :user_id
    
    UNION ALL
    
    SELECT 
        'badge' AS activity_type,
        NULL AS points,
        b.description,
        NULL AS subtype,
        ub.awarded_at AS activity_date,
        b.badge_id,
        b.name AS badge_name,
        b.image AS badge_image,
        NULL AS challenge_id,
        NULL AS challenge_title,
        NULL AS lesson_id,
        NULL AS lesson_title
    FROM 
        user_badges ub
    JOIN 
        badges b ON ub.badge_id = b.badge_id
    WHERE 
        ub.user_id = :user_id
    
    UNION ALL
    
    SELECT 
        'challenge' AS activity_type,
        NULL AS points,
        CONCAT('Status: ', cp.status) AS description,
        NULL AS subtype,
        CASE 
            WHEN cp.status = 'completed' THEN cp.completion_date
            ELSE cp.join_date
        END AS activity_date,
        NULL AS badge_id,
        NULL AS badge_name,
        NULL AS badge_image,
        c.challenge_id,
        c.title AS challenge_title,
        NULL AS lesson_id,
        NULL AS lesson_title
    FROM 
        challenge_participation cp
    JOIN 
        challenges c ON cp.challenge_id = c.challenge_id
    WHERE 
        cp.user_id = :user_id
    
    UNION ALL
    
    SELECT 
        'lesson' AS activity_type,
        NULL AS points,
        CONCAT('Status: ', lp.status) AS description,
        NULL AS subtype,
        lp.last_updated AS activity_date,
        NULL AS badge_id,
        NULL AS badge_name,
        NULL AS badge_image,
        NULL AS challenge_id,
        NULL AS challenge_title,
        l.lesson_id,
        l.title AS lesson_title
    FROM 
        lesson_progress lp
    JOIN 
        lessons l ON lp.lesson_id = l.lesson_id
    WHERE 
        lp.user_id = :user_id
    
    ORDER BY 
        activity_date DESC
    LIMIT 15
";

$stmt = $db->prepare($activityQuery);
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle profile update
$message = '';
$error = '';

if ($isProfileOwner && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $bio = trim($_POST['bio']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate username
    if (empty($username)) {
        $error = "Username cannot be empty";
    } else {
        // Check if username exists (excluding current user)
        $checkUsernameQuery = "SELECT COUNT(*) FROM users WHERE username = :username AND user_id != :user_id";
        $stmt = $db->prepare($checkUsernameQuery);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            $error = "Username already taken";
        } else {
            // Update profile
            $updateQuery = "UPDATE users SET username = :username, bio = :bio WHERE user_id = :user_id";
            $stmt = $db->prepare($updateQuery);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":bio", $bio);
            $stmt->bindParam(":user_id", $userId);
            
            if ($stmt->execute()) {
                // Handle password change if requested
                if (!empty($currentPassword) && !empty($newPassword)) {
                    // Verify current password
                    $checkPasswordQuery = "SELECT password FROM users WHERE user_id = :user_id";
                    $stmt = $db->prepare($checkPasswordQuery);
                    $stmt->bindParam(":user_id", $userId);
                    $stmt->execute();
                    $storedPassword = $stmt->fetchColumn();
                    
                    if (password_verify($currentPassword, $storedPassword)) {
                        if ($newPassword === $confirmPassword) {
                            // Update password
                            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                            $updatePasswordQuery = "UPDATE users SET password = :password WHERE user_id = :user_id";
                            $stmt = $db->prepare($updatePasswordQuery);
                            $stmt->bindParam(":password", $hashedPassword);
                            $stmt->bindParam(":user_id", $userId);
                            
                            if ($stmt->execute()) {
                                $message = "Profile and password updated successfully";
                            } else {
                                $error = "Error updating password";
                            }
                        } else {
                            $error = "New passwords do not match";
                        }
                    } else {
                        $error = "Current password is incorrect";
                    }
                } else {
                    $message = "Profile updated successfully";
                }
                
                // Handle profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = "uploads/profile_images/";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($fileExtension, $allowedExtensions)) {
                        $newFileName = "user_" . $userId . "_" . time() . "." . $fileExtension;
                        $uploadPath = $uploadDir . $newFileName;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                            // Update profile image path in database
                            $updateImageQuery = "UPDATE users SET profile_image = :profile_image WHERE user_id = :user_id";
                            $stmt = $db->prepare($updateImageQuery);
                            $stmt->bindParam(":profile_image", $uploadPath);
                            $stmt->bindParam(":user_id", $userId);
                            
                            if ($stmt->execute()) {
                                $message = "Profile updated successfully with new image";
                                // Update user data to show new image
                                $user['profile_image'] = $uploadPath;
                            } else {
                                $error = "Error updating profile image in database";
                            }
                        } else {
                            $error = "Error uploading profile image";
                        }
                    } else {
                        $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed";
                    }
                }
                
                // Redirect to refresh page with updated data
                if (empty($error)) {
                    header("Location: profile.php");
                    exit;
                }
            } else {
                $error = "Error updating profile";
            }
        }
    }
}
?>

<div class="container mt-4">
    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-4">
            <div class="card eco-card mb-4">
                <div class="card-header bg-success text-white">
                    <h2 class="mb-0">
                        <?php if ($isProfileOwner): ?>
                            <i class="fas fa-user-circle"></i> My Profile
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i> User Profile
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if ($user['profile_image']): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                 alt="Profile" class="img-fluid rounded-circle profile-image" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <img src="assets/images/default-avatar.png" 
                                 alt="Default Profile" class="img-fluid rounded-circle profile-image" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p class="text-muted"><?php echo ucfirst(htmlspecialchars($user['role_name'])); ?></p>
                    
                    <?php if (!empty($user['school_name']) && $user['school_name'] !== 'Independent'): ?>
                        <p><i class="fas fa-school"></i> <?php echo htmlspecialchars($user['school_name']); ?></p>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <div class="badge badge-primary badge-pill p-2 mb-2">
                            <i class="fas fa-leaf"></i> <?php echo number_format($stats['total_points']); ?> Eco Points
                        </div>
                        <div class="badge badge-info badge-pill p-2 mb-2">
                            <i class="fas fa-trophy"></i> Rank #<?php echo $rank; ?>
                        </div>
                        <div class="badge badge-warning badge-pill p-2 mb-2">
                            <i class="fas fa-certificate"></i> <?php echo $stats['badge_count']; ?> Badges
                        </div>
                    </div>
                    
                    <?php if (!empty($user['bio'])): ?>
                        <div class="mt-3">
                            <h5>About Me</h5>
                            <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <p class="text-muted">
                            <small>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></small>
                        </p>
                    </div>
                    
                    <?php if ($isProfileOwner): ?>
                        <button class="btn btn-outline-success btn-block mt-3" data-toggle="modal" data-target="#editProfileModal">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Environmental Impact -->
            <div class="card eco-card mb-4">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-chart-bar"></i> Environmental Impact</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 text-center mb-3">
                            <div class="h1 text-success"><?php echo $stats['completed_challenges']; ?></div>
                            <div class="text-muted">Challenges Completed</div>
                        </div>
                        <div class="col-6 text-center mb-3">
                            <div class="h1 text-primary"><?php echo $stats['completed_lessons']; ?></div>
                            <div class="text-muted">Lessons Completed</div>
                        </div>
                    </div>
                    
                    <!-- Progress bars for impact metrics -->
                    <div class="mt-3">
                        <h6>Carbon Reduction</h6>
                        <div class="progress mb-3" style="height: 20px;">
                            <?php 
                            // Calculate progress based on challenges completed (just for demonstration)
                            $carbonProgress = min(100, $stats['completed_challenges'] * 5);
                            ?>
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $carbonProgress; ?>%;" 
                                 aria-valuenow="<?php echo $carbonProgress; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo $carbonProgress; ?>%
                            </div>
                        </div>
                        
                        <h6>Water Conservation</h6>
                        <div class="progress mb-3" style="height: 20px;">
                            <?php 
                            // Calculate progress based on points (just for demonstration)
                            $waterProgress = min(100, ($stats['total_points'] / 1000) * 100);
                            ?>
                            <div class="progress-bar bg-info" role="progressbar" 
                                 style="width: <?php echo $waterProgress; ?>%;" 
                                 aria-valuenow="<?php echo $waterProgress; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round($waterProgress); ?>%
                            </div>
                        </div>
                        
                        <h6>Energy Savings</h6>
                        <div class="progress" style="height: 20px;">
                            <?php 
                            // Calculate progress based on lessons completed (just for demonstration)
                            $energyProgress = min(100, $stats['completed_lessons'] * 8);
                            ?>
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: <?php echo $energyProgress; ?>%;" 
                                 aria-valuenow="<?php echo $energyProgress; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round($energyProgress); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Badges -->
            <div class="card eco-card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-certificate"></i> Earned Badges</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($badges)): ?>
                        <div class="alert alert-info">
                            No badges earned yet. Complete challenges and lessons to earn badges!
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($badges as $badge): ?>
                                <div class="col-md-4 col-sm-6 mb-4">
                                    <div class="card h-100 border-warning">
                                        <div class="card-body text-center">
                                            <?php if ($badge['image']): ?>
                                                <img src="assets/images/badges/<?php echo htmlspecialchars($badge['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($badge['name']); ?>" class="img-fluid mb-2" 
                                                     style="max-width: 100px;">
                                            <?php else: ?>
                                                <i class="fas fa-certificate fa-4x text-warning mb-2"></i>
                                            <?php endif; ?>
                                            
                                            <h5 class="card-title"><?php echo htmlspecialchars($badge['name']); ?></h5>
                                            <p class="card-text small"><?php echo htmlspecialchars($badge['description']); ?></p>
                                            <div class="text-muted">
                                                <small>Earned on <?php echo date('M j, Y', strtotime($badge['awarded_at'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card eco-card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($activities)): ?>
                        <div class="alert alert-info">No recent activity.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($activities as $activity): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div>
                                            <?php if ($activity['activity_type'] === 'points'): ?>
                                                <h5 class="mb-1">
                                                    <i class="fas fa-leaf text-success"></i> 
                                                    Earned <?php echo $activity['points']; ?> Eco Points
                                                </h5>
                                                <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($activity['subtype']); ?> activity
                                                </small>
                                                
                                            <?php elseif ($activity['activity_type'] === 'badge'): ?>
                                                <h5 class="mb-1">
                                                    <i class="fas fa-certificate text-warning"></i> 
                                                    Earned "<?php echo htmlspecialchars($activity['badge_name']); ?>" Badge
                                                </h5>
                                                <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                
                                            <?php elseif ($activity['activity_type'] === 'challenge'): ?>
                                                <h5 class="mb-1">
                                                    <i class="fas fa-tasks text-primary"></i> 
                                                    Challenge: <?php echo htmlspecialchars($activity['challenge_title']); ?>
                                                </h5>
                                                <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                
                                            <?php elseif ($activity['activity_type'] === 'lesson'): ?>
                                                <h5 class="mb-1">
                                                    <i class="fas fa-book-reader text-info"></i> 
                                                    Lesson: <?php echo htmlspecialchars($activity['lesson_title']); ?>
                                                </h5>
                                                <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <small>
                                            <?php echo date('M j, Y', strtotime($activity['activity_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<?php if ($isProfileOwner): ?>
<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                        <small class="form-text text-muted">Tell us about yourself and your environmental interests.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_image">Profile Image</label>
                        <input type="file" class="form-control-file" id="profile_image" name="profile_image">
                        <small class="form-text text-muted">Supported formats: JPG, JPEG, PNG, GIF</small>
                    </div>
                    
                    <hr>
                    
                    <h5>Change Password</h5>
                    <small class="form-text text-muted mb-3">Leave blank if you don't want to change your password.</small>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" name="update_profile">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Include the footer
require_once 'includes/footer.php';
?>