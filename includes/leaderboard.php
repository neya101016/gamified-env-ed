<?php
/**
 * Leaderboard Class
 * Handles retrieving leaderboard data from the database
 */
class Leaderboard {
    private $db;
    
    /**
     * Constructor
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get global leaderboard data
     * @param string $period Time period filter (daily, weekly, monthly, all)
     * @param int $limit Number of records to retrieve
     * @return array Array of leaderboard data
     */
    public function getGlobalLeaderboard($period = 'all', $limit = 10) {
        $where_clause = $this->getPeriodWhereClause($period);
        
        $query = "SELECT 
                  u.user_id, 
                  u.name, 
                  u.profile_pic,
                  s.name as school_name,
                  COALESCE(SUM(ep.points), 0) as total_points,
                  (SELECT COUNT(*) FROM user_badges ub WHERE ub.user_id = u.user_id) as badge_count
                  FROM users u
                  LEFT JOIN eco_points ep ON u.user_id = ep.user_id $where_clause
                  LEFT JOIN schools s ON u.school_id = s.school_id
                  WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
                  GROUP BY u.user_id
                  ORDER BY total_points DESC, u.name ASC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get school-specific leaderboard data
     * @param int $school_id School ID
     * @param string $period Time period filter (daily, weekly, monthly, all)
     * @param int $limit Number of records to retrieve
     * @return array Array of leaderboard data
     */
    public function getSchoolLeaderboard($school_id, $period = 'all', $limit = 10) {
        $where_clause = $this->getPeriodWhereClause($period);
        
        $query = "SELECT 
                  u.user_id, 
                  u.name, 
                  u.profile_pic,
                  COALESCE(SUM(ep.points), 0) as total_points,
                  (SELECT COUNT(*) FROM user_badges ub WHERE ub.user_id = u.user_id) as badge_count
                  FROM users u
                  LEFT JOIN eco_points ep ON u.user_id = ep.user_id $where_clause
                  WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
                  AND u.school_id = :school_id
                  GROUP BY u.user_id
                  ORDER BY total_points DESC, u.name ASC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get top schools ranked by student eco-points
     * @param int $limit Number of records to retrieve
     * @return array Array of school ranking data
     */
    public function getSchoolRankings($limit = 10) {
        $query = "SELECT 
                 s.school_id,
                 s.name,
                 s.city,
                 s.state,
                 COUNT(DISTINCT u.user_id) as student_count,
                 COALESCE(SUM(ep.points), 0) as total_points
                 FROM schools s
                 LEFT JOIN users u ON s.school_id = u.school_id AND u.role_id = (SELECT role_id FROM roles WHERE role_name = 'student')
                 LEFT JOIN eco_points ep ON u.user_id = ep.user_id
                 GROUP BY s.school_id
                 HAVING student_count > 0
                 ORDER BY total_points DESC, student_count DESC
                 LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get WHERE clause for time period filtering
     * @param string $period Time period (daily, weekly, monthly, all)
     * @return string SQL WHERE clause
     */
    private function getPeriodWhereClause($period) {
        switch ($period) {
            case 'daily':
                return "AND DATE(ep.created_at) = CURDATE()";
            case 'weekly':
                return "AND YEARWEEK(ep.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            case 'monthly':
                return "AND YEAR(ep.created_at) = YEAR(CURDATE()) AND MONTH(ep.created_at) = MONTH(CURDATE())";
            case 'all':
            default:
                return "";
        }
    }
}

/**
 * This function is now located in functions.php to avoid duplication
 */
?>