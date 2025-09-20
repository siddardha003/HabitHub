<?php
// Comprehensive Achievement Fix - Check and Award ALL Qualifying Achievements
require_once 'config/database.php';
require_once 'includes/achievement_manager.php';

try {
    $user_id = 1; // Change if needed
    
    echo "<h2>🔧 Comprehensive Achievement Fix for User ID: $user_id</h2>\n";
    
    $achievement_manager = new AchievementManager();
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get current user statistics
    echo "<h3>📊 Current User Statistics</h3>\n";
    
    $sql = "SELECT COUNT(*) FROM habits WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $habit_count = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) FROM user_login_days WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $login_count = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) FROM habit_completions hc JOIN habits h ON hc.habit_id = h.id WHERE h.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $completion_count = $stmt->fetchColumn();
    
    $best_login_streak = $achievement_manager->calculateBestLoginStreak($user_id);
    $perfect_days = $achievement_manager->calculatePerfectDays($user_id);
    
    $sql = "SELECT MAX(hs.streak) FROM habit_streaks hs JOIN habits h ON hs.habit_id = h.id WHERE h.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $max_habit_streak = $stmt->fetchColumn() ?: 0;
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Statistic</th><th>Value</th></tr>\n";
    echo "<tr><td>Total Habits Created</td><td><strong>$habit_count</strong></td></tr>\n";
    echo "<tr><td>Total Login Days</td><td><strong>$login_count</strong></td></tr>\n";
    echo "<tr><td>Total Completions</td><td><strong>$completion_count</strong></td></tr>\n";
    echo "<tr><td>Best Login Streak</td><td><strong>$best_login_streak</strong></td></tr>\n";
    echo "<tr><td>Max Habit Streak</td><td><strong>$max_habit_streak</strong></td></tr>\n";
    echo "<tr><td>Perfect Days</td><td><strong>$perfect_days</strong></td></tr>\n";
    echo "</table>\n";
    
    echo "<hr>\n";
    
    // Define all achievements and their requirements
    $achievement_checks = [
        // Login Streak Achievements
        'streak_starter' => ['type' => 'login_streak', 'required' => 3, 'current' => $best_login_streak],
        'streak_pro' => ['type' => 'login_streak', 'required' => 7, 'current' => $best_login_streak],
        'streak_master' => ['type' => 'login_streak', 'required' => 30, 'current' => $best_login_streak],
        'streak_legend' => ['type' => 'login_streak', 'required' => 100, 'current' => $best_login_streak],
        
        // Total Login Achievements  
        'regular_visitor' => ['type' => 'total_logins', 'required' => 10, 'current' => $login_count],
        'habitual_user' => ['type' => 'total_logins', 'required' => 50, 'current' => $login_count],
        'veteran' => ['type' => 'total_logins', 'required' => 200, 'current' => $login_count],
        'lifetime_member' => ['type' => 'total_logins', 'required' => 500, 'current' => $login_count],
        
        // Habit Creation Achievements
        'getting_started' => ['type' => 'habit_creation', 'required' => 1, 'current' => $habit_count],
        'habit_builder' => ['type' => 'habit_creation', 'required' => 5, 'current' => $habit_count],
        'habit_architect' => ['type' => 'habit_creation', 'required' => 20, 'current' => $habit_count],
        'habit_tycoon' => ['type' => 'habit_creation', 'required' => 50, 'current' => $habit_count],
        
        // Completion Achievements
        'first_step' => ['type' => 'completion', 'required' => 1, 'current' => $completion_count],
        'consistency_champ' => ['type' => 'completion', 'required' => 100, 'current' => $completion_count],
        'completionist' => ['type' => 'completion', 'required' => 500, 'current' => $completion_count],
        'habit_hero' => ['type' => 'completion', 'required' => 1000, 'current' => $completion_count],
        
        // Habit Streak Achievements
        'mini_streak' => ['type' => 'habit_streak', 'required' => 7, 'current' => $max_habit_streak],
        'mega_streak' => ['type' => 'habit_streak', 'required' => 30, 'current' => $max_habit_streak],
        'ultimate_streak' => ['type' => 'habit_streak', 'required' => 100, 'current' => $max_habit_streak],
        'streak_king' => ['type' => 'habit_streak', 'required' => 365, 'current' => $max_habit_streak],
        
        // Perfect Day Achievements
        'perfect_day' => ['type' => 'perfect_day', 'required' => 1, 'current' => $perfect_days],
    ];
    
    echo "<h3>🎯 Achievement Check and Award Results</h3>\n";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Achievement</th><th>Type</th><th>Required</th><th>Your Score</th><th>Qualified?</th><th>Award Result</th></tr>\n";
    
    $total_awarded = 0;
    
    foreach ($achievement_checks as $achievement_key => $check) {
        $required = $check['required'];
        $current = $check['current'];
        $qualified = $current >= $required;
        
        // Update progress first
        $achievement_manager->updateProgress($user_id, $achievement_key, $current);
        
        $award_result = "N/A";
        if ($qualified) {
            // Try to award the achievement
            $achievement_id = $achievement_manager->getAchievementId($achievement_key);
            if ($achievement_id) {
                $awarded = $achievement_manager->awardAchievement($user_id, $achievement_id, $current);
                if ($awarded) {
                    $award_result = "✅ Newly Awarded!";
                    $total_awarded++;
                } else {
                    $award_result = "🏆 Already Earned";
                }
            } else {
                $award_result = "❌ Achievement Not Found";
            }
        } else {
            $award_result = "⏳ Not Qualified Yet";
        }
        
        $row_color = '';
        if ($qualified && strpos($award_result, 'Newly Awarded') !== false) {
            $row_color = 'style="background-color: #d4edda; color: #155724;"';
        } elseif ($qualified) {
            $row_color = 'style="background-color: #fff3cd; color: #856404;"';
        }
        
        echo "<tr $row_color>";
        echo "<td><strong>$achievement_key</strong></td>";
        echo "<td>" . $check['type'] . "</td>";
        echo "<td>$required</td>";
        echo "<td><strong>$current</strong></td>";
        echo "<td>" . ($qualified ? "✅ Yes" : "❌ No") . "</td>";
        echo "<td>$award_result</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<hr>\n";
    echo "<h3>📈 Summary</h3>\n";
    echo "<p>✅ <strong>$total_awarded new achievements awarded!</strong></p>\n";
    echo "<p>🔄 All progress values updated with correct calculations</p>\n";
    echo "<p>🎯 Check your <a href='pages/dashboard/achievements.html' target='_blank'>Achievements Page</a> - progress should now show correctly!</p>\n";
    
    // Force update all progress one more time
    $achievement_manager->updateAllProgress($user_id);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>