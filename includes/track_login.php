<?php
// Track user login - call this when user successfully logs in
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Check if user already logged in today
    $stmt = $pdo->prepare("SELECT id FROM user_login_days WHERE user_id = ? AND login_date = ?");
    $stmt->execute([$user_id, $today]);
    
    // If no record for today, insert it
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO user_login_days (user_id, login_date) VALUES (?, ?)");
        $stmt->execute([$user_id, $today]);
    }
    
    // Also update last_login in users table
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user_id]);
    
} catch (PDOException $e) {
    error_log("Error tracking login: " . $e->getMessage());
}
?>