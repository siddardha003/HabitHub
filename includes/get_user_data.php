<?php
// Prevent any HTML error output
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session to access user data
session_start();

// Set JSON content type header before any output
header('Content-Type: application/json');

try {
    // Include files after headers
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../classes/User.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $user = new User($db);
    $userId = $_SESSION['user_id'];
    $userData = $user->getUserById($userId);
    
    if ($userData) {
        // Get total habits count
        $habitsStmt = $db->prepare("SELECT COUNT(*) as total_habits FROM habits WHERE user_id = ?");
        $habitsStmt->execute([$userId]);
        $habitsResult = $habitsStmt->fetch(PDO::FETCH_ASSOC);
        $totalHabits = $habitsResult['total_habits'] ?? 0;

        // Get longest streak (best streak calculation)
        $longestStreak = 0;
        try {
            // Get all habits for this user
            $habitsQuery = $db->prepare("SELECT id, created_at FROM habits WHERE user_id = ? ORDER BY created_at ASC");
            $habitsQuery->execute([$userId]);
            $habits = $habitsQuery->fetchAll(PDO::FETCH_ASSOC);

            // Get all completions
            $completionsQuery = $db->prepare("
                SELECT hc.habit_id, hc.completion_date
                FROM habit_completions hc
                INNER JOIN habits h ON hc.habit_id = h.id
                WHERE h.user_id = ?
                ORDER BY completion_date ASC
            ");
            $completionsQuery->execute([$userId]);
            $completionsData = $completionsQuery->fetchAll(PDO::FETCH_ASSOC);

            // Format completions data
            $completions = [];
            foreach ($completionsData as $completion) {
                $date = $completion['completion_date'];
                $habitId = $completion['habit_id'];
                if (!isset($completions[$date])) {
                    $completions[$date] = [];
                }
                $completions[$date][$habitId] = true;
            }

            // Calculate best streak if we have habits and completions
            if (!empty($habits) && !empty($completions)) {
                $bestStreak = 0;
                $tempStreak = 0;
                
                // Get date range for checking
                $allDates = array_keys($completions);
                if (!empty($allDates)) {
                    $firstDate = min($allDates);
                    $lastDate = max($allDates);
                    
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
                                if (isset($completions[$dateKey][$habit['id']])) {
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
                
                $longestStreak = $bestStreak;
            }
        } catch (Exception $e) {
            // If streak calculation fails, default to 0
            error_log("Streak calculation error: " . $e->getMessage());
            $longestStreak = 0;
        }

        echo json_encode([
            'success' => true,
            'user' => [
                'name' => $userData['username'], // Using username as name
                'email' => $userData['email'],
                'created_at' => $userData['created_at'],
                'total_habits' => $totalHabits,
                'longest_streak' => $longestStreak
            ]
        ]);
    } else {
        throw new Exception('User not found');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
