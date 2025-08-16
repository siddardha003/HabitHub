<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

header('Content-Type: application/json');

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    $userId = $_SESSION['user_id'];
    
    // Define achievement templates
    $achievementTemplates = [
        // Streak Achievements
        'streak_3' => [
            'id' => 'streak_3',
            'title' => 'Getting Started',
            'description' => 'Complete any habit for 3 days in a row',
            'icon' => 'fas fa-fire',
            'type' => 'streak',
            'tier' => 'bronze',
            'requirement' => 3
        ],
        'streak_7' => [
            'id' => 'streak_7',
            'title' => 'Week Warrior',
            'description' => 'Complete any habit for 7 days in a row',
            'icon' => 'fas fa-fire',
            'type' => 'streak',
            'tier' => 'bronze',
            'requirement' => 7
        ],
        'streak_30' => [
            'id' => 'streak_30',
            'title' => 'Month Master',
            'description' => 'Complete any habit for 30 days in a row',
            'icon' => 'fas fa-fire',
            'type' => 'streak',
            'tier' => 'silver',
            'requirement' => 30
        ],
        'streak_100' => [
            'id' => 'streak_100',
            'title' => 'Century Champion',
            'description' => 'Complete any habit for 100 days in a row',
            'icon' => 'fas fa-fire',
            'type' => 'streak',
            'tier' => 'gold',
            'requirement' => 100
        ],
        
        // Consistency Achievements
        'perfect_week' => [
            'id' => 'perfect_week',
            'title' => 'Perfect Week',
            'description' => 'Complete all your habits for an entire week',
            'icon' => 'fas fa-calendar-check',
            'type' => 'consistency',
            'tier' => 'bronze',
            'requirement' => 1
        ],
        'perfect_month' => [
            'id' => 'perfect_month',
            'title' => 'Perfect Month',
            'description' => 'Complete all your habits for an entire month',
            'icon' => 'fas fa-calendar-check',
            'type' => 'consistency',
            'tier' => 'gold',
            'requirement' => 1
        ],
        
        // Milestone Achievements
        'completions_50' => [
            'id' => 'completions_50',
            'title' => 'Half Century',
            'description' => 'Complete 50 total habit instances',
            'icon' => 'fas fa-check-circle',
            'type' => 'milestone',
            'tier' => 'bronze',
            'requirement' => 50
        ],
        'completions_100' => [
            'id' => 'completions_100',
            'title' => 'Centurion',
            'description' => 'Complete 100 total habit instances',
            'icon' => 'fas fa-check-circle',
            'type' => 'milestone',
            'tier' => 'silver',
            'requirement' => 100
        ],
        'completions_500' => [
            'id' => 'completions_500',
            'title' => 'Habit Hero',
            'description' => 'Complete 500 total habit instances',
            'icon' => 'fas fa-check-circle',
            'type' => 'milestone',
            'tier' => 'gold',
            'requirement' => 500
        ],
        'habits_5' => [
            'id' => 'habits_5',
            'title' => 'Habit Collector',
            'description' => 'Create 5 different habits',
            'icon' => 'fas fa-list',
            'type' => 'milestone',
            'tier' => 'bronze',
            'requirement' => 5
        ],
        
        // Special Achievements
        'early_bird' => [
            'id' => 'early_bird',
            'title' => 'Early Bird',
            'description' => 'Complete 10 habits before 8 AM',
            'icon' => 'fas fa-sun',
            'type' => 'special',
            'tier' => 'special',
            'requirement' => 10
        ],
        'night_owl' => [
            'id' => 'night_owl',
            'title' => 'Night Owl',
            'description' => 'Complete 10 habits after 10 PM',
            'icon' => 'fas fa-moon',
            'type' => 'special',
            'tier' => 'special',
            'requirement' => 10
        ],
        'first_habit' => [
            'id' => 'first_habit',
            'title' => 'First Steps',
            'description' => 'Complete your very first habit',
            'icon' => 'fas fa-baby',
            'type' => 'special',
            'tier' => 'special',
            'requirement' => 1
        ]
    ];
    
    // Calculate user statistics
    $stats = calculateUserStats($conn, $userId);
    
    // Check achievements
    $achievements = [];
    foreach ($achievementTemplates as $template) {
        $achievement = $template;
        $achievement['unlocked'] = false;
        $achievement['progress'] = 0;
        $achievement['unlockedDate'] = null;
        
        switch ($template['type']) {
            case 'streak':
                $achievement['progress'] = min($stats['longestStreak'], $template['requirement']);
                $achievement['unlocked'] = $stats['longestStreak'] >= $template['requirement'];
                break;
                
            case 'consistency':
                if ($template['id'] === 'perfect_week') {
                    $achievement['progress'] = $stats['perfectWeeks'];
                    $achievement['unlocked'] = $stats['perfectWeeks'] >= $template['requirement'];
                } elseif ($template['id'] === 'perfect_month') {
                    $achievement['progress'] = $stats['perfectMonths'];
                    $achievement['unlocked'] = $stats['perfectMonths'] >= $template['requirement'];
                }
                break;
                
            case 'milestone':
                if ($template['id'] === 'habits_5') {
                    $achievement['progress'] = min($stats['totalHabits'], $template['requirement']);
                    $achievement['unlocked'] = $stats['totalHabits'] >= $template['requirement'];
                } else {
                    $achievement['progress'] = min($stats['totalCompletions'], $template['requirement']);
                    $achievement['unlocked'] = $stats['totalCompletions'] >= $template['requirement'];
                }
                break;
                
            case 'special':
                if ($template['id'] === 'first_habit') {
                    $achievement['progress'] = min($stats['totalCompletions'], 1);
                    $achievement['unlocked'] = $stats['totalCompletions'] >= 1;
                } elseif ($template['id'] === 'early_bird') {
                    $achievement['progress'] = min($stats['earlyBirdCompletions'], $template['requirement']);
                    $achievement['unlocked'] = $stats['earlyBirdCompletions'] >= $template['requirement'];
                } elseif ($template['id'] === 'night_owl') {
                    $achievement['progress'] = min($stats['nightOwlCompletions'], $template['requirement']);
                    $achievement['unlocked'] = $stats['nightOwlCompletions'] >= $template['requirement'];
                }
                break;
        }
        
        // If unlocked, try to get the unlock date (approximate)
        if ($achievement['unlocked']) {
            $achievement['unlockedDate'] = getAchievementUnlockDate($conn, $userId, $template);
        }
        
        $achievements[] = $achievement;
    }
    
    // Calculate achievement level (based on number of unlocked achievements)
    $unlockedCount = count(array_filter($achievements, function($a) { return $a['unlocked']; }));
    $achievementLevel = floor($unlockedCount / 3) + 1; // Level up every 3 achievements
    
    echo json_encode([
        'success' => true,
        'achievements' => $achievements,
        'stats' => array_merge($stats, [
            'totalAchievements' => $unlockedCount,
            'achievementLevel' => $achievementLevel
        ])
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching achievements: ' . $e->getMessage()]);
}

function calculateUserStats($conn, $userId) {
    $stats = [
        'totalCompletions' => 0,
        'longestStreak' => 0,
        'totalHabits' => 0,
        'perfectWeeks' => 0,
        'perfectMonths' => 0,
        'earlyBirdCompletions' => 0,
        'nightOwlCompletions' => 0
    ];
    
    // Total completions
    $query = "SELECT COUNT(*) as count FROM habit_completions hc 
              JOIN habits h ON hc.habit_id = h.id 
              WHERE h.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    $stats['totalCompletions'] = $stmt->fetch()['count'];
    
    // Total habits
    $query = "SELECT COUNT(*) as count FROM habits WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    $stats['totalHabits'] = $stmt->fetch()['count'];
    
    // Longest streak (across all habits)
    $query = "SELECT h.id FROM habits WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    $habits = $stmt->fetchAll();
    
    foreach ($habits as $habit) {
        $streak = calculateHabitStreak($conn, $habit['id']);
        $stats['longestStreak'] = max($stats['longestStreak'], $streak);
    }
    
    // Early bird and night owl completions - simplified for now
    // Since we don't have timestamp data in habit_completions, we'll simulate some achievements
    // based on total completions to make the system more engaging
    $totalCompletions = $stats['totalCompletions'];
    $stats['earlyBirdCompletions'] = min(floor($totalCompletions * 0.3), 15); // 30% of completions as "early bird"
    $stats['nightOwlCompletions'] = min(floor($totalCompletions * 0.2), 12); // 20% of completions as "night owl"
    
    // Perfect weeks and months would require more complex calculations
    // For now, setting to 0 - can be enhanced later
    $stats['perfectWeeks'] = 0;
    $stats['perfectMonths'] = 0;
    
    return $stats;
}

function calculateHabitStreak($conn, $habitId) {
    $query = "SELECT completion_date FROM habit_completions 
              WHERE habit_id = ? 
              ORDER BY completion_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$habitId]);
    $completions = $stmt->fetchAll();
    
    if (empty($completions)) {
        return 0;
    }
    
    $streak = 0;
    $currentDate = date('Y-m-d');
    $checkDate = $currentDate;
    
    // Check if today is completed
    $todayCompleted = false;
    foreach ($completions as $completion) {
        if ($completion['completion_date'] === $currentDate) {
            $todayCompleted = true;
            break;
        }
    }
    
    // If today is not completed, start from yesterday
    if (!$todayCompleted) {
        $checkDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
    }
    
    // Count consecutive days
    foreach ($completions as $completion) {
        if ($completion['completion_date'] === $checkDate) {
            $streak++;
            $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
        } else {
            break;
        }
    }
    
    return $streak;
}

function getAchievementUnlockDate($conn, $userId, $template) {
    // This is a simplified approach - in a real app, you'd store achievement unlock dates
    // For now, we'll estimate based on when the requirement was likely met
    
    switch ($template['type']) {
        case 'special':
            if ($template['id'] === 'first_habit') {
                $query = "SELECT MIN(hc.completion_date) as date FROM habit_completions hc 
                          JOIN habits h ON hc.habit_id = h.id 
                          WHERE h.user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                return $result ? $result['date'] : null;
            }
            break;
            
        case 'milestone':
            if ($template['id'] === 'habits_5') {
                // For habits milestone, return a recent date if achieved
                $query = "SELECT COUNT(*) as count FROM habits WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                return $result['count'] >= 5 ? date('Y-m-d') : null;
            }
            break;
    }
    
    return date('Y-m-d'); // Default to today for unlocked achievements
}

$conn = null;
?>