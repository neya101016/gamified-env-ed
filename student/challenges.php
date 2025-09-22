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

// Get total eco points for current student
$query = "SELECT SUM(points) as total_points FROM eco_points WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$points = $stmt->fetch(PDO::FETCH_ASSOC);
$total_points = $points['total_points'] ?? 0;

// Get all challenges with participation status
$query = "SELECT c.*, 
          ngo.name as ngo_name,
          CASE 
            WHEN uc.status IS NULL THEN 'not_started'
            ELSE uc.status
          END as participation_status,
          uc.completed_at
          FROM challenges c
          JOIN users ngo ON c.created_by = ngo.user_id
          LEFT JOIN user_challenges uc ON c.challenge_id = uc.challenge_id AND uc.user_id = :user_id
          WHERE c.end_date >= CURRENT_DATE()
          ORDER BY 
            CASE 
                WHEN uc.status = 'pending' THEN 1
                WHEN uc.status = 'verified' THEN 2
                WHEN uc.status = 'rejected' THEN 3
                WHEN uc.status IS NULL THEN 4
            END,
            c.end_date ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count challenges by status
$pending_count = 0;
$completed_count = 0;
$available_count = 0;
$expired_count = 0;

foreach ($challenges as $challenge) {
    $now = new DateTime();
    $end_date = new DateTime($challenge['end_date']);
    
    if ($challenge['participation_status'] == 'pending') {
        $pending_count++;
    } elseif ($challenge['participation_status'] == 'verified') {
        $completed_count++;
    } elseif ($challenge['participation_status'] == 'not_started') {
        $available_count++;
    }
}

// Get count of expired challenges
$query = "SELECT COUNT(*) as expired_count 
          FROM challenges 
          LEFT JOIN user_challenges uc ON challenges.challenge_id = uc.challenge_id AND uc.user_id = :user_id
          WHERE end_date < CURRENT_DATE() 
          AND (uc.status IS NULL OR uc.status != 'verified')";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$expired_result = $stmt->fetch(PDO::FETCH_ASSOC);
$expired_count = $expired_result['expired_count'] ?? 0;

// Get challenge categories for filter
// NOTE: Currently there's no category column in the challenges table
// We're creating mock data for the UI until the database is updated
$categories = ['Environmental', 'Conservation', 'Energy', 'Recycling', 'Water'];

// Get student's top badges (most recent 3)
$query = "SELECT b.*, ub.awarded_at as date_earned 
          FROM badges b
          JOIN user_badges ub ON b.badge_id = ub.badge_id
          WHERE ub.user_id = :user_id
          ORDER BY ub.awarded_at DESC
          LIMIT 3";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Environmental Challenges - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge-small {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 5px;
        }
        .challenge-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
        }
        .challenge-card:hover {
            transform: translateY(-5px);
        }
        .challenge-img {
            height: 160px;
            object-fit: cover;
        }
        .card-body {
            position: relative;
        }
        .challenge-badge {
            position: absolute;
            top: -20px;
            right: 10px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        .status-pending {
            background-color: #fd7e14;
        }
        .status-verified {
            background-color: #198754;
        }
        .status-rejected {
            background-color: #dc3545;
        }
        .difficulty-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            border-radius: 20px;
            padding: 5px 10px;
            font-size: 0.8rem;
            color: white;
        }
        .difficulty-easy {
            background-color: #198754;
        }
        .difficulty-medium {
            background-color: #fd7e14;
        }
        .difficulty-hard {
            background-color: #dc3545;
        }
        .points-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .challenge-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        .status-icon {
            width: 12px;
            height: 12px;
            display: inline-block;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-icon-pending {
            background-color: #fd7e14;
        }
        .status-icon-verified {
            background-color: #198754;
        }
        .status-icon-rejected {
            background-color: #dc3545;
        }
        .status-icon-available {
            background-color: #0d6efd;
        }
        .filters {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .days-left {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .days-critical {
            color: #dc3545;
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
                        <a class="nav-link active" href="challenges.php">Challenges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">Leaderboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="batch.php">My Batch</a>
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
            <div class="col-md-8">
                <h2><i class="fas fa-tasks me-2"></i>Environmental Challenges</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Challenges</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-4 text-end align-self-center">
                <div class="eco-points-display p-2 bg-success text-white rounded shadow-sm">
                    <i class="fas fa-leaf me-2"></i>Your Eco-Points: <strong><?php echo number_format($total_points); ?></strong>
                    <?php if (!empty($badges)): ?>
                        <span class="ms-2 border-start ps-2">
                            <?php foreach($badges as $badge): ?>
                                <img src="../uploads/badges/<?php echo htmlspecialchars($badge['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($badge['name']); ?>"
                                     title="<?php echo htmlspecialchars($badge['name']); ?>"
                                     class="badge-small">
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Status Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="card text-center h-100 shadow-sm">
                    <div class="card-body">
                        <div class="display-4 text-primary"><?php echo $available_count; ?></div>
                        <h5 class="card-title">Available</h5>
                        <p class="card-text small text-muted">Challenges you can participate in</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="card text-center h-100 shadow-sm">
                    <div class="card-body">
                        <div class="display-4 text-warning"><?php echo $pending_count; ?></div>
                        <h5 class="card-title">Pending</h5>
                        <p class="card-text small text-muted">Challenges awaiting verification</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="card text-center h-100 shadow-sm">
                    <div class="card-body">
                        <div class="display-4 text-success"><?php echo $completed_count; ?></div>
                        <h5 class="card-title">Completed</h5>
                        <p class="card-text small text-muted">Successfully verified challenges</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100 shadow-sm">
                    <div class="card-body">
                        <div class="display-4 text-danger"><?php echo $expired_count; ?></div>
                        <h5 class="card-title">Expired</h5>
                        <p class="card-text small text-muted">Challenges that have ended</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters shadow-sm">
            <div class="row">
                <div class="col-md-8">
                    <div class="input-group mb-3 mb-md-0">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="challenge-search" class="form-control" placeholder="Search challenges...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="category-filter" class="form-select">
                        <option value="all">All Categories</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-filter="all">All</button>
                        <button type="button" class="btn btn-outline-primary" data-filter="available">Available</button>
                        <button type="button" class="btn btn-outline-warning" data-filter="pending">Pending</button>
                        <button type="button" class="btn btn-outline-success" data-filter="verified">Completed</button>
                    </div>
                    <div class="btn-group btn-group-sm ms-2" role="group">
                        <button type="button" class="btn btn-outline-secondary" data-sort="newest">Newest First</button>
                        <button type="button" class="btn btn-outline-secondary" data-sort="points">Highest Points</button>
                        <button type="button" class="btn btn-outline-secondary active" data-sort="ending">Ending Soon</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Challenges List -->
        <div class="row" id="challenges-container">
            <?php if(count($challenges) > 0): ?>
                <?php foreach($challenges as $challenge): ?>
                    <?php 
                    $now = new DateTime();
                    $end_date = new DateTime($challenge['end_date']);
                    $interval = $now->diff($end_date);
                    $days_left = $interval->days;
                    $is_expired = $end_date < $now;
                    
                    // Determine card class based on status
                    $card_class = '';
                    $status_text = '';
                    $status_icon = '';
                    
                    if ($challenge['participation_status'] == 'pending') {
                        $card_class = 'border-warning';
                        $status_text = 'Pending Verification';
                        $status_icon = 'status-icon-pending';
                    } elseif ($challenge['participation_status'] == 'verified') {
                        $card_class = 'border-success';
                        $status_text = 'Completed';
                        $status_icon = 'status-icon-verified';
                    } elseif ($challenge['participation_status'] == 'rejected') {
                        $card_class = 'border-danger';
                        $status_text = 'Submission Rejected';
                        $status_icon = 'status-icon-rejected';
                    } else {
                        if ($is_expired) {
                            $card_class = 'border-secondary';
                            $status_text = 'Expired';
                        } else {
                            $card_class = 'border-primary';
                            $status_text = 'Available';
                            $status_icon = 'status-icon-available';
                        }
                    }
                    
                    // Determine difficulty class based on points (since there's no difficulty column)
                    $points = isset($challenge['eco_points']) ? $challenge['eco_points'] : (isset($challenge['points']) ? $challenge['points'] : 0);
                    $difficulty = 'Medium';
                    $difficulty_class = 'difficulty-medium';
                    
                    if ($points < 50) {
                        $difficulty = 'Easy';
                        $difficulty_class = 'difficulty-easy';
                    } elseif ($points >= 100) {
                        $difficulty = 'Hard';
                        $difficulty_class = 'difficulty-hard';
                    }
                    ?>
                    
                    <div class="col-md-4 mb-4 challenge-item" 
                         data-status="<?php echo $challenge['participation_status']; ?>" 
                         data-category="<?php echo isset($challenge['verification_type_id']) ? 'Type' . $challenge['verification_type_id'] : 'General'; ?>"
                         data-expired="<?php echo $is_expired ? 'true' : 'false'; ?>"
                         data-points="<?php echo isset($challenge['eco_points']) ? $challenge['eco_points'] : (isset($challenge['points']) ? $challenge['points'] : 0); ?>"
                         data-date="<?php echo $challenge['created_at']; ?>">
                        <div class="card challenge-card shadow-sm <?php echo $card_class; ?>">
                            <div class="position-relative">
                                <img src="<?php echo !empty($challenge['image']) ? '../uploads/challenges/' . htmlspecialchars($challenge['image']) : '../assets/images/default_challenge.jpg'; ?>" 
                                     class="card-img-top challenge-img" alt="Challenge Image">
                                <span class="difficulty-badge <?php echo $difficulty_class; ?>">
                                    <?php echo $difficulty; ?>
                                </span>
                                <span class="points-badge">
                                    <i class="fas fa-leaf me-1"></i><?php echo isset($challenge['eco_points']) ? $challenge['eco_points'] : (isset($challenge['points']) ? $challenge['points'] : 0); ?> points
                                </span>
                            </div>
                            <div class="card-body">
                                <?php if ($challenge['participation_status'] != 'not_started'): ?>
                                    <div class="challenge-badge 
                                         <?php echo 'status-' . $challenge['participation_status']; ?>">
                                        <i class="fas <?php echo $challenge['participation_status'] == 'pending' ? 'fa-clock' : ($challenge['participation_status'] == 'verified' ? 'fa-check' : 'fa-times'); ?>"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <h5 class="card-title"><?php echo htmlspecialchars($challenge['title']); ?></h5>
                                <p class="card-text small">
                                    <?php echo substr(htmlspecialchars($challenge['description']), 0, 100) . '...'; ?>
                                </p>
                                
                                <div class="challenge-info text-muted">
                                    <span>
                                        <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($challenge['ngo_name']); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-tag me-1"></i><?php echo isset($challenge['verification_type_id']) ? 'Type ' . $challenge['verification_type_id'] : 'General'; ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <span class="status-icon <?php echo $status_icon; ?>"></span>
                                        <small><?php echo $status_text; ?></small>
                                    </div>
                                    
                                    <?php if (!$is_expired && $challenge['participation_status'] == 'not_started'): ?>
                                        <small class="days-left <?php echo $days_left <= 3 ? 'days-critical' : ''; ?>">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo $days_left; ?> days left
                                        </small>
                                    <?php elseif ($is_expired): ?>
                                        <small class="days-left days-critical">
                                            <i class="fas fa-calendar-times me-1"></i>Expired
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <a href="challenge_details.php?id=<?php echo $challenge['challenge_id']; ?>" 
                                   class="btn btn-sm <?php echo $challenge['participation_status'] == 'verified' ? 'btn-success' : ($challenge['participation_status'] == 'pending' ? 'btn-warning' : 'btn-primary'); ?> w-100 mt-3">
                                    <?php 
                                    if ($challenge['participation_status'] == 'verified') {
                                        echo '<i class="fas fa-check-circle me-1"></i>View Completed';
                                    } elseif ($challenge['participation_status'] == 'pending') {
                                        echo '<i class="fas fa-hourglass-half me-1"></i>View Pending';
                                    } elseif ($challenge['participation_status'] == 'rejected') {
                                        echo '<i class="fas fa-redo me-1"></i>Resubmit';
                                    } else {
                                        if ($is_expired) {
                                            echo '<i class="fas fa-info-circle me-1"></i>View Details';
                                        } else {
                                            echo '<i class="fas fa-play-circle me-1"></i>Start Challenge';
                                        }
                                    }
                                    ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No challenges available at the moment. Check back later!
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Show success message if redirected from challenge submission
            <?php if(isset($_SESSION['success_message'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo $_SESSION['success_message']; ?>',
                    confirmButtonColor: '#28a745'
                });
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            // Show error message if there is one
            <?php if(isset($_SESSION['error_message'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '<?php echo $_SESSION['error_message']; ?>',
                    confirmButtonColor: '#dc3545'
                });
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            // Filter challenges by status
            $('[data-filter]').click(function() {
                // Remove active class from all filter buttons
                $('[data-filter]').removeClass('active');
                // Add active class to clicked button
                $(this).addClass('active');
                
                const filter = $(this).data('filter');
                
                if (filter === 'all') {
                    $('.challenge-item').show();
                } else {
                    $('.challenge-item').hide();
                    
                    if (filter === 'available') {
                        $('.challenge-item[data-status="not_started"][data-expired="false"]').show();
                    } else {
                        $('.challenge-item[data-status="' + filter + '"]').show();
                    }
                }
                
                updateNoResultsMessage();
            });
            
            // Search challenges
            $('#challenge-search').keyup(function() {
                const searchTerm = $(this).val().toLowerCase();
                
                $('.challenge-item').each(function() {
                    const challengeText = $(this).text().toLowerCase();
                    if (challengeText.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                
                updateNoResultsMessage();
            });
            
            // Filter by category
            $('#category-filter').change(function() {
                const category = $(this).val();
                
                if (category === 'all') {
                    $('.challenge-item').show();
                } else {
                    $('.challenge-item').hide();
                    $('.challenge-item[data-category="' + category + '"]').show();
                }
                
                updateNoResultsMessage();
            });
            
            // Sort challenges
            $('[data-sort]').click(function() {
                // Remove active class from all sort buttons
                $('[data-sort]').removeClass('active');
                // Add active class to clicked button
                $(this).addClass('active');
                
                const sortBy = $(this).data('sort');
                const $container = $('#challenges-container');
                const $items = $('.challenge-item');
                
                $items.detach().sort(function(a, b) {
                    if (sortBy === 'newest') {
                        return $(b).data('date').localeCompare($(a).data('date'));
                    } else if (sortBy === 'points') {
                        return $(b).data('points') - $(a).data('points');
                    } else if (sortBy === 'ending') {
                        // If both are expired, sort by date
                        if ($(a).data('expired') === 'true' && $(b).data('expired') === 'true') {
                            return $(b).data('date').localeCompare($(a).data('date'));
                        }
                        // If one is expired, the non-expired comes first
                        if ($(a).data('expired') === 'true') return 1;
                        if ($(b).data('expired') === 'true') return -1;
                        
                        // Otherwise sort by date
                        return $(a).data('date').localeCompare($(b).data('date'));
                    }
                });
                
                $container.append($items);
            });
            
            // Function to show/hide no results message
            function updateNoResultsMessage() {
                if ($('.challenge-item:visible').length === 0) {
                    if ($('#no-results-message').length === 0) {
                        $('#challenges-container').append('<div id="no-results-message" class="col-12"><div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No challenges match your filters. Try adjusting your search criteria.</div></div>');
                    }
                } else {
                    $('#no-results-message').remove();
                }
            }
        });
    </script>
</body>
</html>