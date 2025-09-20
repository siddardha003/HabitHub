<?php
// Database verification script
try {
    require_once 'config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>🔍 Database Structure Verification</h2>";
    
    // Check if achievement tables exist
    $tables_to_check = [
        'achievements',
        'achievement_categories', 
        'user_achievements',
        'user_login_days',
        'habits',
        'habit_completions',
        'habit_streaks',
        'user_streaks'
    ];
    
    echo "<h3>📋 Table Existence Check</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Table Name</th><th>Exists</th><th>Row Count</th></tr>";
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "<tr><td>$table</td><td style='color: green;'>✅ Yes</td><td>$count</td></tr>";
        } catch (PDOException $e) {
            echo "<tr><td>$table</td><td style='color: red;'>❌ No</td><td>-</td></tr>";
        }
    }
    echo "</table>";
    
    // Check if we have achievement data
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM achievements");
        $achievement_count = $stmt->fetchColumn();
        
        if ($achievement_count == 0) {
            echo "<h3>⚠️ Missing Achievement Data</h3>";
            echo "<p>No achievements found in database. You may need to populate the achievements table.</p>";
            echo "<p><a href='database_fix.sql' target='_blank'>Check database_fix.sql for achievement data</a></p>";
        } else {
            echo "<h3>✅ Achievement Data Found</h3>";
            echo "<p>Found $achievement_count achievements in database.</p>";
            
            // Show sample achievements
            $stmt = $pdo->query("SELECT achievement_key, name, requirement_type, requirement_value FROM achievements LIMIT 5");
            $sample_achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>📝 Sample Achievements:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Key</th><th>Name</th><th>Type</th><th>Requirement</th></tr>";
            
            foreach ($sample_achievements as $achievement) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($achievement['achievement_key']) . "</td>";
                echo "<td>" . htmlspecialchars($achievement['name']) . "</td>";
                echo "<td>" . htmlspecialchars($achievement['requirement_type']) . "</td>";
                echo "<td>" . htmlspecialchars($achievement['requirement_value']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<h3>❌ Error checking achievements</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Database Connection Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
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
</style>