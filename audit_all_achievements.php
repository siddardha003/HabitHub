<?php
require_once 'config/database.php';

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

echo "<h1>🏆 Complete Achievement System Audit</h1>\n";
echo "<p>Ensuring ALL 32 achievements have proper logic...</p>\n";

$user_id = 1;

// Get all achievements from database
echo "<h2>📋 All 32 Achievements in Database</h2>\n";
$sql = "SELECT id, key_name, name, description, category, requirement FROM achievements ORDER BY category, requirement";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$all_achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>\n";
echo "<tr><th>ID</th><th>Key</th><th>Name</th><th>Category</th><th>Requirement</th><th>Description</th></tr>\n";

$achievement_keys = [];
$categories = [];

foreach ($all_achievements as $achievement) {
    $achievement_keys[] = $achievement['key_name'];
    $categories[$achievement['category']][] = $achievement;
    
    echo "<tr>";
    echo "<td>" . $achievement['id'] . "</td>";
    echo "<td><strong>" . $achievement['key_name'] . "</strong></td>";
    echo "<td>" . $achievement['name'] . "</td>";
    echo "<td>" . $achievement['category'] . "</td>";
    echo "<td>" . $achievement['requirement'] . "</td>";
    echo "<td><small>" . $achievement['description'] . "</small></td>";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h2>📊 Achievements by Category</h2>\n";
foreach ($categories as $category => $achievements) {
    echo "<h3>$category (" . count($achievements) . " achievements)</h3>\n";
    echo "<ul>\n";
    foreach ($achievements as $achievement) {
        echo "<li><strong>" . $achievement['key_name'] . "</strong> - " . $achievement['name'] . " (Req: " . $achievement['requirement'] . ")</li>\n";
    }
    echo "</ul>\n";
}

echo "<h2>🔍 Achievement Logic Analysis</h2>\n";

// Check which achievements have logic in achievement_manager.php
$achievement_manager_file = file_get_contents('includes/achievement_manager.php');

echo "<h3>✅ Achievements with Existing Logic</h3>\n";
echo "<ul>\n";

$achievements_with_logic = [];
$achievements_without_logic = [];

foreach ($achievement_keys as $key) {
    // Check if there's logic for this achievement
    $has_logic = false;
    
    // Check for specific method calls or key references
    if (strpos($achievement_manager_file, "'" . $key . "'") !== false || 
        strpos($achievement_manager_file, '"' . $key . '"') !== false ||
        strpos($achievement_manager_file, $key) !== false) {
        $has_logic = true;
        $achievements_with_logic[] = $key;
        echo "<li style='color: green;'>✅ <strong>$key</strong></li>\n";
    } else {
        $achievements_without_logic[] = $key;
    }
}
echo "</ul>\n";

echo "<h3>❌ Achievements WITHOUT Logic</h3>\n";
if (!empty($achievements_without_logic)) {
    echo "<ul>\n";
    foreach ($achievements_without_logic as $key) {
        echo "<li style='color: red;'>❌ <strong>$key</strong> - MISSING LOGIC!</li>\n";
    }
    echo "</ul>\n";
} else {
    echo "<p style='color: green;'>🎉 All achievements have logic!</p>\n";
}

echo "<h2>📈 Summary</h2>\n";
echo "<p><strong>Total Achievements:</strong> " . count($all_achievements) . "</p>\n";
echo "<p><strong>With Logic:</strong> " . count($achievements_with_logic) . "</p>\n";
echo "<p><strong>Without Logic:</strong> " . count($achievements_without_logic) . "</p>\n";

if (count($achievements_without_logic) > 0) {
    echo "<p style='color: red; font-weight: bold;'>⚠️ " . count($achievements_without_logic) . " achievements need logic implementation!</p>\n";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ All achievements have logic!</p>\n";
}

// Show the specific categories that need attention
if (!empty($achievements_without_logic)) {
    echo "<h3>🎯 Missing Logic by Category</h3>\n";
    foreach ($categories as $category => $achievements) {
        $missing_in_category = [];
        foreach ($achievements as $achievement) {
            if (in_array($achievement['key_name'], $achievements_without_logic)) {
                $missing_in_category[] = $achievement;
            }
        }
        
        if (!empty($missing_in_category)) {
            echo "<h4>$category</h4>\n";
            echo "<ul>\n";
            foreach ($missing_in_category as $achievement) {
                echo "<li><strong>" . $achievement['key_name'] . "</strong> - " . $achievement['name'] . " (Req: " . $achievement['requirement'] . ")</li>\n";
            }
            echo "</ul>\n";
        }
    }
}

?>