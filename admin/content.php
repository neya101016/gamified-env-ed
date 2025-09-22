<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and has admin role
requireRole('admin');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Set page title
$pageTitle = "Content Management";

// Get content types
$query = "SELECT * FROM content_types ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$content_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all lessons with their creators
$query = "SELECT l.*, u.name as creator_name, COUNT(lc.content_id) as content_count,
         (SELECT COUNT(*) FROM quizzes WHERE lesson_id = l.lesson_id) as has_quiz
         FROM lessons l
         LEFT JOIN users u ON l.created_by = u.user_id
         LEFT JOIN lesson_contents lc ON l.lesson_id = lc.lesson_id
         GROUP BY l.lesson_id
         ORDER BY l.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all challenges with their creators
$query = "SELECT c.*, u.name as creator_name, vt.name as verification_type,
         (SELECT COUNT(*) FROM user_challenges WHERE challenge_id = c.challenge_id) as enrollment_count
         FROM challenges c
         LEFT JOIN users u ON c.created_by = u.user_id
         LEFT JOIN verification_types vt ON c.verification_type_id = vt.verification_type_id
         ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Difficulty levels
$difficulty_labels = [
    1 => 'Beginner',
    2 => 'Easy',
    3 => 'Intermediate',
    4 => 'Advanced',
    5 => 'Expert'
];

// Handle lesson status toggle
if (isset($_GET['toggle_lesson']) && !empty($_GET['lesson_id'])) {
    $lesson_id = intval($_GET['lesson_id']);
    $current_status = intval($_GET['current_status']);
    $new_status = $current_status ? 0 : 1;
    
    // Update lesson status
    $query = "UPDATE lessons SET is_active = :status WHERE lesson_id = :lesson_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':lesson_id', $lesson_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Lesson status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update lesson status.";
    }
    
    header("Location: content.php");
    exit;
}

// Handle challenge status toggle
if (isset($_GET['toggle_challenge']) && !empty($_GET['challenge_id'])) {
    $challenge_id = intval($_GET['challenge_id']);
    $current_status = intval($_GET['current_status']);
    $new_status = $current_status ? 0 : 1;
    
    // Update challenge status
    $query = "UPDATE challenges SET is_active = :status WHERE challenge_id = :challenge_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':challenge_id', $challenge_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Challenge status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update challenge status.";
    }
    
    header("Location: content.php");
    exit;
}

// Include admin header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-file-alt me-2"></i>Content Management</h2>
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

<!-- Content Management Tabs -->
<ul class="nav nav-tabs mb-4" id="contentTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="lessons-tab" data-bs-toggle="tab" data-bs-target="#lessons" type="button" role="tab" aria-controls="lessons" aria-selected="true">
            <i class="fas fa-book me-1"></i>Lessons
            <span class="badge bg-primary ms-1"><?php echo count($lessons); ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="challenges-tab" data-bs-toggle="tab" data-bs-target="#challenges" type="button" role="tab" aria-controls="challenges" aria-selected="false">
            <i class="fas fa-tasks me-1"></i>Challenges
            <span class="badge bg-primary ms-1"><?php echo count($challenges); ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="content-types-tab" data-bs-toggle="tab" data-bs-target="#content-types" type="button" role="tab" aria-controls="content-types" aria-selected="false">
            <i class="fas fa-list-alt me-1"></i>Content Types
            <span class="badge bg-primary ms-1"><?php echo count($content_types); ?></span>
        </button>
    </li>
</ul>

<div class="tab-content" id="contentTabsContent">
    <!-- Lessons Tab -->
    <div class="tab-pane fade show active" id="lessons" role="tabpanel" aria-labelledby="lessons-tab">
        <div class="card shadow-sm">
            <div class="card-body">
                <table id="lessonsTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Creator</th>
                            <th>Difficulty</th>
                            <th>Content</th>
                            <th>Quiz</th>
                            <th>Created Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($lessons) > 0): ?>
                            <?php foreach($lessons as $lesson): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($lesson['title']); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['creator_name']); ?></td>
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
                                        <?php $is_active = isset($lesson['is_active']) ? $lesson['is_active'] : 1; ?>
                                        <span class="badge bg-<?php echo $is_active ? 'success' : 'danger'; ?>">
                                            <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?toggle_lesson=1&lesson_id=<?php echo $lesson['lesson_id']; ?>&current_status=<?php echo $is_active; ?>" class="btn btn-sm btn-outline-<?php echo $is_active ? 'danger' : 'success'; ?>" title="<?php echo $is_active ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas fa-<?php echo $is_active ? 'ban' : 'check-circle'; ?>"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-outline-info view-lesson" data-id="<?php echo $lesson['lesson_id']; ?>" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No lessons found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Challenges Tab -->
    <div class="tab-pane fade" id="challenges" role="tabpanel" aria-labelledby="challenges-tab">
        <div class="card shadow-sm">
            <div class="card-body">
                <table id="challengesTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Creator</th>
                            <th>Verification</th>
                            <th>Points</th>
                            <th>Enrollments</th>
                            <th>Date Range</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($challenges) > 0): ?>
                            <?php foreach($challenges as $challenge): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($challenge['title']); ?></td>
                                    <td><?php echo htmlspecialchars($challenge['creator_name']); ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?php echo htmlspecialchars($challenge['verification_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo $challenge['eco_points']; ?> points
                                        </span>
                                    </td>
                                    <td><?php echo $challenge['enrollment_count']; ?></td>
                                    <td>
                                        <?php if($challenge['start_date'] && $challenge['end_date']): ?>
                                            <?php echo date('M d, Y', strtotime($challenge['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($challenge['end_date'])); ?>
                                        <?php else: ?>
                                            <em>No date range</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $is_active = isset($challenge['is_active']) ? $challenge['is_active'] : 1; ?>
                                        <span class="badge bg-<?php echo $is_active ? 'success' : 'danger'; ?>">
                                            <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?toggle_challenge=1&challenge_id=<?php echo $challenge['challenge_id']; ?>&current_status=<?php echo $is_active; ?>" class="btn btn-sm btn-outline-<?php echo $is_active ? 'danger' : 'success'; ?>" title="<?php echo $is_active ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas fa-<?php echo $is_active ? 'ban' : 'check-circle'; ?>"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-outline-info view-challenge" data-id="<?php echo $challenge['challenge_id']; ?>" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No challenges found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Content Types Tab -->
    <div class="tab-pane fade" id="content-types" role="tabpanel" aria-labelledby="content-types-tab">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Add Content Type</h5>
                    </div>
                    <div class="card-body">
                        <form id="addContentTypeForm">
                            <div class="mb-3">
                                <label for="content_type_name" class="form-label">Content Type Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="content_type_name" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus-circle me-2"></i>Add Content Type
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Content Types</h5>
                    </div>
                    <div class="card-body">
                        <table id="contentTypesTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($content_types) > 0): ?>
                                    <?php foreach($content_types as $type): ?>
                                        <tr>
                                            <td><?php echo $type['content_type_id']; ?></td>
                                            <td><?php echo htmlspecialchars($type['name']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-content-type" data-id="<?php echo $type['content_type_id']; ?>" data-name="<?php echo htmlspecialchars($type['name']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No content types found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lesson Detail Modal -->
<div class="modal fade" id="lessonDetailModal" tabindex="-1" aria-labelledby="lessonDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="lessonDetailModalLabel">Lesson Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading lesson details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Challenge Detail Modal -->
<div class="modal fade" id="challengeDetailModal" tabindex="-1" aria-labelledby="challengeDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="challengeDetailModalLabel">Challenge Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading challenge details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Content Type Modal -->
<div class="modal fade" id="editContentTypeModal" tabindex="-1" aria-labelledby="editContentTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editContentTypeModalLabel">Edit Content Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editContentTypeForm">
                    <input type="hidden" id="edit_content_type_id">
                    <div class="mb-3">
                        <label for="edit_content_type_name" class="form-label">Content Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_content_type_name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveContentTypeBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom JS for Content Management -->
<script>
    $(document).ready(function() {
        // Initialize DataTables
        $('#lessonsTable').DataTable({
            responsive: true,
            order: [[5, 'desc']] // Sort by created date by default
        });
        
        $('#challengesTable').DataTable({
            responsive: true,
            order: [[5, 'desc']] // Sort by date range by default
        });
        
        $('#contentTypesTable').DataTable({
            responsive: true,
            order: [[0, 'asc']] // Sort by ID by default
        });
        
        // View Lesson Details
        $('.view-lesson').click(function(e) {
            e.preventDefault();
            
            const lessonId = $(this).data('id');
            const modal = $('#lessonDetailModal');
            
            modal.find('.modal-body').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading lesson details...</p>
                </div>
            `);
            
            modal.modal('show');
            
            // Here you would normally fetch lesson details via AJAX
            // For demonstration, we'll simulate it with a timeout
            setTimeout(function() {
                // This would be replaced with actual AJAX call to get lesson details
                const lesson = {
                    title: 'Sample Lesson Title',
                    summary: 'This is a sample lesson summary that would be loaded from the server.',
                    difficulty: 'Intermediate',
                    created_by: 'John Doe',
                    created_at: '2023-07-15',
                    contents: [
                        { title: 'Introduction', type: 'Article' },
                        { title: 'Key Concepts', type: 'Article' },
                        { title: 'Video Tutorial', type: 'Video' }
                    ],
                    quiz: {
                        title: 'Lesson Quiz',
                        questions: 5,
                        total_marks: 10
                    }
                };
                
                let contentHtml = '';
                lesson.contents.forEach((content, index) => {
                    contentHtml += `
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${index + 1}. ${content.title}</strong>
                                </div>
                                <span class="badge bg-info text-dark">${content.type}</span>
                            </div>
                        </li>
                    `;
                });
                
                modal.find('.modal-body').html(`
                    <div class="mb-4">
                        <h4>${lesson.title}</h4>
                        <p class="text-muted">Created by ${lesson.created_by} on ${lesson.created_at}</p>
                        <div class="mb-3">
                            <span class="badge bg-warning text-dark">Difficulty: ${lesson.difficulty}</span>
                        </div>
                        <p>${lesson.summary}</p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Content Sections</h5>
                        <ul class="list-group">
                            ${contentHtml}
                        </ul>
                    </div>
                    
                    <div>
                        <h5>Quiz Information</h5>
                        <p>
                            <strong>Title:</strong> ${lesson.quiz.title}<br>
                            <strong>Questions:</strong> ${lesson.quiz.questions}<br>
                            <strong>Total Marks:</strong> ${lesson.quiz.total_marks}
                        </p>
                    </div>
                `);
            }, 1000);
        });
        
        // View Challenge Details
        $('.view-challenge').click(function(e) {
            e.preventDefault();
            
            const challengeId = $(this).data('id');
            const modal = $('#challengeDetailModal');
            
            modal.find('.modal-body').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading challenge details...</p>
                </div>
            `);
            
            modal.modal('show');
            
            // Here you would normally fetch challenge details via AJAX
            // For demonstration, we'll simulate it with a timeout
            setTimeout(function() {
                // This would be replaced with actual AJAX call to get challenge details
                const challenge = {
                    title: 'Sample Challenge Title',
                    description: 'This is a sample challenge description that would be loaded from the server.',
                    verification_type: 'Photo',
                    eco_points: 50,
                    created_by: 'Jane Smith',
                    start_date: '2023-07-01',
                    end_date: '2023-08-01',
                    enrollments: 25,
                    completions: 10,
                    verifications: {
                        approved: 8,
                        rejected: 2,
                        pending: 15
                    }
                };
                
                modal.find('.modal-body').html(`
                    <div class="mb-4">
                        <h4>${challenge.title}</h4>
                        <p class="text-muted">Created by ${challenge.created_by}</p>
                        <div class="mb-3">
                            <span class="badge bg-success me-2">${challenge.eco_points} Points</span>
                            <span class="badge bg-info text-dark">Verification: ${challenge.verification_type}</span>
                        </div>
                        <p>${challenge.description}</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Date Range</h5>
                                    <p class="card-text">
                                        <strong>Start:</strong> ${challenge.start_date}<br>
                                        <strong>End:</strong> ${challenge.end_date}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Participation</h5>
                                    <p class="card-text">
                                        <strong>Enrollments:</strong> ${challenge.enrollments}<br>
                                        <strong>Completions:</strong> ${challenge.completions}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h5>Verification Status</h5>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: ${(challenge.verifications.approved / challenge.enrollments) * 100}%">
                                ${challenge.verifications.approved} Approved
                            </div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: ${(challenge.verifications.rejected / challenge.enrollments) * 100}%">
                                ${challenge.verifications.rejected} Rejected
                            </div>
                            <div class="progress-bar bg-warning text-dark" role="progressbar" style="width: ${(challenge.verifications.pending / challenge.enrollments) * 100}%">
                                ${challenge.verifications.pending} Pending
                            </div>
                        </div>
                    </div>
                `);
            }, 1000);
        });
        
        // Edit Content Type
        $('.edit-content-type').click(function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            $('#edit_content_type_id').val(id);
            $('#edit_content_type_name').val(name);
            
            $('#editContentTypeModal').modal('show');
        });
        
        // Save Content Type Changes
        $('#saveContentTypeBtn').click(function() {
            const id = $('#edit_content_type_id').val();
            const name = $('#edit_content_type_name').val();
            
            if (!name) {
                alert('Please enter a content type name.');
                return;
            }
            
            // Here you would normally send an AJAX request to update the content type
            // For demonstration, we'll just show a success message
            
            alert('Content type updated successfully!');
            $('#editContentTypeModal').modal('hide');
            
            // In a real application, you would reload the data or update the table row
        });
        
        // Add Content Type
        $('#addContentTypeForm').submit(function(e) {
            e.preventDefault();
            
            const name = $('#content_type_name').val();
            
            if (!name) {
                alert('Please enter a content type name.');
                return;
            }
            
            // Here you would normally send an AJAX request to add the content type
            // For demonstration, we'll just show a success message
            
            alert('Content type added successfully!');
            $('#content_type_name').val('');
            
            // In a real application, you would reload the data or add a new row to the table
        });
    });
</script>

<?php
// Include admin footer
include 'includes/footer.php';
?>
