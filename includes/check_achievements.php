<?php
// Real-time achievement checking API
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Try to load achievement manager
try {
    require_once 'achievement_manager.php';
} catch (Exception $e) {
    error_log("Failed to load achievement_manager.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Achievement system unavailable']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get action type from request
$action_type = $_POST['action_type'] ?? $_GET['action_type'] ?? null;
$data = $_POST['data'] ?? $_GET['data'] ?? [];

// Parse JSON data if it's a string
if (is_string($data)) {
    $decoded_data = json_decode($data, true);
    if ($decoded_data !== null) {
        $data = $decoded_data;
    }
}

if (!$action_type) {
    echo json_encode(['success' => false, 'message' => 'Action type required']);
    exit;
}

try {
    $achievement_manager = new AchievementManager();
    
    // Update all progress first
    $achievement_manager->updateAllProgress($user_id);
    
    // Check for new achievements based on action
    $awarded_achievements = $achievement_manager->checkAchievements($user_id, $action_type, $data);
    
    echo json_encode([
        'success' => true,
        'awarded_achievements' => $awarded_achievements,
        'count' => count($awarded_achievements)
    ]);
    
} catch (Exception $e) {
    error_log("Achievement check error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error checking achievements: ' . $e->getMessage()
    ]);
}
?>