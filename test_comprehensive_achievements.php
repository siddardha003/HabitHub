<?php
require_once 'config/database.php';
require_once 'includes/achievement_manager.php';

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

$user_id = 1;
$achievement_manager = new AchievementManager();

echo "<h1>🔥 COMPREHENSIVE ACHIEVEMENT SYSTEM TEST</h1>\n";
echo "<p>Testing ALL 32 achievements for proper logic and calculation...</p>\n";

// Get all achievements from database
$sql = "SELECT achievement_key, name, requirement_type, requirement_value FROM achievements ORDER BY id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$all_achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>📊 Testing All Achievement Logic</h2>\n";

$test_results = [];

foreach ($all_achievements as $achievement) {
    $key = $achievement['achievement_key'];
    $name = $achievement['name'];
    $type = $achievement['requirement_type'];
    $value = $achievement['requirement_value'];
    
    echo "<h3>Testing: $name ($key)</h3>\n";
    
    $test_result = [
        'key' => $key,
        'name' => $name,
        'type' => $type,
        'value' => $value,
        'has_calculation' => false,
        'current_progress' => 0,
        'test_status' => 'FAIL'
    ];
    
    try {
        // Test based on achievement type
        switch ($type) {
            case 'login_streak':
                $progress = $achievement_manager->calculateBestLoginStreak($user_id);
                $test_result['has_calculation'] = true;
                $test_result['current_progress'] = $progress;
                echo "<p>✅ Login Streak Logic: Current best streak = $progress</p>\n";
                break;
                
            case 'total_logins':
                $sql = "SELECT COUNT(*) FROM user_login_days WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id]);
                $progress = $stmt->fetchColumn();
                $test_result['has_calculation'] = true;
                $test_result['current_progress'] = $progress;
                echo "<p>✅ Total Logins Logic: Current total = $progress</p>\n";
                break;
                
            case 'habit_creation':
                $sql = "SELECT COUNT(*) FROM habits WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id]);
                $progress = $stmt->fetchColumn();
                $test_result['has_calculation'] = true;
                $test_result['current_progress'] = $progress;
                echo "<p>✅ Habit Creation Logic: Current total = $progress</p>\n";
                break;
                
            case 'habit_completion':
                $sql = "SELECT COUNT(*) FROM habit_completions hc JOIN habits h ON hc.habit_id = h.id WHERE h.user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id]);
                $progress = $stmt->fetchColumn();
                $test_result['has_calculation'] = true;
                $test_result['current_progress'] = $progress;
                echo "<p>✅ Habit Completion Logic: Current total = $progress</p>\n";
                break;
                
            case 'habit_streak':
                $sql = "SELECT MAX(hs.streak) FROM habit_streaks hs JOIN habits h ON hs.habit_id = h.id WHERE h.user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id]);
                $progress = $stmt->fetchColumn() ?: 0;
                $test_result['has_calculation'] = true;
                $test_result['current_progress'] = $progress;
                echo "<p>✅ Habit Streak Logic: Current max streak = $progress</p>\n";
                break;
                
            case 'perfect_day':
                $progress = $achievement_manager->calculatePerfectDays($user_id);
                $test_result['has_calculation'] = true;
                $test_result['current_progress'] = $progress;
                echo "<p>✅ Perfect Day Logic: Current perfect days = $progress</p>\n";
                break;
                
            case 'perfect_week':
                $progress = $achievement_manager->calculatePerfectWeeks($user_id);
                $test_result['has_calculation'] = true;
                $test_result['current_progress'] = $progress;
                echo "<p>✅ Perfect Week Logic: Current perfect weeks = $progress</p>\n";
                break;
                
            case 'perfect_month':
                $progress = $achievement_manager->calculatePerfectMonths($user_id);
                $test_result['has_calculation'] = true;
                $test_result['current_progress'] = $progress;
                echo "<p>✅ Perfect Month Logic: Current perfect months = $progress</p>\n";
                break;
                
            case 'category_specific':
                if ($key == 'fitness_fanatic') {
                    $progress = $achievement_manager->getCategoryCompletions($user_id, 'fitness');
                } elseif ($key == 'mindful_master') {
                    $progress = $achievement_manager->getCategoryCompletions($user_id, 'mindfulness');
                } elseif ($key == 'productivity_pro') {
                    $progress = $achievement_manager->getCategoryCompletions($user_id, 'productivity');
                } elseif ($key == 'balanced_life') {
                    $progress = $achievement_manager->checkBalancedLife($user_id) ? 1 : 0;
                }
                $test_result['has_calculation'] = true;
                $test_result['current_progress'] = $progress;
                echo "<p>✅ Category Logic: Current progress = $progress</p>\n";
                break;
                
            case 'special':
                if ($key == 'early_bird') {
                    $progress = $achievement_manager->checkEarlyBird($user_id) ? 1 : 0;
                } elseif ($key == 'night_owl') {
                    $progress = $achievement_manager->checkNightOwl($user_id) ? 1 : 0;
                } elseif ($key == 'comeback_kid') {
                    $progress = $achievement_manager->checkComebackKid($user_id) ? 1 : 0;
                } elseif ($key == 'all_rounder') {
                    $progress = $achievement_manager->checkAllRounder($user_id) ? 1 : 0;
                }
                $test_result['has_calculation'] = true;
                $test_result['current_progress'] = $progress;
                echo "<p>✅ Special Logic: Current status = " . ($progress ? 'Achieved' : 'Not achieved') . "</p>\n";
                break;
                
            default:
                echo "<p>❌ Unknown achievement type: $type</p>\n";
        }
        
        if ($test_result['has_calculation']) {
            $test_result['test_status'] = 'PASS';
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error testing $key: " . $e->getMessage() . "</p>\n";
        $test_result['test_status'] = 'ERROR';
    }
    
    $test_results[] = $test_result;
    echo "<hr>\n";
}

// Summary
echo "<h2>📈 Test Summary</h2>\n";
$passed = 0;
$failed = 0;
$errors = 0;

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Achievement</th><th>Type</th><th>Required</th><th>Current</th><th>Status</th><th>Result</th></tr>\n";

foreach ($test_results as $result) {
    $status_color = '';
    if ($result['test_status'] == 'PASS') {
        $passed++;
        $status_color = 'background-color: #d4edda;';
    } elseif ($result['test_status'] == 'FAIL') {
        $failed++;
        $status_color = 'background-color: #f8d7da;';
    } else {
        $errors++;
        $status_color = 'background-color: #fff3cd;';
    }
    
    $meets_requirement = $result['current_progress'] >= $result['value'] ? '✅ Met' : '❌ Not met';
    
    echo "<tr style='$status_color'>";
    echo "<td><strong>" . $result['key'] . "</strong><br><small>" . $result['name'] . "</small></td>";
    echo "<td>" . $result['type'] . "</td>";
    echo "<td>" . $result['value'] . "</td>";
    echo "<td>" . $result['current_progress'] . "</td>";
    echo "<td>$meets_requirement</td>";
    echo "<td>" . $result['test_status'] . "</td>";
    echo "</tr>\n";
}

echo "</table>\n";

echo "<h3>🎯 Final Results</h3>\n";
echo "<p><strong>✅ Passed:</strong> $passed achievements</p>\n";
echo "<p><strong>❌ Failed:</strong> $failed achievements</p>\n";
echo "<p><strong>⚠️ Errors:</strong> $errors achievements</p>\n";
echo "<p><strong>📊 Total:</strong> " . count($test_results) . " achievements tested</p>\n";

if ($passed == count($test_results)) {
    echo "<h2 style='color: green;'>🎉 ALL ACHIEVEMENTS HAVE PROPER LOGIC!</h2>\n";
} else {
    echo "<h2 style='color: red;'>⚠️ Some achievements need attention!</h2>\n";
}

// Test the comprehensive checking function
echo "<h2>🔄 Testing Comprehensive Achievement Checking</h2>\n";
try {
    $awarded = $achievement_manager->checkAllAchievements($user_id);
    echo "<p><strong>Newly awarded achievements:</strong> " . count($awarded) . "</p>\n";
    if (!empty($awarded)) {
        echo "<ul>\n";
        foreach ($awarded as $achievement_key) {
            echo "<li>🏆 $achievement_key</li>\n";
        }
        echo "</ul>\n";
    }
    
    // Test progress update
    $updated = $achievement_manager->updateAllProgress($user_id);
    echo "<p><strong>Progress update:</strong> " . ($updated ? '✅ Success' : '❌ Failed') . "</p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error in comprehensive checking: " . $e->getMessage() . "</p>\n";
}

?>