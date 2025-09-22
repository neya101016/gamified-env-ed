<?php
// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Include database and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Handle request based on action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action) {
    case 'login':
        handleLogin($db);
        break;
    case 'register':
        handleRegister($db);
        break;
    case 'check_session':
        checkSession();
        break;
    case 'submit_quiz':
        submitQuiz($db);
        break;
    case 'enroll_challenge':
        enrollChallenge($db);
        break;
    case 'submit_challenge_proof':
        submitChallengeProof($db);
        break;
    case 'verify_challenge':
        verifyChallenge($db);
        break;
    case 'get_leaderboard':
        getLeaderboard($db);
        break;
    case 'get_user_points':
        getUserPoints($db);
        break;
    case 'get_user_badges':
        getUserBadges($db);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

// Login handler
function handleLogin($db) {
    // Check if request method is POST
    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        return;
    }
    
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // If no JSON data, try POST data
    if(!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    if(!isset($data['email']) || !isset($data['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
        return;
    }
    
    // Verify captcha
    if(!isset($data['captcha']) || empty($data['captcha'])) {
        echo json_encode(['status' => 'error', 'message' => 'Security verification is required']);
        return;
    }
    
    // Verify captcha with our custom implementation
    if(!SimpleCaptcha::verify($data['captcha'])) {
        echo json_encode(['status' => 'error', 'message' => 'Security code verification failed. Please try again.']);
        return;
    }
    
    // Create user object
    $user = new User($db);
    $user->email = $data['email'];
    $user->password = $data['password'];
    
    // Attempt login
    if($user->login()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['name'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role_name']
            ],
            'redirect' => getRedirectUrl($_SESSION['role_name'], false)
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    }
}

// Register handler
function handleRegister($db) {
    // Check if request method is POST
    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        return;
    }
    
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // If no JSON data, try POST data
    if(!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    if(!isset($data['name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['role_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        return;
    }
    
    // Check if email already exists
    $query = "SELECT COUNT(*) as count FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data['email']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($result['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
        return;
    }
    
    // Create user object
    $user = new User($db);
    $user->name = $data['name'];
    $user->email = $data['email'];
    $user->password = $data['password'];
    $user->role_id = $data['role_id'];
    $user->school_id = isset($data['school_id']) ? $data['school_id'] : null;
    
    // Attempt registration
    if($user->register()) {
        // If registration successful, attempt login
        $user->email = $data['email'];
        $user->password = $data['password'];
        
        if($user->login()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Registration successful',
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'name' => $_SESSION['name'],
                    'email' => $_SESSION['email'],
                    'role' => $_SESSION['role_name']
                ],
                'redirect' => getRedirectUrl($_SESSION['role_name'], false)
            ]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Registration successful. Please login.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
    }
}

// Session check handler
function checkSession() {
    if(isLoggedIn()) {
        echo json_encode([
            'status' => 'active',
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['name'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role_name']
            ],
            'timeout' => false
        ]);
    } else {
        $timeout = isset($_SESSION['login_time']);
        echo json_encode([
            'status' => 'inactive',
            'timeout' => $timeout
        ]);
    }
}

// Submit quiz handler
function submitQuiz($db) {
    // Check if user is logged in
    if(!isLoggedIn()) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        return;
    }
    
    // Check if request method is POST
    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        return;
    }
    
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // If no JSON data, try POST data
    if(!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    if(!isset($data['quiz_id']) || !isset($data['answers']) || !is_array($data['answers'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid quiz submission data']);
        return;
    }
    
    // Create quiz object
    $quiz = new Quiz($db);
    $quiz->quiz_id = $data['quiz_id'];
    
    // Submit quiz answers
    $result = $quiz->submitAttempt($_SESSION['user_id'], $data['answers']);
    
    if($result) {
        // Check if user earned a badge
        $badge_info = ['badge_awarded' => false];
        
        // Check if badge was awarded during quiz submission
        if(isset($result['badge_awarded']) && $result['badge_awarded']) {
            $badge_info = [
                'badge_awarded' => true,
                'badge_name' => $result['badge_name'],
                'badge_id' => $result['badge_id']
            ];
        } else {
            // Get user's badges
            $badge = new Badge($db);
            $user_badges = $badge->getUserBadges($_SESSION['user_id']);
            
            // Check if user has new badges (latest badge awarded within the last minute)
            foreach($user_badges as $badge) {
                $awarded_time = strtotime($badge['awarded_at']);
                if(time() - $awarded_time < 60) {
                    $badge_info = [
                        'badge_awarded' => true,
                        'badge_name' => $badge['name'],
                        'badge_id' => $badge['badge_id']
                    ];
                    break;
                }
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'result' => $result,
            'badge_info' => $badge_info
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit quiz']);
    }
}

// Enroll in challenge handler
function enrollChallenge($db) {
    // Check if user is logged in
    if(!isLoggedIn()) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        return;
    }
    
    // Check if request method is POST
    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        return;
    }
    
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // If no JSON data, try POST data
    if(!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    if(!isset($data['challenge_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Challenge ID is required']);
        return;
    }
    
    // Create challenge object
    $challenge = new Challenge($db);
    $challenge->challenge_id = $data['challenge_id'];
    
    // Enroll user in challenge
    $result = $challenge->enrollUser($_SESSION['user_id']);
    
    echo json_encode($result);
}

// Submit challenge proof handler
function submitChallengeProof($db) {
    // Check if user is logged in
    if(!isLoggedIn()) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        return;
    }
    
    // Check if request method is POST
    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        return;
    }
    
    // Validate required fields
    if(!isset($_POST['user_challenge_id']) || !isset($_FILES['proof_file'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        return;
    }
    
    // Upload proof file
    $upload_result = uploadFile(
        $_FILES['proof_file'],
        '../uploads/challenge_proofs',
        ['image/jpeg', 'image/png', 'image/gif']
    );
    
    if($upload_result['status'] !== 'success') {
        echo json_encode($upload_result);
        return;
    }
    
    // Create challenge object
    $challenge = new Challenge($db);
    
    // Prepare metadata
    $metadata = [
        'original_filename' => $_FILES['proof_file']['name'],
        'file_type' => $_FILES['proof_file']['type'],
        'file_size' => $_FILES['proof_file']['size'],
        'submission_date' => date('Y-m-d H:i:s'),
        'description' => isset($_POST['description']) ? $_POST['description'] : null
    ];
    
    // Submit proof
    $result = $challenge->submitProof(
        $_POST['user_challenge_id'],
        $upload_result['filepath'],
        json_encode($metadata)
    );
    
    echo json_encode($result);
}

// Verify challenge handler
function verifyChallenge($db) {
    // Check if user is logged in
    if(!isLoggedIn()) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        return;
    }
    
    // Check if user has permission to verify challenges
    if(!in_array($_SESSION['role_name'], ['teacher', 'admin', 'ngo'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }
    
    // Check if request method is POST
    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        return;
    }
    
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // If no JSON data, try POST data
    if(!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    if(!isset($data['proof_id']) || !isset($data['verdict']) || 
       !in_array($data['verdict'], ['approved', 'rejected'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid verification data']);
        return;
    }
    
    // Create challenge object
    $challenge = new Challenge($db);
    
    // Verify proof
    $result = $challenge->verifyProof(
        $data['proof_id'],
        $_SESSION['user_id'],
        $data['verdict']
    );
    
    if($result['status'] === 'success' && $result['verdict'] === 'approved') {
        // Check if user earned a badge
        $badge = new Badge($db);
        
        // Get user_id from proof
        $query = "SELECT uc.user_id FROM challenge_proofs cp
                  JOIN user_challenges uc ON cp.user_challenge_id = uc.user_challenge_id
                  WHERE cp.proof_id = :proof_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':proof_id', $data['proof_id']);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user_data) {
            $user_badges = $badge->getUserBadges($user_data['user_id']);
            
            // Check if user has new badges (latest badge awarded within the last minute)
            foreach($user_badges as $badge_data) {
                $awarded_time = strtotime($badge_data['awarded_at']);
                if(time() - $awarded_time < 60) {
                    $result['badge_awarded'] = true;
                    $result['badge_name'] = $badge_data['name'];
                    $result['badge_id'] = $badge_data['badge_id'];
                    $result['user_id'] = $user_data['user_id'];
                    break;
                }
            }
        }
    }
    
    echo json_encode($result);
}

// Get leaderboard handler
function getLeaderboard($db) {
    // Validate parameters
    $type = isset($_GET['type']) ? $_GET['type'] : 'global';
    $period = isset($_GET['period']) ? $_GET['period'] : 'all';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Create leaderboard object
    $leaderboard = new Leaderboard($db);
    
    if($type === 'school' && isset($_GET['school_id'])) {
        $school_id = intval($_GET['school_id']);
        $result = $leaderboard->getSchoolLeaderboard($school_id, $period, $limit);
    } elseif($type === 'schools') {
        $result = $leaderboard->getSchoolRankings($limit);
    } else {
        $result = $leaderboard->getGlobalLeaderboard($period, $limit);
    }
    
    echo json_encode([
        'status' => 'success',
        'leaderboard' => $result,
        'type' => $type,
        'period' => $period
    ]);
}

// Get user points handler
function getUserPoints($db) {
    // Check if user is logged in
    if(!isLoggedIn()) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        return;
    }
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $_SESSION['user_id'];
    
    // If requesting another user's points, check permission
    if($user_id !== $_SESSION['user_id'] && !in_array($_SESSION['role_name'], ['teacher', 'admin'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }
    
    // Get user's total points
    $query = "SELECT SUM(points) as total_points FROM eco_points WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get user's recent points
    $query = "SELECT ep.*, epr.reason_key, epr.description
              FROM eco_points ep
              LEFT JOIN eco_point_reasons epr ON ep.reason_id = epr.reason_id
              WHERE ep.user_id = :user_id
              ORDER BY ep.awarded_at DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $recent_points = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'total_points' => $result['total_points'] ?? 0,
        'recent_points' => $recent_points
    ]);
}

// Get user badges handler
function getUserBadges($db) {
    // Check if user is logged in
    if(!isLoggedIn()) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        return;
    }
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $_SESSION['user_id'];
    
    // If requesting another user's badges, check permission
    if($user_id !== $_SESSION['user_id'] && !in_array($_SESSION['role_name'], ['teacher', 'admin'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }
    
    // Create badge object
    $badge = new Badge($db);
    
    // Get user's badges
    $user_badges = $badge->getUserBadges($user_id);
    
    // Get all badges to show which ones user doesn't have
    $all_badges = $badge->getAllBadges();
    
    // Create array of badge IDs that user has
    $user_badge_ids = array_map(function($badge) {
        return $badge['badge_id'];
    }, $user_badges);
    
    // Mark badges as earned or not
    foreach($all_badges as &$badge) {
        $badge['earned'] = in_array($badge['badge_id'], $user_badge_ids);
        $badge['awarded_at'] = null;
        
        // Add awarded_at date if badge is earned
        foreach($user_badges as $user_badge) {
            if($user_badge['badge_id'] === $badge['badge_id']) {
                $badge['awarded_at'] = $user_badge['awarded_at'];
                break;
            }
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'badges' => $all_badges,
        'earned_count' => count($user_badges)
    ]);
}
?>