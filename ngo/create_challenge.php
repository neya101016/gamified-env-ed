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

// Get verification types
$query = "SELECT * FROM verification_types ORDER BY verification_type_id";
$stmt = $db->prepare($query);
$stmt->execute();
$verification_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_challenge'])) {
    // Validate form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_date = trim($_POST['start_date']);
    $end_date = trim($_POST['end_date']);
    $eco_points = intval($_POST['eco_points']);
    $verification_type_id = intval($_POST['verification_type_id']);
    
    $errors = [];
    
    if(empty($title)) {
        $errors[] = "Challenge title is required.";
    }
    
    if(empty($description)) {
        $errors[] = "Challenge description is required.";
    }
    
    if(empty($start_date)) {
        $errors[] = "Start date is required.";
    }
    
    if(empty($end_date)) {
        $errors[] = "End date is required.";
    } else if($end_date < $start_date) {
        $errors[] = "End date cannot be earlier than start date.";
    }
    
    if($eco_points <= 0 || $eco_points > 500) {
        $errors[] = "Eco-points must be between 1 and 500.";
    }
    
    if($verification_type_id <= 0) {
        $errors[] = "Please select a verification type.";
    }
    
    // Handle image upload if provided
    $challenge_image = null;
    if(isset($_FILES['challenge_image']) && $_FILES['challenge_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if(!in_array($_FILES['challenge_image']['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed.";
        } else if($_FILES['challenge_image']['size'] > $max_size) {
            $errors[] = "Image size cannot exceed 2MB.";
        } else {
            $upload_dir = '../uploads/challenges/';
            
            // Create directory if it doesn't exist
            if(!file_exists($upload_dir) && !is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = uniqid() . '_' . basename($_FILES['challenge_image']['name']);
            $target_file = $upload_dir . $filename;
            
            if(move_uploaded_file($_FILES['challenge_image']['tmp_name'], $target_file)) {
                $challenge_image = $filename;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    }
    
    // If no errors, create the challenge
    if(empty($errors)) {
        $challenge = new Challenge($db);
        $challenge->title = $title;
        $challenge->description = $description;
        $challenge->start_date = $start_date;
        $challenge->end_date = $end_date;
        $challenge->eco_points = $eco_points;
        $challenge->verification_type_id = $verification_type_id;
        $challenge->created_by = $_SESSION['user_id'];
        $challenge->challenge_image = $challenge_image;
        
        if($challenge->create()) {
            // Redirect to challenges page
            $_SESSION['success_message'] = "Challenge created successfully!";
            header("Location: challenges.php");
            exit;
        } else {
            $errors[] = "Failed to create challenge. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Challenge - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        }
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 5px;
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
                        <a class="nav-link active" href="challenges.php">Challenges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="verifications.php">Verifications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="impact.php">Impact</a>
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-plus-circle me-2"></i>Create New Challenge</h2>
                <p class="text-muted">Create a new environmental challenge for students to participate in and earn eco-points.</p>
            </div>
        </div>
        
        <!-- Error messages if any -->
        <?php if(isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Challenge Creation Form -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post" action="" id="createChallengeForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Challenge Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        <div class="form-text">Choose a clear and engaging title for your environmental challenge.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Challenge Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <div class="form-text">Provide detailed instructions for the challenge, including what students need to do and any tips for success.</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control datepicker" id="start_date" name="start_date" required
                                   value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control datepicker" id="end_date" name="end_date" required
                                   value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : date('Y-m-d', strtotime('+30 days')); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="eco_points" class="form-label">Eco-Points Reward <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="eco_points" name="eco_points" min="1" max="500" required
                                   value="<?php echo isset($_POST['eco_points']) ? intval($_POST['eco_points']) : 50; ?>">
                            <div class="form-text">The number of eco-points students will earn upon completing this challenge (1-500).</div>
                        </div>
                        <div class="col-md-6">
                            <label for="verification_type_id" class="form-label">Verification Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="verification_type_id" name="verification_type_id" required>
                                <option value="" disabled <?php echo !isset($_POST['verification_type_id']) ? 'selected' : ''; ?>>Select verification type</option>
                                <?php foreach($verification_types as $type): ?>
                                    <option value="<?php echo $type['verification_type_id']; ?>" <?php echo (isset($_POST['verification_type_id']) && $_POST['verification_type_id'] == $type['verification_type_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">How students will prove they've completed the challenge.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="challenge_image" class="form-label">Challenge Image (Optional)</label>
                        <input type="file" class="form-control" id="challenge_image" name="challenge_image" accept="image/*">
                        <div class="form-text">Upload an image related to the challenge (max size: 2MB, formats: JPG, PNG, GIF).</div>
                        <img id="image_preview" class="image-preview" src="#" alt="Challenge Image Preview">
                    </div>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="fas fa-lightbulb me-2"></i>Tips for Creating Engaging Challenges:</h6>
                        <ul class="mb-0">
                            <li>Make challenges actionable and specific</li>
                            <li>Set reasonable time frames for completion</li>
                            <li>Consider different difficulty levels for various age groups</li>
                            <li>Reward challenges proportionally to their difficulty and impact</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="challenges.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" name="create_challenge" class="btn btn-primary">Create Challenge</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date pickers
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                minDate: "today"
            });
            
            // Image preview
            const imageInput = document.getElementById('challenge_image');
            const imagePreview = document.getElementById('image_preview');
            
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                } else {
                    imagePreview.style.display = 'none';
                }
            });
            
            // Form validation
            const form = document.getElementById('createChallengeForm');
            
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Check title
                const title = document.getElementById('title');
                if (title.value.trim() === '') {
                    isValid = false;
                    title.classList.add('is-invalid');
                } else {
                    title.classList.remove('is-invalid');
                }
                
                // Check description
                const description = document.getElementById('description');
                if (description.value.trim() === '') {
                    isValid = false;
                    description.classList.add('is-invalid');
                } else {
                    description.classList.remove('is-invalid');
                }
                
                // Check dates
                const startDate = document.getElementById('start_date');
                const endDate = document.getElementById('end_date');
                
                if (startDate.value === '') {
                    isValid = false;
                    startDate.classList.add('is-invalid');
                } else {
                    startDate.classList.remove('is-invalid');
                }
                
                if (endDate.value === '') {
                    isValid = false;
                    endDate.classList.add('is-invalid');
                } else if (new Date(endDate.value) < new Date(startDate.value)) {
                    isValid = false;
                    endDate.classList.add('is-invalid');
                } else {
                    endDate.classList.remove('is-invalid');
                }
                
                // Check eco-points
                const ecoPoints = document.getElementById('eco_points');
                if (ecoPoints.value < 1 || ecoPoints.value > 500) {
                    isValid = false;
                    ecoPoints.classList.add('is-invalid');
                } else {
                    ecoPoints.classList.remove('is-invalid');
                }
                
                // Check verification type
                const verificationType = document.getElementById('verification_type_id');
                if (verificationType.value === '') {
                    isValid = false;
                    verificationType.classList.add('is-invalid');
                } else {
                    verificationType.classList.remove('is-invalid');
                }
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>