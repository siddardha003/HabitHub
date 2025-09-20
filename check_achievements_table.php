<?php
try {
    require_once 'config/database.php';
    
    // Get database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    echo "<h1>🔍 Database Schema Check</h1>\n";
    
    // Check if achievements table exists and its structure
    echo "<h2>Achievements Table Structure</h2>\n";
    $stmt = $pdo->query("DESCRIBE achievements");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($col['Extra'] ?? '') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Get all achievements
    echo "<h2>📋 All Achievements</h2>\n";
    $sql = "SELECT * FROM achievements ORDER BY id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total achievements found:</strong> " . count($achievements) . "</p>\n";
    
    if (!empty($achievements)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>\n";
        echo "<tr>";
        foreach (array_keys($achievements[0]) as $column) {
            echo "<th>$column</th>";
        }
        echo "</tr>\n";
        
        foreach ($achievements as $achievement) {
            echo "<tr>";
            foreach ($achievement as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
} catch (Exception $e) {
    echo "<h1>❌ Error</h1>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Make sure the database is running and the achievements table exists.</p>\n";
}
?>