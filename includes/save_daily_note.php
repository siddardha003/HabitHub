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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['date'])) {
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    exit;
}

$userId = $_SESSION['user_id'];
$date = $input['date'];
$noteContent = trim($input['noteContent'] ?? '');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

try {
    // Check if daily_notes table exists, create if not
    $tableCheckStmt = $pdo->prepare("SHOW TABLES LIKE 'daily_notes'");
    $tableCheckStmt->execute();
    
    if (!$tableCheckStmt->fetch()) {
        // Create the table if it doesn't exist
        $createTableSQL = "
            CREATE TABLE daily_notes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                note_date DATE NOT NULL,
                content TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_daily_note (user_id, note_date)
            )
        ";
        $pdo->exec($createTableSQL);
    }
    
    if (empty($noteContent)) {
        // Delete note if content is empty
        $deleteStmt = $pdo->prepare("DELETE FROM daily_notes WHERE user_id = ? AND note_date = ?");
        $deleteStmt->execute([$userId, $date]);
        
        echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
    } else {
        // Insert or update note
        $upsertStmt = $pdo->prepare("
            INSERT INTO daily_notes (user_id, note_date, content) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            content = VALUES(content),
            updated_at = CURRENT_TIMESTAMP
        ");
        $upsertStmt->execute([$userId, $date, $noteContent]);
        
        echo json_encode(['success' => true, 'message' => 'Note saved successfully']);
    }
    
} catch (PDOException $e) {
    error_log("Save daily note error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'debug' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
?>