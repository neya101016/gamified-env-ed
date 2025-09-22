-- Eco Points Table
CREATE TABLE IF NOT EXISTS `eco_points` (
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

-- Sample Badge Data
INSERT INTO `badges` (`name`, `description`, `image`, `points_required`, `category`) VALUES
('Eco Novice', 'Complete your first eco lesson', 'eco-novice.png', 10, 'achievement'),
('Green Thumb', 'Complete 5 plant-related challenges', 'green-thumb.png', 50, 'challenge'),
('Water Warrior', 'Complete 5 water conservation challenges', 'water-warrior.png', 50, 'challenge'),
('Energy Expert', 'Complete 5 energy conservation challenges', 'energy-expert.png', 50, 'challenge'),
('Recycling Champion', 'Complete 5 recycling challenges', 'recycling-champion.png', 50, 'challenge'),
('Quiz Master', 'Score 100% on 10 different quizzes', 'quiz-master.png', 100, 'achievement'),
('Eco Scholar', 'Complete 25 eco lessons', 'eco-scholar.png', 250, 'achievement'),
('Community Leader', 'Participate in 3 community events', 'community-leader.png', 150, 'community'),
('Earth Guardian', 'Earn 1000 eco points', 'earth-guardian.png', 1000, 'achievement');