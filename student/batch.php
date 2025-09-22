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

// Check if student is associated with a school
if (empty($student['school_id'])) {
    $_SESSION['error_message'] = "You are not associated with any school or batch.";
    header("Location: dashboard.php");
    exit;
}

// Get school info
$query = "SELECT * FROM schools WHERE school_id = :school_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':school_id', $student['school_id']);
$stmt->execute();
$school = $stmt->fetch(PDO::FETCH_ASSOC);

// Get batch mates (other students from the same school)
$query = "SELECT u.user_id, u.name, u.profile_pic, u.join_date,
          (SELECT SUM(points) FROM eco_points WHERE user_id = u.user_id) as total_points,
          (SELECT COUNT(*) FROM user_badges WHERE user_id = u.user_id) as badge_count,
          (SELECT COUNT(*) FROM user_challenges WHERE user_id = u.user_id AND status = 'verified') as completed_challenges
          FROM users u
          JOIN roles r ON u.role_id = r.role_id
          WHERE u.school_id = :school_id AND r.role_name = 'student' AND u.user_id != :user_id
          ORDER BY total_points DESC, badge_count DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':school_id', $student['school_id']);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$batchmates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get school rank in the global leaderboard
$query = "SELECT s.school_id, 
          SUM(ep.points) as total_points,
          COUNT(DISTINCT u.user_id) as student_count,
          @rank := @rank + 1 as rank
          FROM schools s
          JOIN users u ON s.school_id = u.school_id
          LEFT JOIN eco_points ep ON u.user_id = ep.user_id
          JOIN roles r ON u.role_id = r.role_id
          WHERE r.role_name = 'student'
          GROUP BY s.school_id
          ORDER BY total_points DESC";

// First execute the rank reset query
$db->query("SET @rank := 0");
$stmt = $db->prepare($query);
$stmt->execute();
$schools_ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

$school_rank = 'N/A';
$school_total_points = 0;
foreach ($schools_ranking as $rank_info) {
    if ($rank_info['school_id'] == $student['school_id']) {
        $school_rank = '#' . $rank_info['rank'];
        $school_total_points = $rank_info['total_points'] ?? 0;
        $school_student_count = $rank_info['student_count'];
        break;
    }
}

// Get teachers of this school
$query = "SELECT u.user_id, u.name, u.profile_pic
          FROM users u
          JOIN roles r ON u.role_id = r.role_id
          WHERE u.school_id = :school_id AND r.role_name = 'teacher'
          ORDER BY u.name";
$stmt = $db->prepare($query);
$stmt->bindParam(':school_id', $student['school_id']);
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total eco points for current student
$query = "SELECT SUM(points) as total_points FROM eco_points WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$points = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate student's contribution percentage to school total
$contribution_percentage = 0;
if ($school_total_points > 0 && ($points['total_points'] ?? 0) > 0) {
    $contribution_percentage = (($points['total_points'] ?? 0) / $school_total_points) * 100;
}

// Get school performance metrics (average points per student)
$avg_points_per_student = 0;
if ($school_student_count > 0) {
    $avg_points_per_student = $school_total_points / $school_student_count;
}

// Get school challenge participation
$query = "SELECT COUNT(DISTINCT uc.challenge_id) as challenge_count
          FROM user_challenges uc
          JOIN users u ON uc.user_id = u.user_id
          WHERE u.school_id = :school_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':school_id', $student['school_id']);
$stmt->execute();
$school_challenges = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Batch - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .school-header {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .school-logo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #28a745;
        }
        .profile-pic {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
        .teacher-card {
            transition: transform 0.3s;
            height: 100%;
        }
        .teacher-card:hover {
            transform: translateY(-5px);
        }
        .teacher-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin: 0 auto 15px;
        }
        .stat-card {
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            height: 100%;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #28a745;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
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
                        <a class="nav-link active" href="batch.php">My Batch</a>
                    </li>
                </ul>
                <div class="dropdown">
                    <a class="btn btn-outline-light dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card me-2"></i>Profile</a></li>
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
                <h2><i class="fas fa-users me-2"></i>My Batch</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Batch</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- School Header -->
        <div class="school-header shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-2 text-center mb-3 mb-md-0">
                    <?php if (!empty($school['logo'])): ?>
                        <img src="../uploads/school_logos/<?php echo htmlspecialchars($school['logo']); ?>" alt="School Logo" class="img-fluid mb-2" style="max-height: 100px;">
                    <?php else: ?>
                        <div class="school-logo mx-auto">
                            <i class="fas fa-school"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h3><?php echo htmlspecialchars($school['name']); ?></h3>
                    <p class="text-muted mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?php 
                        $location = [];
                        if (!empty($school['city'])) $location[] = $school['city'];
                        if (!empty($school['state'])) $location[] = $school['state'];
                        echo !empty($location) ? htmlspecialchars(implode(', ', $location)) : 'Location not specified';
                        ?>
                    </p>
                    <p class="text-muted mb-2">
                        <i class="fas fa-users me-2"></i>
                        <?php echo $school_student_count ?? 0; ?> Students
                    </p>
                    <p class="text-muted mb-0">
                        <i class="fas fa-chalkboard-teacher me-2"></i>
                        <?php echo count($teachers); ?> Teachers
                    </p>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <div class="mb-3">
                        <span class="badge bg-success p-2 fs-6">
                            <i class="fas fa-leaf me-1"></i>
                            <?php echo number_format($school_total_points); ?> Total Eco-Points
                        </span>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-primary p-2">
                            <i class="fas fa-trophy me-1"></i>
                            School Rank: <?php echo $school_rank; ?>
                        </span>
                    </div>
                    <div>
                        <span class="badge bg-info text-dark p-2">
                            <i class="fas fa-tasks me-1"></i>
                            <?php echo $school_challenges['challenge_count'] ?? 0; ?> Challenges Participated
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- School Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-leaf"></i></div>
                    <h5><?php echo number_format($avg_points_per_student, 0); ?></h5>
                    <p class="text-muted mb-0">Avg. Points Per Student</p>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                    <h5><?php echo number_format($contribution_percentage, 1); ?>%</h5>
                    <p class="text-muted mb-0">Your Contribution</p>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                    <h5><?php echo $school_challenges['challenge_count'] ?? 0; ?></h5>
                    <p class="text-muted mb-0">Total Challenges</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <h5><?php echo $school_student_count ?? 0; ?></h5>
                    <p class="text-muted mb-0">Active Students</p>
                </div>
            </div>
        </div>
        
        <!-- Teachers Section -->
        <div class="row mb-4">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Your Teachers</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if(count($teachers) > 0): ?>
                                <?php foreach($teachers as $teacher): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card teacher-card text-center shadow-sm h-100">
                                        <div class="card-body">
                                            <img src="<?php echo !empty($teacher['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($teacher['profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                                                alt="Teacher" class="teacher-img">
                                            <h6 class="card-title"><?php echo htmlspecialchars($teacher['name']); ?></h6>
                                            <p class="card-text small text-muted">Teacher</p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-3">
                                    <p class="text-muted">No teachers assigned to your school yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Classmates Table -->
        <div class="row">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-user-friends me-2"></i>Your Classmates</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($batchmates) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Joined</th>
                                            <th>Eco-Points</th>
                                            <th>Badges</th>
                                            <th>Challenges</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($batchmates as $mate): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo !empty($mate['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($mate['profile_pic']) : '../uploads/avatars/default_avatar.png'; ?>" 
                                                         alt="Profile" class="profile-pic me-2">
                                                    <div><?php echo htmlspecialchars($mate['name']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($mate['join_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-success"><?php echo number_format($mate['total_points'] ?? 0); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark"><?php echo $mate['badge_count'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark"><?php echo $mate['completed_challenges'] ?? 0; ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">You don't have any classmates in your batch yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
            $('.datatable').DataTable({
                responsive: true,
                order: [[2, 'desc']], // Sort by eco-points by default
                language: {
                    search: "Search classmates:",
                    lengthMenu: "Show _MENU_ classmates",
                    info: "Showing _START_ to _END_ of _TOTAL_ classmates",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        });
    </script>
</body>
</html>