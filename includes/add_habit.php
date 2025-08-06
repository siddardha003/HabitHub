<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Check if there were any PHP errors
if (error_get_last()) {
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    exit;
}

require_once '../config/database.php';

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
    
    if (!isset($data['name']) || !isset($data['category'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $name = $data['name'];
    $category = $data['category'];
    $icon = $data['icon'] ?? '';
    
    $query = "INSERT INTO habits (user_id, name, category, icon) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if ($stmt->execute([$userId, $name, $category, $icon])) {
        $habitId = $conn->lastInsertId();
        echo json_encode([
            'success' => true, 
            'message' => 'Habit created successfully',
            'habit' => [
                'id' => $habitId,
                'name' => $name,
                'category' => $category,
                'icon' => $icon,
                'currentStreak' => 0,
                'weekProgress' => array_fill(0, 7, false),
                'completedDays' => 0
            ]
        ]);
    } else {
        throw new Exception('Failed to create habit');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error creating habit: ' . $e->getMessage()]);
}

$conn = null;
?>
