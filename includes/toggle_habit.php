<?php
// Clean output buffer and disable error display
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config/database.php';
require_once 'update_global_streak.php';

// Clean any previous output
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Get PDO connection
$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['habitId'])) {
        echo json_encode(['success' => false, 'message' => 'Missing habit ID']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $habitId = $data['habitId'];
    $completed = $data['completed'] ?? true;
    $date = $data['date'] ?? date('Y-m-d');
    
    // Verify habit belongs to user
    $verifyQuery = "SELECT id FROM habits WHERE id = ? AND user_id = ?";
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->execute([$habitId, $userId]);
    
    if ($verifyStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Habit not found or unauthorized']);
        exit;
    }
    
    if ($completed) {
        // Add completion record
        $query = "INSERT INTO habit_completions (habit_id, completion_date) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE completion_date = completion_date";
    } else {
        // Remove completion record
        $query = "DELETE FROM habit_completions WHERE habit_id = ? AND completion_date = ?";
    }
    
    $stmt = $conn->prepare($query);
    
    if ($stmt->execute([$habitId, $date])) {
        // Calculate current streak manually (simpler and more reliable)
        $currentStreak = 0;
        $checkDate = $date;
        
        // Check consecutive days backwards from the given date
        for ($i = 0; $i < 365; $i++) { // Max 365 days to prevent infinite loop
            $checkQuery = "SELECT COUNT(*) as count FROM habit_completions WHERE habit_id = ? AND completion_date = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->execute([$habitId, $checkDate]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $currentStreak++;
                $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
            } else {
                break;
            }
        }
        
        // Update or insert streak record
        $updateStreakQuery = "INSERT INTO habit_streaks (habit_id, streak, last_completion_date) 
                             VALUES (?, ?, ?) 
                             ON DUPLICATE KEY UPDATE 
                             streak = VALUES(streak), 
                             last_completion_date = VALUES(last_completion_date)";
        $updateStreakStmt = $conn->prepare($updateStreakQuery);
        $updateStreakStmt->execute([$habitId, $currentStreak, $date]);
        
        // Update global streak
        try {
            $globalStreakResult = updateGlobalStreak($userId, $date);
        } catch (Exception $e) {
            error_log("Global streak update failed: " . $e->getMessage());
            $globalStreakResult = ['current_streak' => 0, 'all_habits_completed' => false];
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $completed ? 'Habit marked as completed' : 'Habit marked as incomplete',
            'currentStreak' => $currentStreak,
            'globalStreak' => $globalStreakResult['current_streak'],
            'allHabitsCompleted' => $globalStreakResult['all_habits_completed']
        ]);
    } else {
        throw new Exception('Failed to update habit completion status');
    }
    
} catch (Exception $e) {
    ob_clean(); // Clean any error output
    echo json_encode(['success' => false, 'message' => 'Error updating habit: ' . $e->getMessage()]);
}

$conn = null;
ob_end_flush();
?>
