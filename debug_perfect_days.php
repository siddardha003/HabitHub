<?php
// Debug Perfect Days Calculation
require_once 'config/database.php';
require_once 'includes/achievement_manager.php';

try {
    $user_id = 1; // Change if needed
    
    echo "<h2>🔍 Debug Perfect Days Calculation for User ID: $user_id</h2>\n";
    
    $database = new Database();
    $pdo = $database->getConnection();
    $achievement_manager = new AchievementManager();
    
    // Get the perfect days calculation result
    $perfect_days_count = $achievement_manager->calculatePerfectDays($user_id);
    echo "<p><strong>Current Perfect Days Count:</strong> $perfect_days_count</p>\n";
    
    echo "<h3>📋 Detailed Perfect Days Analysis</h3>\n";
    
    // Show the detailed query results
    $sql = "
        SELECT 
            hc.completion_date,
            COUNT(DISTINCT hc.habit_id) as completed_habits,
            (SELECT COUNT(*) FROM habits WHERE user_id = ? AND created_at <= hc.completion_date) as total_habits_that_day,
            GROUP_CONCAT(DISTINCT h2.name SEPARATOR ', ') as completed_habit_names
        FROM habit_completions hc
        JOIN habits h ON hc.habit_id = h.id
        JOIN habits h2 ON hc.habit_id = h2.id
        WHERE h.user_id = ?
        GROUP BY hc.completion_date
        ORDER BY hc.completion_date DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Date</th><th>Completed Habits</th><th>Total Habits That Day</th><th>Perfect Day?</th><th>Completed Habit Names</th></tr>\n";
    
    $actual_perfect_days = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $is_perfect = $row['completed_habits'] == $row['total_habits_that_day'] && $row['total_habits_that_day'] > 0;
        if ($is_perfect) $actual_perfect_days++;
        
        $perfect_status = $is_perfect ? '🌟 YES' : '❌ No';
        $row_color = $is_perfect ? 'style="background-color: #d4edda;"' : '';
        
        echo "<tr $row_color>";
        echo "<td>" . $row['completion_date'] . "</td>";
        echo "<td><strong>" . $row['completed_habits'] . "</strong></td>";
        echo "<td><strong>" . $row['total_habits_that_day'] . "</strong></td>";
        echo "<td>$perfect_status</td>";
        echo "<td><small>" . $row['completed_habit_names'] . "</small></td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<p><strong>Manual Count of Perfect Days:</strong> $actual_perfect_days</p>\n";
    
    if ($actual_perfect_days != $perfect_days_count) {
        echo "<p style='color: red;'><strong>⚠️ Mismatch detected!</strong> The calculatePerfectDays method returns $perfect_days_count but manual count shows $actual_perfect_days</p>\n";
    }
    
    echo "<hr>\n";
    
    // Show habits creation timeline
    echo "<h3>📅 Habits Creation Timeline</h3>\n";
    
    $sql = "SELECT name, created_at FROM habits WHERE user_id = ? ORDER BY created_at";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Habit Name</th><th>Created Date</th></tr>\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<hr>\n";
    
    // Show explanation
    echo "<h3>❓ What Makes a Perfect Day?</h3>\n";
    echo "<p>A <strong>Perfect Day</strong> is when you complete ALL the habits that existed on that specific date.</p>\n";
    echo "<p><strong>Logic:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>For each completion date, count how many habits you completed</li>\n";
    echo "<li>Count how many total habits existed on or before that date</li>\n";
    echo "<li>If completed_habits = total_habits_that_day AND total_habits > 0, it's a perfect day</li>\n";
    echo "</ul>\n";
    
    if ($perfect_days_count > 0) {
        echo "<p>✅ <strong>You have $perfect_days_count perfect days!</strong> This means on $perfect_days_count different days, you completed every single habit that existed at that time.</p>\n";
    } else {
        echo "<p>❌ You don't have any perfect days yet. To get a perfect day, complete all your habits on the same day.</p>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>