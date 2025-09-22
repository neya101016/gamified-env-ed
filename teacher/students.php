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

// Get current teacher's school ID
$query = "SELECT school_id FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);
$school_id = $teacher['school_id'];

// Get all students from this teacher's school
$query = "SELECT u.user_id, u.name, u.email, u.profile_pic, u.join_date,
          (SELECT SUM(points) FROM eco_points WHERE user_id = u.user_id) as total_points,
          (SELECT COUNT(*) FROM user_badges WHERE user_id = u.user_id) as badge_count,
          (SELECT COUNT(*) FROM quiz_attempts qa
           JOIN quizzes q ON qa.quiz_id = q.quiz_id
           JOIN lessons l ON q.lesson_id = l.lesson_id
           WHERE qa.user_id = u.user_id AND l.created_by = :teacher_id) as lesson_completion_count
          FROM users u
          WHERE u.school_id = :school_id
          AND u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
          ORDER BY u.name ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':school_id', $school_id);
$stmt->bindParam(':teacher_id', $_SESSION['user_id']);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get school name
$query = "SELECT name FROM schools WHERE school_id = :school_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':school_id', $school_id);
$stmt->execute();
$school = $stmt->fetch(PDO::FETCH_ASSOC);
$school_name = $school['name'] ?? 'Your School';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .student-card {
            transition: transform 0.3s;
        }
        .student-card:hover {
            transform: translateY(-5px);
        }
        .profile-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
        .student-name {
            font-weight: 600;
            font-size: 1.1rem;
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
                        <a class="nav-link active" href="students.php">Students</a>
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
                <h2><i class="fas fa-users me-2"></i>Students at <?php echo htmlspecialchars($school_name); ?></h2>
                <p class="text-muted">View and monitor your students' progress in environmental education.</p>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card bg-primary text-white shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Students</h6>
                                <h2 class="mt-2 mb-0"><?php echo count($students); ?></h2>
                            </div>
                            <div>
                                <i class="fas fa-users fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card bg-success text-white shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Eco-Points</h6>
                                <h2 class="mt-2 mb-0">
                                    <?php
                                    $total_points = array_reduce($students, function($carry, $student) {
                                        return $carry + ($student['total_points'] ?? 0);
                                    }, 0);
                                    echo number_format($total_points);
                                    ?>
                                </h2>
                            </div>
                            <div>
                                <i class="fas fa-leaf fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card bg-info text-white shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Lesson Completions</h6>
                                <h2 class="mt-2 mb-0">
                                    <?php
                                    $total_completions = array_reduce($students, function($carry, $student) {
                                        return $carry + ($student['lesson_completion_count'] ?? 0);
                                    }, 0);
                                    echo number_format($total_completions);
                                    ?>
                                </h2>
                            </div>
                            <div>
                                <i class="fas fa-book-open fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Students List -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="fas fa-user-graduate me-2"></i>Student List</h5>
            </div>
            <div class="card-body">
                <table id="studentsTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Eco-Points</th>
                            <th>Badges</th>
                            <th>Lesson Completions</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $student): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo getProfileImage($student); ?>" class="profile-image me-3" alt="Profile Picture">
                                        <span class="student-name"><?php echo htmlspecialchars($student['name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td>
                                    <span class="badge bg-success"><?php echo number_format($student['total_points'] ?? 0); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-warning text-dark"><?php echo $student['badge_count'] ?? 0; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark"><?php echo $student['lesson_completion_count'] ?? 0; ?></span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($student['join_date'])); ?></td>
                                <td>
                                    <a href="student_details.php?id=<?php echo $student['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#studentsTable').DataTable({
                responsive: true,
                order: [[2, 'desc']], // Sort by eco-points by default
                language: {
                    search: "Search students:",
                    lengthMenu: "Show _MENU_ students per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ students",
                    emptyTable: "No students found"
                }
            });
        });
    </script>
</body>
</html>