<?php
// Manual Achievement Fix Script
require_once 'config/database.php';
require_once 'includes/achievement_manager.php';

session_start();

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get current user ID (assuming you're logged in as user 1)
    $user_id = 1; // Change this if needed
    
    echo "<h2>Manual Achievement Fix for User ID: $user_id</h2>\n";
    
    // First, let's check current habit count
    $sql = "SELECT COUNT(*) FROM habits WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $total_habits = $stmt->fetchColumn();
    
    echo "<p><strong>Current Habits Count:</strong> $total_habits</p>\n";
    
    // Update progress for habit creation achievements
    $achievement_manager = new AchievementManager();
    
    echo "<h3>Updating Progress...</h3>\n";
    
    // Update progress for getting_started (1 habit)
    $result1 = $achievement_manager->updateProgress($user_id, 'getting_started', $total_habits);
    echo "<p>Getting Started progress update: " . ($result1 ? "✅ Success" : "❌ Failed") . "</p>\n";
    
    // Update progress for habit_builder (5 habits)
    $result2 = $achievement_manager->updateProgress($user_id, 'habit_builder', $total_habits);
    echo "<p>Habit Builder progress update: " . ($result2 ? "✅ Success" : "❌ Failed") . "</p>\n";
    
    // Check and award achievements
    echo "<h3>Checking Achievements...</h3>\n";
    $awarded = $achievement_manager->checkAchievements($user_id, 'habit_created');
    
    if (!empty($awarded)) {
        echo "<p><strong>Achievements Awarded:</strong></p>\n";
        echo "<ul>\n";
        foreach ($awarded as $achievement) {
            echo "<li>✅ $achievement</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p>❌ No new achievements awarded</p>\n";
    }
    
    // Force update all progress
    echo "<h3>Forcing Progress Update...</h3>\n";
    $all_updated = $achievement_manager->updateAllProgress($user_id);
    echo "<p>All progress update: " . ($all_updated ? "✅ Success" : "❌ Failed") . "</p>\n";
    
    // Show current achievement status
    echo "<h3>Current Achievement Status</h3>\n";
    
    $sql = "
        SELECT a.name as title, a.achievement_key, ua.is_earned, ua.current_progress as progress, a.requirement_value as required_value
        FROM achievements a 
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        WHERE a.achievement_key IN ('getting_started', 'habit_builder', 'habit_architect', 'habit_tycoon')
        ORDER BY a.requirement_value
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Achievement</th><th>Key</th><th>Required</th><th>Progress</th><th>Status</th></tr>\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['is_earned'] ? '🏆 Earned' : '⏳ In Progress';
        $progress = $row['progress'] ?: 0;
        $required = $row['required_value'];
        
        echo "<tr>";
        echo "<td>" . $row['title'] . "</td>";
        echo "<td>" . $row['achievement_key'] . "</td>";
        echo "<td>" . $required . "</td>";
        echo "<td>" . $progress . "/" . $required . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // Manual award if needed
    echo "<h3>Manual Award Check</h3>\n";
    
    if ($total_habits >= 1) {
        // Award getting_started
        $achievement_id = $achievement_manager->getAchievementId('getting_started');
        if ($achievement_id) {
            $awarded1 = $achievement_manager->awardAchievement($user_id, $achievement_id, $total_habits);
            echo "<p>Getting Started manual award: " . ($awarded1 ? "✅ Awarded" : "❌ Failed or already earned") . "</p>\n";
        }
    }
    
    if ($total_habits >= 5) {
        // Award habit_builder
        $achievement_id = $achievement_manager->getAchievementId('habit_builder');
        if ($achievement_id) {
            $awarded2 = $achievement_manager->awardAchievement($user_id, $achievement_id, $total_habits);
            echo "<p>Habit Builder manual award: " . ($awarded2 ? "✅ Awarded" : "❌ Failed or already earned") . "</p>\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>