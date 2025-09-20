<?php
// Check all achievement keys in database
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>All Achievement Keys in Database</h2>\n";
    
    $sql = "
        SELECT 
            achievement_key, 
            name, 
            requirement_type, 
            requirement_value,
            ac.display_name as category
        FROM achievements a
        LEFT JOIN achievement_categories ac ON a.category_id = ac.id
        WHERE a.is_active = TRUE
        ORDER BY ac.display_name, a.requirement_value
    ";
    
    $stmt = $pdo->query($sql);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Category</th><th>Achievement Key</th><th>Name</th><th>Type</th><th>Required</th></tr>\n";
    
    $by_category = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $category = $row['category'] ?: 'Unknown';
        $by_category[$category][] = $row;
        
        echo "<tr>";
        echo "<td>" . $category . "</td>";
        echo "<td><strong>" . $row['achievement_key'] . "</strong></td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['requirement_type'] . "</td>";
        echo "<td>" . $row['requirement_value'] . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // Show organized by category for easier reading
    echo "<h3>Organized by Category</h3>\n";
    foreach ($by_category as $category => $achievements) {
        echo "<h4>$category</h4>\n";
        echo "<ul>\n";
        foreach ($achievements as $achievement) {
            echo "<li><code>" . $achievement['achievement_key'] . "</code> - " . $achievement['name'] . " (" . $achievement['requirement_value'] . ")</li>\n";
        }
        echo "</ul>\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>