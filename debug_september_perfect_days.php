<?php
require_once 'config/database.php';

$user_id = 1;

echo "<h2>🔍 September Perfect Days Investigation</h2>\n";

// Check all habit completions for September 2025
$sql = "
    SELECT 
        hc.completion_date,
        COUNT(DISTINCT hc.habit_id) as completed_habits,
        GROUP_CONCAT(DISTINCT h.name SEPARATOR ', ') as completed_habit_names
    FROM habit_completions hc
    JOIN habits h ON hc.habit_id = h.id
    WHERE h.user_id = ? 
    AND hc.completion_date >= '2025-09-01' 
    AND hc.completion_date <= '2025-09-30'
    GROUP BY hc.completion_date
    ORDER BY hc.completion_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

echo "<h3>📅 September 2025 Habit Completions</h3>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Date</th><th>Completed Habits</th><th>Habit Names</th></tr>\n";

$september_data = [];
while ($row = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
    foreach ($row as $r) {
        $september_data[$r['completion_date']] = $r;
        echo "<tr>";
        echo "<td>" . $r['completion_date'] . "</td>";
        echo "<td>" . $r['completed_habits'] . "</td>";
        echo "<td>" . $r['completed_habit_names'] . "</td>";
        echo "</tr>\n";
    }
}
echo "</table>\n";

// Check specifically for the dates you mentioned
$target_dates = ['2025-09-17', '2025-09-23', '2025-09-24', '2025-09-25'];

echo "<h3>🎯 Checking Your Perfect Day Dates</h3>\n";
foreach ($target_dates as $date) {
    echo "<h4>Date: $date</h4>\n";
    
    // Check habit completions for this date
    $sql = "
        SELECT h.name, hc.completion_date
        FROM habit_completions hc
        JOIN habits h ON hc.habit_id = h.id
        WHERE h.user_id = ? AND hc.completion_date = ?
        ORDER BY h.name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $date]);
    $completions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check total habits that existed on this date
    $sql = "
        SELECT name
        FROM habits 
        WHERE user_id = ? AND created_at <= ?
        ORDER BY name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $date . ' 23:59:59']);
    $available_habits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Completions found:</strong> " . count($completions) . "</p>\n";
    echo "<p><strong>Available habits:</strong> " . count($available_habits) . "</p>\n";
    
    if (!empty($completions)) {
        echo "<p><strong>Completed:</strong> ";
        foreach ($completions as $comp) {
            echo $comp['name'] . ", ";
        }
        echo "</p>\n";
    }
    
    if (!empty($available_habits)) {
        echo "<p><strong>Available:</strong> ";
        foreach ($available_habits as $habit) {
            echo $habit['name'] . ", ";
        }
        echo "</p>\n";
    }
    
    $is_perfect = count($completions) == count($available_habits) && count($available_habits) > 0;
    echo "<p><strong>Perfect Day?</strong> " . ($is_perfect ? "🌟 YES" : "❌ NO") . "</p>\n";
    echo "<hr>\n";
}

// Check if there are any completion records at all in September
$sql = "SELECT COUNT(*) as total FROM habit_completions hc JOIN habits h ON hc.habit_id = h.id WHERE h.user_id = ? AND hc.completion_date >= '2025-09-01' AND hc.completion_date <= '2025-09-30'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$total_september = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "<h3>📊 September Summary</h3>\n";
echo "<p><strong>Total habit completions in September:</strong> $total_september</p>\n";

?>