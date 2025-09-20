<?php
// Fix ALL Achievement Progress Values
require_once 'config/database.php';
require_once 'includes/achievement_manager.php';

try {
    $user_id = 1; // Change if needed
    
    echo "<h2>🔧 Fixing ALL Achievement Progress for User ID: $user_id</h2>\n";
    
    $achievement_manager = new AchievementManager();
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get current user stats
    echo "<h3>📊 Current User Statistics</h3>\n";
    
    // Habits count
    $sql = "SELECT COUNT(*) FROM habits WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $habit_count = $stmt->fetchColumn();
    echo "<p><strong>Total Habits:</strong> $habit_count</p>\n";
    
    // Login days count
    $sql = "SELECT COUNT(*) FROM user_login_days WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $login_count = $stmt->fetchColumn();
    echo "<p><strong>Total Login Days:</strong> $login_count</p>\n";
    
    // Completions count
    $sql = "SELECT COUNT(*) FROM habit_completions hc JOIN habits h ON hc.habit_id = h.id WHERE h.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $completion_count = $stmt->fetchColumn();
    echo "<p><strong>Total Completions:</strong> $completion_count</p>\n";
    
    // Max streak
    $sql = "SELECT MAX(hs.streak) FROM habit_streaks hs JOIN habits h ON hs.habit_id = h.id WHERE h.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $max_streak = $stmt->fetchColumn() ?: 0;
    echo "<p><strong>Max Habit Streak:</strong> $max_streak</p>\n";
    
    // Login streak
    $login_streak = $achievement_manager->calculateLoginStreak($user_id);
    echo "<p><strong>Current Login Streak:</strong> $login_streak</p>\n";
    
    echo "<hr>\n";
    
    // Update all progress
    echo "<h3>🔄 Updating ALL Progress...</h3>\n";
    $result = $achievement_manager->updateAllProgress($user_id);
    echo "<p>Update All Progress: " . ($result ? "✅ Success" : "❌ Failed") . "</p>\n";
    
    echo "<hr>\n";
    
    // Show results for ALL achievements
    echo "<h3>📋 ALL Achievement Progress Results</h3>\n";
    
    $sql = "
        SELECT 
            a.achievement_key,
            a.name,
            a.requirement_value,
            ua.current_progress,
            ua.is_earned,
            ac.display_name as category
        FROM achievements a 
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        LEFT JOIN achievement_categories ac ON a.category_id = ac.id
        WHERE a.is_active = TRUE
        ORDER BY ac.display_name, a.requirement_value
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Category</th><th>Achievement</th><th>Required</th><th>Progress</th><th>Status</th></tr>\n";
    
    $current_category = '';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $category = $row['category'] ?: 'Unknown';
        $status = $row['is_earned'] ? '🏆 Earned' : '⏳ In Progress';
        $progress = $row['current_progress'] ?? 0;
        $required = $row['requirement_value'];
        
        // Color code based on progress
        $progress_color = '';
        if ($row['is_earned']) {
            $progress_color = 'style="background-color: #d4edda; color: #155724;"';
        } elseif ($progress > 0) {
            $progress_color = 'style="background-color: #fff3cd; color: #856404;"';
        }
        
        echo "<tr $progress_color>";
        echo "<td>" . ($category !== $current_category ? "<strong>$category</strong>" : '') . "</td>";
        echo "<td>" . $row['name'] . " <small>(" . $row['achievement_key'] . ")</small></td>";
        echo "<td>" . $required . "</td>";
        echo "<td><strong>" . $progress . "/" . $required . "</strong></td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>\n";
        
        $current_category = $category;
    }
    
    echo "</table>\n";
    
    echo "<hr>\n";
    echo "<h3>🎯 Next Steps</h3>\n";
    echo "<p>✅ All progress values have been updated with correct achievement keys</p>\n";
    echo "<p>✅ Check the <a href='pages/dashboard/achievements.html' target='_blank'>Achievements Page</a> to see the updated progress</p>\n";
    echo "<p>✅ Progress should now show actual values instead of 0</p>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>