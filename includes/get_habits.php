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
    // Calculate the start of current week (Sunday)
    $today = date('Y-m-d');
    $dayOfWeek = date('w'); // 0 = Sunday, 1 = Monday, etc.
    $weekStart = date('Y-m-d', strtotime($today . ' -' . $dayOfWeek . ' days'));
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    
    $query = "SELECT h.id, h.user_id, h.name, h.category, h.icon, h.created_at, h.updated_at,
              (SELECT COUNT(*) FROM habit_completions hc 
               WHERE hc.habit_id = h.id 
               AND hc.completion_date >= ? 
               AND hc.completion_date <= ?) as completed_days
              FROM habits h 
              WHERE h.user_id = ?";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . implode(' ', $conn->errorInfo()));
    }
    
    if (!$stmt->execute([$weekStart, $weekEnd, $userId])) {
        throw new Exception('Execute failed: ' . implode(' ', $stmt->errorInfo()));
    }
    
    $habits = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Get week progress for current week (Sunday = 0, Monday = 1, etc.)
        $weekProgressQuery = "SELECT DATE_FORMAT(completion_date, '%w') as day_of_week 
                            FROM habit_completions 
                            WHERE habit_id = ? 
                            AND completion_date >= ? 
                            AND completion_date <= ?";
        $weekStmt = $conn->prepare($weekProgressQuery);
        $weekStmt->execute([$row['id'], $weekStart, $weekEnd]);
        
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
        
        // Calculate current streak properly
        $currentStreak = 0;
        $today = date('Y-m-d');
        
        // Check if today is completed
        $todayQuery = "SELECT COUNT(*) as count FROM habit_completions WHERE habit_id = ? AND completion_date = ?";
        $todayStmt = $conn->prepare($todayQuery);
        $todayStmt->execute([$row['id'], $today]);
        $todayResult = $todayStmt->fetch(PDO::FETCH_ASSOC);
        $todayCompleted = $todayResult['count'] > 0;
        
        if ($todayCompleted) {
            // If today is completed, start from today and count backwards
            $checkDate = $today;
        } else {
            // If today is not completed, start from yesterday to show existing streak
            $checkDate = date('Y-m-d', strtotime($today . ' -1 day'));
        }
        
        // Check consecutive days backwards
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
            'completedDays' => (int)$row['completed_days'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode(['success' => true, 'habits' => $habits]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching habits: ' . $e->getMessage()]);
}

$conn = null;
?>
