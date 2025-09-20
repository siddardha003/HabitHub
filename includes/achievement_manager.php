<?php
// Achievement Manager - Handles awarding and tracking achievements

// Smart path detection for database config
if (!class_exists('Database')) {
    $config_paths = [
        '../config/database.php',  // When called from includes/ context
        'config/database.php',     // When called from root context
        __DIR__ . '/../config/database.php'  // Absolute path as fallback
    ];
    
    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
    
    // Final check if Database class exists
    if (!class_exists('Database')) {
        throw new Exception('Could not load Database class');
    }
}

class AchievementManager {
    private $pdo;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->pdo = $database->getConnection();
            
            if (!$this->pdo) {
                throw new Exception("Database connection failed");
            }
        } catch (Exception $e) {
            error_log("AchievementManager database connection error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check and award achievements for a user action
     */
    public function checkAchievements($user_id, $action_type, $data = []) {
        $awarded_achievements = [];
        
        switch ($action_type) {
            case 'login':
                $awarded_achievements = array_merge(
                    $awarded_achievements,
                    $this->checkLoginStreakAchievements($user_id),
                    $this->checkTotalLoginAchievements($user_id)
                );
                break;
                
            case 'habit_created':
                $awarded_achievements = array_merge(
                    $awarded_achievements,
                    $this->checkHabitCreationAchievements($user_id)
                );
                break;
                
            case 'habit_completed':
                $awarded_achievements = array_merge(
                    $awarded_achievements,
                    $this->checkHabitCompletionAchievements($user_id),
                    $this->checkHabitStreakAchievements($user_id, $data['habit_id'] ?? null),
                    $this->checkPerfectDayAchievements($user_id),
                    $this->checkCategoryAchievements($user_id, $data['category'] ?? null),
                    $this->checkSpecialAchievements($user_id, $data)
                );
                break;
        }
        
        return $awarded_achievements;
    }
    
    /**
     * Check ALL achievements for a user and award any qualifying ones
     */
    public function checkAllAchievements($user_id) {
        $awarded_achievements = [];
        
        // Check all types of achievements
        $awarded_achievements = array_merge(
            $awarded_achievements,
            $this->checkLoginStreakAchievements($user_id),
            $this->checkTotalLoginAchievements($user_id),
            $this->checkHabitCreationAchievements($user_id),
            $this->checkHabitCompletionAchievements($user_id),
            $this->checkHabitStreakAchievements($user_id),
            $this->checkPerfectDayAchievements($user_id),
            $this->checkPerfectWeekAchievements($user_id),
            $this->checkPerfectMonthAchievements($user_id),
            $this->checkCategoryAchievements($user_id),
            $this->checkSpecialAchievements($user_id)
        );
        
        return $awarded_achievements;
    }
    
    /**
     * Check login streak achievements
     */
    private function checkLoginStreakAchievements($user_id) {
        $awarded = [];
        
        // Get current login streak from user_streaks table
        $sql = "SELECT current_streak FROM user_streaks WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $current_streak = $stmt->fetchColumn() ?: 0;
        
        // Also get best login streak by counting consecutive login days
        $best_login_streak = $this->calculateBestLoginStreak($user_id);
        $streak_to_check = max($current_streak, $best_login_streak);
        
        // Define login streak achievements (matching database achievement_key values)
        $streak_achievements = [
            'streak_starter' => 3,
            'streak_pro' => 7,
            'streak_master' => 30,
            'streak_legend' => 100
        ];
        
        foreach ($streak_achievements as $achievement_key => $required_streak) {
            if ($streak_to_check >= $required_streak) {
                $achievement_id = $this->getAchievementId($achievement_key);
                if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, $streak_to_check)) {
                    $awarded[] = $achievement_key;
                }
            }
        }
        
        return $awarded;
    }
    
    /**
     * Calculate the BEST/LONGEST login streak from user_login_days table
     */
    public function calculateBestLoginStreak($user_id) {
        $sql = "SELECT login_date FROM user_login_days WHERE user_id = ? ORDER BY login_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $login_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($login_dates)) {
            return 0;
        }
        
        $max_streak = 1;
        $current_streak = 1;
        
        for ($i = 1; $i < count($login_dates); $i++) {
            $prev_date = new DateTime($login_dates[$i - 1]);
            $curr_date = new DateTime($login_dates[$i]);
            
            // Check if dates are consecutive
            $prev_date->add(new DateInterval('P1D'));
            if ($prev_date->format('Y-m-d') === $curr_date->format('Y-m-d')) {
                $current_streak++;
            } else {
                $max_streak = max($max_streak, $current_streak);
                $current_streak = 1;
            }
        }
        
        return max($max_streak, $current_streak);
    }
    
    /**
     * Calculate login streak from user_login_days table (current streak)
     */
    public function calculateLoginStreak($user_id) {
        $streak = 0;
        $today = date('Y-m-d');
        
        // Get all login dates for the user, ordered by date descending
        $sql = "SELECT login_date FROM user_login_days WHERE user_id = ? ORDER BY login_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $login_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($login_dates)) {
            return 0;
        }
        
        // Convert to array of date objects for easier comparison
        $dates = array_map(function($date) {
            return new DateTime($date);
        }, $login_dates);
        
        // Start from the most recent login date
        $current_date = clone $dates[0];
        $streak = 1; // Start with 1 for the most recent login
        
        // Check consecutive days going backwards
        for ($i = 1; $i < count($dates); $i++) {
            $previous_date = clone $current_date;
            $previous_date->sub(new DateInterval('P1D'));
            
            if ($dates[$i]->format('Y-m-d') === $previous_date->format('Y-m-d')) {
                $streak++;
                $current_date = $dates[$i];
            } else {
                break; // Streak is broken
            }
        }
        
        return $streak;
    }
    
    /**
     * Calculate perfect days (days where all habits were completed)
     */
    public function calculatePerfectDays($user_id) {
        $sql = "
            SELECT 
                hc.completion_date,
                COUNT(DISTINCT hc.habit_id) as completed_habits,
                (SELECT COUNT(*) FROM habits WHERE user_id = ? AND created_at <= hc.completion_date) as total_habits
            FROM habit_completions hc
            JOIN habits h ON hc.habit_id = h.id
            WHERE h.user_id = ?
            GROUP BY hc.completion_date
            HAVING completed_habits = total_habits AND total_habits > 0
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id, $user_id]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Check total login achievements
     */
    private function checkTotalLoginAchievements($user_id) {
        $awarded = [];
        
        // Get total login count from user_login_days table
        $sql = "SELECT COUNT(*) FROM user_login_days WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $total_logins = $stmt->fetchColumn() ?: 0;
        
        // Define total login achievements (matching database achievement_key values)
        $login_achievements = [
            'regular_visitor' => 10,
            'loyal_user' => 50,
            'veteran' => 200
        ];
        
        foreach ($login_achievements as $achievement_key => $required_logins) {
            if ($total_logins >= $required_logins) {
                $achievement_id = $this->getAchievementId($achievement_key);
                if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, $total_logins)) {
                    $awarded[] = $achievement_key;
                }
            }
        }
        
        return $awarded;
    }
    
    /**
     * Check habit creation achievements
     */
    private function checkHabitCreationAchievements($user_id) {
        $awarded = [];
        
        // Get total habits created
        $sql = "SELECT COUNT(*) FROM habits WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $total_habits = $stmt->fetchColumn();
        
        // Define habit creation achievements (matching database achievement_key values)
        $creation_achievements = [
            'getting_started' => 1,
            'habit_builder' => 5,
            'habit_architect' => 20,
            'habit_tycoon' => 50
        ];
        
        foreach ($creation_achievements as $achievement_key => $required_habits) {
            if ($total_habits >= $required_habits) {
                $achievement_id = $this->getAchievementId($achievement_key);
                if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, $total_habits)) {
                    $awarded[] = $achievement_key;
                }
            }
        }
        
        return $awarded;
    }
    
    /**
     * Check habit completion achievements
     */
    private function checkHabitCompletionAchievements($user_id) {
        $awarded = [];
        
        // Get total habit completions
        $sql = "
            SELECT COUNT(*) 
            FROM habit_completions hc 
            JOIN habits h ON hc.habit_id = h.id 
            WHERE h.user_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $total_completions = $stmt->fetchColumn();
        
        // Define completion achievements (matching database achievement_key values)
        $completion_achievements = [
            'first_step' => 1,
            'consistency_champ' => 100,
            'completionist' => 500,
            'habit_hero' => 1000
        ];
        
        foreach ($completion_achievements as $achievement_key => $required_completions) {
            if ($total_completions >= $required_completions) {
                $achievement_id = $this->getAchievementId($achievement_key);
                if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, $total_completions)) {
                    $awarded[] = $achievement_key;
                }
            }
        }
        
        return $awarded;
    }
    
    /**
     * Check habit streak achievements
     */
    private function checkHabitStreakAchievements($user_id, $habit_id = null) {
        $awarded = [];
        
        // Get max streak for any habit from habit_streaks table
        $sql = "
            SELECT MAX(hs.streak) 
            FROM habit_streaks hs 
            JOIN habits h ON hs.habit_id = h.id 
            WHERE h.user_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $max_streak = $stmt->fetchColumn() ?: 0;
        
        // Define streak achievements (matching database achievement_key values)
        $streak_achievements = [
            'max_streak' => 7,
            'ultimate_streak' => 30
        ];
        
        foreach ($streak_achievements as $achievement_key => $required_streak) {
            if ($max_streak >= $required_streak) {
                $achievement_id = $this->getAchievementId($achievement_key);
                if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, $max_streak)) {
                    $awarded[] = $achievement_key;
                }
            }
        }
        
        return $awarded;
    }
    
    /**
     * Check perfect day/week/month achievements
     */
    private function checkPerfectDayAchievements($user_id) {
        $awarded = [];
        
        // Check if today is a perfect day (all habits completed)
        $today = date('Y-m-d');
        $perfect_day = $this->isPerfectDay($user_id, $today);
        
        if ($perfect_day) {
            // Perfect day achieved
            $achievement_id = $this->getAchievementId('perfect_day_1');
            if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, 1)) {
                $awarded[] = 'perfect_day_1';
            }
            
            // Check for consecutive perfect days
            $consecutive_perfect = $this->getConsecutivePerfectDays($user_id);
            
            if ($consecutive_perfect >= 7) {
                $achievement_id = $this->getAchievementId('perfect_week_1');
                if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, 1)) {
                    $awarded[] = 'perfect_week_1';
                }
            }
            
            if ($consecutive_perfect >= 30) {
                $achievement_id = $this->getAchievementId('perfect_month_1');
                if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, 1)) {
                    $awarded[] = 'perfect_month_1';
                }
            }
        }
        
        return $awarded;
    }
    
    /**
     * Check if a specific day is perfect (all habits completed)
     */
    private function isPerfectDay($user_id, $date) {
        $sql = "
            SELECT 
                COUNT(h.id) as total_habits,
                COUNT(hc.id) as completed_habits
            FROM habits h
            LEFT JOIN habit_completions hc ON h.id = hc.habit_id AND hc.completion_date = ?
            WHERE h.user_id = ? 
            AND DATE(h.created_at) <= ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$date, $user_id, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total_habits'] > 0 && $result['total_habits'] == $result['completed_habits'];
    }
    
    /**
     * Get consecutive perfect days from today backwards
     */
    private function getConsecutivePerfectDays($user_id) {
        $consecutive = 0;
        $today = date('Y-m-d');
        
        for ($i = 0; $i < 365; $i++) { // Check up to 1 year back
            $check_date = date('Y-m-d', strtotime($today . " -$i days"));
            
            if ($this->isPerfectDay($user_id, $check_date)) {
                $consecutive++;
            } else {
                break;
            }
        }
        
        return $consecutive;
    }
    
    /**
     * Check category-based achievements
     */
    private function checkCategoryAchievements($user_id, $category = null) {
        $awarded = [];
        
        // Check category-specific achievements
        $category_achievements = [
            'fitness_fanatic' => ['category' => 'fitness', 'required' => 50],
            'mindful_master' => ['category' => 'mindfulness', 'required' => 50], 
            'productivity_pro' => ['category' => 'productivity', 'required' => 50]
        ];
        
        foreach ($category_achievements as $achievement_key => $data) {
            $completions = $this->getCategoryCompletions($user_id, $data['category']);
            if ($completions >= $data['required']) {
                $achievement_id = $this->getAchievementId($achievement_key);
                if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, $completions)) {
                    $awarded[] = $achievement_key;
                }
            }
        }
        
        // Check balanced life (20 habits in each category)
        if ($this->checkBalancedLife($user_id)) {
            $achievement_id = $this->getAchievementId('balanced_life');
            if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, 1)) {
                $awarded[] = 'balanced_life';
            }
        }
        
        return $awarded;
    }
    
    /**
     * Check special achievements  
     */
    private function checkSpecialAchievements($user_id, $data = []) {
        $awarded = [];
        
        // Check early bird (completion before 7 AM)
        if ($this->checkEarlyBird($user_id)) {
            $achievement_id = $this->getAchievementId('early_bird');
            if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, 1)) {
                $awarded[] = 'early_bird';
            }
        }
        
        // Check night owl (completion after 10 PM)
        if ($this->checkNightOwl($user_id)) {
            $achievement_id = $this->getAchievementId('night_owl');
            if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, 1)) {
                $awarded[] = 'night_owl';
            }
        }
        
        // Check comeback kid (resume streak after breaking it)
        if ($this->checkComebackKid($user_id)) {
            $achievement_id = $this->getAchievementId('comeback_kid');
            if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, 1)) {
                $awarded[] = 'comeback_kid';
            }
        }
        
        // Check all-rounder (at least one achievement in every section)
        if ($this->checkAllRounder($user_id)) {
            $achievement_id = $this->getAchievementId('all_rounder');
            if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, 1)) {
                $awarded[] = 'all_rounder';
            }
        }
        
        return $awarded;
    }
    
    /**
     * Check perfect week achievements
     */
    private function checkPerfectWeekAchievements($user_id) {
        $awarded = [];
        
        // Calculate perfect weeks (7 consecutive perfect days)
        $perfect_weeks = $this->calculatePerfectWeeks($user_id);
        
        if ($perfect_weeks >= 1) {
            $achievement_id = $this->getAchievementId('perfect_week');
            if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, $perfect_weeks)) {
                $awarded[] = 'perfect_week';
            }
        }
        
        return $awarded;
    }
    
    /**
     * Check perfect month achievements
     */
    private function checkPerfectMonthAchievements($user_id) {
        $awarded = [];
        
        // Calculate perfect months (30 consecutive perfect days)
        $perfect_months = $this->calculatePerfectMonths($user_id);
        
        if ($perfect_months >= 1) {
            $achievement_id = $this->getAchievementId('perfect_month');
            if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, $perfect_months)) {
                $awarded[] = 'perfect_month';
            }
        }
        
        // Check flawless quarter (3 perfect months)
        if ($perfect_months >= 3) {
            $achievement_id = $this->getAchievementId('flawless_quarter');
            if ($achievement_id && $this->awardAchievement($user_id, $achievement_id, 1)) {
                $awarded[] = 'flawless_quarter';
            }
        }
        
        return $awarded;
    }
    
    // Helper calculation methods for new achievements
    
    /**
     * Calculate perfect weeks
     */
    public function calculatePerfectWeeks($user_id) {
        $consecutive_perfect_days = $this->getConsecutivePerfectDays($user_id);
        return intval($consecutive_perfect_days / 7);
    }
    
    /**
     * Calculate perfect months  
     */
    public function calculatePerfectMonths($user_id) {
        $consecutive_perfect_days = $this->getConsecutivePerfectDays($user_id);
        return intval($consecutive_perfect_days / 30);
    }
    
    /**
     * Get category completions
     */
    public function getCategoryCompletions($user_id, $category) {
        $sql = "
            SELECT COUNT(hc.id) 
            FROM habit_completions hc
            JOIN habits h ON hc.habit_id = h.id
            WHERE h.user_id = ? AND h.category = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id, $category]);
        return $stmt->fetchColumn() ?: 0;
    }
    
    /**
     * Check balanced life achievement
     */
    public function checkBalancedLife($user_id) {
        $categories = ['fitness', 'mindfulness', 'productivity', 'personal'];
        
        foreach ($categories as $category) {
            $completions = $this->getCategoryCompletions($user_id, $category);
            if ($completions < 20) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Check early bird achievement
     */
    public function checkEarlyBird($user_id) {
        $sql = "
            SELECT COUNT(hc.id) 
            FROM habit_completions hc
            JOIN habits h ON hc.habit_id = h.id
            WHERE h.user_id = ? AND TIME(hc.completion_date) < '07:00:00'
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check night owl achievement
     */
    public function checkNightOwl($user_id) {
        $sql = "
            SELECT COUNT(hc.id) 
            FROM habit_completions hc
            JOIN habits h ON hc.habit_id = h.id
            WHERE h.user_id = ? AND TIME(hc.completion_date) > '22:00:00'
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check comeback kid achievement
     */
    public function checkComebackKid($user_id) {
        // Logic: Check if user has broken a streak and then resumed
        $sql = "
            SELECT COUNT(DISTINCT hs.habit_id) 
            FROM habit_streaks hs
            JOIN habits h ON hs.habit_id = h.id
            WHERE h.user_id = ? AND hs.streak > 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        
        // Check if there are gaps in completions (indicating broken streaks that were resumed)
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check all-rounder achievement
     */
    public function checkAllRounder($user_id) {
        // Check if user has at least one achievement in every category
        $sql = "
            SELECT COUNT(DISTINCT ac.id) as earned_categories
            FROM user_achievements ua
            JOIN achievements a ON ua.achievement_id = a.id
            JOIN achievement_categories ac ON a.category_id = ac.id
            WHERE ua.user_id = ? AND ua.is_earned = 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $earned_categories = $stmt->fetchColumn();
        
        // Check total categories
        $sql = "SELECT COUNT(*) FROM achievement_categories";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $total_categories = $stmt->fetchColumn();
        
        return $earned_categories >= $total_categories;
    }
    
    /**
     * Get achievement ID by key
     */
    public function getAchievementId($achievement_key) {
        $sql = "SELECT id FROM achievements WHERE achievement_key = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$achievement_key]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Award achievement to user
     */
    public function awardAchievement($user_id, $achievement_id, $progress) {
        try {
            // Check if already earned
            $sql = "SELECT is_earned FROM user_achievements WHERE user_id = ? AND achievement_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id, $achievement_id]);
            $existing = $stmt->fetch();
            
            if ($existing && $existing['is_earned']) {
                return false; // Already earned
            }
            
            if ($existing) {
                // Update existing record
                $sql = "
                    UPDATE user_achievements 
                    SET current_progress = ?, is_earned = TRUE, earned_at = NOW()
                    WHERE user_id = ? AND achievement_id = ?
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$progress, $user_id, $achievement_id]);
            } else {
                // Insert new record
                $sql = "
                    INSERT INTO user_achievements 
                    (user_id, achievement_id, current_progress, is_earned, earned_at) 
                    VALUES (?, ?, ?, TRUE, NOW())
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$user_id, $achievement_id, $progress]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error awarding achievement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update achievement progress (without awarding)
     */
    public function updateProgress($user_id, $achievement_key, $progress) {
        try {
            $achievement_id = $this->getAchievementId($achievement_key);
            if (!$achievement_id) return false;
            
            $sql = "
                INSERT INTO user_achievements (user_id, achievement_id, current_progress) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                current_progress = GREATEST(current_progress, VALUES(current_progress))
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id, $achievement_id, $progress]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error updating achievement progress: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Batch update all achievement progress for a user
     */
    public function updateAllProgress($user_id) {
        try {
            // Update login streak progress (use BEST login streak for achievements)
            $best_login_streak = $this->calculateBestLoginStreak($user_id);
            $this->updateProgress($user_id, 'streak_starter', $best_login_streak);
            $this->updateProgress($user_id, 'streak_pro', $best_login_streak);
            $this->updateProgress($user_id, 'streak_master', $best_login_streak);
            $this->updateProgress($user_id, 'streak_legend', $best_login_streak);
            
            // Update total login days progress
            $sql = "SELECT COUNT(*) FROM user_login_days WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $total_logins = $stmt->fetchColumn();
            
            $this->updateProgress($user_id, 'regular_visitor', $total_logins);
            $this->updateProgress($user_id, 'habitual_user', $total_logins);
            $this->updateProgress($user_id, 'veteran', $total_logins);
            $this->updateProgress($user_id, 'lifetime_member', $total_logins);
            
            // Update habit creation progress
            $sql = "SELECT COUNT(*) FROM habits WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $total_habits = $stmt->fetchColumn();
            
            $this->updateProgress($user_id, 'getting_started', $total_habits);
            $this->updateProgress($user_id, 'habit_builder', $total_habits);
            $this->updateProgress($user_id, 'habit_architect', $total_habits);
            $this->updateProgress($user_id, 'habit_tycoon', $total_habits);
            
            // Update completion progress
            $sql = "
                SELECT COUNT(*) 
                FROM habit_completions hc 
                JOIN habits h ON hc.habit_id = h.id 
                WHERE h.user_id = ?
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $total_completions = $stmt->fetchColumn();
            
            $this->updateProgress($user_id, 'first_step', $total_completions);
            $this->updateProgress($user_id, 'consistency_champ', $total_completions);
            $this->updateProgress($user_id, 'completionist', $total_completions);
            $this->updateProgress($user_id, 'habit_hero', $total_completions);
            
            // Update habit streak progress
            $sql = "
                SELECT MAX(hs.streak) 
                FROM habit_streaks hs 
                JOIN habits h ON hs.habit_id = h.id 
                WHERE h.user_id = ?
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $max_streak = $stmt->fetchColumn() ?: 0;
            
            $this->updateProgress($user_id, 'mini_streak', $max_streak);
            $this->updateProgress($user_id, 'mega_streak', $max_streak);
            $this->updateProgress($user_id, 'ultimate_streak', $max_streak);
            $this->updateProgress($user_id, 'streak_king', $max_streak);
            
            // Update perfect day/week/month progress (calculate properly)
            $perfect_days = $this->calculatePerfectDays($user_id);
            $this->updateProgress($user_id, 'perfect_day', $perfect_days);
            
            $perfect_weeks = $this->calculatePerfectWeeks($user_id);
            $this->updateProgress($user_id, 'perfect_week', $perfect_weeks);
            
            $perfect_months = $this->calculatePerfectMonths($user_id);
            $this->updateProgress($user_id, 'perfect_month', $perfect_months);
            $this->updateProgress($user_id, 'flawless_quarter', $perfect_months >= 3 ? 1 : 0);
            
            // Update category specific achievements
            $fitness_completions = $this->getCategoryCompletions($user_id, 'fitness');
            $this->updateProgress($user_id, 'fitness_fanatic', $fitness_completions);
            
            $mindfulness_completions = $this->getCategoryCompletions($user_id, 'mindfulness');
            $this->updateProgress($user_id, 'mindful_master', $mindfulness_completions);
            
            $productivity_completions = $this->getCategoryCompletions($user_id, 'productivity');
            $this->updateProgress($user_id, 'productivity_pro', $productivity_completions);
            
            $this->updateProgress($user_id, 'balanced_life', $this->checkBalancedLife($user_id) ? 1 : 0);
            
            // Update special achievements
            $this->updateProgress($user_id, 'early_bird', $this->checkEarlyBird($user_id) ? 1 : 0);
            $this->updateProgress($user_id, 'night_owl', $this->checkNightOwl($user_id) ? 1 : 0);
            $this->updateProgress($user_id, 'comeback_kid', $this->checkComebackKid($user_id) ? 1 : 0);
            $this->updateProgress($user_id, 'all_rounder', $this->checkAllRounder($user_id) ? 1 : 0);
            
            return true;
        } catch (Exception $e) {
            error_log("Error updating all progress: " . $e->getMessage());
            return false;
        }
    }
}

// Helper function to check achievements from other files
function checkUserAchievements($user_id, $action_type, $data = []) {
    $manager = new AchievementManager();
    return $manager->checkAchievements($user_id, $action_type, $data);
}
?>