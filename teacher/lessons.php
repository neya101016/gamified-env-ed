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

// Handle lesson deletion
if(isset($_POST['delete_lesson']) && !empty($_POST['lesson_id'])) {
    $lesson_id = intval($_POST['lesson_id']);
    
    // Check if teacher is authorized to delete this lesson
    $query = "SELECT * FROM lessons WHERE lesson_id = :lesson_id AND created_by = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':lesson_id', $lesson_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        // Delete lesson contents first (foreign key constraint)
        $query = "DELETE FROM lesson_contents WHERE lesson_id = :lesson_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':lesson_id', $lesson_id);
        $stmt->execute();
        
        // Delete quiz questions and options (foreign key constraints)
        $query = "SELECT question_id FROM quiz_questions 
                  WHERE quiz_id IN (SELECT quiz_id FROM quizzes WHERE lesson_id = :lesson_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':lesson_id', $lesson_id);
        $stmt->execute();
        $question_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if(!empty($question_ids)) {
            $question_ids_str = implode(',', $question_ids);
            $query = "DELETE FROM quiz_options WHERE question_id IN ($question_ids_str)";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $query = "DELETE FROM quiz_questions WHERE question_id IN ($question_ids_str)";
            $stmt = $db->prepare($query);
            $stmt->execute();
        }
        
        // Delete quizzes
        $query = "DELETE FROM quizzes WHERE lesson_id = :lesson_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':lesson_id', $lesson_id);
        $stmt->execute();
        
        // Finally delete the lesson
        $query = "DELETE FROM lessons WHERE lesson_id = :lesson_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':lesson_id', $lesson_id);
        
        if($stmt->execute()) {
            $_SESSION['success_message'] = "Lesson deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to delete the lesson.";
        }
    } else {
        $_SESSION['error_message'] = "You are not authorized to delete this lesson.";
    }
    
    // Redirect to refresh the page
    header("Location: lessons.php");
    exit;
}

// Get all lessons created by this teacher
$query = "SELECT l.*, COUNT(lc.content_id) as content_count, 
          (SELECT COUNT(*) FROM quizzes WHERE lesson_id = l.lesson_id) as has_quiz
          FROM lessons l
          LEFT JOIN lesson_contents lc ON l.lesson_id = lc.lesson_id
          WHERE l.created_by = :user_id
          GROUP BY l.lesson_id
          ORDER BY l.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Manage Lessons - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
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
                <h2><i class="fas fa-book-open me-2"></i>Manage Lessons</h2>
                <p class="text-muted">Create, edit, and manage your environmental education lessons.</p>
            </div>
            <div class="col-auto">
                <a href="create_lesson.php" class="btn btn-success">
                    <i class="fas fa-plus-circle me-2"></i>Create New Lesson
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
        
        <!-- Lessons Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <table id="lessonsTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Difficulty</th>
                            <th>Content Sections</th>
                            <th>Quiz</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($lessons) > 0): ?>
                            <?php foreach($lessons as $lesson): ?>
                                <tr>
                                    <td>
                                        <a href="edit_lesson.php?id=<?php echo $lesson['lesson_id']; ?>">
                                            <?php echo htmlspecialchars($lesson['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($lesson['difficulty']) {
                                                case 1: echo 'success'; break;
                                                case 2: echo 'primary'; break;
                                                case 3: echo 'warning text-dark'; break;
                                                case 4: 
                                                case 5: echo 'danger'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>">
                                            <?php echo $difficulty_labels[$lesson['difficulty']] ?? 'Unknown'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?php echo $lesson['content_count']; ?> sections
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($lesson['has_quiz']): ?>
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Yes</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fas fa-times me-1"></i>No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($lesson['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="edit_lesson.php?id=<?php echo $lesson['lesson_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                            <?php if($lesson['has_quiz']): ?>
                                                <a href="edit_quiz.php?lesson_id=<?php echo $lesson['lesson_id']; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-question-circle me-1"></i>Edit Quiz
                                                </a>
                                            <?php else: ?>
                                                <a href="create_quiz.php?lesson_id=<?php echo $lesson['lesson_id']; ?>" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-plus-circle me-1"></i>Add Quiz
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger delete-lesson" data-id="<?php echo $lesson['lesson_id']; ?>" data-title="<?php echo htmlspecialchars($lesson['title']); ?>">
                                                <i class="fas fa-trash-alt me-1"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No lessons found. Click "Create New Lesson" to get started.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Delete Lesson Form (hidden) -->
    <form id="deleteLessonForm" method="post" action="" style="display: none;">
        <input type="hidden" name="delete_lesson" value="1">
        <input type="hidden" name="lesson_id" id="lesson_id_to_delete">
    </form>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#lessonsTable').DataTable({
                responsive: true,
                order: [[4, 'desc']], // Sort by created date by default
                language: {
                    search: "Search lessons:",
                    lengthMenu: "Show _MENU_ lessons per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ lessons",
                    emptyTable: "No lessons available"
                }
            });
            
            // Delete lesson confirmation
            $('.delete-lesson').on('click', function() {
                const lessonId = $(this).data('id');
                const lessonTitle = $(this).data('title');
                
                Swal.fire({
                    title: 'Delete Lesson?',
                    html: `Are you sure you want to delete <strong>${lessonTitle}</strong>?<br><br>This will also delete all lesson content, quizzes, and student progress. This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#lesson_id_to_delete').val(lessonId);
                        $('#deleteLessonForm').submit();
                    }
                });
            });
        });
    </script>
</body>
</html>