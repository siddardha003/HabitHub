-- Test and recreate habit_streaks table

-- Drop and recreate habit_streaks table to ensure it has the correct structure
DROP TABLE IF EXISTS habit_streaks;

CREATE TABLE habit_streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habit_id INT NOT NULL,
    streak INT DEFAULT 0,
    last_completion_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_habit_streak (habit_id),
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE
);

-- Also recreate user_streaks table
DROP TABLE IF EXISTS user_streaks;

CREATE TABLE user_streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    current_streak INT DEFAULT 0,
    best_streak INT DEFAULT 0,
    last_completion_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_streak (user_id)
);

-- Show the structure to verify
SHOW COLUMNS FROM habit_streaks;
SHOW COLUMNS FROM user_streaks;