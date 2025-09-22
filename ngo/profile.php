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

// Get user ID
$user_id = $_SESSION['user_id'];

// Initialize variables
$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic information update
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $bio = trim($_POST['bio']);
        $website = trim($_POST['website']);
        
        // Validate input
        if (empty($name) || empty($email)) {
            $error_message = "Name and email are required fields.";
        } else {
            // Update profile
            $query = "UPDATE users SET 
                name = :name, 
                email = :email, 
                phone = :phone,
                bio = :bio,
                website = :website,
                updated_at = NOW()
                WHERE user_id = :user_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':bio', $bio);
            $stmt->bindParam(':website', $website);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                // Update session variable
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
        }
    }
    
    // Password update
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long.";
        } else {
            // Verify current password
            $query = "SELECT password FROM users WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $query = "UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Password updated successfully!";
                } else {
                    $error_message = "Failed to update password. Please try again.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
    }
    
    // Profile image update
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $error_message = "Only JPG, PNG, and GIF images are allowed.";
        } elseif ($_FILES['profile_image']['size'] > $max_size) {
            $error_message = "Image size cannot exceed 2MB.";
        } else {
            // Process and save the image
            $upload_dir = "../uploads/profile_images/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = "profile_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Update database with new image path
                $image_path = "uploads/profile_images/" . $filename;
                
                $query = "UPDATE users SET profile_image = :profile_image, updated_at = NOW() WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':profile_image', $image_path);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Profile image updated successfully!";
                } else {
                    $error_message = "Failed to update profile image in database.";
                }
            } else {
                $error_message = "Failed to upload image. Please try again.";
            }
        }
    }
}

// Get user information
$query = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get profile image
$profile_image = getProfileImage($user);
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
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-header {
            background-color: #f8f9fa;
            padding: 2rem 0;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .image-upload {
            position: relative;
        }
        .image-upload .camera-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: #fff;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            cursor: pointer;
        }
        #profile_image_input {
            display: none;
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
                        <li><a class="dropdown-item active" href="profile.php"><i class="fas fa-id-card me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container py-4">
        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Profile Header -->
        <div class="profile-header shadow-sm">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center">
                        <form action="profile.php" method="post" enctype="multipart/form-data" id="image_form">
                            <div class="image-upload">
                                <img src="<?php echo $profile_image; ?>" alt="Profile Image" class="profile-image mb-3">
                                <div class="camera-icon" id="upload_trigger">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <input type="file" name="profile_image" id="profile_image_input" accept="image/*">
                            </div>
                        </form>
                    </div>
                    <div class="col-md-8">
                        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="text-muted"><?php echo ucfirst($_SESSION['role_name']); ?> Account</p>
                        
                        <?php if (!empty($user['bio'])): ?>
                            <p class="mb-2"><?php echo htmlspecialchars($user['bio']); ?></p>
                        <?php endif; ?>
                        
                        <div class="d-flex flex-wrap mt-3">
                            <?php if (!empty($user['email'])): ?>
                                <div class="me-4 mb-2">
                                    <i class="fas fa-envelope text-muted me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($user['phone'])): ?>
                                <div class="me-4 mb-2">
                                    <i class="fas fa-phone text-muted me-2"></i><?php echo htmlspecialchars($user['phone']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($user['website'])): ?>
                                <div class="mb-2">
                                    <i class="fas fa-globe text-muted me-2"></i>
                                    <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank"><?php echo htmlspecialchars($user['website']); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Profile Information -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <form action="profile.php" method="post">
                            <div class="mb-3">
                                <label for="name" class="form-label">Organization Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" placeholder="https://example.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Organization Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                <div class="form-text">Brief description of your organization and its environmental mission.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Password Change -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form action="profile.php" method="post">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="update_password" class="btn btn-primary">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Account Security</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Last login: <?php echo date('M d, Y g:i A', strtotime($user['last_login'] ?? 'now')); ?></p>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_2fa" <?php echo isset($user['2fa_enabled']) && $user['2fa_enabled'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_2fa">Enable Two-Factor Authentication</label>
                            </div>
                            <div class="form-text">This feature will be available soon.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button class="btn btn-outline-primary" disabled>Setup 2FA</button>
                        </div>
                    </div>
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
            // Profile image upload
            $('#upload_trigger').click(function() {
                $('#profile_image_input').click();
            });
            
            $('#profile_image_input').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        Swal.fire({
                            title: 'Upload New Profile Image?',
                            imageUrl: e.target.result,
                            imageWidth: 250,
                            imageHeight: 250,
                            imageAlt: 'New profile image',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Upload it!',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $('#image_form').submit();
                            }
                        });
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
            
            // Password match validation
            $('#confirm_password').on('keyup', function() {
                if ($('#new_password').val() === $('#confirm_password').val()) {
                    $('#confirm_password').removeClass('is-invalid').addClass('is-valid');
                } else {
                    $('#confirm_password').removeClass('is-valid').addClass('is-invalid');
                }
            });
            
            // 2FA toggle placeholder
            $('#enable_2fa').click(function(e) {
                e.preventDefault();
                Swal.fire({
                    icon: 'info',
                    title: 'Coming Soon',
                    text: 'Two-factor authentication will be available in a future update.',
                    confirmButtonText: 'OK'
                });
                return false;
            });
        });
    </script>
</body>
</html>