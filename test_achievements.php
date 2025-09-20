<?php
// Test Achievement System
session_start();

echo "<h2>🏆 Achievement System Test</h2>";

// Test database connection first
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration.</p>";
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>ℹ️ No user session found. Please log in first to test achievements.</p>";
    echo "<p><a href='pages/auth/signin.html'>Login here</a></p>";
    echo "<hr>";
    echo "<h3>🧪 Test Database Connection Only</h3>";
    echo "<p><a href='includes/test_api.php' target='_blank'>Test API Connection</a></p>";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<p>Testing achievements for User ID: $user_id</p>";

// Test achievement manager loading
try {
    require_once 'includes/achievement_manager.php';
    $achievement_manager = new AchievementManager();
    echo "<p>✅ Achievement Manager loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Achievement Manager failed to load: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

try {
    echo "<h3>📊 Current Progress Update</h3>";
    $update_result = $achievement_manager->updateAllProgress($user_id);
    echo $update_result ? "✅ Progress updated successfully<br>" : "❌ Failed to update progress<br>";
    
    echo "<h3>🔐 Testing Login Achievement Check</h3>";
    $login_achievements = $achievement_manager->checkAchievements($user_id, 'login');
    if (!empty($login_achievements)) {
        echo "🎉 New login achievements unlocked: " . implode(', ', $login_achievements) . "<br>";
    } else {
        echo "ℹ️ No new login achievements unlocked<br>";
    }
    
    echo "<h3>🎯 Testing Habit Creation Achievement Check</h3>";
    $creation_achievements = $achievement_manager->checkAchievements($user_id, 'habit_created');
    if (!empty($creation_achievements)) {
        echo "🎉 New habit creation achievements unlocked: " . implode(', ', $creation_achievements) . "<br>";
    } else {
        echo "ℹ️ No new habit creation achievements unlocked<br>";
    }
    
    echo "<h3>✅ Testing Habit Completion Achievement Check</h3>";
    $completion_achievements = $achievement_manager->checkAchievements($user_id, 'habit_completed', [
        'habit_id' => 1, // Test with first habit
        'category' => 'health',
        'completion_time' => date('Y-m-d H:i:s')
    ]);
    if (!empty($completion_achievements)) {
        echo "🎉 New completion achievements unlocked: " . implode(', ', $completion_achievements) . "<br>";
    } else {
        echo "ℹ️ No new completion achievements unlocked<br>";
    }
    
    echo "<h3>📈 Database Statistics</h3>";
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get user stats
    $stats = [];
    
    // Login days
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_login_days WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['login_days'] = $stmt->fetchColumn();
    
    // Total habits
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM habits WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['total_habits'] = $stmt->fetchColumn();
    
    // Total completions
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM habit_completions hc 
        JOIN habits h ON hc.habit_id = h.id 
        WHERE h.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats['total_completions'] = $stmt->fetchColumn();
    
    // Max habit streak
    $stmt = $pdo->prepare("
        SELECT MAX(hs.streak) 
        FROM habit_streaks hs 
        JOIN habits h ON hs.habit_id = h.id 
        WHERE h.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats['max_habit_streak'] = $stmt->fetchColumn() ?: 0;
    
    // Current login streak
    $stmt = $pdo->prepare("SELECT current_streak FROM user_streaks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['current_login_streak'] = $stmt->fetchColumn() ?: 0;
    
    foreach ($stats as $key => $value) {
        echo "📊 " . ucwords(str_replace('_', ' ', $key)) . ": $value<br>";
    }
    
    echo "<h3>🏅 Current Achievements</h3>";
    $stmt = $pdo->prepare("
        SELECT a.name, a.achievement_key, ua.is_earned, ua.current_progress, a.requirement_value
        FROM achievements a
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        ORDER BY a.category_id, a.requirement_value
    ");
    $stmt->execute([$user_id]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Achievement</th><th>Key</th><th>Status</th><th>Progress</th></tr>";
    
    foreach ($achievements as $achievement) {
        $status = $achievement['is_earned'] ? '🏆 Earned' : '⏳ In Progress';
        $progress = ($achievement['current_progress'] ?? 0) . '/' . $achievement['requirement_value'];
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($achievement['name']) . "</td>";
        echo "<td>" . htmlspecialchars($achievement['achievement_key']) . "</td>";
        echo "<td>$status</td>";
        echo "<td>$progress</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🧪 Test API Endpoint</h3>";
    echo "<p><a href='includes/check_achievements.php?action_type=login' target='_blank'>Test Login Achievement API</a></p>";
    echo "<p><a href='includes/check_achievements.php?action_type=habit_created' target='_blank'>Test Habit Creation Achievement API</a></p>";
    
    echo "<h3>✅ Test Complete</h3>";
    echo "<p>Achievement system is set up and ready to use! 🎉</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error</h3>";
    echo "<p>Error testing achievements: " . htmlspecialchars($e->getMessage()) . "</p>";
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

h2, h3 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
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

a {
    color: #007bff;
    text-decoration: none;
    padding: 8px 16px;
    background: white;
    border: 1px solid #007bff;
    border-radius: 4px;
    display: inline-block;
    margin: 5px;
}

a:hover {
    background: #007bff;
    color: white;
}
</style>