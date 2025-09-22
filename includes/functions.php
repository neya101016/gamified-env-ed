<?php
// Include database configuration
require_once 'config.php';

class User {
    private $conn;
    private $table_name = "users";
    
    // User properties
    public $user_id;
    public $name;
    public $email;
    public $password;
    public $role_id;
    public $school_id;
    public $profile_pic;
    public $is_active;
    
    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Register new user
    public function register() {
        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role_id = htmlspecialchars(strip_tags($this->role_id));
        $this->school_id = $this->school_id ? htmlspecialchars(strip_tags($this->school_id)) : null;
        
        // Hash password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        // Insert query
        $query = "INSERT INTO " . $this->table_name . "
                  (name, email, password_hash, role_id, school_id)
                  VALUES (:name, :email, :password_hash, :role_id, :school_id)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':role_id', $this->role_id);
        $stmt->bindParam(':school_id', $this->school_id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Login user
    public function login() {
        // Sanitize email
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Query to check if user exists
        $query = "SELECT u.user_id, u.name, u.email, u.password_hash, u.role_id, u.school_id, u.profile_pic, u.is_active, r.role_name
                  FROM " . $this->table_name . " u
                  JOIN roles r ON u.role_id = r.role_id
                  WHERE u.email = :email AND u.is_active = 1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':email', $this->email);
        
        // Execute query
        $stmt->execute();
        
        // Check if user exists
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if(password_verify($this->password, $row['password_hash'])) {
                // Set user properties
                $this->user_id = $row['user_id'];
                $this->name = $row['name'];
                $this->role_id = $row['role_id'];
                $this->school_id = $row['school_id'];
                $this->profile_pic = $row['profile_pic'];
                $this->is_active = $row['is_active'];
                
                // Set session variables
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role_id'] = $row['role_id'];
                $_SESSION['role_name'] = $row['role_name'];
                $_SESSION['school_id'] = $row['school_id'];
                $_SESSION['login_time'] = time();
                
                return true;
            }
        }
        
        return false;
    }
    
    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT u.*, r.role_name, s.name as school_name,
                 (SELECT SUM(points) FROM eco_points WHERE user_id = :id) as total_points
                 FROM " . $this->table_name . " u
                 JOIN roles r ON u.role_id = r.role_id
                 LEFT JOIN schools s ON u.school_id = s.school_id
                 WHERE u.user_id = :id";
                 
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update user profile
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . "
                  SET name = :name, 
                      email = :email";
        
        // Only update password if provided
        if(!empty($this->password)) {
            $query .= ", password_hash = :password_hash";
        }
        
        // Only update profile pic if provided
        if(!empty($this->profile_pic)) {
            $query .= ", profile_pic = :profile_pic";
        }
        
        $query .= " WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        
        // Bind parameters
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Bind password if provided
        if(!empty($this->password)) {
            $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(':password_hash', $password_hash);
        }
        
        // Bind profile pic if provided
        if(!empty($this->profile_pic)) {
            $stmt->bindParam(':profile_pic', $this->profile_pic);
        }
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Get all users (for admin)
    public function getAllUsers($offset = 0, $limit = 10, $role_id = null) {
        $query = "SELECT u.user_id, u.name, u.email, u.is_active, r.role_name, s.name as school_name
                  FROM " . $this->table_name . " u
                  JOIN roles r ON u.role_id = r.role_id
                  LEFT JOIN schools s ON u.school_id = s.school_id";
        
        // Add role filter if provided
        if($role_id !== null) {
            $query .= " WHERE u.role_id = :role_id";
        }
        
        $query .= " ORDER BY u.user_id DESC LIMIT :offset, :limit";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
        // Bind role_id if provided
        if($role_id !== null) {
            $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class Lesson {
    private $conn;
    private $table_name = "lessons";
    
    // Lesson properties
    public $lesson_id;
    public $title;
    public $summary;
    public $difficulty;
    public $created_by;
    
    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new lesson
    public function create() {
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->summary = htmlspecialchars(strip_tags($this->summary));
        $this->difficulty = htmlspecialchars(strip_tags($this->difficulty));
        $this->created_by = htmlspecialchars(strip_tags($this->created_by));
        
        // Insert query
        $query = "INSERT INTO " . $this->table_name . "
                  (title, summary, difficulty, created_by)
                  VALUES (:title, :summary, :difficulty, :created_by)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':summary', $this->summary);
        $stmt->bindParam(':difficulty', $this->difficulty);
        $stmt->bindParam(':created_by', $this->created_by);
        
        // Execute query
        if($stmt->execute()) {
            $this->lesson_id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Get all lessons
    public function getAllLessons() {
        $query = "SELECT l.*, u.name as creator_name
                  FROM " . $this->table_name . " l
                  LEFT JOIN users u ON l.created_by = u.user_id
                  ORDER BY l.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get lesson by ID with contents
    public function getLessonById($id) {
        $query = "SELECT l.*, u.name as creator_name
                  FROM " . $this->table_name . " l
                  LEFT JOIN users u ON l.created_by = u.user_id
                  WHERE l.lesson_id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($lesson) {
            // Get lesson contents
            $query = "SELECT lc.*, ct.name as content_type_name
                      FROM lesson_contents lc
                      JOIN content_types ct ON lc.content_type_id = ct.content_type_id
                      WHERE lc.lesson_id = :lesson_id
                      ORDER BY lc.sequence_num ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':lesson_id', $id);
            $stmt->execute();
            
            $lesson['contents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get lesson quiz
            $query = "SELECT q.*
                      FROM quizzes q
                      WHERE q.lesson_id = :lesson_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':lesson_id', $id);
            $stmt->execute();
            
            $lesson['quiz'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $lesson;
    }
    
    // Add content to lesson
    public function addContent($content_type_id, $title, $body, $external_url = null) {
        // Get next sequence number
        $query = "SELECT MAX(sequence_num) as max_seq FROM lesson_contents WHERE lesson_id = :lesson_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':lesson_id', $this->lesson_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $sequence_num = ($row['max_seq'] !== null) ? $row['max_seq'] + 1 : 0;
        
        // Insert query
        $query = "INSERT INTO lesson_contents
                  (lesson_id, content_type_id, title, body, external_url, sequence_num)
                  VALUES (:lesson_id, :content_type_id, :title, :body, :external_url, :sequence_num)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $title = htmlspecialchars(strip_tags($title));
        $body = htmlspecialchars($body); // Allow some HTML for rich content
        $external_url = $external_url ? htmlspecialchars(strip_tags($external_url)) : null;
        
        // Bind parameters
        $stmt->bindParam(':lesson_id', $this->lesson_id);
        $stmt->bindParam(':content_type_id', $content_type_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':body', $body);
        $stmt->bindParam(':external_url', $external_url);
        $stmt->bindParam(':sequence_num', $sequence_num);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Update lesson
    public function update() {
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->summary = htmlspecialchars(strip_tags($this->summary));
        $this->difficulty = htmlspecialchars(strip_tags($this->difficulty));
        $this->lesson_id = htmlspecialchars(strip_tags($this->lesson_id));
        
        // Update query
        $query = "UPDATE " . $this->table_name . "
                  SET title = :title, summary = :summary, difficulty = :difficulty
                  WHERE lesson_id = :lesson_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':summary', $this->summary);
        $stmt->bindParam(':difficulty', $this->difficulty);
        $stmt->bindParam(':lesson_id', $this->lesson_id);
        
        // Execute query
        return $stmt->execute();
    }
}

class Quiz {
    private $conn;
    private $table_name = "quizzes";
    
    // Quiz properties
    public $quiz_id;
    public $lesson_id;
    public $title;
    public $total_marks;
    public $time_limit_minutes;
    
    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new quiz
    public function create() {
        // Sanitize input
        $this->lesson_id = htmlspecialchars(strip_tags($this->lesson_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->time_limit_minutes = htmlspecialchars(strip_tags($this->time_limit_minutes));
        
        // Insert query
        $query = "INSERT INTO " . $this->table_name . "
                  (lesson_id, title, time_limit_minutes)
                  VALUES (:lesson_id, :title, :time_limit_minutes)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':lesson_id', $this->lesson_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':time_limit_minutes', $this->time_limit_minutes);
        
        // Execute query
        if($stmt->execute()) {
            $this->quiz_id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Add question to quiz
    public function addQuestion($question_text, $marks, $options) {
        // Get next sequence number
        $query = "SELECT MAX(sequence_num) as max_seq FROM quiz_questions WHERE quiz_id = :quiz_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quiz_id', $this->quiz_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $sequence_num = ($row['max_seq'] !== null) ? $row['max_seq'] + 1 : 0;
        
        // Begin transaction
        $this->conn->beginTransaction();
        
        try {
            // Insert question
            $query = "INSERT INTO quiz_questions
                      (quiz_id, question_text, marks, sequence_num)
                      VALUES (:quiz_id, :question_text, :marks, :sequence_num)";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize input
            $question_text = htmlspecialchars(strip_tags($question_text));
            $marks = htmlspecialchars(strip_tags($marks));
            
            // Bind parameters
            $stmt->bindParam(':quiz_id', $this->quiz_id);
            $stmt->bindParam(':question_text', $question_text);
            $stmt->bindParam(':marks', $marks);
            $stmt->bindParam(':sequence_num', $sequence_num);
            
            // Execute question insert
            $stmt->execute();
            $question_id = $this->conn->lastInsertId();
            
            // Insert options
            $query = "INSERT INTO quiz_options
                      (question_id, option_text, is_correct)
                      VALUES (:question_id, :option_text, :is_correct)";
            
            $stmt = $this->conn->prepare($query);
            
            foreach($options as $option) {
                // Sanitize input
                $option_text = htmlspecialchars(strip_tags($option['text']));
                $is_correct = $option['is_correct'] ? 1 : 0;
                
                // Bind parameters
                $stmt->bindParam(':question_id', $question_id);
                $stmt->bindParam(':option_text', $option_text);
                $stmt->bindParam(':is_correct', $is_correct);
                
                // Execute option insert
                $stmt->execute();
            }
            
            // Update quiz total marks
            $query = "UPDATE quizzes 
                      SET total_marks = (SELECT SUM(marks) FROM quiz_questions WHERE quiz_id = :quiz_id)
                      WHERE quiz_id = :quiz_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':quiz_id', $this->quiz_id);
            $stmt->execute();
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch(Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Error adding quiz question: " . $e->getMessage());
            return false;
        }
    }
    
    // Get quiz by ID with questions and options
    public function getQuizById($id) {
        $query = "SELECT q.*, l.title as lesson_title
                  FROM " . $this->table_name . " q
                  JOIN lessons l ON q.lesson_id = l.lesson_id
                  WHERE q.quiz_id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($quiz) {
            // Get quiz questions
            $query = "SELECT *
                      FROM quiz_questions
                      WHERE quiz_id = :quiz_id
                      ORDER BY sequence_num ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':quiz_id', $id);
            $stmt->execute();
            
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get options for each question
            foreach($questions as &$question) {
                $query = "SELECT *
                          FROM quiz_options
                          WHERE question_id = :question_id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':question_id', $question['question_id']);
                $stmt->execute();
                
                $question['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $quiz['questions'] = $questions;
        }
        
        return $quiz;
    }
    
    // Submit quiz attempt
    public function submitAttempt($user_id, $answers) {
        // Begin transaction
        $this->conn->beginTransaction();
        
        try {
            // Insert quiz attempt
            $query = "INSERT INTO quiz_attempts
                      (quiz_id, user_id)
                      VALUES (:quiz_id, :user_id)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':quiz_id', $this->quiz_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $attempt_id = $this->conn->lastInsertId();
            $total_score = 0;
            
            // Process each answer
            foreach($answers as $answer) {
                $question_id = $answer['question_id'];
                $selected_option_id = $answer['option_id'];
                
                // Check if answer is correct
                $query = "SELECT q.marks, o.is_correct
                          FROM quiz_questions q
                          JOIN quiz_options o ON o.question_id = q.question_id
                          WHERE q.question_id = :question_id AND o.option_id = :option_id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':question_id', $question_id);
                $stmt->bindParam(':option_id', $selected_option_id);
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $is_correct = $result['is_correct'] == 1;
                $marks_awarded = $is_correct ? $result['marks'] : 0;
                $total_score += $marks_awarded;
                
                // Save the answer
                $query = "INSERT INTO quiz_answers
                          (attempt_id, question_id, selected_option_id, is_marked_correct, marks_awarded)
                          VALUES (:attempt_id, :question_id, :selected_option_id, :is_marked_correct, :marks_awarded)";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':attempt_id', $attempt_id);
                $stmt->bindParam(':question_id', $question_id);
                $stmt->bindParam(':selected_option_id', $selected_option_id);
                $stmt->bindParam(':is_marked_correct', $is_correct, PDO::PARAM_BOOL);
                $stmt->bindParam(':marks_awarded', $marks_awarded);
                $stmt->execute();
            }
            
            // Update attempt with final score and submission time
            $query = "UPDATE quiz_attempts
                      SET score = :score, submitted_at = NOW()
                      WHERE attempt_id = :attempt_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':score', $total_score);
            $stmt->bindParam(':attempt_id', $attempt_id);
            $stmt->execute();
            
            // Award eco points based on score percentage
            $query = "SELECT total_marks FROM quizzes WHERE quiz_id = :quiz_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':quiz_id', $this->quiz_id);
            $stmt->execute();
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $percentage = ($total_score / $quiz['total_marks']) * 100;
            $eco_points = 0;
            
            // Award points based on score percentage
            if($percentage >= 90) {
                $eco_points = 50; // Excellent
            } elseif($percentage >= 75) {
                $eco_points = 30; // Good
            } elseif($percentage >= 50) {
                $eco_points = 15; // Average
            } else {
                $eco_points = 5; // Completed
            }
            
            // Get reason_id for quiz
            $query = "SELECT reason_id FROM eco_point_reasons WHERE reason_key = 'quiz'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $reason = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Add eco points
            $query = "INSERT INTO eco_points
                      (user_id, points, reason_id, related_entity_type, related_entity_id, note)
                      VALUES (:user_id, :points, :reason_id, 'quiz_attempt', :related_entity_id, :note)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':points', $eco_points);
            $stmt->bindParam(':reason_id', $reason['reason_id']);
            $stmt->bindParam(':related_entity_id', $attempt_id);
            
            $note = "Scored {$total_score}/{$quiz['total_marks']} ({$percentage}%) on quiz";
            $stmt->bindParam(':note', $note);
            
            $stmt->execute();
            
            // Check if user should earn badges
            $this->checkAndAwardBadges($user_id);
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'attempt_id' => $attempt_id,
                'score' => $total_score,
                'total_marks' => $quiz['total_marks'],
                'percentage' => $percentage,
                'eco_points_earned' => $eco_points
            ];
        } catch(Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Error submitting quiz: " . $e->getMessage());
            return false;
        }
    }
    
    // Check and award badges
    private function checkAndAwardBadges($user_id) {
        // Get user's total quiz count with high scores
        $query = "SELECT COUNT(DISTINCT qa.quiz_id) as high_score_count
                  FROM quiz_attempts qa
                  JOIN quizzes q ON qa.quiz_id = q.quiz_id
                  WHERE qa.user_id = :user_id
                  AND qa.score >= (q.total_marks * 0.9)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $high_score_count = $result['high_score_count'];
        
        // Check for Quiz Master badge (5 quizzes with 90%+ score)
        if($high_score_count >= 5) {
            $query = "SELECT badge_id FROM badges WHERE name = 'Quiz Master'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $badge = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($badge) {
                // Check if user already has this badge
                $query = "SELECT * FROM user_badges
                          WHERE user_id = :user_id AND badge_id = :badge_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':badge_id', $badge['badge_id']);
                $stmt->execute();
                
                // If user doesn't have badge, award it
                if($stmt->rowCount() == 0) {
                    $query = "INSERT INTO user_badges (user_id, badge_id)
                              VALUES (:user_id, :badge_id)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':badge_id', $badge['badge_id']);
                    $stmt->execute();
                    
                    // Return badge info for notification
                    return [
                        'badge_awarded' => true,
                        'badge_name' => 'Quiz Master',
                        'badge_id' => $badge['badge_id']
                    ];
                }
            }
        }
        
        // Check for Eco Warrior badge (1000+ points)
        $query = "SELECT SUM(points) as total_points FROM eco_points WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $total_points = $result['total_points'] ?? 0;
        
        if($total_points >= 1000) {
            $query = "SELECT badge_id FROM badges WHERE name = 'Eco Warrior'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $badge = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($badge) {
                // Check if user already has this badge
                $query = "SELECT * FROM user_badges
                          WHERE user_id = :user_id AND badge_id = :badge_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':badge_id', $badge['badge_id']);
                $stmt->execute();
                
                // If user doesn't have badge, award it
                if($stmt->rowCount() == 0) {
                    $query = "INSERT INTO user_badges (user_id, badge_id)
                              VALUES (:user_id, :badge_id)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':badge_id', $badge['badge_id']);
                    $stmt->execute();
                    
                    // Return badge info for notification
                    return [
                        'badge_awarded' => true,
                        'badge_name' => 'Eco Warrior',
                        'badge_id' => $badge['badge_id']
                    ];
                }
            }
        }
        
        return ['badge_awarded' => false];
    }
}

class Challenge {
    private $conn;
    private $table_name = "challenges";
    
    // Challenge properties
    public $challenge_id;
    public $title;
    public $description;
    public $start_date;
    public $end_date;
    public $eco_points;
    public $verification_type_id;
    public $created_by;
    
    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new challenge
    public function create() {
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->start_date = htmlspecialchars(strip_tags($this->start_date));
        $this->end_date = htmlspecialchars(strip_tags($this->end_date));
        $this->eco_points = htmlspecialchars(strip_tags($this->eco_points));
        $this->verification_type_id = htmlspecialchars(strip_tags($this->verification_type_id));
        $this->created_by = htmlspecialchars(strip_tags($this->created_by));
        
        // Insert query
        $query = "INSERT INTO " . $this->table_name . "
                  (title, description, start_date, end_date, eco_points, verification_type_id, created_by)
                  VALUES (:title, :description, :start_date, :end_date, :eco_points, :verification_type_id, :created_by)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':end_date', $this->end_date);
        $stmt->bindParam(':eco_points', $this->eco_points);
        $stmt->bindParam(':verification_type_id', $this->verification_type_id);
        $stmt->bindParam(':created_by', $this->created_by);
        
        // Execute query
        if($stmt->execute()) {
            $this->challenge_id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Get all active challenges
    public function getActiveChallenges() {
        $query = "SELECT c.*, v.name as verification_type, u.name as creator_name
                  FROM " . $this->table_name . " c
                  JOIN verification_types v ON c.verification_type_id = v.verification_type_id
                  LEFT JOIN users u ON c.created_by = u.user_id
                  WHERE c.end_date >= CURRENT_DATE
                  ORDER BY c.start_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get challenge by ID
    public function getChallengeById($id) {
        $query = "SELECT c.*, v.name as verification_type, u.name as creator_name
                  FROM " . $this->table_name . " c
                  JOIN verification_types v ON c.verification_type_id = v.verification_type_id
                  LEFT JOIN users u ON c.created_by = u.user_id
                  WHERE c.challenge_id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Enroll user in challenge
    public function enrollUser($user_id) {
        // Check if user is already enrolled
        $query = "SELECT * FROM user_challenges
                  WHERE user_id = :user_id AND challenge_id = :challenge_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':challenge_id', $this->challenge_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return ['status' => 'already_enrolled'];
        }
        
        // Enroll user
        $query = "INSERT INTO user_challenges
                  (user_id, challenge_id)
                  VALUES (:user_id, :challenge_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':challenge_id', $this->challenge_id);
        
        if($stmt->execute()) {
            return [
                'status' => 'success',
                'user_challenge_id' => $this->conn->lastInsertId()
            ];
        }
        
        return ['status' => 'error'];
    }
    
    // Submit challenge proof
    public function submitProof($user_challenge_id, $proof_url, $metadata = null) {
        // Update user challenge status
        $query = "UPDATE user_challenges
                  SET status = 'completed', completed_at = NOW()
                  WHERE user_challenge_id = :user_challenge_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_challenge_id', $user_challenge_id);
        $stmt->execute();
        
        // Insert proof
        $query = "INSERT INTO challenge_proofs
                  (user_challenge_id, proof_url, metadata)
                  VALUES (:user_challenge_id, :proof_url, :metadata)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_challenge_id', $user_challenge_id);
        $stmt->bindParam(':proof_url', $proof_url);
        $stmt->bindParam(':metadata', $metadata);
        
        if($stmt->execute()) {
            return [
                'status' => 'success',
                'proof_id' => $this->conn->lastInsertId()
            ];
        }
        
        return ['status' => 'error'];
    }
    
    // Verify challenge proof
    public function verifyProof($proof_id, $verifier_id, $verdict) {
        // Begin transaction
        $this->conn->beginTransaction();
        
        try {
            // Update proof status
            $query = "UPDATE challenge_proofs
                      SET verifier_id = :verifier_id, verified_at = NOW(), verdict = :verdict
                      WHERE proof_id = :proof_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':verifier_id', $verifier_id);
            $stmt->bindParam(':verdict', $verdict);
            $stmt->bindParam(':proof_id', $proof_id);
            $stmt->execute();
            
            // Get user_challenge_id from proof
            $query = "SELECT user_challenge_id FROM challenge_proofs WHERE proof_id = :proof_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':proof_id', $proof_id);
            $stmt->execute();
            $proof = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Update user challenge status
            $status = ($verdict == 'approved') ? 'verified' : 'rejected';
            $query = "UPDATE user_challenges
                      SET status = :status
                      WHERE user_challenge_id = :user_challenge_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':user_challenge_id', $proof['user_challenge_id']);
            $stmt->execute();
            
            // If approved, award eco points
            if($verdict == 'approved') {
                // Get challenge details and user_id
                $query = "SELECT uc.user_id, c.eco_points, c.challenge_id, c.title
                          FROM user_challenges uc
                          JOIN challenges c ON uc.challenge_id = c.challenge_id
                          WHERE uc.user_challenge_id = :user_challenge_id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_challenge_id', $proof['user_challenge_id']);
                $stmt->execute();
                $challenge_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get reason_id for challenge
                $query = "SELECT reason_id FROM eco_point_reasons WHERE reason_key = 'challenge'";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                $reason = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Add eco points
                $query = "INSERT INTO eco_points
                          (user_id, points, reason_id, related_entity_type, related_entity_id, awarded_by, note)
                          VALUES (:user_id, :points, :reason_id, 'challenge', :related_entity_id, :awarded_by, :note)";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $challenge_data['user_id']);
                $stmt->bindParam(':points', $challenge_data['eco_points']);
                $stmt->bindParam(':reason_id', $reason['reason_id']);
                $stmt->bindParam(':related_entity_id', $challenge_data['challenge_id']);
                $stmt->bindParam(':awarded_by', $verifier_id);
                
                $note = "Completed challenge: " . $challenge_data['title'];
                $stmt->bindParam(':note', $note);
                
                $stmt->execute();
                
                // Check if user should earn Green Thumb badge (3 planting challenges)
                $query = "SELECT COUNT(*) as completed_count
                          FROM user_challenges uc
                          JOIN challenges c ON uc.challenge_id = c.challenge_id
                          WHERE uc.user_id = :user_id
                          AND uc.status = 'verified'
                          AND LOWER(c.title) LIKE '%plant%'";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $challenge_data['user_id']);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($result['completed_count'] >= 3) {
                    $query = "SELECT badge_id FROM badges WHERE name = 'Green Thumb'";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute();
                    $badge = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if($badge) {
                        // Check if user already has this badge
                        $query = "SELECT * FROM user_badges
                                  WHERE user_id = :user_id AND badge_id = :badge_id";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':user_id', $challenge_data['user_id']);
                        $stmt->bindParam(':badge_id', $badge['badge_id']);
                        $stmt->execute();
                        
                        // If user doesn't have badge, award it
                        if($stmt->rowCount() == 0) {
                            $query = "INSERT INTO user_badges (user_id, badge_id)
                                      VALUES (:user_id, :badge_id)";
                            $stmt = $this->conn->prepare($query);
                            $stmt->bindParam(':user_id', $challenge_data['user_id']);
                            $stmt->bindParam(':badge_id', $badge['badge_id']);
                            $stmt->execute();
                        }
                    }
                }
                
                // Also check for Eco Warrior badge (1000+ points)
                $query = "SELECT SUM(points) as total_points FROM eco_points WHERE user_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $challenge_data['user_id']);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $total_points = $result['total_points'] ?? 0;
                
                if($total_points >= 1000) {
                    $query = "SELECT badge_id FROM badges WHERE name = 'Eco Warrior'";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute();
                    $badge = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if($badge) {
                        // Check if user already has this badge
                        $query = "SELECT * FROM user_badges
                                  WHERE user_id = :user_id AND badge_id = :badge_id";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':user_id', $challenge_data['user_id']);
                        $stmt->bindParam(':badge_id', $badge['badge_id']);
                        $stmt->execute();
                        
                        // If user doesn't have badge, award it
                        if($stmt->rowCount() == 0) {
                            $query = "INSERT INTO user_badges (user_id, badge_id)
                                      VALUES (:user_id, :badge_id)";
                            $stmt = $this->conn->prepare($query);
                            $stmt->bindParam(':user_id', $challenge_data['user_id']);
                            $stmt->bindParam(':badge_id', $badge['badge_id']);
                            $stmt->execute();
                        }
                    }
                }
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'status' => 'success',
                'verdict' => $verdict
            ];
        } catch(Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Error verifying challenge proof: " . $e->getMessage());
            return ['status' => 'error'];
        }
    }
}

class Leaderboard {
    private $conn;
    
    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get global leaderboard
    public function getGlobalLeaderboard($period = 'all', $limit = 10) {
        $date_condition = $this->getDateCondition($period);
        
        $query = "SELECT u.user_id, u.name, u.profile_pic, s.name as school_name,
                  SUM(ep.points) as total_points,
                  COUNT(DISTINCT ub.badge_id) as badge_count
                  FROM users u
                  LEFT JOIN eco_points ep ON u.user_id = ep.user_id {$date_condition}
                  LEFT JOIN user_badges ub ON u.user_id = ub.user_id
                  LEFT JOIN schools s ON u.school_id = s.school_id
                  WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
                  GROUP BY u.user_id
                  ORDER BY total_points DESC, badge_count DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get school leaderboard
    public function getSchoolLeaderboard($school_id, $period = 'all', $limit = 10) {
        $date_condition = $this->getDateCondition($period);
        
        $query = "SELECT u.user_id, u.name, u.profile_pic,
                  SUM(ep.points) as total_points,
                  COUNT(DISTINCT ub.badge_id) as badge_count
                  FROM users u
                  LEFT JOIN eco_points ep ON u.user_id = ep.user_id {$date_condition}
                  LEFT JOIN user_badges ub ON u.user_id = ub.user_id
                  WHERE u.school_id = :school_id
                  AND u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
                  GROUP BY u.user_id
                  ORDER BY total_points DESC, badge_count DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':school_id', $school_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get school rankings
    public function getSchoolRankings($limit = 10) {
        $query = "SELECT s.school_id, s.name, s.city, s.state,
                  SUM(ep.points) as total_points,
                  COUNT(DISTINCT u.user_id) as student_count
                  FROM schools s
                  JOIN users u ON s.school_id = u.school_id
                  LEFT JOIN eco_points ep ON u.user_id = ep.user_id
                  WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
                  GROUP BY s.school_id
                  ORDER BY total_points DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Helper method to get date condition for leaderboard queries
    private function getDateCondition($period) {
        switch($period) {
            case 'daily':
                return "AND DATE(ep.awarded_at) = CURRENT_DATE";
            case 'weekly':
                return "AND ep.awarded_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)";
            case 'monthly':
                return "AND ep.awarded_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
            default:
                return "";
        }
    }
}

class Badge {
    private $conn;
    private $table_name = "badges";
    
    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get all badges
    public function getAllBadges() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY badge_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get user badges
    public function getUserBadges($user_id) {
        $query = "SELECT b.*, ub.awarded_at
                  FROM user_badges ub
                  JOIN badges b ON ub.badge_id = b.badge_id
                  WHERE ub.user_id = :user_id
                  ORDER BY ub.awarded_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/**
 * Helper function to get profile image URL
 * @param array $user User data array
 * @return string Profile image URL
 */
// Function to upload files safely
function uploadFile($file, $destination_dir, $allowed_types = ['image/jpeg', 'image/png', 'image/gif']) {
    // Create directory if it doesn't exist
    if(!file_exists($destination_dir) && !is_dir($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }
    
    // Check if file upload is valid
    if(!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['status' => 'error', 'message' => 'Invalid file upload'];
    }
    
    // Validate file type
    if(!in_array($file['type'], $allowed_types)) {
        return ['status' => 'error', 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types)];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $destination_dir . '/' . $filename;
    
    // Move uploaded file to destination
    if(move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'status' => 'success',
            'filepath' => $filepath,
            'filename' => $filename
        ];
    }
    
    return ['status' => 'error', 'message' => 'Failed to move uploaded file'];
}

// Session management functions
function checkSessionTimeout() {
    $timeout_duration = 1800; // 30 minutes in seconds
    
    if(isset($_SESSION['login_time'])) {
        $inactive_time = time() - $_SESSION['login_time'];
        
        if($inactive_time >= $timeout_duration) {
            // Session expired, destroy session
            session_unset();
            session_destroy();
            
            return true;
        } else {
            // Update login time
            $_SESSION['login_time'] = time();
            
            return false;
        }
    }
    
    return true; // No session exists
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !checkSessionTimeout();
}

function requireLogin() {
    if(!isLoggedIn()) {
        $baseUrl = getBaseUrl();
        header("Location: {$baseUrl}/login.php");
        exit;
    }
}

function requireRole($allowed_roles) {
    requireLogin();
    
    if(!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    if(!in_array($_SESSION['role_name'], $allowed_roles)) {
        $baseUrl = getBaseUrl();
        header("Location: {$baseUrl}/unauthorized.php");
        exit;
    }
}

// Export data to CSV
function exportToCSV($data, $filename, $headers) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, $headers);
    
    // Add data rows
    foreach($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Admin Dashboard Helper Functions
function getTotalUsers($conn) {
    $query = "SELECT COUNT(*) as total FROM users";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

function getPendingApprovals($conn) {
    // Count pending user challenge verifications
    $query = "SELECT COUNT(*) as count FROM challenge_proofs cp
              JOIN user_challenges uc ON cp.user_challenge_id = uc.user_challenge_id
              WHERE cp.verdict = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $challenge_proofs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Count pending new user registrations (assuming is_active = 0 means pending approval)
    $query = "SELECT COUNT(*) as count FROM users WHERE is_active = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $user_registrations = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Count pending content submissions (placeholder - replace with actual approval tables if they exist)
    $content_submissions = ['count' => 0];
    
    // Count pending content submissions (placeholder - replace with actual approval tables if they exist)
    $content_submissions = ['count' => 0];
    
    // Calculate total
    $total = ($challenge_proofs['count'] ?? 0) + ($user_registrations['count'] ?? 0) + 
             ($content_submissions['count'] ?? 0);
    
    return [
        'challenge_proofs' => $challenge_proofs['count'] ?? 0,
        'user_registrations' => $user_registrations['count'] ?? 0,
        'content_submissions' => $content_submissions['count'] ?? 0,
        'total' => $total
    ];
}

function getRecentActivities($conn, $limit = 10) {
    // Since we don't have a dedicated activities table, we'll combine data from multiple tables
    // to create a synthetic activity feed

    // Get recent quiz attempts
    $query1 = "SELECT 
                qa.submitted_at as created_at,
                u.user_id,
                u.name as user_name,
                CONCAT('Completed quiz \"', q.title, '\" with score ', qa.score, '/', q.total_marks) as description,
                'quiz_completed' as activity_type
              FROM quiz_attempts qa
              JOIN users u ON qa.user_id = u.user_id
              JOIN quizzes q ON qa.quiz_id = q.quiz_id
              ORDER BY qa.submitted_at DESC
              LIMIT " . intval($limit/3);
    $stmt1 = $conn->prepare($query1);
    $stmt1->execute();
    $quiz_activities = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // Get recent challenge enrollments and completions
    $query2 = "SELECT 
                CASE
                    WHEN uc.completed_at IS NOT NULL THEN uc.completed_at
                    ELSE uc.enrolled_at
                END as created_at,
                u.user_id,
                u.name as user_name,
                CASE
                    WHEN uc.status = 'verified' THEN CONCAT('Completed challenge \"', c.title, '\"')
                    ELSE CONCAT('Enrolled in challenge \"', c.title, '\"')
                END as description,
                CASE
                    WHEN uc.status = 'verified' THEN 'challenge_completed'
                    ELSE 'challenge_enrolled'
                END as activity_type
              FROM user_challenges uc
              JOIN users u ON uc.user_id = u.user_id
              JOIN challenges c ON uc.challenge_id = c.challenge_id
              WHERE uc.status IN ('pending', 'verified')
              ORDER BY created_at DESC
              LIMIT " . intval($limit/3);
    $stmt2 = $conn->prepare($query2);
    $stmt2->execute();
    $challenge_activities = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Get recent user registrations
    $query3 = "SELECT 
                u.created_at,
                u.user_id,
                u.name as user_name,
                'Joined GreenQuest' as description,
                'register' as activity_type
              FROM users u
              ORDER BY u.created_at DESC
              LIMIT " . intval($limit/3);
    $stmt3 = $conn->prepare($query3);
    $stmt3->execute();
    $user_activities = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    // Combine and sort activities
    $activities = array_merge($quiz_activities, $challenge_activities, $user_activities);
    
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // Return only the requested number of activities
    return array_slice($activities, 0, $limit);
}

function getSystemStats($conn) {
    // Count active challenges (challenges with end_date in the future or null)
    $query = "SELECT COUNT(*) as count FROM challenges 
              WHERE end_date >= CURRENT_DATE OR end_date IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $active_challenges = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Count completed lessons (based on quiz attempts as proxy for completion)
    $query = "SELECT COUNT(DISTINCT quiz_id) as count FROM quiz_attempts";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $completed_lessons = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Count reported content (placeholder - add actual table if it exists)
    $reported_content = ['count' => 0];
    
    return [
        'active_challenges' => $active_challenges['count'] ?? 0,
        'completed_lessons' => $completed_lessons['count'] ?? 0,
        'reported_content' => $reported_content['count'] ?? 0
    ];
}

/**
 * Helper function to get profile image URL
 * @param mixed $user User data array or user ID
 * @param PDO $conn Optional database connection (required if $user is an ID)
 * @return string Profile image URL
 */
function getProfileImage($user, $conn = null) {
    // Check if $user is an array (user data) or an ID
    if (is_array($user)) {
        // Return default image if user has no profile image
        if (empty($user['profile_pic']) && empty($user['profile_image'])) {
            return '../assets/img/default-avatar.png';
        }
        
        // Check both profile_pic and profile_image fields
        $profile_image = $user['profile_pic'] ?? $user['profile_image'] ?? null;
        if ($profile_image) {
            return '../uploads/profile_pics/' . $profile_image;
        }
        
        return '../assets/img/default-avatar.png';
    } else if (is_numeric($user) && $conn !== null) {
        // Assume $user is a user_id and $conn is a database connection
        
        // Get user's profile image
        $query = "SELECT profile_pic FROM users WHERE user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Return default image if user has no profile image or user not found
        if (!$result || empty($result['profile_pic'])) {
            return '../assets/img/default-avatar.png';
        }
        
        return '../uploads/profile_pics/' . $result['profile_pic'];
    }
    
    // Default case if neither array nor valid ID with connection
    return '../assets/img/default-avatar.png';
}

// Get the appropriate redirect URL based on user role
function getRedirectUrl($role, $fromRoot = true) {
    $paths = [
        'admin' => 'admin/dashboard.php',
        'teacher' => 'teacher/dashboard.php',
        'ngo' => 'ngo/dashboard.php',
        'student' => 'student/dashboard.php'
    ];
    
    $path = $paths[$role] ?? $paths['student'];
    $baseUrl = '';
    
    if (!$fromRoot) {
        // We're in a subdirectory (like /api/), so we need to go up one level
        $baseUrl = '../';
    } else {
        // If we're already at the root, we might need to add the project folder
        $baseUrl = getBaseUrl() . '/';
    }
    
    return $baseUrl . $path;
}

function getActivityIcon($activity_type) {
    switch($activity_type) {
        case 'login':
            return 'sign-in-alt';
        case 'register':
            return 'user-plus';
        case 'lesson_completed':
            return 'book';
        case 'quiz_completed':
            return 'question-circle';
        case 'challenge_enrolled':
            return 'tasks';
        case 'challenge_completed':
            return 'check-circle';
        case 'badge_earned':
            return 'award';
        case 'content_created':
            return 'file-alt';
        case 'content_updated':
            return 'edit';
        default:
            return 'circle';
    }
}

function getActivityIconClass($activity_type) {
    switch($activity_type) {
        case 'login':
        case 'register':
            return 'primary';
        case 'lesson_completed':
        case 'quiz_completed':
            return 'info';
        case 'challenge_enrolled':
        case 'challenge_completed':
            return 'success';
        case 'badge_earned':
            return 'warning';
        case 'content_created':
        case 'content_updated':
            return 'secondary';
        default:
            return 'dark';
    }
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $time_diff = time() - $timestamp;
    
    if($time_diff < 60) {
        return 'Just now';
    } elseif($time_diff < 3600) {
        $minutes = floor($time_diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif($time_diff < 604800) {
        $days = floor($time_diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif($time_diff < 2592000) {
        $weeks = floor($time_diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

function getActivityBadgeClass($activity_type) {
    switch($activity_type) {
        case 'login':
            return 'secondary';
        case 'register':
            return 'primary';
        case 'lesson_completed':
            return 'info';
        case 'quiz_completed':
            return 'info';
        case 'challenge_enrolled':
            return 'warning';
        case 'challenge_completed':
            return 'success';
        case 'badge_earned':
            return 'warning';
        case 'content_created':
            return 'dark';
        case 'content_updated':
            return 'secondary';
        default:
            return 'secondary';
    }
}

function getRoleBadgeClass($role) {
    switch($role) {
        case 'admin':
            return 'danger';
        case 'teacher':
            return 'primary';
        case 'ngo':
            return 'success';
        case 'student':
            return 'info';
        default:
            return 'secondary';
    }
}

function getStatusBadgeClass($status) {
    switch($status) {
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

function getAllUsers($conn) {
    $query = "SELECT u.*, r.role_name 
              FROM users u
              JOIN roles r ON u.role_id = r.role_id
              ORDER BY u.user_id DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserById($conn, $user_id) {
    $query = "SELECT u.*, r.role_name 
              FROM users u
              JOIN roles r ON u.role_id = r.role_id
              WHERE u.user_id = :user_id";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateUserStatus($conn, $user_id, $status) {
    $query = "UPDATE users SET is_active = :status WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':user_id', $user_id);
    
    return $stmt->execute();
}

function deleteUser($conn, $user_id) {
    $query = "DELETE FROM users WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    
    return $stmt->execute();
}

function getAllLessons($conn) {
    $query = "SELECT l.*, u.name as creator_name 
              FROM lessons l
              LEFT JOIN users u ON l.created_by = u.user_id
              ORDER BY l.created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllChallenges($conn) {
    $query = "SELECT c.*, u.name as creator_name 
              FROM challenges c
              LEFT JOIN users u ON c.created_by = u.user_id
              ORDER BY c.created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllQuizzes($conn) {
    $query = "SELECT q.*, l.title as lesson_title, u.name as creator_name 
              FROM quizzes q
              LEFT JOIN lessons l ON q.lesson_id = l.lesson_id
              LEFT JOIN users u ON l.created_by = u.user_id
              ORDER BY q.created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateContentStatus($conn, $table, $content_id, $status) {
    $query = "UPDATE {$table} SET status = :status WHERE id = :content_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':content_id', $content_id);
    
    return $stmt->execute();
}

function deleteContent($conn, $table, $content_id) {
    $query = "DELETE FROM {$table} WHERE id = :content_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':content_id', $content_id);
    
    return $stmt->execute();
}

function getLessonById($conn, $lesson_id) {
    $query = "SELECT l.*, u.name as creator_name 
              FROM lessons l
              LEFT JOIN users u ON l.created_by = u.user_id
              WHERE l.lesson_id = :lesson_id";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':lesson_id', $lesson_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getChallengeById($conn, $challenge_id) {
    $query = "SELECT c.*, u.name as creator_name 
              FROM challenges c
              LEFT JOIN users u ON c.created_by = u.user_id
              WHERE c.challenge_id = :challenge_id";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':challenge_id', $challenge_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getQuizById($conn, $quiz_id) {
    $query = "SELECT q.*, l.title as lesson_title, u.name as creator_name 
              FROM quizzes q
              LEFT JOIN lessons l ON q.lesson_id = l.lesson_id
              LEFT JOIN users u ON l.created_by = u.user_id
              WHERE q.quiz_id = :quiz_id";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':quiz_id', $quiz_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getQuizQuestions($conn, $quiz_id) {
    $query = "SELECT * FROM quiz_questions
              WHERE quiz_id = :quiz_id
              ORDER BY sequence_num ASC";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':quiz_id', $quiz_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getQuestionOptions($conn, $question_id) {
    $query = "SELECT * FROM quiz_options
              WHERE question_id = :question_id
              ORDER BY option_id ASC";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':question_id', $question_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUsersByRole($conn) {
    $query = "SELECT r.role_name as role, COUNT(u.user_id) as count
              FROM users u
              JOIN roles r ON u.role_id = r.role_id
              GROUP BY r.role_name
              ORDER BY count DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getContentStatistics($conn) {
    // Lessons statistics
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as lessons_approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as lessons_pending,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as lessons_rejected
              FROM lessons";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $lessons = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Challenges statistics
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as challenges_approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as challenges_pending,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as challenges_rejected
              FROM challenges";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $challenges = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Quizzes statistics
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as quizzes_approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as quizzes_pending,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as quizzes_rejected
              FROM quizzes";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $quizzes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total' => ($lessons['total'] ?? 0) + ($challenges['total'] ?? 0) + ($quizzes['total'] ?? 0),
        'lessons_approved' => $lessons['lessons_approved'] ?? 0,
        'lessons_pending' => $lessons['lessons_pending'] ?? 0,
        'lessons_rejected' => $lessons['lessons_rejected'] ?? 0,
        'challenges_approved' => $challenges['challenges_approved'] ?? 0,
        'challenges_pending' => $challenges['challenges_pending'] ?? 0,
        'challenges_rejected' => $challenges['challenges_rejected'] ?? 0,
        'quizzes_approved' => $quizzes['quizzes_approved'] ?? 0,
        'quizzes_pending' => $quizzes['quizzes_pending'] ?? 0,
        'quizzes_rejected' => $quizzes['quizzes_rejected'] ?? 0
    ];
}

function getChallengeCompletions($conn) {
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as not_started
              FROM user_challenges";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getDailyLogins($conn, $days = 30) {
    $query = "SELECT 
                DATE(login_time) as date,
                COUNT(*) as count
              FROM user_logins
              WHERE login_time >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY)
              GROUP BY DATE(login_time)
              ORDER BY date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':days', $days, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveSessionCount($conn) {
    $timeout = time() - 1800; // 30 minutes ago
    
    $query = "SELECT COUNT(*) as count FROM user_sessions
              WHERE last_activity > :timeout";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':timeout', $timeout);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] ?? 0;
}

/**
 * Get the base URL for redirections based on the application's folder structure
 * @return string The base URL (e.g., '/ECO' or '')
 */
function getBaseUrl() {
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $rootFolder = dirname(dirname($scriptPath));
    $baseUrl = "";
    
    if ($rootFolder !== '/' && $rootFolder !== '\\') {
        // We're in a subfolder (like /ECO/)
        $parts = explode('/', $rootFolder);
        $folder = end($parts);
        if (!empty($folder)) {
            $baseUrl = "/$folder";
        }
    }
    
    return $baseUrl;
}
?>