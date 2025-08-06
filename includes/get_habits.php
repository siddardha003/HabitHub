<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

// Capture all errors, even fatal ones
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error $errno: $errstr in $errfile on line $errline");
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'debug' => [
            'error' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]
    ]);
    exit;
});

header('Content-Type: application/json');

require_once '../config/database.php';

// Get PDO connection
$database = new Database();
$conn = $database->getConnection();

// Debug information
$debug = [
    'session_status' => session_status(),
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'user_id' => $_SESSION['user_id'] ?? 'not set'
];

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'User not authenticated',
        'debug' => $debug
    ]);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // Get current week boundaries (Sunday to Saturday)
    $query = "SELECT h.*, 
              (SELECT COUNT(*) FROM habit_completions hc 
               WHERE hc.habit_id = h.id 
               AND hc.completion_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) + 1 DAY)
               AND hc.completion_date <= DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) + 1 DAY), INTERVAL 6 DAY)) as completed_days,
              (SELECT COALESCE(MAX(streak), 0) FROM habit_streaks hs WHERE hs.habit_id = h.id) as current_streak
              FROM habits h 
              WHERE h.user_id = ?";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . implode(' ', $conn->errorInfo()));
    }
    
    if (!$stmt->execute([$userId])) {
        throw new Exception('Execute failed: ' . implode(' ', $stmt->errorInfo()));
    }
    
    $habits = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Get week progress for current week (Sunday = 0, Monday = 1, etc.)
        $weekProgressQuery = "SELECT DATE_FORMAT(completion_date, '%w') as day_of_week 
                            FROM habit_completions 
                            WHERE habit_id = ? 
                            AND completion_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) + 1 DAY)
                            AND completion_date <= DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) + 1 DAY), INTERVAL 6 DAY)";
        $weekStmt = $conn->prepare($weekProgressQuery);
        $weekStmt->execute([$row['id']]);
        
        $weekProgress = array_fill(0, 7, false);
        while ($day = $weekStmt->fetch(PDO::FETCH_ASSOC)) {
            $weekProgress[(int)$day['day_of_week']] = true;
        }
        
        // Calculate current streak properly
        $streakQuery = "SELECT COUNT(*) as streak FROM (
                          SELECT completion_date,
                                 @row_number := CASE 
                                   WHEN @prev_date = DATE_SUB(completion_date, INTERVAL 1 DAY) OR @prev_date IS NULL 
                                   THEN @row_number + 1 
                                   ELSE 1 
                                 END AS rn,
                                 @prev_date := completion_date
                          FROM habit_completions, (SELECT @row_number := 0, @prev_date := NULL) AS vars
                          WHERE habit_id = ? 
                          ORDER BY completion_date DESC
                        ) AS streaks 
                        WHERE rn = (SELECT MAX(rn) FROM (
                          SELECT @row_number := CASE 
                            WHEN @prev_date = DATE_SUB(completion_date, INTERVAL 1 DAY) OR @prev_date IS NULL 
                            THEN @row_number + 1 
                            ELSE 1 
                          END AS rn,
                          @prev_date := completion_date
                          FROM habit_completions, (SELECT @row_number := 0, @prev_date := NULL) AS vars2
                          WHERE habit_id = ? 
                          ORDER BY completion_date DESC
                        ) AS max_streak)";
        
        // For now, let's use a simpler streak calculation
        $currentStreak = 0;
        $today = date('Y-m-d');
        $checkDate = $today;
        
        // Check consecutive days backwards from today
        for ($i = 0; $i < 365; $i++) { // Max 365 days to prevent infinite loop
            $checkQuery = "SELECT COUNT(*) as count FROM habit_completions WHERE habit_id = ? AND completion_date = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->execute([$row['id'], $checkDate]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $currentStreak++;
                $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
            } else {
                break;
            }
        }
        
        $habits[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'category' => $row['category'],
            'icon' => $row['icon'],
            'currentStreak' => $currentStreak,
            'weekProgress' => $weekProgress,
            'completedDays' => (int)$row['completed_days']
        ];
    }
    
    echo json_encode(['success' => true, 'habits' => $habits]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching habits: ' . $e->getMessage()]);
}

$conn = null;
?>
