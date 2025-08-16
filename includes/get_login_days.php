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
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Count unique login days for this user
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