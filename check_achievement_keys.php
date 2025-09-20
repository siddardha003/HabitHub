<?php
// Check achievement keys in database
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Achievement Keys in Database</h2>\n";
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>ID</th><th>Achievement Key</th><th>Title</th><th>Type</th><th>Required</th></tr>\n";
    
    $sql = "SELECT id, achievement_key, title, type, required_value FROM achievements ORDER BY id";
    $stmt = $pdo->query($sql);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><strong>" . $row['achievement_key'] . "</strong></td>";
        echo "<td>" . $row['title'] . "</td>";
        echo "<td>" . $row['type'] . "</td>";
        echo "<td>" . $row['required_value'] . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // Also test getAchievementId method
    echo "<h2>Testing getAchievementId Method</h2>\n";
    
    class TestAchievementManager {
        private $pdo;
        
        public function __construct() {
            $database = new Database();
            $this->pdo = $database->getConnection();
        }
        
        public function testGetAchievementId($achievement_key) {
            $sql = "SELECT id FROM achievements WHERE achievement_key = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$achievement_key]);
            return $stmt->fetchColumn();
        }
    }
    
    $test = new TestAchievementManager();
    
    $test_keys = ['login_streak_3', 'login_streak_7', 'habit_creation_1', 'habit_completion_10'];
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Test Key</th><th>Returns ID</th><th>Status</th></tr>\n";
    
    foreach ($test_keys as $key) {
        $id = $test->testGetAchievementId($key);
        echo "<tr>";
        echo "<td>" . $key . "</td>";
        echo "<td>" . ($id ?: 'NULL') . "</td>";
        echo "<td>" . ($id ? '✅ Found' : '❌ Not Found') . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>