-- Create table to track user login days
CREATE TABLE IF NOT EXISTS user_login_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, login_date),
    INDEX idx_user_id (user_id),
    INDEX idx_login_date (login_date)
);

-- Add last_login column to users table if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL DEFAULT NULL;

-- Insert today's login for existing users (optional - for testing)
-- INSERT IGNORE INTO user_login_days (user_id, login_date)
-- SELECT id, CURDATE() FROM users;