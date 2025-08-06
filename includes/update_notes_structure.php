<?php
// Update database structure for daily notes instead of habit-specific notes
$host = 'localhost';
$dbname = 'habithub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Updating Notes Structure</h2>";
    
    // Create daily_notes table for general daily notes
    $createDailyNotesTable = "
        CREATE TABLE IF NOT EXISTS daily_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            date DATE NOT NULL,
            note_content TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_daily_note (user_id, date)
        )
    ";
    
    $pdo->exec($createDailyNotesTable);
    echo "<p style='color: green;'>✓ daily_notes table created successfully!</p>";
    
    echo "<h3 style='color: green;'>Structure Update Complete!</h3>";
    echo "<p>Now using daily notes instead of habit-specific notes.</p>";
    echo "<p><a href='../pages/dashboard/calender.html'>Go to Calendar</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>