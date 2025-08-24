-- Database schema fix for HabitHub
-- This script ensures the correct database structure

-- Create habits table (if not exists)
CREATE TABLE IF NOT EXISTS habits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    icon VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create habit_completions table (if not exists)
CREATE TABLE IF NOT EXISTS habit_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habit_id INT NOT NULL,
    completion_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_completion (habit_id, completion_date),
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE
);

-- Create habit_streaks table (if not exists)
CREATE TABLE IF NOT EXISTS habit_streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habit_id INT NOT NULL,
    streak INT DEFAULT 0,
    last_completion_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_habit_streak (habit_id),
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE
);

-- Create user_streaks table (if not exists)
CREATE TABLE IF NOT EXISTS user_streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    current_streak INT DEFAULT 0,
    best_streak INT DEFAULT 0,
    last_completion_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_streak (user_id)
);

-- Remove streak column from habits table if it exists (this might be causing the error)
-- First, let's check and remove any streak-related columns
ALTER TABLE habits DROP COLUMN IF EXISTS streak;
ALTER TABLE habits DROP COLUMN IF EXISTS current_streak;
ALTER TABLE habits DROP COLUMN IF EXISTS best_streak;

-- Also ensure we don't have any triggers or procedures trying to update streak
DROP TRIGGER IF EXISTS update_habit_streak;
DROP PROCEDURE IF EXISTS UpdateHabitStreak;