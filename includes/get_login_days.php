<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // First, try to check if the table exists
    $checkTableStmt = $pdo->prepare("SHOW TABLES LIKE 'user_login_days'");
    $checkTableStmt->execute();
    $tableExists = $checkTableStmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the table if it doesn't exist
        $createTableSQL = "CREATE TABLE user_login_days (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            login_date DATE NOT NULL,
            UNIQUE KEY unique_user_date (user_id, login_date),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $pdo->exec($createTableSQL);
        
        // Insert current date as first login day for this user
        $insertStmt = $pdo->prepare("INSERT IGNORE INTO user_login_days (user_id, login_date) VALUES (?, CURDATE())");
        $insertStmt->execute([$user_id]);
    }
    
    // Count unique login days for this user
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_days FROM user_login_days WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    
    $active_days = $result['active_days'] ?? 0;
    
    // If this is a new day, add it to login days
    $todayStmt = $pdo->prepare("INSERT IGNORE INTO user_login_days (user_id, login_date) VALUES (?, CURDATE())");
    $todayStmt->execute([$user_id]);
    
    // Get updated count
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_days FROM user_login_days WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $active_days = $result['active_days'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'active_days' => $active_days
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_login_days.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'active_days' => 0
    ]);
}
?>