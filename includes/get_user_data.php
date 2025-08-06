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
        echo json_encode([
            'success' => true,
            'user' => [
                'name' => $userData['username'], // Using username as name
                'email' => $userData['email']
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
