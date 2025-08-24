<?php
// Disable error display and enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config/database.php';

function updateGlobalStreak($userId, $date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // Get all user's habits with their creation dates
        $habitsQuery = "SELECT id, created_at FROM habits WHERE user_id = ?";
        $habitsStmt = $conn->prepare($habitsQuery);
        $habitsStmt->execute([$userId]);
        $habits = $habitsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($habits)) {
            // No habits, maintain current streak at 0
            $updateQuery = "INSERT INTO user_streaks (user_id, current_streak, best_streak) 
                           VALUES (?, 0, 0) 
                           ON DUPLICATE KEY UPDATE current_streak = 0";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute([$userId]);
            return ['current_streak' => 0, 'best_streak' => 0];
        }
        
        // Filter habits that existed on the given date
        $existingHabits = [];
        $dateTimestamp = strtotime($date . ' 23:59:59'); // End of day
        
        foreach ($habits as $habit) {
            $habitCreatedTimestamp = strtotime($habit['created_at']);
            if ($habitCreatedTimestamp <= $dateTimestamp) {
                $existingHabits[] = $habit['id'];
            }
        }
        
        if (empty($existingHabits)) {
            // No habits existed on this date, maintain current streak at 0
            $updateQuery = "INSERT INTO user_streaks (user_id, current_streak, best_streak) 
                           VALUES (?, 0, 0) 
                           ON DUPLICATE KEY UPDATE current_streak = 0";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute([$userId]);
            return ['current_streak' => 0, 'best_streak' => 0];
        }
        
        $placeholders = str_repeat('?,', count($existingHabits) - 1) . '?';
        
        // Check if ALL habits that existed on this date are completed
        $completionQuery = "SELECT COUNT(DISTINCT habit_id) as completed_count 
                           FROM habit_completions 
                           WHERE habit_id IN ($placeholders) AND completion_date = ?";
        $completionStmt = $conn->prepare($completionQuery);
        $completionStmt->execute(array_merge($existingHabits, [$date]));
        $result = $completionStmt->fetch(PDO::FETCH_ASSOC);
        
        $allHabitsCompleted = ($result['completed_count'] == count($existingHabits));
        
        // Debug logging (commented out to prevent JSON issues)
        // error_log("Global streak debug - Date: $date, Total habits: " . count($habits) . ", Completed: " . $result['completed_count'] . ", All completed: " . ($allHabitsCompleted ? 'YES' : 'NO'));
        
        // Get current streak data
        $streakQuery = "SELECT current_streak, best_streak, last_completion_date 
                       FROM user_streaks WHERE user_id = ?";
        $streakStmt = $conn->prepare($streakQuery);
        $streakStmt->execute([$userId]);
        $streakData = $streakStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$streakData) {
            // Initialize streak record
            $streakData = [
                'current_streak' => 0,
                'best_streak' => 0,
                'last_completion_date' => null
            ];
        }
        
        $currentStreak = $streakData['current_streak'];
        $bestStreak = $streakData['best_streak'];
        $lastCompletionDate = $streakData['last_completion_date'];
        
        if ($allHabitsCompleted) {
            if ($lastCompletionDate) {
                $lastDate = new DateTime($lastCompletionDate);
                $currentDate = new DateTime($date);
                $yesterday = clone $currentDate;
                $yesterday->modify('-1 day');
                
                if ($lastDate->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                    // Consecutive day - increment streak
                    $currentStreak++;
                } else if ($lastDate->format('Y-m-d') !== $currentDate->format('Y-m-d')) {
                    // Not consecutive and not same day - reset to 1
                    $currentStreak = 1;
                }
                // If same day, keep current streak (no change)
            } else {
                // First completion ever
                $currentStreak = 1;
            }
            
            // Update best streak if current is higher
            if ($currentStreak > $bestStreak) {
                $bestStreak = $currentStreak;
            }
            
            $lastCompletionDate = $date;
        } else {
            // Not all habits completed
            $today = date('Y-m-d');
            
            if ($date === $today) {
                // Today - don't reset streak yet, user might still complete remaining habits
                // Keep current streak as is (maintain existing streak)
            } else {
                // Past day and not all completed - reset streak to 0
                $currentStreak = 0;
                $lastCompletionDate = null; // Clear last completion date
            }
        }
        
        // Update streak record
        $updateQuery = "INSERT INTO user_streaks (user_id, current_streak, best_streak, last_completion_date) 
                       VALUES (?, ?, ?, ?) 
                       ON DUPLICATE KEY UPDATE 
                       current_streak = VALUES(current_streak),
                       best_streak = VALUES(best_streak),
                       last_completion_date = VALUES(last_completion_date)";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$userId, $currentStreak, $bestStreak, $lastCompletionDate]);
        
        // Debug logging (commented out to prevent JSON issues)
        // error_log("Global streak result - Current: $currentStreak, Best: $bestStreak, All completed: " . ($allHabitsCompleted ? 'YES' : 'NO'));
        
        return [
            'current_streak' => $currentStreak,
            'best_streak' => $bestStreak,
            'all_habits_completed' => $allHabitsCompleted
        ];
        
    } catch (Exception $e) {
        error_log("Error updating global streak: " . $e->getMessage());
        return ['current_streak' => 0, 'best_streak' => 0, 'all_habits_completed' => false];
    }
}

// If called directly via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        ob_end_flush();
        exit;
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $date = $data['date'] ?? date('Y-m-d');
        
        $result = updateGlobalStreak($_SESSION['user_id'], $date);
        
        echo json_encode([
            'success' => true,
            'current_streak' => $result['current_streak'],
            'best_streak' => $result['best_streak'],
            'all_habits_completed' => $result['all_habits_completed']
        ]);
        
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    ob_end_flush();
}
?>