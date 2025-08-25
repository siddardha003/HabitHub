<?php
// Temporary script to refresh streak calculation
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Please log in first";
    exit;
}

require_once 'config/database.php';

$userId = $_SESSION['user_id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h3>Analyzing streak data...</h3>";
    
    // Get all habits with creation dates
    $habitsQuery = "SELECT id, name, created_at FROM habits WHERE user_id = ? ORDER BY created_at";
    $habitsStmt = $conn->prepare($habitsQuery);
    $habitsStmt->execute([$userId]);
    $habits = $habitsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Your Habits:</h4>";
    foreach ($habits as $habit) {
        echo "<p>- {$habit['name']} (created: {$habit['created_at']})</p>";
    }
    
    // Get completions for the last 10 days
    $startDate = date('Y-m-d', strtotime('-10 days'));
    $endDate = date('Y-m-d');
    
    $completionsQuery = "SELECT habit_id, completion_date FROM habit_completions hc 
                        INNER JOIN habits h ON hc.habit_id = h.id 
                        WHERE h.user_id = ? AND completion_date BETWEEN ? AND ?
                        ORDER BY completion_date DESC";
    $completionsStmt = $conn->prepare($completionsQuery);
    $completionsStmt->execute([$userId, $startDate, $endDate]);
    $completions = $completionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group completions by date
    $completionsByDate = [];
    foreach ($completions as $completion) {
        $date = $completion['completion_date'];
        if (!isset($completionsByDate[$date])) {
            $completionsByDate[$date] = [];
        }
        $completionsByDate[$date][] = $completion['habit_id'];
    }
    
    echo "<h4>Perfect Days Analysis:</h4>";
    
    // Check each day for perfect completion
    $perfectDays = [];
    for ($i = 0; $i <= 10; $i++) {
        $checkDate = date('Y-m-d', strtotime("-$i days"));
        $dateTimestamp = strtotime($checkDate . ' 23:59:59');
        
        // Count habits that existed on this date
        $existingHabits = [];
        foreach ($habits as $habit) {
            if (strtotime($habit['created_at']) <= $dateTimestamp) {
                $existingHabits[] = $habit['id'];
            }
        }
        
        $completedHabits = $completionsByDate[$checkDate] ?? [];
        $isPerfect = count($existingHabits) > 0 && count($completedHabits) === count($existingHabits);
        
        if ($isPerfect) {
            $perfectDays[] = $checkDate;
        }
        
        echo "<p>$checkDate: " . count($existingHabits) . " habits existed, " . count($completedHabits) . " completed " . ($isPerfect ? "✅ PERFECT" : "❌") . "</p>";
    }
    
    // Calculate current streak
    $currentStreak = 0;
    $today = date('Y-m-d');
    
    for ($i = 0; $i <= 10; $i++) {
        $checkDate = date('Y-m-d', strtotime("-$i days"));
        if (in_array($checkDate, $perfectDays)) {
            $currentStreak++;
        } else {
            break;
        }
    }
    
    echo "<h4>Streak Calculation:</h4>";
    echo "<p>Perfect days: " . implode(', ', $perfectDays) . "</p>";
    echo "<p><strong>Current streak should be: $currentStreak</strong></p>";
    
    // Update the database
    $updateQuery = "INSERT INTO user_streaks (user_id, current_streak, best_streak, last_completion_date) 
                   VALUES (?, ?, ?, ?) 
                   ON DUPLICATE KEY UPDATE 
                   current_streak = VALUES(current_streak),
                   best_streak = GREATEST(best_streak, VALUES(best_streak)),
                   last_completion_date = VALUES(last_completion_date)";
    
    $lastPerfectDay = !empty($perfectDays) ? $perfectDays[0] : null;
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->execute([$userId, $currentStreak, $currentStreak, $lastPerfectDay]);
    
    echo "<p><strong>✅ Streak updated in database!</strong></p>";
    echo "<p><a href='pages/dashboard/dashboard.html'>Back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>