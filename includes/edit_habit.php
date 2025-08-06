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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['habitId']) || !isset($input['name']) || !isset($input['category'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$habitId = (int)$input['habitId'];
$name = trim($input['name']);
$category = trim($input['category']);
$icon = $input['icon'] ?? '';
$userId = $_SESSION['user_id'];

// Validate input
if (empty($name) || empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Name and category are required']);
    exit;
}

// Validate category
$validCategories = ['health', 'physical', 'learning', 'mindfulness', 'creativity', 'productivity', 'social', 'lifestyle'];
if (!in_array($category, $validCategories)) {
    echo json_encode(['success' => false, 'message' => 'Invalid category']);
    exit;
}

try {
    // First, verify that the habit belongs to the current user
    $checkStmt = $pdo->prepare("SELECT id FROM habits WHERE id = ? AND user_id = ?");
    $checkStmt->execute([$habitId, $userId]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Habit not found or access denied']);
        exit;
    }
    
    // Update the habit
    $updateStmt = $pdo->prepare("UPDATE habits SET name = ?, category = ?, icon = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $updateStmt->execute([$name, $category, $icon, $habitId, $userId]);
    
    if ($updateStmt->rowCount() > 0) {
        // Fetch the updated habit data
        $fetchStmt = $pdo->prepare("SELECT * FROM habits WHERE id = ? AND user_id = ?");
        $fetchStmt->execute([$habitId, $userId]);
        $updatedHabit = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Habit updated successfully',
            'habit' => $updatedHabit
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes were made']);
    }
    
} catch (PDOException $e) {
    error_log("Edit habit error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>