<?php
// Sample data for testing badges functionality

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Add sample badge types if they don't exist
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

// Check if badges table exists
$query = "SHOW TABLES LIKE 'badges'";
$stmt = $db->prepare($query);
$stmt->execute();
$tableExists = $stmt->rowCount() > 0;

if (!$tableExists) {
    // Create badges table
    $query = "CREATE TABLE IF NOT EXISTS `badges` (
      `badge_id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(100) NOT NULL,
      `description` varchar(255) NOT NULL,
      `image` varchar(255) NOT NULL,
      `points_required` int(11) DEFAULT NULL,
      `category` varchar(50) DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`badge_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // Create user_badges table
    $query = "CREATE TABLE IF NOT EXISTS `user_badges` (
      `user_badge_id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `badge_id` int(11) NOT NULL,
      `awarded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`user_badge_id`),
      UNIQUE KEY `user_badge_unique` (`user_id`,`badge_id`),
      KEY `badge_id` (`badge_id`),
      CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
      CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`badge_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // Create eco_points table
    $query = "CREATE TABLE IF NOT EXISTS `eco_points` (
      `point_id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `points` int(11) NOT NULL DEFAULT 0,
      `activity_type` varchar(50) NOT NULL COMMENT 'lesson, challenge, quiz, etc.',
      `activity_id` int(11) DEFAULT NULL COMMENT 'ID of the related activity',
      `description` varchar(255) DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`point_id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `eco_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // Insert sample badges
    foreach ($badges as $badge) {
        $query = "INSERT INTO badges (name, description, image, points_required, category) 
                  VALUES (:name, :description, :image, :points_required, :category)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $badge['name']);
        $stmt->bindParam(':description', $badge['description']);
        $stmt->bindParam(':image', $badge['image']);
        $stmt->bindParam(':points_required', $badge['points_required']);
        $stmt->bindParam(':category', $badge['category']);
        $stmt->execute();
    }
    
    echo "Badge tables created and sample data inserted!";
} else {
    echo "Badge tables already exist!";
}
?>