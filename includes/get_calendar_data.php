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
$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');

try {
    // Debug: Log the user ID and date parameters
    error_log("Calendar data request - User ID: $userId, Month: $month, Year: $year");
    
    // Get user habits
    $habitsStmt = $pdo->prepare("
        SELECT id, name, category, icon, created_at 
        FROM habits 
        WHERE user_id = ? 
        ORDER BY created_at ASC
    ");
    $habitsStmt->execute([$userId]);
    $habits = $habitsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($habits) . " habits for user $userId");

    // Get habit completions for the month
    $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    error_log("Date range: $startDate to $endDate");
    
    $completionsStmt = $pdo->prepare("
        SELECT hc.habit_id, hc.completion_date, 1 as completed
        FROM habit_completions hc
        INNER JOIN habits h ON hc.habit_id = h.id
        WHERE h.user_id = ? 
        AND hc.completion_date BETWEEN ? AND ?
    ");
    $completionsStmt->execute([$userId, $startDate, $endDate]);
    $completionsData = $completionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($completionsData) . " completions");

    // Get daily notes for the month (if table exists)
    $notesData = [];
    try {
        $notesStmt = $pdo->prepare("
            SELECT date, note_content
            FROM daily_notes 
            WHERE user_id = ? 
            AND date BETWEEN ? AND ?
        ");
        $notesStmt->execute([$userId, $startDate, $endDate]);
        $dailyNotes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format notes data
        foreach ($dailyNotes as $note) {
            $notesData[$note['date']] = $note['note_content'];
        }
    } catch (PDOException $e) {
        // Table might not exist yet, continue without notes
        error_log("Daily notes table not found: " . $e->getMessage());
        $notesData = [];
    }

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

    // Notes are already formatted above

    // Calculate monthly stats
    $totalDays = 0;
    $perfectDays = 0;
    $activeDays = 0;
    $totalCompletions = 0;
    $totalPossible = 0;
    $currentStreak = 0;
    $bestHabit = null;
    $bestHabitRate = 0;

    // Calculate stats
    $habitStats = [];
    foreach ($habits as $habit) {
        $habitStats[$habit['id']] = ['completed' => 0, 'total' => 0];
    }

    $daysInMonth = date('t', strtotime($startDate));
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
        
        if (strtotime($date) <= time()) { // Only count past and current days
            $dayCompletions = 0;
            $dayTotal = count($habits);
            
            foreach ($habits as $habit) {
                $habitStats[$habit['id']]['total']++;
                if (isset($completions[$date][$habit['id']]) && $completions[$date][$habit['id']]) {
                    $dayCompletions++;
                    $habitStats[$habit['id']]['completed']++;
                }
            }
            
            if ($dayTotal > 0) {
                $totalCompletions += $dayCompletions;
                $totalPossible += $dayTotal;
                
                if ($dayCompletions === $dayTotal && $dayTotal > 0) {
                    $perfectDays++;
                }
            }
        }
    }

    // Find best habit
    foreach ($habits as $habit) {
        $stats = $habitStats[$habit['id']];
        if ($stats['total'] > 0) {
            $rate = $stats['completed'] / $stats['total'];
            if ($rate > $bestHabitRate) {
                $bestHabitRate = $rate;
                $bestHabit = $habit['name'];
            }
        }
    }

    // Update and get current streak from global streak system
    $currentStreak = 0;
    try {
        // First, update the global streak to ensure it's current
        require_once 'update_global_streak.php';
        $streakData = updateGlobalStreak($userId);
        $currentStreak = $streakData['current_streak'];
    } catch (Exception $e) {
        error_log("Global streak update failed: " . $e->getMessage());
        
        // Fallback: try to get from database
        try {
            $streakQuery = "SELECT current_streak FROM user_streaks WHERE user_id = ?";
            $streakStmt = $pdo->prepare($streakQuery);
            $streakStmt->execute([$userId]);
            $streakResult = $streakStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($streakResult) {
                $currentStreak = (int)$streakResult['current_streak'];
            }
        } catch (PDOException $e2) {
            error_log("Streak fallback query failed: " . $e2->getMessage());
            $currentStreak = 0;
        }
    }

    $overallProgress = $totalPossible > 0 ? round(($totalCompletions / $totalPossible) * 100) : 0;
    
    // Calculate active days from server-side visit tracking
    $activeDays = 0;
    try {
        $visitsStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT visit_date) as active_days
            FROM user_visits 
            WHERE user_id = ? 
            AND visit_date BETWEEN ? AND ?
        ");
        $visitsStmt->execute([$userId, $startDate, $endDate]);
        $visitResult = $visitsStmt->fetch(PDO::FETCH_ASSOC);
        $activeDays = (int)$visitResult['active_days'];
    } catch (PDOException $e) {
        // Fallback: count days with habit interactions
        error_log("Visits query failed, using fallback: " . $e->getMessage());
        foreach ($completions as $date => $dayData) {
            if (count($dayData) > 0) {
                $activeDays++;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'habits' => $habits,
        'completions' => $completions,
        'notes' => $notesData,
        'stats' => [
            'overallProgress' => $overallProgress,
            'perfectDays' => $perfectDays,
            'currentStreak' => $currentStreak,
            'bestHabit' => $bestHabit ?: 'None',
            'activeDays' => $activeDays,
            'daysInMonth' => $daysInMonth
        ]
    ]);

} catch (PDOException $e) {
    error_log("Calendar data error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'debug' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
?>