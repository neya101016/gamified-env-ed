<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize variables
$success_message = '';
$error_message = '';
$content_type = isset($_GET['type']) ? $_GET['type'] : 'lessons';
$content_list = [];

// Handle content actions (approve/reject/delete)
if (isset($_POST['action']) && isset($_POST['content_id'])) {
    $action = $_POST['action'];
    $content_id = $_POST['content_id'];
    $content_table = '';
    
    // Determine which table to use based on content type
    switch ($_POST['content_type']) {
        case 'lessons':
            $content_table = 'lessons';
            break;
        case 'challenges':
            $content_table = 'challenges';
            break;
        case 'quizzes':
            $content_table = 'quizzes';
            break;
    }
    
    if (!empty($content_table)) {
        switch ($action) {
            case 'approve':
                if (updateContentStatus($conn, $content_table, $content_id, 'approved')) {
                    $success_message = "Content approved successfully.";
                } else {
                    $error_message = "Failed to approve content.";
                }
                break;
            case 'reject':
                if (updateContentStatus($conn, $content_table, $content_id, 'rejected')) {
                    $success_message = "Content rejected successfully.";
                } else {
                    $error_message = "Failed to reject content.";
                }
                break;
            case 'delete':
                if (deleteContent($conn, $content_table, $content_id)) {
                    $success_message = "Content deleted successfully.";
                } else {
                    $error_message = "Failed to delete content.";
                }
                break;
        }
    }
}

// Get content based on the selected type
switch ($content_type) {
    case 'lessons':
        $content_list = getAllLessons($conn);
        $page_title = "Lessons Management";
        break;
    case 'challenges':
        $content_list = getAllChallenges($conn);
        $page_title = "Challenges Management";
        break;
    case 'quizzes':
        $content_list = getAllQuizzes($conn);
        $page_title = "Quizzes Management";
        break;
    default:
        $content_list = getAllLessons($conn);
        $page_title = "Lessons Management";
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenQuest - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <!-- Admin Header -->
    <?php include_once 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $page_title; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="?type=lessons" class="btn btn-sm btn-outline-secondary <?php echo $content_type === 'lessons' ? 'active' : ''; ?>">Lessons</a>
                            <a href="?type=challenges" class="btn btn-sm btn-outline-secondary <?php echo $content_type === 'challenges' ? 'active' : ''; ?>">Challenges</a>
                            <a href="?type=quizzes" class="btn btn-sm btn-outline-secondary <?php echo $content_type === 'quizzes' ? 'active' : ''; ?>">Quizzes</a>
                        </div>
                    </div>
                </div>

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

                <!-- Content Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Creator</th>
                                        <th>Status</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($content_list)): ?>
                                        <?php foreach ($content_list as $item): ?>
                                            <tr>
                                                <td><?php echo $item['id']; ?></td>
                                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                                <td><?php echo htmlspecialchars($item['creator_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo getStatusBadgeClass($item['status']); ?>">
                                                        <?php echo ucfirst($item['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewContent(<?php echo $item['id']; ?>, '<?php echo $content_type; ?>')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($item['status'] !== 'approved'): ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="approve">
                                                                <input type="hidden" name="content_id" value="<?php echo $item['id']; ?>">
                                                                <input type="hidden" name="content_type" value="<?php echo $content_type; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Are you sure you want to approve this content?')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($item['status'] !== 'rejected'): ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="reject">
                                                                <input type="hidden" name="content_id" value="<?php echo $item['id']; ?>">
                                                                <input type="hidden" name="content_type" value="<?php echo $content_type; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Are you sure you want to reject this content?')">
                                                                    <i class="fas fa-ban"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="content_id" value="<?php echo $item['id']; ?>">
                                                            <input type="hidden" name="content_type" value="<?php echo $content_type; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this content? This action cannot be undone.')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No content found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Content Modal -->
    <div class="modal fade" id="viewContentModal" tabindex="-1" aria-labelledby="viewContentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewContentModalLabel">Content Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="contentDetails">
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading content details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="approveContentBtn">Approve</button>
                    <button type="button" class="btn btn-warning" id="rejectContentBtn">Reject</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript to handle modal data -->
    <script>
        function viewContent(contentId, contentType) {
            // Fetch content data using AJAX
            fetch(`get_content.php?id=${contentId}&type=${contentType}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const content = data.content;
                        let htmlContent = '';
                        
                        // Common details for all content types
                        htmlContent += `
                            <div class="content-info">
                                <h4>${content.title}</h4>
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label fw-bold">Creator:</label>
                                    <div class="col-sm-9">
                                        <p class="form-control-plaintext">${content.creator_name}</p>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label fw-bold">Status:</label>
                                    <div class="col-sm-9">
                                        <span class="badge bg-${getStatusBadgeClass(content.status)}">${content.status.charAt(0).toUpperCase() + content.status.slice(1)}</span>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label fw-bold">Created:</label>
                                    <div class="col-sm-9">
                                        <p class="form-control-plaintext">${new Date(content.created_at).toLocaleString()}</p>
                                    </div>
                                </div>
                        `;
                        
                        // Type-specific details
                        if (contentType === 'lessons') {
                            htmlContent += `
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label fw-bold">Category:</label>
                                    <div class="col-sm-9">
                                        <p class="form-control-plaintext">${content.category}</p>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label fw-bold">Level:</label>
                                    <div class="col-sm-9">
                                        <p class="form-control-plaintext">${content.difficulty}</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Description:</label>
                                    <div class="p-2 bg-light rounded">
                                        ${content.description}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Content:</label>
                                    <div class="p-3 bg-light rounded">
                                        ${content.content}
                                    </div>
                                </div>
                            `;
                        } else if (contentType === 'challenges') {
                            htmlContent += `
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label fw-bold">Category:</label>
                                    <div class="col-sm-9">
                                        <p class="form-control-plaintext">${content.category}</p>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label fw-bold">Points:</label>
                                    <div class="col-sm-9">
                                        <p class="form-control-plaintext">${content.points}</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Description:</label>
                                    <div class="p-2 bg-light rounded">
                                        ${content.description}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Requirements:</label>
                                    <div class="p-3 bg-light rounded">
                                        ${content.requirements}
                                    </div>
                                </div>
                            `;
                        } else if (contentType === 'quizzes') {
                            htmlContent += `
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label fw-bold">Lesson:</label>
                                    <div class="col-sm-9">
                                        <p class="form-control-plaintext">${content.lesson_title}</p>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label fw-bold">Questions:</label>
                                    <div class="col-sm-9">
                                        <p class="form-control-plaintext">${content.question_count}</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Description:</label>
                                    <div class="p-2 bg-light rounded">
                                        ${content.description}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Questions:</label>
                                    <div class="accordion" id="quizQuestionsAccordion">
                            `;
                            
                            // Add questions to accordion
                            content.questions.forEach((question, index) => {
                                htmlContent += `
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading${index}">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}" aria-expanded="false" aria-controls="collapse${index}">
                                                Question ${index + 1}: ${question.question_text.substring(0, 50)}...
                                            </button>
                                        </h2>
                                        <div id="collapse${index}" class="accordion-collapse collapse" aria-labelledby="heading${index}" data-bs-parent="#quizQuestionsAccordion">
                                            <div class="accordion-body">
                                                <p><strong>Question:</strong> ${question.question_text}</p>
                                                <p><strong>Options:</strong></p>
                                                <ul>
                                `;
                                
                                // Add options
                                question.options.forEach(option => {
                                    const isCorrect = option.is_correct == 1 ? 'text-success fw-bold' : '';
                                    htmlContent += `<li class="${isCorrect}">${option.option_text} ${option.is_correct == 1 ? '(Correct)' : ''}</li>`;
                                });
                                
                                htmlContent += `
                                                </ul>
                                                <p><strong>Explanation:</strong> ${question.explanation || 'No explanation provided'}</p>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            
                            htmlContent += `
                                    </div>
                                </div>
                            `;
                        }
                        
                        htmlContent += '</div>';
                        
                        // Update modal content
                        document.getElementById('contentDetails').innerHTML = htmlContent;
                        
                        // Set up action buttons
                        document.getElementById('approveContentBtn').onclick = function() {
                            approveContent(contentId, contentType);
                        };
                        document.getElementById('rejectContentBtn').onclick = function() {
                            rejectContent(contentId, contentType);
                        };
                        
                        // Show/hide buttons based on content status
                        document.getElementById('approveContentBtn').style.display = content.status === 'approved' ? 'none' : 'inline-block';
                        document.getElementById('rejectContentBtn').style.display = content.status === 'rejected' ? 'none' : 'inline-block';
                        
                        // Open the modal
                        new bootstrap.Modal(document.getElementById('viewContentModal')).show();
                    } else {
                        alert('Failed to load content data.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching content data.');
                });
        }

        function approveContent(contentId, contentType) {
            if (confirm('Are you sure you want to approve this content?')) {
                const form = document.createElement('form');
                form.method = 'post';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.name = 'action';
                actionInput.value = 'approve';
                form.appendChild(actionInput);
                
                const contentIdInput = document.createElement('input');
                contentIdInput.name = 'content_id';
                contentIdInput.value = contentId;
                form.appendChild(contentIdInput);
                
                const contentTypeInput = document.createElement('input');
                contentTypeInput.name = 'content_type';
                contentTypeInput.value = contentType;
                form.appendChild(contentTypeInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function rejectContent(contentId, contentType) {
            if (confirm('Are you sure you want to reject this content?')) {
                const form = document.createElement('form');
                form.method = 'post';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.name = 'action';
                actionInput.value = 'reject';
                form.appendChild(actionInput);
                
                const contentIdInput = document.createElement('input');
                contentIdInput.name = 'content_id';
                contentIdInput.value = contentId;
                form.appendChild(contentIdInput);
                
                const contentTypeInput = document.createElement('input');
                contentTypeInput.name = 'content_type';
                contentTypeInput.value = contentType;
                form.appendChild(contentTypeInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function getStatusBadgeClass(status) {
            switch (status) {
                case 'approved':
                    return 'success';
                case 'pending':
                    return 'warning';
                case 'rejected':
                    return 'danger';
                default:
                    return 'secondary';
            }
        }
    </script>

    <!-- Footer -->
    <?php include_once '../includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>