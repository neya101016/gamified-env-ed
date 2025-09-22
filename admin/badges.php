<?php
// Start session
session_start();

// Include database configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
requireLogin();
requireRole('admin');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize EcoPoints class
require_once '../includes/eco_points.php';
$ecoPoints = new EcoPoints($db);

// Handle badge actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new badge
        if ($_POST['action'] === 'add' && isset($_POST['name'], $_POST['description'], $_POST['image'], $_POST['category'])) {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $image = trim($_POST['image']);
            $category = trim($_POST['category']);
            $points_required = !empty($_POST['points_required']) ? (int)$_POST['points_required'] : null;
            
            $query = "INSERT INTO badges (name, description, image, points_required, category) 
                      VALUES (:name, :description, :image, :points_required, :category)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':image', $image);
            $stmt->bindParam(':points_required', $points_required);
            $stmt->bindParam(':category', $category);
            
            if ($stmt->execute()) {
                $success_message = "Badge created successfully";
            } else {
                $error_message = "Error creating badge";
            }
        }
        
        // Edit badge
        else if ($_POST['action'] === 'edit' && isset($_POST['badge_id'], $_POST['name'], $_POST['description'], $_POST['image'], $_POST['category'])) {
            $badge_id = (int)$_POST['badge_id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $image = trim($_POST['image']);
            $category = trim($_POST['category']);
            $points_required = !empty($_POST['points_required']) ? (int)$_POST['points_required'] : null;
            
            $query = "UPDATE badges 
                      SET name = :name, description = :description, image = :image, 
                          points_required = :points_required, category = :category
                      WHERE badge_id = :badge_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':badge_id', $badge_id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':image', $image);
            $stmt->bindParam(':points_required', $points_required);
            $stmt->bindParam(':category', $category);
            
            if ($stmt->execute()) {
                $success_message = "Badge updated successfully";
            } else {
                $error_message = "Error updating badge";
            }
        }
        
        // Delete badge
        else if ($_POST['action'] === 'delete' && isset($_POST['badge_id'])) {
            $badge_id = (int)$_POST['badge_id'];
            
            $query = "DELETE FROM badges WHERE badge_id = :badge_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':badge_id', $badge_id);
            
            if ($stmt->execute()) {
                $success_message = "Badge deleted successfully";
            } else {
                $error_message = "Error deleting badge";
            }
        }
        
        // Award badge manually
        else if ($_POST['action'] === 'award' && isset($_POST['badge_id'], $_POST['user_id'])) {
            $badge_id = (int)$_POST['badge_id'];
            $user_id = (int)$_POST['user_id'];
            
            if ($ecoPoints->awardBadge($user_id, $badge_id)) {
                $success_message = "Badge awarded successfully";
            } else {
                $error_message = "Error awarding badge. User may already have this badge.";
            }
        }
    }
}

// Get all badges
$query = "SELECT * FROM badges ORDER BY category, name";
$stmt = $db->prepare($query);
$stmt->execute();
$badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group badges by category
$badge_categories = [];
foreach ($badges as $badge) {
    $category = $badge['category'];
    if (!isset($badge_categories[$category])) {
        $badge_categories[$category] = [];
    }
    $badge_categories[$category][] = $badge;
}

// Get recent badge awards
$query = "SELECT ub.user_badge_id, ub.awarded_at, u.name as user_name, u.profile_pic, b.name as badge_name, b.image
          FROM user_badges ub
          JOIN users u ON ub.user_id = u.user_id
          JOIN badges b ON ub.badge_id = b.badge_id
          ORDER BY ub.awarded_at DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_awards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get badge statistics
$query = "SELECT b.badge_id, b.name, COUNT(ub.user_badge_id) as award_count
          FROM badges b
          LEFT JOIN user_badges ub ON b.badge_id = ub.badge_id
          GROUP BY b.badge_id
          ORDER BY award_count DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$badge_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get students for badge awarding (limited to first 100 for dropdown)
$query = "SELECT u.user_id, u.name, s.name as school_name
          FROM users u
          LEFT JOIN schools s ON u.school_id = s.school_id
          WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
          ORDER BY u.name
          LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badge Management - GreenQuest Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <style>
        .badge-image {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
        .profile-img-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .nav-tabs .nav-link {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .badge-card {
            border-radius: 10px;
            transition: transform 0.2s;
        }
        .badge-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-leaf me-2"></i>GreenQuest Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-1"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schools.php">
                            <i class="fas fa-school me-1"></i>Schools
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="content.php">
                            <i class="fas fa-book-open me-1"></i>Content
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">
                            <i class="fas fa-trophy me-1"></i>Leaderboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="badges.php">
                            <i class="fas fa-award me-1"></i>Badges
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                    </li>
                </ul>
                <div class="dropdown">
                    <a class="btn btn-outline-light dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
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
    <div class="container-fluid py-4">
        <h2 class="mb-4"><i class="fas fa-award me-2 text-warning"></i>Badge Management</h2>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="badgeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage" type="button" role="tab" aria-controls="manage" aria-selected="true">
                    <i class="fas fa-list me-2"></i>Manage Badges
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="create-tab" data-bs-toggle="tab" data-bs-target="#create" type="button" role="tab" aria-controls="create" aria-selected="false">
                    <i class="fas fa-plus me-2"></i>Create Badge
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="award-tab" data-bs-toggle="tab" data-bs-target="#award" type="button" role="tab" aria-controls="award" aria-selected="false">
                    <i class="fas fa-medal me-2"></i>Award Badge
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab" aria-controls="stats" aria-selected="false">
                    <i class="fas fa-chart-pie me-2"></i>Badge Stats
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="badgeTabsContent">
            <!-- Manage Badges Tab -->
            <div class="tab-pane fade show active" id="manage" role="tabpanel" aria-labelledby="manage-tab">
                <div class="card">
                    <div class="card-body">
                        <table id="badgesTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Badge</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Points Required</th>
                                    <th>Times Awarded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($badges as $badge): 
                                    // Find badge stats
                                    $award_count = 0;
                                    foreach ($badge_stats as $stat) {
                                        if ($stat['badge_id'] == $badge['badge_id']) {
                                            $award_count = $stat['award_count'];
                                            break;
                                        }
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <img src="../assets/img/badges/<?php echo htmlspecialchars($badge['image']); ?>" alt="<?php echo htmlspecialchars($badge['name']); ?>" class="badge-image">
                                    </td>
                                    <td><?php echo htmlspecialchars($badge['name']); ?></td>
                                    <td><?php echo htmlspecialchars($badge['description']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($badge['category']); ?></span></td>
                                    <td><?php echo $badge['points_required'] ? number_format($badge['points_required']) : 'N/A'; ?></td>
                                    <td><?php echo number_format($award_count); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-badge" data-bs-toggle="modal" data-bs-target="#editBadgeModal" 
                                                data-id="<?php echo $badge['badge_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($badge['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($badge['description']); ?>"
                                                data-image="<?php echo htmlspecialchars($badge['image']); ?>"
                                                data-category="<?php echo htmlspecialchars($badge['category']); ?>"
                                                data-points="<?php echo $badge['points_required']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-badge" data-bs-toggle="modal" data-bs-target="#deleteBadgeModal" 
                                                data-id="<?php echo $badge['badge_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($badge['name']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Create Badge Tab -->
            <div class="tab-pane fade" id="create" role="tabpanel" aria-labelledby="create-tab">
                <div class="card">
                    <div class="card-body">
                        <form action="badges.php" method="post">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Badge Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Category</label>
                                        <select class="form-select" id="category" name="category" required>
                                            <option value="achievement">Achievement</option>
                                            <option value="challenge">Challenge</option>
                                            <option value="community">Community</option>
                                            <option value="special">Special</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="points_required" class="form-label">Points Required (optional)</label>
                                        <input type="number" class="form-control" id="points_required" name="points_required" min="0">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Badge Image Filename</label>
                                        <input type="text" class="form-control" id="image" name="image" placeholder="badge-name.png" required>
                                        <div class="form-text">Badge images should be placed in ../assets/img/badges/</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Badge Preview</label>
                                        <div class="border rounded p-3 text-center">
                                            <div id="badgePreview" class="mb-2">
                                                <img src="../assets/img/badges/default-badge.png" alt="Badge Preview" class="img-fluid" style="max-width: 100px;">
                                            </div>
                                            <div id="badgeNamePreview" class="fw-bold">Badge Name</div>
                                            <div id="badgeDescriptionPreview" class="text-muted small">Badge description will appear here</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Create Badge
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Award Badge Tab -->
            <div class="tab-pane fade" id="award" role="tabpanel" aria-labelledby="award-tab">
                <div class="card">
                    <div class="card-body">
                        <form action="badges.php" method="post">
                            <input type="hidden" name="action" value="award">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="user_id" class="form-label">Select Student</label>
                                        <select class="form-select" id="user_id" name="user_id" required>
                                            <option value="">-- Select a student --</option>
                                            <?php foreach ($students as $student): ?>
                                                <option value="<?php echo $student['user_id']; ?>">
                                                    <?php echo htmlspecialchars($student['name']); ?>
                                                    <?php if (!empty($student['school_name'])): ?>
                                                        (<?php echo htmlspecialchars($student['school_name']); ?>)
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="badge_id" class="form-label">Select Badge</label>
                                        <select class="form-select" id="badge_id" name="badge_id" required>
                                            <option value="">-- Select a badge --</option>
                                            <?php foreach ($badge_categories as $category => $category_badges): ?>
                                                <optgroup label="<?php echo ucfirst(htmlspecialchars($category)); ?>">
                                                    <?php foreach ($category_badges as $badge): ?>
                                                        <option value="<?php echo $badge['badge_id']; ?>">
                                                            <?php echo htmlspecialchars($badge['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Manually awarding badges should be done only in special circumstances. Most badges are automatically awarded based on achievements and points.
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-medal me-2"></i>Award Badge
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <h5 class="mt-4">Recent Badge Awards</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Badge</th>
                                        <th>Awarded On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_awards as $award): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo !empty($award['profile_pic']) ? '../uploads/profile_pics/' . $award['profile_pic'] : '../assets/img/default-avatar.png'; ?>" class="profile-img-sm me-2" alt="Profile">
                                                <?php echo htmlspecialchars($award['user_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../assets/img/badges/<?php echo htmlspecialchars($award['image']); ?>" alt="Badge" class="me-2" style="width: 30px; height: 30px;">
                                                <?php echo htmlspecialchars($award['badge_name']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($award['awarded_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Badge Stats Tab -->
            <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
                <div class="row">
                    <!-- Badge Distribution -->
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Badge Distribution by Category</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="badgeDistributionChart" width="400" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Most Awarded Badges -->
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Most Awarded Badges</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Badge</th>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Times Awarded</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Sort badge stats by award count
                                            usort($badge_stats, function($a, $b) {
                                                return $b['award_count'] - $a['award_count'];
                                            });
                                            
                                            // Get the top 10
                                            $top_badges = array_slice($badge_stats, 0, 10);
                                            
                                            foreach ($top_badges as $stat): 
                                                // Find badge details
                                                $badge_details = null;
                                                foreach ($badges as $badge) {
                                                    if ($badge['badge_id'] == $stat['badge_id']) {
                                                        $badge_details = $badge;
                                                        break;
                                                    }
                                                }
                                                
                                                if ($badge_details):
                                            ?>
                                            <tr>
                                                <td>
                                                    <img src="../assets/img/badges/<?php echo htmlspecialchars($badge_details['image']); ?>" alt="Badge" style="width: 30px; height: 30px;">
                                                </td>
                                                <td><?php echo htmlspecialchars($badge_details['name']); ?></td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($badge_details['category']); ?></span></td>
                                                <td><?php echo number_format($stat['award_count']); ?></td>
                                            </tr>
                                            <?php endif; endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Badge Gallery -->
                <h5 class="mt-2 mb-3">Badge Gallery by Category</h5>
                <?php foreach ($badge_categories as $category => $category_badges): ?>
                    <h6 class="mt-4 text-capitalize"><?php echo htmlspecialchars($category); ?> Badges</h6>
                    <div class="row">
                        <?php foreach ($category_badges as $badge): 
                            // Find badge stats
                            $award_count = 0;
                            foreach ($badge_stats as $stat) {
                                if ($stat['badge_id'] == $badge['badge_id']) {
                                    $award_count = $stat['award_count'];
                                    break;
                                }
                            }
                        ?>
                            <div class="col-md-3 col-lg-2 mb-4">
                                <div class="card badge-card h-100">
                                    <div class="card-body text-center">
                                        <img src="../assets/img/badges/<?php echo htmlspecialchars($badge['image']); ?>" alt="<?php echo htmlspecialchars($badge['name']); ?>" class="img-fluid mb-3" style="max-width: 80px;">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($badge['name']); ?></h6>
                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($badge['description']); ?></p>
                                        <div class="badge bg-success">
                                            <i class="fas fa-award me-1"></i><?php echo number_format($award_count); ?> awarded
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Badge Modal -->
    <div class="modal fade" id="editBadgeModal" tabindex="-1" aria-labelledby="editBadgeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBadgeModalLabel">Edit Badge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="badges.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="badge_id" id="edit_badge_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Badge Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">Badge Image Filename</label>
                            <input type="text" class="form-control" id="edit_image" name="image" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_category" class="form-label">Category</label>
                            <select class="form-select" id="edit_category" name="category" required>
                                <option value="achievement">Achievement</option>
                                <option value="challenge">Challenge</option>
                                <option value="community">Community</option>
                                <option value="special">Special</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_points_required" class="form-label">Points Required (optional)</label>
                            <input type="number" class="form-control" id="edit_points_required" name="points_required" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Badge Modal -->
    <div class="modal fade" id="deleteBadgeModal" tabindex="-1" aria-labelledby="deleteBadgeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteBadgeModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the badge <span id="delete_badge_name" class="fw-bold"></span>?</p>
                    <p class="text-danger">This action cannot be undone. Any users who have earned this badge will have it removed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="badges.php" method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="badge_id" id="delete_badge_id">
                        <button type="submit" class="btn btn-danger">Delete Badge</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-3 mt-auto">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 GreenQuest Admin. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white me-3">Help</a>
                    <a href="#" class="text-white me-3">Privacy Policy</a>
                    <a href="#" class="text-white">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#badgesTable').DataTable({
                order: [[1, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [0, 6] }
                ]
            });
            
            // Edit badge modal
            $('.edit-badge').click(function() {
                $('#edit_badge_id').val($(this).data('id'));
                $('#edit_name').val($(this).data('name'));
                $('#edit_description').val($(this).data('description'));
                $('#edit_image').val($(this).data('image'));
                $('#edit_category').val($(this).data('category'));
                $('#edit_points_required').val($(this).data('points'));
            });
            
            // Delete badge modal
            $('.delete-badge').click(function() {
                $('#delete_badge_id').val($(this).data('id'));
                $('#delete_badge_name').text($(this).data('name'));
            });
            
            // Live preview for create badge
            $('#name').on('input', function() {
                $('#badgeNamePreview').text($(this).val() || 'Badge Name');
            });
            
            $('#description').on('input', function() {
                $('#badgeDescriptionPreview').text($(this).val() || 'Badge description will appear here');
            });
            
            // Badge distribution chart
            const categoryData = <?php 
                $categories = array_keys($badge_categories);
                $categoryCount = [];
                foreach ($categories as $cat) {
                    $categoryCount[] = count($badge_categories[$cat]);
                }
                echo json_encode([
                    'labels' => array_map('ucfirst', $categories),
                    'data' => $categoryCount
                ]);
            ?>;
            
            const ctx = document.getElementById('badgeDistributionChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: categoryData.labels,
                    datasets: [{
                        data: categoryData.data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
    </script>
</body>
</html>