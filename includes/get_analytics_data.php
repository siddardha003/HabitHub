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
$startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-3 months'));
$endDate = $_GET['end'] ?? date('Y-m-d');

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
        ORDER BY hc.completion_date DESC
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

    // Update and get current streak from global streak system
    $currentStreak = 0;
    try {
        // First, update the global streak to ensure it's current
        require_once 'update_global_streak.php';
        $streakData = updateGlobalStreak($userId);
        $currentStreak = $streakData['current_streak'];
    } catch (Exception $e) {
        error_log("Global streak update failed in analytics: " . $e->getMessage());
        
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
            error_log("Streak fallback query failed in analytics: " . $e2->getMessage());
            $currentStreak = 0;
        }
    }

    // Get best streak from global streak system
    $bestStreak = $currentStreak; // Default to current streak
    
    try {
        // Try to get best streak from user_streaks table
        $bestStreakStmt = $pdo->prepare("
            SELECT best_streak 
            FROM user_streaks 
            WHERE user_id = ?
        ");
        $bestStreakStmt->execute([$userId]);
        $bestStreakResult = $bestStreakStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bestStreakResult && $bestStreakResult['best_streak']) {
            $bestStreak = (int)$bestStreakResult['best_streak'];
        }
    } catch (PDOException $e) {
        error_log("Best streak query failed in analytics: " . $e->getMessage());
        // Use current streak as fallback
    }

    // Calculate total days with data
    $totalDays = count($completions);

    // Get user visits data
    $visits = [];
    try {
        $visitsStmt = $pdo->prepare("
            SELECT visit_date, visit_count 
            FROM user_visits 
            WHERE user_id = ? 
            AND visit_date BETWEEN ? AND ?
            ORDER BY visit_date ASC
        ");
        $visitsStmt->execute([$userId, $startDate, $endDate]);
        $visitsData = $visitsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($visitsData as $visit) {
            $visits[$visit['visit_date']] = (int)$visit['visit_count'];
        }
    } catch (PDOException $e) {
        // Table might not exist yet, that's okay
        error_log("Visits query failed: " . $e->getMessage());
    }

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
        'debug' => $e->getMessage()
    ]);
}
?>