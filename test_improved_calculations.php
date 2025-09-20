<?php
// Test Best Streak and Perfect Day Calculations
require_once 'config/database.php';
require_once 'includes/achievement_manager.php';

try {
    $user_id = 1; // Change if needed
    
    echo "<h2>🔧 Testing Improved Achievement Calculations</h2>\n";
    
    $achievement_manager = new AchievementManager();
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Test streak calculations
    echo "<h3>📊 Streak Calculations Comparison</h3>\n";
    
    $current_login_streak = $achievement_manager->calculateLoginStreak($user_id);
    $best_login_streak = $achievement_manager->calculateBestLoginStreak($user_id);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Calculation Type</th><th>Value</th><th>Used For</th></tr>\n";
    echo "<tr><td>Current Login Streak</td><td><strong>$current_login_streak</strong></td><td>Display only</td></tr>\n";
    echo "<tr><td><strong>Best Login Streak</strong></td><td><strong>$best_login_streak</strong></td><td>🏆 Achievements</td></tr>\n";
    echo "</table>\n";
    
    // Test perfect days calculation
    echo "<h3>🌟 Perfect Days Calculation</h3>\n";
    
    $perfect_days = $achievement_manager->calculatePerfectDays($user_id);
    echo "<p><strong>Perfect Days Found:</strong> $perfect_days</p>\n";
    
    // Show some perfect day details
    $sql = "
        SELECT 
            hc.completion_date,
            COUNT(DISTINCT hc.habit_id) as completed_habits,
            (SELECT COUNT(*) FROM habits WHERE user_id = ? AND created_at <= hc.completion_date) as total_habits_that_day
        FROM habit_completions hc
        JOIN habits h ON hc.habit_id = h.id
        WHERE h.user_id = ?
        GROUP BY hc.completion_date
        HAVING completed_habits = total_habits_that_day AND total_habits_that_day > 0
        ORDER BY hc.completion_date DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo "<h4>Recent Perfect Days:</h4>\n";
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Date</th><th>Completed Habits</th><th>Total Habits That Day</th></tr>\n";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['completion_date'] . "</td>";
            echo "<td>" . $row['completed_habits'] . "</td>";
            echo "<td>" . $row['total_habits_that_day'] . "</td>";
            echo "</tr>\n";
        }
        
        echo "</table>\n";
    } else {
        echo "<p>No perfect days found. A perfect day is when you complete ALL your habits for that day.</p>\n";
    }
    
    // Update all progress with new calculations
    echo "<h3>🔄 Updating Progress with Improved Calculations</h3>\n";
    $result = $achievement_manager->updateAllProgress($user_id);
    echo "<p>Update All Progress: " . ($result ? "✅ Success" : "❌ Failed") . "</p>\n";
    
    // Show updated streak achievements
    echo "<h3>🏆 Login Streak Achievement Status</h3>\n";
    
    $sql = "
        SELECT 
            a.achievement_key,
            a.name,
            a.requirement_value,
            ua.current_progress,
            ua.is_earned
        FROM achievements a 
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        WHERE a.achievement_key IN ('streak_starter', 'streak_pro', 'streak_master', 'streak_legend')
        ORDER BY a.requirement_value
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Achievement</th><th>Required</th><th>Your Best Streak</th><th>Status</th></tr>\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['is_earned'] ? '🏆 Earned' : '⏳ In Progress';
        $progress = $row['current_progress'] ?? 0;
        $required = $row['requirement_value'];
        
        $row_style = $row['is_earned'] ? 'style="background-color: #d4edda;"' : '';
        
        echo "<tr $row_style>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $required . "</td>";
        echo "<td><strong>" . $progress . "</strong></td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // Show perfect day achievement status
    echo "<h3>🌟 Perfect Day Achievement Status</h3>\n";
    
    $sql = "
        SELECT 
            a.achievement_key,
            a.name,
            a.requirement_value,
            ua.current_progress,
            ua.is_earned
        FROM achievements a 
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        WHERE a.achievement_key = 'perfect_day'
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['is_earned'] ? '🏆 Earned' : '⏳ In Progress';
        $progress = $row['current_progress'] ?? 0;
        $required = $row['requirement_value'];
        
        echo "<p><strong>" . $row['name'] . ":</strong> $progress/$required - $status</p>\n";
        
        if ($progress >= $required && !$row['is_earned']) {
            echo "<p>🎯 <strong>You should have earned this achievement! Let's award it...</strong></p>\n";
            
            // Try to award the achievement
            $achievement_id = $achievement_manager->getAchievementId('perfect_day');
            if ($achievement_id) {
                $awarded = $achievement_manager->awardAchievement($user_id, $achievement_id, $progress);
                echo "<p>Award result: " . ($awarded ? "✅ Awarded!" : "❌ Failed or already earned") . "</p>\n";
            }
        }
    }
    
    echo "<hr>\n";
    echo "<p><strong>✅ All calculations now use BEST streaks and ACTUAL perfect day counts!</strong></p>\n";
    echo "<p>Check your <a href='pages/dashboard/achievements.html' target='_blank'>Achievements Page</a> to see the updates!</p>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>