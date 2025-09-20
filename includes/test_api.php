<?php
// Simple API test
header('Content-Type: application/json');

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($pdo) {
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>