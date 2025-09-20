<?php
// Trigger habit creation achievements manually
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Please login first";
    exit;
}

require_once 'includes/achievement_manager.php';

$user_id = $_SESSION['user_id'];
$achievement_manager = new AchievementManager();

echo "<h2>Triggering Habit Creation Achievements</h2>\n";

try {
    // Check and award habit creation achievements
    $awarded = $achievement_manager->checkAchievements($user_id, 'habit_created');
    
    if (!empty($awarded)) {
        echo "<p><strong>✅ Achievements Awarded:</strong></p>\n";
        echo "<ul>\n";
        foreach ($awarded as $achievement) {
            echo "<li>🏆 $achievement</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p>❌ No new achievements awarded</p>\n";
    }
    
    // Update all progress
    $result = $achievement_manager->updateAllProgress($user_id);
    echo "<p>Progress update: " . ($result ? "✅ Success" : "❌ Failed") . "</p>\n";
    
    echo "<br><a href='pages/dashboard/achievements.html'>View Achievements Page</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>