<?php
// Run this file once to create the login tracking table
require_once '../config/database.php';

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "Creating login tracking table...\n";
    
    // Create the user_login_days table
    $sql = "
    CREATE TABLE IF NOT EXISTS user_login_days (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        login_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_date (user_id, login_date),
        INDEX idx_user_id (user_id),
        INDEX idx_login_date (login_date)
    )";
    
    $pdo->exec($sql);
    echo "✓ user_login_days table created successfully\n";
    
    // Add last_login column to users table
    $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL DEFAULT NULL";
    $pdo->exec($sql);
    echo "✓ last_login column added to users table\n";
    
    // Optional: Add today's login for existing users (for testing)
    $sql = "INSERT IGNORE INTO user_login_days (user_id, login_date) 
            SELECT id, CURDATE() FROM users";
    $pdo->exec($sql);
    echo "✓ Added today's login for existing users\n";
    
    echo "\nMigration completed successfully!\n";
    echo "Login tracking is now active.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>