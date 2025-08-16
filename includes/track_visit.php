<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'habithub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

try {
    // Create user_visits table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS user_visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            visit_date DATE NOT NULL,
            visit_count INT DEFAULT 1,
            first_visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_date (user_id, visit_date),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableSQL);

    // Insert or update today's visit
    $visitStmt = $pdo->prepare("
        INSERT INTO user_visits (user_id, visit_date, visit_count, first_visit_time, last_visit_time) 
        VALUES (?, ?, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
            visit_count = visit_count + 1,
            last_visit_time = NOW()
    ");
    $visitStmt->execute([$userId, $today]);

    echo json_encode([
        'success' => true,
        'message' => 'Visit tracked successfully',
        'date' => $today
    ]);

} catch (PDOException $e) {
    error_log("Visit tracking error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to track visit',
        'debug' => $e->getMessage()
    ]);
}
?>