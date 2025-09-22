<?php
/**
 * Eco Points Management Class
 * 
 * This class handles all operations related to eco points in the system,
 * including awarding points, retrieving point histories, and calculating totals.
 */
class EcoPoints {
    private $db;
    
    /**
     * Constructor - initialize database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Award points to a user
     * 
     * @param int $userId The user ID to award points to
     * @param int $points The number of points to award
     * @param string $activityType The type of activity (lesson, challenge, quiz, etc.)
     * @param int|null $activityId The ID of the related activity (optional)
     * @param string|null $description A description of why points were awarded
     * @param string|null $reasonKey A predefined reason key from eco_point_reasons table
     * @return bool True if points were awarded successfully, false otherwise
     */
    public function awardPoints($userId, $points, $activityType, $activityId = null, $description = null, $reasonKey = null) {
        try {
            // Get reason_id if reasonKey is provided
            $reasonId = null;
            if ($reasonKey) {
                $reasonQuery = "SELECT reason_id FROM eco_point_reasons WHERE reason_key = :reason_key";
                $stmt = $this->db->prepare($reasonQuery);
                $stmt->bindParam(':reason_key', $reasonKey);
                $stmt->execute();
                $reasonId = $stmt->fetchColumn();
            }
            
            // Insert points record
            $query = "INSERT INTO eco_points (user_id, points, activity_type, activity_id, reason_id, description) 
                      VALUES (:user_id, :points, :activity_type, :activity_id, :reason_id, :description)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':points', $points);
            $stmt->bindParam(':activity_type', $activityType);
            $stmt->bindParam(':activity_id', $activityId);
            $stmt->bindParam(':reason_id', $reasonId);
            $stmt->bindParam(':description', $description);
            
            $result = $stmt->execute();
            
            // Check for new badges
            if ($result) {
                $this->checkForBadges($userId);
            }
            
            return $result;
        } catch (PDOException $e) {
            // Log error
            error_log("Error awarding points: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a user qualifies for any new badges
     * 
     * @param int $userId The user ID to check for badges
     * @return array Array of awarded badge IDs
     */
    public function checkForBadges($userId) {
        $awardedBadges = [];
        
        try {
            // Get total user points
            $totalPoints = $this->getUserTotalPoints($userId);
            
            // Get all badges that the user doesn't have yet and that are based on points
            $query = "SELECT b.badge_id, b.name, b.points_required 
                      FROM badges b 
                      WHERE b.points_required <= :total_points 
                      AND b.badge_id NOT IN (
                          SELECT badge_id FROM user_badges WHERE user_id = :user_id
                      )";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':total_points', $totalPoints);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $eligibleBadges = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Award new badges
            foreach ($eligibleBadges as $badge) {
                $this->awardBadge($userId, $badge['badge_id']);
                $awardedBadges[] = $badge['badge_id'];
            }
            
            // Check other badge criteria
            $this->checkActivityBasedBadges($userId);
            
            return $awardedBadges;
        } catch (PDOException $e) {
            error_log("Error checking for badges: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user qualifies for activity-based badges
     * 
     * @param int $userId The user ID to check
     */
    private function checkActivityBasedBadges($userId) {
        try {
            // Check for lesson completion badges
            $this->checkLessonBadges($userId);
            
            // Check for challenge completion badges
            $this->checkChallengeBadges($userId);
            
            // Check for quiz-related badges
            $this->checkQuizBadges($userId);
            
        } catch (PDOException $e) {
            error_log("Error checking activity badges: " . $e->getMessage());
        }
    }
    
    /**
     * Check for lesson-related badges
     * 
     * @param int $userId The user ID to check
     */
    private function checkLessonBadges($userId) {
        // Get count of completed lessons
        $query = "SELECT COUNT(*) FROM lesson_progress 
                  WHERE user_id = :user_id AND status = 'completed'";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $lessonCount = $stmt->fetchColumn();
        
        // Check for Eco Novice badge (first lesson)
        if ($lessonCount >= 1) {
            $this->awardBadgeByName($userId, 'Eco Novice');
        }
        
        // Check for Eco Scholar badge (25 lessons)
        if ($lessonCount >= 25) {
            $this->awardBadgeByName($userId, 'Eco Scholar');
        }
    }
    
    /**
     * Check for challenge-related badges
     * 
     * @param int $userId The user ID to check
     */
    private function checkChallengeBadges($userId) {
        // Get categories of completed challenges
        $query = "SELECT c.category, COUNT(*) as count
                  FROM challenge_participation cp
                  JOIN challenges c ON cp.challenge_id = c.challenge_id
                  WHERE cp.user_id = :user_id AND cp.status = 'completed'
                  GROUP BY c.category";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $challengeCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($challengeCategories as $category) {
            // Check for category-specific badges
            if ($category['count'] >= 5) {
                switch ($category['category']) {
                    case 'plants':
                        $this->awardBadgeByName($userId, 'Green Thumb');
                        break;
                    case 'water':
                        $this->awardBadgeByName($userId, 'Water Warrior');
                        break;
                    case 'energy':
                        $this->awardBadgeByName($userId, 'Energy Expert');
                        break;
                    case 'recycling':
                        $this->awardBadgeByName($userId, 'Recycling Champion');
                        break;
                }
            }
        }
        
        // Check for community participation
        $query = "SELECT COUNT(*) FROM challenge_participation cp
                  JOIN challenges c ON cp.challenge_id = c.challenge_id
                  WHERE cp.user_id = :user_id AND cp.status = 'completed'
                  AND c.category = 'community'";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $communityCount = $stmt->fetchColumn();
        
        if ($communityCount >= 3) {
            $this->awardBadgeByName($userId, 'Community Leader');
        }
    }
    
    /**
     * Check for quiz-related badges
     * 
     * @param int $userId The user ID to check
     */
    private function checkQuizBadges($userId) {
        // Get count of perfect quizzes
        $query = "SELECT COUNT(*) FROM quiz_attempts
                  WHERE user_id = :user_id AND score_percentage = 100";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $perfectQuizCount = $stmt->fetchColumn();
        
        if ($perfectQuizCount >= 10) {
            $this->awardBadgeByName($userId, 'Quiz Master');
        }
    }
    
    /**
     * Award a badge to a user by badge ID
     * 
     * @param int $userId The user ID to award the badge to
     * @param int $badgeId The badge ID to award
     * @return bool True if badge was awarded, false otherwise
     */
    public function awardBadge($userId, $badgeId) {
        try {
            // Check if user already has this badge
            $checkQuery = "SELECT COUNT(*) FROM user_badges 
                           WHERE user_id = :user_id AND badge_id = :badge_id";
            
            $stmt = $this->db->prepare($checkQuery);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':badge_id', $badgeId);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                return false; // User already has this badge
            }
            
            // Award the badge
            $query = "INSERT INTO user_badges (user_id, badge_id) 
                      VALUES (:user_id, :badge_id)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':badge_id', $badgeId);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error awarding badge: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Award a badge to a user by badge name
     * 
     * @param int $userId The user ID to award the badge to
     * @param string $badgeName The name of the badge to award
     * @return bool True if badge was awarded, false otherwise
     */
    public function awardBadgeByName($userId, $badgeName) {
        try {
            // Get badge ID from name
            $query = "SELECT badge_id FROM badges WHERE name = :name";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $badgeName);
            $stmt->execute();
            
            $badgeId = $stmt->fetchColumn();
            
            if ($badgeId) {
                return $this->awardBadge($userId, $badgeId);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error awarding badge by name: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the total points for a user
     * 
     * @param int $userId The user ID to get points for
     * @return int The total points
     */
    public function getUserTotalPoints($userId) {
        try {
            $query = "SELECT COALESCE(SUM(points), 0) FROM eco_points WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting user total points: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get points history for a user
     * 
     * @param int $userId The user ID to get history for
     * @param int $limit The maximum number of records to return
     * @param int $offset The offset for pagination
     * @return array The points history
     */
    public function getUserPointsHistory($userId, $limit = 10, $offset = 0) {
        try {
            $query = "SELECT ep.*, epr.description as reason_description
                      FROM eco_points ep
                      LEFT JOIN eco_point_reasons epr ON ep.reason_id = epr.reason_id
                      WHERE ep.user_id = :user_id
                      ORDER BY ep.awarded_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user points history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get points breakdown by activity type for a user
     * 
     * @param int $userId The user ID to get breakdown for
     * @return array The points breakdown by activity type
     */
    public function getUserPointsByActivityType($userId) {
        try {
            $query = "SELECT activity_type, SUM(points) as total_points
                      FROM eco_points
                      WHERE user_id = :user_id
                      GROUP BY activity_type
                      ORDER BY total_points DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user points by activity type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get points earned within a specific time period
     * 
     * @param int $userId The user ID to get points for
     * @param string $period The time period ('week', 'month', 'year')
     * @return int The points earned in the specified period
     */
    public function getUserPointsByPeriod($userId, $period = 'month') {
        try {
            $timeFilter = '';
            
            switch ($period) {
                case 'week':
                    $timeFilter = "AND YEARWEEK(awarded_at, 1) = YEARWEEK(NOW(), 1)";
                    break;
                case 'month':
                    $timeFilter = "AND MONTH(awarded_at) = MONTH(NOW()) AND YEAR(awarded_at) = YEAR(NOW())";
                    break;
                case 'year':
                    $timeFilter = "AND YEAR(awarded_at) = YEAR(NOW())";
                    break;
                default:
                    $timeFilter = "";
            }
            
            $query = "SELECT COALESCE(SUM(points), 0) FROM eco_points 
                      WHERE user_id = :user_id $timeFilter";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting user points by period: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get the leaderboard
     * 
     * @param string $period The time period ('all', 'month', 'week')
     * @param int $limit The maximum number of users to return
     * @return array The leaderboard data
     */
    public function getLeaderboard($period = 'all', $limit = 10) {
        try {
            $timeFilter = '';
            
            switch ($period) {
                case 'week':
                    $timeFilter = "AND YEARWEEK(ep.awarded_at, 1) = YEARWEEK(NOW(), 1)";
                    break;
                case 'month':
                    $timeFilter = "AND MONTH(ep.awarded_at) = MONTH(NOW()) AND YEAR(ep.awarded_at) = YEAR(NOW())";
                    break;
                default:
                    $timeFilter = "";
            }
            
            $query = "
                SELECT 
                    u.user_id,
                    u.username,
                    u.profile_image,
                    COALESCE(s.school_name, 'Independent') as school_name,
                    SUM(ep.points) as total_points,
                    COUNT(DISTINCT ub.badge_id) as badge_count
                FROM 
                    users u
                LEFT JOIN 
                    eco_points ep ON u.user_id = ep.user_id $timeFilter
                LEFT JOIN 
                    user_badges ub ON u.user_id = ub.user_id
                LEFT JOIN
                    students st ON u.user_id = st.user_id
                LEFT JOIN
                    schools s ON st.school_id = s.school_id
                WHERE 
                    u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
                GROUP BY 
                    u.user_id
                HAVING 
                    total_points > 0
                ORDER BY 
                    total_points DESC, badge_count DESC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting leaderboard: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get the school leaderboard
     * 
     * @param string $period The time period ('all', 'month', 'week')
     * @param int $limit The maximum number of schools to return
     * @return array The school leaderboard data
     */
    public function getSchoolLeaderboard($period = 'all', $limit = 10) {
        try {
            $timeFilter = '';
            
            switch ($period) {
                case 'week':
                    $timeFilter = "AND YEARWEEK(ep.awarded_at, 1) = YEARWEEK(NOW(), 1)";
                    break;
                case 'month':
                    $timeFilter = "AND MONTH(ep.awarded_at) = MONTH(NOW()) AND YEAR(ep.awarded_at) = YEAR(NOW())";
                    break;
                default:
                    $timeFilter = "";
            }
            
            $query = "
                SELECT 
                    s.school_id,
                    s.school_name,
                    s.logo,
                    COUNT(DISTINCT st.user_id) as student_count,
                    SUM(ep.points) as total_points,
                    ROUND(SUM(ep.points) / COUNT(DISTINCT st.user_id)) as points_per_student
                FROM 
                    schools s
                JOIN 
                    students st ON s.school_id = st.school_id
                JOIN 
                    users u ON st.user_id = u.user_id
                LEFT JOIN 
                    eco_points ep ON u.user_id = ep.user_id $timeFilter
                WHERE 
                    u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
                GROUP BY 
                    s.school_id
                HAVING 
                    total_points > 0
                ORDER BY 
                    total_points DESC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting school leaderboard: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get the user's rank on the leaderboard
     * 
     * @param int $userId The user ID to get rank for
     * @param string $period The time period ('all', 'month', 'week')
     * @return int The user's rank (1-based)
     */
    public function getUserRank($userId, $period = 'all') {
        try {
            $timeFilter = '';
            
            switch ($period) {
                case 'week':
                    $timeFilter = "AND YEARWEEK(ep.awarded_at, 1) = YEARWEEK(NOW(), 1)";
                    break;
                case 'month':
                    $timeFilter = "AND MONTH(ep.awarded_at) = MONTH(NOW()) AND YEAR(ep.awarded_at) = YEAR(NOW())";
                    break;
                default:
                    $timeFilter = "";
            }
            
            $query = "
                SELECT position
                FROM (
                    SELECT 
                        u.user_id, 
                        ROW_NUMBER() OVER (ORDER BY SUM(COALESCE(ep.points, 0)) DESC) as position
                    FROM 
                        users u
                    LEFT JOIN 
                        eco_points ep ON u.user_id = ep.user_id $timeFilter
                    WHERE 
                        u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
                    GROUP BY 
                        u.user_id
                ) as rankings
                WHERE user_id = :user_id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (int) $result['position'] : 0;
        } catch (PDOException $e) {
            error_log("Error getting user rank: " . $e->getMessage());
            return 0;
        }
    }
}
?>