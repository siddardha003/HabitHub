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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['habitId'])) {
    echo json_encode(['success' => false, 'message' => 'Habit ID is required']);
    exit;
}

$habitId = (int)$input['habitId'];
$userId = $_SESSION['user_id'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // First, verify that the habit belongs to the current user
    $checkStmt = $pdo->prepare("SELECT id FROM habits WHERE id = ? AND user_id = ?");
    $checkStmt->execute([$habitId, $userId]);
    
    if (!$checkStmt->fetch()) {
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => 'Habit not found or access denied']);
        exit;
    }
    
    // Delete related habit completions first (to maintain referential integrity)
    $deleteCompletionsStmt = $pdo->prepare("DELETE FROM habit_completions WHERE habit_id = ?");
    $deleteCompletionsStmt->execute([$habitId]);
    
    // Delete the habit
    $deleteHabitStmt = $pdo->prepare("DELETE FROM habits WHERE id = ? AND user_id = ?");
    $deleteHabitStmt->execute([$habitId, $userId]);
    
    if ($deleteHabitStmt->rowCount() > 0) {
        // Commit transaction
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Habit deleted successfully']);
    } else {
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete habit']);
    }
    
} catch (PDOException $e) {
    $pdo->rollback();
    error_log("Delete habit error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>