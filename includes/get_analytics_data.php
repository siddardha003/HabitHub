<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Database connection
require_once '../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$userId = $_SESSION['user_id'];
$endDate = $_GET['end'] ?? date('Y-m-d');

// If no start date provided, get ALL data since first habit creation
if (isset($_GET['start'])) {
    $startDate = $_GET['start'];
} else {
    // Get the earliest habit creation date for this user
    $earliestStmt = $pdo->prepare("SELECT MIN(created_at) as earliest FROM habits WHERE user_id = ?");
    $earliestStmt->execute([$userId]);
    $earliest = $earliestStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($earliest && $earliest['earliest']) {
        $startDate = date('Y-m-d', strtotime($earliest['earliest']));
    } else {
        // Fallback if no habits exist
        $startDate = date('Y-m-d', strtotime('-1 year'));
    }
}

try {
    // Get user habits
    $habitsStmt = $pdo->prepare("
        SELECT id, name, category, icon, created_at 
        FROM habits 
        WHERE user_id = ? 
        ORDER BY created_at ASC
    ");
    $habitsStmt->execute([$userId]);
    $habits = $habitsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get habit completions for the date range
    $completionsStmt = $pdo->prepare("
        SELECT hc.habit_id, hc.completion_date, 1 as completed
        FROM habit_completions hc
        INNER JOIN habits h ON hc.habit_id = h.id
        WHERE h.user_id = ? 
        AND hc.completion_date BETWEEN ? AND ?
    ");
    $completionsStmt->execute([$userId, $startDate, $endDate]);
    $completionsData = $completionsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format completions data
    $completions = [];
    foreach ($completionsData as $completion) {
        $date = $completion['completion_date'];
        $habitId = $completion['habit_id'];
        if (!isset($completions[$date])) {
            $completions[$date] = [];
        }
        $completions[$date][$habitId] = (bool)$completion['completed'];
    }

    // Calculate current streak
    $currentStreak = 0;
    $today = date('Y-m-d');
    
    // Start from today and work backwards to find consecutive perfect days
    for ($i = 0; $i < 365; $i++) { // Check up to 1 year back
        $checkDate = date('Y-m-d', strtotime($today . " -$i days"));
        
        // Only check dates not in the future
        if (strtotime($checkDate) <= time()) {
            // Count habits that existed on this date
            $dayTotal = 0;
            $dayCompletions = 0;
            
            foreach ($habits as $habit) {
                $habitCreatedDate = strtotime($habit['created_at']);
                $checkDateTimestamp = strtotime($checkDate . ' 23:59:59');
                
                if ($habitCreatedDate <= $checkDateTimestamp) {
                    $dayTotal++;
                    if (isset($completions[$checkDate][$habit['id']]) && $completions[$checkDate][$habit['id']]) {
                        $dayCompletions++;
                    }
                }
            }
            
            // If it's a perfect day, increment streak
            if ($dayTotal > 0 && $dayCompletions === $dayTotal) {
                $currentStreak++;
            } else {
                // Break the streak if not a perfect day
                break;
            }
        } else {
            break;
        }
    }

    // Calculate best streak (all-time)
    $bestStreak = 0;
    $tempStreak = 0;
    
    // Get all dates from the beginning of user's habit tracking
    $allDatesStmt = $pdo->prepare("
        SELECT DISTINCT completion_date 
        FROM habit_completions hc
        INNER JOIN habits h ON hc.habit_id = h.id
        WHERE h.user_id = ?
        ORDER BY completion_date ASC
    ");
    $allDatesStmt->execute([$userId]);
    $allDates = $allDatesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($allDates)) {
        $firstDate = $allDates[0];
        $lastDate = end($allDates);
        
        // Check each day from first to last
        $currentDate = new DateTime($firstDate);
        $endDateTime = new DateTime($lastDate);
        
        while ($currentDate <= $endDateTime) {
            $dateKey = $currentDate->format('Y-m-d');
            
            // Count habits that existed on this date
            $dayTotal = 0;
            $dayCompletions = 0;
            
            foreach ($habits as $habit) {
                $habitCreatedDate = strtotime($habit['created_at']);
                $checkDateTimestamp = strtotime($dateKey . ' 23:59:59');
                
                if ($habitCreatedDate <= $checkDateTimestamp) {
                    $dayTotal++;
                    if (isset($completions[$dateKey][$habit['id']]) && $completions[$dateKey][$habit['id']]) {
                        $dayCompletions++;
                    }
                }
            }
            
            // Check if it's a perfect day
            if ($dayTotal > 0 && $dayCompletions === $dayTotal) {
                $tempStreak++;
                $bestStreak = max($bestStreak, $tempStreak);
            } else {
                $tempStreak = 0;
            }
            
            $currentDate->add(new DateInterval('P1D'));
        }
    }

    // Get user login days data (using existing table)
    $visits = [];
    try {
        $visitsStmt = $pdo->prepare("
            SELECT login_date
            FROM user_login_days 
            WHERE user_id = ? 
            AND login_date BETWEEN ? AND ?
        ");
        $visitsStmt->execute([$userId, $startDate, $endDate]);
        $visitData = $visitsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($visitData as $visitDate) {
            $visits[$visitDate] = true;
        }
    } catch (PDOException $e) {
        // Table might not exist, continue without visits data
        error_log("User login days table not found: " . $e->getMessage());
    }

    // Calculate total days in the period
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $totalDays = $start->diff($end)->days + 1;

    echo json_encode([
        'success' => true,
        'habits' => $habits,
        'completions' => $completions,
        'visits' => $visits,
        'stats' => [
            'currentStreak' => $currentStreak,
            'bestStreak' => $bestStreak,
            'totalDays' => $totalDays
        ]
    ]);

} catch (PDOException $e) {
    error_log("Analytics data error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'debug' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
?>