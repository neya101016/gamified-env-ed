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

// Check if lesson ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No lesson ID provided.";
    header("Location: lessons.php");
    exit;
}

$lesson_id = intval($_GET['id']);

// Check if teacher has access to this lesson
$query = "SELECT * FROM lessons WHERE lesson_id = :lesson_id AND created_by = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':lesson_id', $lesson_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    $_SESSION['error_message'] = "You don't have permission to edit this lesson.";
    header("Location: lessons.php");
    exit;
}

// Create lesson object
$lesson = new Lesson($db);
$lesson_data = $lesson->getLessonById($lesson_id);

// Get content types for the content section
$query = "SELECT * FROM content_types ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$content_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission for updating lesson details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lesson'])) {
    // Validate form data
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $difficulty = intval($_POST['difficulty']);
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Lesson title is required.";
    }
    
    if (empty($summary)) {
        $errors[] = "Lesson summary is required.";
    }
    
    if ($difficulty < 1 || $difficulty > 5) {
        $errors[] = "Please select a valid difficulty level.";
    }
    
    // If no errors, update the lesson
    if (empty($errors)) {
        $lesson->lesson_id = $lesson_id;
        $lesson->title = $title;
        $lesson->summary = $summary;
        $lesson->difficulty = $difficulty;
        
        if ($lesson->update()) {
            $_SESSION['success_message'] = "Lesson updated successfully!";
            header("Location: edit_lesson.php?id=" . $lesson_id);
            exit;
        } else {
            $errors[] = "Failed to update lesson. Please try again.";
        }
    }
}

// Handle form submission for adding content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_content'])) {
    // Validate form data
    $content_type_id = intval($_POST['content_type_id']);
    $content_title = trim($_POST['content_title']);
    $content_body = trim($_POST['content_body']);
    $external_url = isset($_POST['external_url']) ? trim($_POST['external_url']) : null;
    
    $errors = [];
    
    if (empty($content_title)) {
        $errors[] = "Content title is required.";
    }
    
    if ($content_type_id < 1) {
        $errors[] = "Please select a valid content type.";
    }
    
    if (empty($content_body) && empty($external_url)) {
        $errors[] = "Content body or external URL is required.";
    }
    
    // If no errors, add the content
    if (empty($errors)) {
        $lesson->lesson_id = $lesson_id;
        
        if ($lesson->addContent($content_type_id, $content_title, $content_body, $external_url)) {
            $_SESSION['success_message'] = "Content added successfully!";
            header("Location: edit_lesson.php?id=" . $lesson_id);
            exit;
        } else {
            $errors[] = "Failed to add content. Please try again.";
        }
    }
}

// Handle content deletion
if (isset($_GET['delete_content']) && !empty($_GET['content_id'])) {
    $content_id = intval($_GET['content_id']);
    
    // Verify ownership
    $query = "SELECT lc.* FROM lesson_contents lc
              JOIN lessons l ON lc.lesson_id = l.lesson_id
              WHERE lc.content_id = :content_id AND l.created_by = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':content_id', $content_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Delete the content
        $query = "DELETE FROM lesson_contents WHERE content_id = :content_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':content_id', $content_id);
        
        if ($stmt->execute()) {
            // Reorder sequence numbers
            $query = "SET @seq := -1;
                      UPDATE lesson_contents 
                      SET sequence_num = @seq := @seq + 1
                      WHERE lesson_id = :lesson_id
                      ORDER BY sequence_num ASC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':lesson_id', $lesson_id);
            $stmt->execute();
            
            $_SESSION['success_message'] = "Content deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete content.";
        }
    } else {
        $_SESSION['error_message'] = "You are not authorized to delete this content.";
    }
    
    header("Location: edit_lesson.php?id=" . $lesson_id);
    exit;
}

// Handle content reordering
if (isset($_GET['move']) && in_array($_GET['move'], ['up', 'down']) && !empty($_GET['content_id'])) {
    $content_id = intval($_GET['content_id']);
    $direction = $_GET['move'];
    
    // Verify ownership
    $query = "SELECT lc.* FROM lesson_contents lc
              JOIN lessons l ON lc.lesson_id = l.lesson_id
              WHERE lc.content_id = :content_id AND l.created_by = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':content_id', $content_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $content = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_seq = $content['sequence_num'];
        
        if ($direction === 'up' && $current_seq > 0) {
            // Swap with previous content
            $query = "UPDATE lesson_contents 
                      SET sequence_num = :new_seq 
                      WHERE lesson_id = :lesson_id AND sequence_num = :target_seq";
            $stmt = $db->prepare($query);
            $target_seq = $current_seq - 1;
            $stmt->bindParam(':new_seq', $current_seq);
            $stmt->bindParam(':lesson_id', $lesson_id);
            $stmt->bindParam(':target_seq', $target_seq);
            $stmt->execute();
            
            $query = "UPDATE lesson_contents 
                      SET sequence_num = :new_seq 
                      WHERE content_id = :content_id";
            $stmt = $db->prepare($query);
            $new_seq = $current_seq - 1;
            $stmt->bindParam(':new_seq', $new_seq);
            $stmt->bindParam(':content_id', $content_id);
            $stmt->execute();
            
            $_SESSION['success_message'] = "Content moved up successfully!";
        } elseif ($direction === 'down') {
            // Check if it's not the last item
            $query = "SELECT MAX(sequence_num) as max_seq FROM lesson_contents WHERE lesson_id = :lesson_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':lesson_id', $lesson_id);
            $stmt->execute();
            $max_seq = $stmt->fetch(PDO::FETCH_ASSOC)['max_seq'];
            
            if ($current_seq < $max_seq) {
                // Swap with next content
                $query = "UPDATE lesson_contents 
                          SET sequence_num = :new_seq 
                          WHERE lesson_id = :lesson_id AND sequence_num = :target_seq";
                $stmt = $db->prepare($query);
                $target_seq = $current_seq + 1;
                $stmt->bindParam(':new_seq', $current_seq);
                $stmt->bindParam(':lesson_id', $lesson_id);
                $stmt->bindParam(':target_seq', $target_seq);
                $stmt->execute();
                
                $query = "UPDATE lesson_contents 
                          SET sequence_num = :new_seq 
                          WHERE content_id = :content_id";
                $stmt = $db->prepare($query);
                $new_seq = $current_seq + 1;
                $stmt->bindParam(':new_seq', $new_seq);
                $stmt->bindParam(':content_id', $content_id);
                $stmt->execute();
                
                $_SESSION['success_message'] = "Content moved down successfully!";
            }
        }
    } else {
        $_SESSION['error_message'] = "You are not authorized to reorder this content.";
    }
    
    header("Location: edit_lesson.php?id=" . $lesson_id);
    exit;
}

// Refresh lesson data
$lesson_data = $lesson->getLessonById($lesson_id);

// Difficulty levels
$difficulty_labels = [
    1 => 'Beginner',
    2 => 'Easy',
    3 => 'Intermediate',
    4 => 'Advanced',
    5 => 'Expert'
];

// Page title
$pageTitle = "Edit Lesson: " . htmlspecialchars($lesson_data['title']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <!-- Summernote CSS (WYSIWYG editor) -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .content-preview {
            max-height: 150px;
            overflow: hidden;
            position: relative;
        }
        .content-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50px;
            background: linear-gradient(rgba(255,255,255,0), rgba(255,255,255,1));
        }
        .badge-difficulty {
            font-size: 0.9rem;
            padding: 0.5rem 0.7rem;
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
                <h2><i class="fas fa-edit me-2"></i>Edit Lesson: <?php echo htmlspecialchars($lesson_data['title']); ?></h2>
                <p class="text-muted">Update lesson details, add content sections, and manage the quiz.</p>
            </div>
            <div class="col-auto">
                <a href="lessons.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Lessons
                </a>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
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
        
        <!-- Tabs for different sections -->
        <ul class="nav nav-tabs mb-4" id="lessonTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">
                    <i class="fas fa-info-circle me-1"></i>Lesson Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab" aria-controls="content" aria-selected="false">
                    <i class="fas fa-list-alt me-1"></i>Content Sections
                    <span class="badge bg-info text-dark ms-1"><?php echo count($lesson_data['contents']); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="quiz-tab" data-bs-toggle="tab" data-bs-target="#quiz" type="button" role="tab" aria-controls="quiz" aria-selected="false">
                    <i class="fas fa-question-circle me-1"></i>Quiz
                    <?php if($lesson_data['quiz']): ?>
                        <span class="badge bg-success ms-1">Created</span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-1">Not Created</span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="lessonTabsContent">
            <!-- Lesson Details Tab -->
            <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="post" action="" id="updateLessonForm">
                            <div class="mb-3">
                                <label for="title" class="form-label">Lesson Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       value="<?php echo htmlspecialchars($lesson_data['title']); ?>">
                                <div class="form-text">Choose a clear and descriptive title for your lesson.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="summary" class="form-label">Lesson Summary <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="summary" name="summary" rows="4" required><?php echo htmlspecialchars($lesson_data['summary']); ?></textarea>
                                <div class="form-text">Provide a brief overview of what students will learn in this lesson.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="difficulty" class="form-label">Difficulty Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="difficulty" name="difficulty" required>
                                    <?php foreach($difficulty_labels as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($lesson_data['difficulty'] == $key) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Choose the appropriate difficulty level for your target audience.</div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="lessons.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" name="update_lesson" class="btn btn-primary">Update Lesson</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Content Sections Tab -->
            <div class="tab-pane fade" id="content" role="tabpanel" aria-labelledby="content-tab">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Add New Content Section</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="" id="addContentForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="content_title" class="form-label">Section Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="content_title" name="content_title" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="content_type_id" class="form-label">Content Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="content_type_id" name="content_type_id" required>
                                        <option value="" disabled selected>Select content type</option>
                                        <?php foreach($content_types as $type): ?>
                                            <option value="<?php echo $type['content_type_id']; ?>">
                                                <?php echo htmlspecialchars($type['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content_body" class="form-label">Content Body</label>
                                <textarea class="form-control summernote" id="content_body" name="content_body" rows="6"></textarea>
                                <div class="form-text">You can use the rich text editor to format your content, add images, etc.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="external_url" class="form-label">External URL (optional)</label>
                                <input type="url" class="form-control" id="external_url" name="external_url" placeholder="https://example.com/resource">
                                <div class="form-text">If you're adding a video or interactive content, you can provide an external URL.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="add_content" class="btn btn-success">
                                    <i class="fas fa-plus-circle me-2"></i>Add Content Section
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Existing Content Sections -->
                <h5 class="mb-3">Existing Content Sections</h5>
                <?php if(count($lesson_data['contents']) > 0): ?>
                    <div class="list-group">
                        <?php foreach($lesson_data['contents'] as $index => $content): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">
                                        <span class="badge bg-secondary me-2"><?php echo $index + 1; ?></span>
                                        <?php echo htmlspecialchars($content['title']); ?>
                                    </h5>
                                    <small class="text-muted">
                                        <span class="badge bg-info text-dark">
                                            <?php echo htmlspecialchars($content['content_type_name']); ?>
                                        </span>
                                    </small>
                                </div>
                                
                                <div class="content-preview mb-3">
                                    <?php if(!empty($content['body'])): ?>
                                        <?php echo $content['body']; ?>
                                    <?php elseif(!empty($content['external_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($content['external_url']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($content['external_url']); ?>
                                        </a>
                                    <?php else: ?>
                                        <em>No content available.</em>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if($index > 0): ?>
                                            <a href="?id=<?php echo $lesson_id; ?>&move=up&content_id=<?php echo $content['content_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-arrow-up"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if($index < count($lesson_data['contents']) - 1): ?>
                                            <a href="?id=<?php echo $lesson_id; ?>&move=down&content_id=<?php echo $content['content_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-arrow-down"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="#" class="btn btn-sm btn-outline-danger delete-content" data-id="<?php echo $content['content_id']; ?>" data-title="<?php echo htmlspecialchars($content['title']); ?>">
                                            <i class="fas fa-trash-alt me-1"></i>Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No content sections have been added yet. Use the form above to add content to your lesson.
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quiz Tab -->
            <div class="tab-pane fade" id="quiz" role="tabpanel" aria-labelledby="quiz-tab">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if($lesson_data['quiz']): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>This lesson has a quiz!
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5><?php echo htmlspecialchars($lesson_data['quiz']['title']); ?></h5>
                                    <p class="mb-0">
                                        <?php if($lesson_data['quiz']['time_limit_minutes']): ?>
                                            <span class="badge bg-info text-dark me-2">
                                                <i class="fas fa-clock me-1"></i><?php echo $lesson_data['quiz']['time_limit_minutes']; ?> min
                                            </span>
                                        <?php endif; ?>
                                        <span class="badge bg-primary me-2">
                                            <i class="fas fa-star me-1"></i><?php echo $lesson_data['quiz']['total_marks']; ?> marks
                                        </span>
                                    </p>
                                </div>
                                <a href="edit_quiz.php?lesson_id=<?php echo $lesson_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit me-1"></i>Edit Quiz
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>This lesson doesn't have a quiz yet.
                            </div>
                            <p>Add a quiz to test your students' knowledge after they complete this lesson.</p>
                            <a href="create_quiz.php?lesson_id=<?php echo $lesson_id; ?>" class="btn btn-success">
                                <i class="fas fa-plus-circle me-2"></i>Create Quiz
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Content Confirmation Modal (hidden) -->
    <form id="deleteContentForm" method="get" action="" style="display: none;">
        <input type="hidden" name="id" value="<?php echo $lesson_id; ?>">
        <input type="hidden" name="delete_content" value="1">
        <input type="hidden" name="content_id" id="content_id_to_delete">
    </form>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <!-- Summernote JS -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <!-- Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Initialize Summernote WYSIWYG editor
            $('.summernote').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
            
            // Form validation for lesson update
            $('#updateLessonForm').on('submit', function(e) {
                var isValid = true;
                
                // Check title
                if ($('#title').val().trim() === '') {
                    isValid = false;
                    $('#title').addClass('is-invalid');
                } else {
                    $('#title').removeClass('is-invalid');
                }
                
                // Check summary
                if ($('#summary').val().trim() === '') {
                    isValid = false;
                    $('#summary').addClass('is-invalid');
                } else {
                    $('#summary').removeClass('is-invalid');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please fill in all required fields.'
                    });
                }
            });
            
            // Form validation for content addition
            $('#addContentForm').on('submit', function(e) {
                var isValid = true;
                
                // Check content title
                if ($('#content_title').val().trim() === '') {
                    isValid = false;
                    $('#content_title').addClass('is-invalid');
                } else {
                    $('#content_title').removeClass('is-invalid');
                }
                
                // Check content type
                if ($('#content_type_id').val() === null) {
                    isValid = false;
                    $('#content_type_id').addClass('is-invalid');
                } else {
                    $('#content_type_id').removeClass('is-invalid');
                }
                
                // Check either body or URL is provided
                var bodyContent = $('.summernote').summernote('code').trim();
                var externalUrl = $('#external_url').val().trim();
                
                if (bodyContent === '<p><br></p>' && externalUrl === '') {
                    isValid = false;
                    $('.note-editor').addClass('border-danger');
                    $('#external_url').addClass('is-invalid');
                } else {
                    $('.note-editor').removeClass('border-danger');
                    $('#external_url').removeClass('is-invalid');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please fill in all required fields.'
                    });
                }
            });
            
            // Delete content confirmation
            $('.delete-content').on('click', function(e) {
                e.preventDefault();
                
                const contentId = $(this).data('id');
                const contentTitle = $(this).data('title');
                
                Swal.fire({
                    title: 'Delete Content Section?',
                    html: `Are you sure you want to delete <strong>${contentTitle}</strong>?<br><br>This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#content_id_to_delete').val(contentId);
                        $('#deleteContentForm').submit();
                    }
                });
            });
            
            // Show active tab based on URL hash
            const activeTabId = window.location.hash ? window.location.hash.substring(1) : 'details';
            $(`#${activeTabId}-tab`).tab('show');
            
            // Update URL hash when tab changes
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                window.location.hash = e.target.id.replace('-tab', '');
            });
        });
    </script>
</body>
</html>