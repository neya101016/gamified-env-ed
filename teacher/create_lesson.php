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

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_lesson'])) {
    // Validate form data
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $difficulty = intval($_POST['difficulty']);
    
    $errors = [];
    
    if(empty($title)) {
        $errors[] = "Lesson title is required.";
    }
    
    if(empty($summary)) {
        $errors[] = "Lesson summary is required.";
    }
    
    if($difficulty < 1 || $difficulty > 5) {
        $errors[] = "Please select a valid difficulty level.";
    }
    
    // If no errors, create the lesson
    if(empty($errors)) {
        $lesson = new Lesson($db);
        $lesson->title = $title;
        $lesson->summary = $summary;
        $lesson->difficulty = $difficulty;
        $lesson->created_by = $_SESSION['user_id'];
        
        if($lesson->create()) {
            // Redirect to edit page to add content
            $_SESSION['success_message'] = "Lesson created successfully! Now you can add content.";
            header("Location: edit_lesson.php?id=" . $lesson->lesson_id);
            exit;
        } else {
            $errors[] = "Failed to create lesson. Please try again.";
        }
    }
}

// Difficulty levels
$difficulty_labels = [
    1 => 'Beginner',
    2 => 'Easy',
    3 => 'Intermediate',
    4 => 'Advanced',
    5 => 'Expert'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Lesson - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        <a class="nav-link active" href="lessons.php">Lessons</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">Leaderboard</a>
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
                <h2><i class="fas fa-plus-circle me-2"></i>Create New Lesson</h2>
                <p class="text-muted">Create a new environmental education lesson for your students.</p>
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
        
        <!-- Lesson Creation Form -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post" action="" id="createLessonForm">
                    <div class="mb-3">
                        <label for="title" class="form-label">Lesson Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        <div class="form-text">Choose a clear and descriptive title for your lesson.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="summary" class="form-label">Lesson Summary <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="summary" name="summary" rows="4" required><?php echo isset($_POST['summary']) ? htmlspecialchars($_POST['summary']) : ''; ?></textarea>
                        <div class="form-text">Provide a brief overview of what students will learn in this lesson.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="difficulty" class="form-label">Difficulty Level <span class="text-danger">*</span></label>
                        <select class="form-select" id="difficulty" name="difficulty" required>
                            <option value="" disabled <?php echo !isset($_POST['difficulty']) ? 'selected' : ''; ?>>Select difficulty level</option>
                            <?php foreach($difficulty_labels as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Choose the appropriate difficulty level for your target audience.</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <p class="mb-0"><i class="fas fa-info-circle me-2"></i> After creating the lesson, you'll be able to add content sections and a quiz.</p>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="lessons.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" name="create_lesson" class="btn btn-primary">Create Lesson</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Add any custom JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const form = document.getElementById('createLessonForm');
            
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
                
                // Check summary
                const summary = document.getElementById('summary');
                if (summary.value.trim() === '') {
                    isValid = false;
                    summary.classList.add('is-invalid');
                } else {
                    summary.classList.remove('is-invalid');
                }
                
                // Check difficulty
                const difficulty = document.getElementById('difficulty');
                if (difficulty.value === '') {
                    isValid = false;
                    difficulty.classList.add('is-invalid');
                } else {
                    difficulty.classList.remove('is-invalid');
                }
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>