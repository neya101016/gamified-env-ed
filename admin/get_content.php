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
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if ID and type parameters are set
if (isset($_GET['id']) && isset($_GET['type'])) {
    $content_id = $_GET['id'];
    $content_type = $_GET['type'];
    $content = null;
    
    // Get content data based on type
    switch ($content_type) {
        case 'lessons':
            $content = getLessonById($conn, $content_id);
            break;
        case 'challenges':
            $content = getChallengeById($conn, $content_id);
            break;
        case 'quizzes':
            $content = getQuizById($conn, $content_id);
            // Get quiz questions
            if ($content) {
                $content['questions'] = getQuizQuestions($conn, $content_id);
                $content['question_count'] = count($content['questions']);
                
                // Get options for each question
                foreach ($content['questions'] as &$question) {
                    $question['options'] = getQuestionOptions($conn, $question['id']);
                }
            }
            break;
        default:
            // Invalid content type
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid content type']);
            exit();
    }
    
    if ($content) {
        // Return content data as JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'content' => $content]);
    } else {
        // Content not found
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Content not found']);
    }
} else {
    // Parameters not set
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Content ID or type not provided']);
}
?>