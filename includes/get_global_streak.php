<?php
// Disable error display and enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $userId = $_SESSION['user_id'];
    
    // Get current streak data
    $query = "SELECT current_streak, best_streak, last_completion_date 
              FROM user_streaks WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        // Initialize streak record if it doesn't exist
        $initQuery = "INSERT INTO user_streaks (user_id, current_streak, best_streak) VALUES (?, 0, 0)";
        $initStmt = $conn->prepare($initQuery);
        $initStmt->execute([$userId]);
        
        $result = [
            'current_streak' => 0,
            'best_streak' => 0,
            'last_completion_date' => null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'current_streak' => (int)$result['current_streak'],
        'best_streak' => (int)$result['best_streak'],
        'last_completion_date' => $result['last_completion_date']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn = null;
?>