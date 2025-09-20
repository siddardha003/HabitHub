<?php
// Debug specific achievement calculations for User ID 1
session_start();

echo "<h2>🔍 Achievement Debug for User ID 1</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    $user_id = 1; // Debug specific user
    
    echo "<h3>📊 User Statistics</h3>";
    
    // Login days count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_login_days WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $login_days = $stmt->fetchColumn();
    echo "<p>👤 Total Login Days: <strong>$login_days</strong></p>";
    
    // Habit count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM habits WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $habit_count = $stmt->fetchColumn();
    echo "<p>🎯 Total Habits Created: <strong>$habit_count</strong></p>";
    
    // Completion count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM habit_completions hc 
        JOIN habits h ON hc.habit_id = h.id 
        WHERE h.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $completion_count = $stmt->fetchColumn();
    echo "<p>✅ Total Completions: <strong>$completion_count</strong></p>";
    
    // Max habit streak
    $stmt = $pdo->prepare("
        SELECT MAX(hs.streak) 
        FROM habit_streaks hs 
        JOIN habits h ON hs.habit_id = h.id 
        WHERE h.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $max_streak = $stmt->fetchColumn() ?: 0;
    echo "<p>🔥 Max Habit Streak: <strong>$max_streak</strong></p>";
    
    echo "<h3>🏆 Achievement Requirements vs Current Progress</h3>";
    
    // Get achievements and check which should be unlocked
    $stmt = $pdo->prepare("
        SELECT a.achievement_key, a.name, a.requirement_type, a.requirement_value,
               COALESCE(ua.is_earned, FALSE) as is_earned
        FROM achievements a
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        ORDER BY a.category_id, a.requirement_value
    ");
    $stmt->execute([$user_id]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Achievement</th><th>Type</th><th>Required</th><th>Current</th><th>Should Unlock?</th><th>Currently Earned</th></tr>";
    
    foreach ($achievements as $achievement) {
        $current_value = 0;
        $should_unlock = false;
        
        switch ($achievement['requirement_type']) {
            case 'login_days':
                $current_value = $login_days;
                $should_unlock = $login_days >= $achievement['requirement_value'];
                break;
            case 'login_streak':
                // We'd need to calculate this
                $current_value = "Need to calculate";
                break;
            case 'habits_created':
                $current_value = $habit_count;
                $should_unlock = $habit_count >= $achievement['requirement_value'];
                break;
            case 'habit_completions':
                $current_value = $completion_count;
                $should_unlock = $completion_count >= $achievement['requirement_value'];
                break;
            case 'habit_streak':
                $current_value = $max_streak;
                $should_unlock = $max_streak >= $achievement['requirement_value'];
                break;
            case 'perfect_days':
                $current_value = "Need to calculate";
                break;
            case 'category_completions':
                $current_value = "Need to calculate";
                break;
            case 'special':
                $current_value = "Special condition";
                break;
        }
        
        $earned_status = $achievement['is_earned'] ? '✅ Yes' : '❌ No';
        $should_status = $should_unlock ? '🎯 YES!' : '⏳ Not yet';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($achievement['name']) . "</td>";
        echo "<td>" . htmlspecialchars($achievement['requirement_type']) . "</td>";
        echo "<td>" . htmlspecialchars($achievement['requirement_value']) . "</td>";
        echo "<td>$current_value</td>";
        echo "<td style='font-weight: bold;'>$should_status</td>";
        echo "<td>$earned_status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🚀 Manual Achievement Test</h3>";
    echo "<p>Now let's try to manually award achievements that should be unlocked...</p>";
    
    // Try to load and test achievement manager
    require_once 'includes/achievement_manager.php';
    $manager = new AchievementManager();
    
    echo "<h4>Testing Login Achievements:</h4>";
    $login_awarded = $manager->checkAchievements($user_id, 'login');
    echo "<p>Login achievements awarded: " . implode(', ', $login_awarded) . "</p>";
    
    echo "<h4>Testing Habit Creation Achievements:</h4>";
    $creation_awarded = $manager->checkAchievements($user_id, 'habit_created');
    echo "<p>Creation achievements awarded: " . implode(', ', $creation_awarded) . "</p>";
    
    echo "<h4>Testing Habit Completion Achievements:</h4>";
    $completion_awarded = $manager->checkAchievements($user_id, 'habit_completed');
    echo "<p>Completion achievements awarded: " . implode(', ', $completion_awarded) . "</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}

table {
    background: white;
    margin: 10px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

th {
    background-color: #007bff;
    color: white;
    padding: 10px;
}

td {
    padding: 8px;
    border-bottom: 1px solid #eee;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

h2, h3 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}
</style>