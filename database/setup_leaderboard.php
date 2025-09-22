<?php
// Create and setup leaderboard database tables and populate with sample data

// Include database configuration
require_once '../includes/config.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create tables if they don't exist
$create_tables_sql = "
-- Eco Points Table
CREATE TABLE IF NOT EXISTS `eco_points` (
  `point_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `activity_type` varchar(50) NOT NULL COMMENT 'lesson, challenge, quiz, etc.',
  `activity_id` int(11) DEFAULT NULL COMMENT 'ID of the related activity',
  `reason_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `awarded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`point_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `eco_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Eco Point Reasons Table
CREATE TABLE IF NOT EXISTS `eco_point_reasons` (
  `reason_id` int(11) NOT NULL AUTO_INCREMENT,
  `reason_key` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`reason_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Badges Table
CREATE TABLE IF NOT EXISTS `badges` (
  `badge_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `points_required` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Badges (Junction Table)
CREATE TABLE IF NOT EXISTS `user_badges` (
  `user_badge_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `awarded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_badge_id`),
  UNIQUE KEY `user_badge_unique` (`user_id`,`badge_id`),
  KEY `badge_id` (`badge_id`),
  CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`badge_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute the create tables query
try {
    $db->exec($create_tables_sql);
    echo "Leaderboard tables created successfully.<br>";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage() . "<br>";
}

// Insert eco point reasons if they don't exist
$reasons = [
    ['lesson_complete', 'Completed an eco lesson'],
    ['quiz_perfect', 'Achieved perfect score on a quiz'],
    ['quiz_pass', 'Passed a quiz'],
    ['challenge_complete', 'Completed an environmental challenge'],
    ['daily_login', 'Daily login bonus'],
    ['content_contribution', 'Contributed educational content'],
    ['community_activity', 'Participated in a community activity'],
    ['profile_complete', 'Completed user profile']
];

$reason_check_sql = "SELECT COUNT(*) as count FROM eco_point_reasons";
$stmt = $db->prepare($reason_check_sql);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] == 0) {
    $reason_insert_sql = "INSERT INTO eco_point_reasons (reason_key, description) VALUES (?, ?)";
    $stmt = $db->prepare($reason_insert_sql);
    
    foreach ($reasons as $reason) {
        $stmt->execute($reason);
    }
    
    echo "Eco point reasons inserted successfully.<br>";
}

// Insert sample badges if they don't exist
$badges = [
    [
        'name' => 'Eco Novice',
        'description' => 'Complete your first eco lesson',
        'image' => 'eco-novice.png',
        'points_required' => 10,
        'category' => 'achievement'
    ],
    [
        'name' => 'Green Thumb',
        'description' => 'Complete 5 plant-related challenges',
        'image' => 'green-thumb.png',
        'points_required' => 50,
        'category' => 'challenge'
    ],
    [
        'name' => 'Water Warrior',
        'description' => 'Complete 5 water conservation challenges',
        'image' => 'water-warrior.png',
        'points_required' => 50,
        'category' => 'challenge'
    ],
    [
        'name' => 'Energy Expert',
        'description' => 'Complete 5 energy conservation challenges',
        'image' => 'energy-expert.png',
        'points_required' => 50,
        'category' => 'challenge'
    ],
    [
        'name' => 'Recycling Champion',
        'description' => 'Complete 5 recycling challenges',
        'image' => 'recycling-champion.png',
        'points_required' => 50,
        'category' => 'challenge'
    ],
    [
        'name' => 'Quiz Master',
        'description' => 'Score 100% on 10 different quizzes',
        'image' => 'quiz-master.png',
        'points_required' => 100,
        'category' => 'achievement'
    ],
    [
        'name' => 'Eco Scholar',
        'description' => 'Complete 25 eco lessons',
        'image' => 'eco-scholar.png',
        'points_required' => 250,
        'category' => 'achievement'
    ],
    [
        'name' => 'Community Leader',
        'description' => 'Participate in 3 community events',
        'image' => 'community-leader.png',
        'points_required' => 150,
        'category' => 'community'
    ],
    [
        'name' => 'Earth Guardian',
        'description' => 'Earn 1000 eco points',
        'image' => 'earth-guardian.png',
        'points_required' => 1000,
        'category' => 'achievement'
    ]
];

$badge_check_sql = "SELECT COUNT(*) as count FROM badges";
$stmt = $db->prepare($badge_check_sql);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] == 0) {
    $badge_insert_sql = "INSERT INTO badges (name, description, image, points_required, category) 
                          VALUES (:name, :description, :image, :points_required, :category)";
    $stmt = $db->prepare($badge_insert_sql);
    
    foreach ($badges as $badge) {
        $stmt->bindParam(':name', $badge['name']);
        $stmt->bindParam(':description', $badge['description']);
        $stmt->bindParam(':image', $badge['image']);
        $stmt->bindParam(':points_required', $badge['points_required']);
        $stmt->bindParam(':category', $badge['category']);
        $stmt->execute();
    }
    
    echo "Sample badges inserted successfully.<br>";
}

// Generate sample eco points and badges for some users (if needed)
$sample_data_sql = "SELECT COUNT(*) as count FROM eco_points";
$stmt = $db->prepare($sample_data_sql);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] == 0) {
    // Get all student users
    $users_sql = "SELECT user_id FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'student') LIMIT 10";
    $stmt = $db->prepare($users_sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        // Get point reasons
        $reasons_sql = "SELECT reason_id FROM eco_point_reasons";
        $stmt = $db->prepare($reasons_sql);
        $stmt->execute();
        $reasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Insert sample points
        $point_insert_sql = "INSERT INTO eco_points (user_id, points, activity_type, reason_id, description) 
                              VALUES (:user_id, :points, :activity_type, :reason_id, :description)";
        $stmt = $db->prepare($point_insert_sql);
        
        $activity_types = ['lesson', 'quiz', 'challenge', 'community'];
        $descriptions = [
            'Completed Renewable Energy lesson',
            'Perfect score on Water Conservation quiz',
            'Completed Recycling Challenge',
            'Participated in Tree Planting Event',
            'Completed Carbon Footprint lesson',
            'Passed Sustainable Agriculture quiz'
        ];
        
        // Generate random points for users
        foreach ($users as $user) {
            $point_count = rand(3, 15); // Random number of point entries
            
            for ($i = 0; $i < $point_count; $i++) {
                $points = rand(5, 50);
                $activity_type = $activity_types[array_rand($activity_types)];
                $reason_id = $reasons[array_rand($reasons)]['reason_id'];
                $description = $descriptions[array_rand($descriptions)];
                
                $stmt->bindParam(':user_id', $user['user_id']);
                $stmt->bindParam(':points', $points);
                $stmt->bindParam(':activity_type', $activity_type);
                $stmt->bindParam(':reason_id', $reason_id);
                $stmt->bindParam(':description', $description);
                $stmt->execute();
            }
        }
        
        echo "Sample eco points generated for users.<br>";
        
        // Award some badges to users
        $badge_ids_sql = "SELECT badge_id FROM badges";
        $stmt = $db->prepare($badge_ids_sql);
        $stmt->execute();
        $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $badge_insert_sql = "INSERT INTO user_badges (user_id, badge_id) VALUES (:user_id, :badge_id)";
        $stmt = $db->prepare($badge_insert_sql);
        
        foreach ($users as $user) {
            $badge_count = rand(0, 4); // Random number of badges (0-4)
            $assigned_badges = [];
            
            for ($i = 0; $i < $badge_count; $i++) {
                $badge = $badges[array_rand($badges)];
                $badge_id = $badge['badge_id'];
                
                // Avoid duplicate badges for the same user
                if (!in_array($badge_id, $assigned_badges)) {
                    $assigned_badges[] = $badge_id;
                    
                    $stmt->bindParam(':user_id', $user['user_id']);
                    $stmt->bindParam(':badge_id', $badge_id);
                    
                    try {
                        $stmt->execute();
                    } catch (PDOException $e) {
                        // Ignore duplicate entry errors
                        if ($e->getCode() != 23000) {
                            throw $e;
                        }
                    }
                }
            }
        }
        
        echo "Sample badges awarded to users.<br>";
    }
}

echo "<br><strong>Leaderboard system setup completed successfully!</strong>";
?>