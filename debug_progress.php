<?php
// Debug current progress values in database
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $user_id = 1; // Change if needed
    
    echo "<h2>Debug User Achievement Progress for User ID: $user_id</h2>\n";
    
    // Check current progress in user_achievements table
    $sql = "
        SELECT 
            a.achievement_key,
            a.name,
            a.requirement_value,
            ua.current_progress,
            ua.is_earned,
            ua.earned_at
        FROM achievements a 
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        WHERE a.achievement_key IN ('getting_started', 'habit_builder', 'habit_architect', 'habit_tycoon')
        ORDER BY a.requirement_value
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Achievement Key</th><th>Name</th><th>Required</th><th>Current Progress</th><th>Earned</th><th>Earned At</th></tr>\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['achievement_key'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['requirement_value'] . "</td>";
        echo "<td>" . ($row['current_progress'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['is_earned'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($row['earned_at'] ?? 'Not earned') . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // Check actual habit count
    echo "<h3>Actual User Data</h3>\n";
    
    $sql = "SELECT COUNT(*) as habit_count FROM habits WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $habit_count = $stmt->fetchColumn();
    
    echo "<p><strong>Actual Habits Created:</strong> $habit_count</p>\n";
    
    // Check if achievement IDs exist
    echo "<h3>Achievement ID Check</h3>\n";
    
    $test_keys = ['getting_started', 'habit_builder'];
    foreach ($test_keys as $key) {
        $sql = "SELECT id, name FROM achievements WHERE achievement_key = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        echo "<p><strong>$key:</strong> ";
        if ($result) {
            echo "ID=" . $result['id'] . ", Name=" . $result['name'];
        } else {
            echo "❌ NOT FOUND";
        }
        echo "</p>\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>