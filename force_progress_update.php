<?php
// Force update progress for all achievements
require_once 'config/database.php';
require_once 'includes/achievement_manager.php';

try {
    $user_id = 1; // Change if needed
    
    echo "<h2>Force Update All Achievement Progress</h2>\n";
    
    $achievement_manager = new AchievementManager();
    
    // Update all progress
    $result = $achievement_manager->updateAllProgress($user_id);
    echo "<p>All progress update: " . ($result ? "✅ Success" : "❌ Failed") . "</p>\n";
    
    // Manually update the missing progress values
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get habit count
    $sql = "SELECT COUNT(*) FROM habits WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $habit_count = $stmt->fetchColumn();
    
    echo "<p><strong>Current Habit Count:</strong> $habit_count</p>\n";
    
    // Update specific achievements that are showing NULL
    $achievements_to_update = ['habit_architect', 'habit_tycoon'];
    
    foreach ($achievements_to_update as $achievement_key) {
        $result = $achievement_manager->updateProgress($user_id, $achievement_key, $habit_count);
        echo "<p>Updated $achievement_key: " . ($result ? "✅ Success" : "❌ Failed") . "</p>\n";
    }
    
    // Show updated results
    echo "<h3>Updated Progress Values</h3>\n";
    
    $sql = "
        SELECT 
            a.achievement_key,
            a.name,
            a.requirement_value,
            ua.current_progress,
            ua.is_earned
        FROM achievements a 
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        WHERE a.achievement_key IN ('getting_started', 'habit_builder', 'habit_architect', 'habit_tycoon')
        ORDER BY a.requirement_value
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Achievement</th><th>Required</th><th>Progress</th><th>Status</th></tr>\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['is_earned'] ? '🏆 Earned' : '⏳ In Progress';
        $progress = $row['current_progress'] ?? 0;
        $required = $row['requirement_value'];
        
        echo "<tr>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $required . "</td>";
        echo "<td><strong>" . $progress . "/" . $required . "</strong></td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<br><p>Now check the <a href='pages/dashboard/achievements.html'>Achievements Page</a> to see if the progress displays correctly!</p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>