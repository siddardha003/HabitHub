<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Update all progress before displaying achievements
    require_once 'achievement_manager.php';
    $achievement_manager = new AchievementManager();
    $achievement_manager->updateAllProgress($user_id);
    
    // Get all achievements with user progress
    $sql = "
        SELECT 
            a.id,
            a.achievement_key,
            a.name,
            a.description,
            a.icon,
            a.rarity,
            a.points,
            a.requirement_type,
            a.requirement_value,
            a.requirement_data,
            ac.name as category_name,
            ac.display_name as category_display_name,
            COALESCE(ua.current_progress, 0) as progress,
            COALESCE(ua.is_earned, FALSE) as earned,
            ua.earned_at
        FROM achievements a
        LEFT JOIN achievement_categories ac ON a.category_id = ac.id
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        WHERE a.is_active = TRUE
        ORDER BY a.category_id, a.requirement_value ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize achievements by category and implement progressive unlocking
    $organized_achievements = [];
    
    // Group achievements by category first
    $achievements_by_category = [];
    foreach ($achievements as $achievement) {
        $category = $achievement['category_name'] ?? 'general';
        if (!isset($achievements_by_category[$category])) {
            $achievements_by_category[$category] = [];
        }
        $achievements_by_category[$category][] = $achievement;
    }
    
    // Apply progressive unlocking logic to each category
    foreach ($achievements_by_category as $category => $category_achievements) {
        $organized_achievements[$category] = [];
        
        // Special achievements are always active
        if ($category === 'special') {
            foreach ($category_achievements as $achievement) {
                $formatted_achievement = [
                    'id' => $achievement['achievement_key'],
                    'name' => $achievement['name'],
                    'description' => $achievement['description'],
                    'icon' => $achievement['icon'],
                    'rarity' => $achievement['rarity'],
                    'points' => (int)$achievement['points'],
                    'progress' => (int)$achievement['progress'],
                    'maxProgress' => (int)$achievement['requirement_value'],
                    'earned' => (bool)$achievement['earned'],
                    'earnedDate' => $achievement['earned_at'],
                    'requirement_type' => $achievement['requirement_type'],
                    'requirement_data' => $achievement['requirement_data'] ? json_decode($achievement['requirement_data'], true) : null,
                    'category' => $category,
                    'isActive' => true, // Always active for special achievements
                    'isLocked' => false
                ];
                $organized_achievements[$category][] = $formatted_achievement;
            }
        } else {
            // Progressive unlocking for other categories
            $found_first_incomplete = false;
            
            foreach ($category_achievements as $achievement) {
                $is_earned = (bool)$achievement['earned'];
                $is_active = false;
                $is_locked = false;
                
                if ($is_earned) {
                    // Already earned - always show as earned
                    $is_active = true;
                } elseif (!$found_first_incomplete) {
                    // First incomplete achievement in this category - make it active
                    $is_active = true;
                    $found_first_incomplete = true;
                } else {
                    // All subsequent achievements are locked
                    $is_locked = true;
                }
                
                $formatted_achievement = [
                    'id' => $achievement['achievement_key'],
                    'name' => $achievement['name'],
                    'description' => $achievement['description'],
                    'icon' => $achievement['icon'],
                    'rarity' => $achievement['rarity'],
                    'points' => (int)$achievement['points'],
                    'progress' => $is_locked ? 0 : (int)$achievement['progress'], // Hide progress for locked achievements
                    'maxProgress' => (int)$achievement['requirement_value'],
                    'earned' => $is_earned,
                    'earnedDate' => $achievement['earned_at'],
                    'requirement_type' => $achievement['requirement_type'],
                    'requirement_data' => $achievement['requirement_data'] ? json_decode($achievement['requirement_data'], true) : null,
                    'category' => $category,
                    'isActive' => $is_active,
                    'isLocked' => $is_locked
                ];
                $organized_achievements[$category][] = $formatted_achievement;
            }
        }
    }
    
    // Get user statistics
    $stats_sql = "
        SELECT 
            COUNT(CASE WHEN ua.is_earned = TRUE THEN 1 END) as total_earned,
            COALESCE(SUM(CASE WHEN ua.is_earned = TRUE THEN a.points END), 0) as total_xp,
            MAX(CASE WHEN ua.is_earned = TRUE THEN a.rarity END) as rarest_rarity
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = ?
    ";
    
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent achievements (last 5 earned)
    $recent_sql = "
        SELECT 
            a.name,
            a.icon,
            a.rarity,
            a.points,
            ua.earned_at
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = ? AND ua.is_earned = TRUE
        ORDER BY ua.earned_at DESC
        LIMIT 5
    ";
    
    $recent_stmt = $pdo->prepare($recent_sql);
    $recent_stmt->execute([$user_id]);
    $recent_achievements = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current login streak for stats
    $streak_sql = "
        SELECT COALESCE(current_streak, 0) as current_streak 
        FROM user_streaks 
        WHERE user_id = ?
    ";
    
    $streak_stmt = $pdo->prepare($streak_sql);
    $streak_stmt->execute([$user_id]);
    $streak_data = $streak_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'achievements' => $organized_achievements,
        'stats' => [
            'total_earned' => (int)($stats['total_earned'] ?? 0),
            'total_xp' => (int)($stats['total_xp'] ?? 0),
            'rarest_rarity' => $stats['rarest_rarity'] ?? 'common',
            'current_streak' => (int)($streak_data['current_streak'] ?? 0)
        ],
        'recent_achievements' => $recent_achievements
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>