-- HabitHub Achievements System Database Schema
-- This file creates the necessary tables for the new achievements system

-- Create achievement categories table
CREATE TABLE IF NOT EXISTS achievement_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create achievements table
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    achievement_key VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(50) NOT NULL,
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') NOT NULL DEFAULT 'common',
    points INT NOT NULL DEFAULT 0,
    category_id INT,
    requirement_type ENUM(
        'login_streak', 
        'total_logins', 
        'habit_creation', 
        'habit_completion', 
        'habit_streak', 
        'perfect_day', 
        'perfect_week', 
        'perfect_month', 
        'category_specific', 
        'special'
    ) NOT NULL,
    requirement_value INT NOT NULL DEFAULT 1,
    requirement_data JSON, -- For storing additional requirement parameters
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES achievement_categories(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_requirement_type (requirement_type),
    INDEX idx_active (is_active)
);

-- Create user achievements table to track progress and earned achievements
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    current_progress INT DEFAULT 0,
    is_earned BOOLEAN DEFAULT FALSE,
    earned_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user_earned (user_id, is_earned),
    INDEX idx_achievement (achievement_id)
);

-- Insert achievement categories
INSERT INTO achievement_categories (name, display_name, description, icon) VALUES
('login_streak', 'Login Streaks', 'Achievements for maintaining consecutive login days', '🔥'),
('total_logins', 'Total Logins', 'Achievements for total number of login days', '📅'),
('habit_creation', 'Habit Creation', 'Achievements for creating habits', '🌱'),
('habit_completion', 'Habit Completion', 'Achievements for completing habits', '✅'),
('habit_streaks', 'Habit Streaks', 'Achievements for maintaining habit streaks', '🔗'),
('perfect_periods', 'Perfect Periods', 'Achievements for perfect completion periods', '🌞'),
('category_based', 'Category Based', 'Achievements based on habit categories', '💪'),
('special', 'Special', 'Special and unique achievements', '🌟');

-- Insert the new achievements based on our design
INSERT INTO achievements (achievement_key, name, description, icon, rarity, points, category_id, requirement_type, requirement_value, requirement_data) VALUES

-- Login Streak Achievements
('streak_starter', 'Streak Starter', 'Log in for 3 consecutive days', 'fas fa-fire', 'common', 10, 
 (SELECT id FROM achievement_categories WHERE name = 'login_streak'), 'login_streak', 3, NULL),

('streak_pro', 'Streak Pro', 'Log in for 7 consecutive days', 'fas fa-fire', 'uncommon', 25, 
 (SELECT id FROM achievement_categories WHERE name = 'login_streak'), 'login_streak', 7, NULL),

('streak_master', 'Streak Master', 'Log in for 30 consecutive days', 'fas fa-fire', 'rare', 100, 
 (SELECT id FROM achievement_categories WHERE name = 'login_streak'), 'login_streak', 30, NULL),

('streak_legend', 'Streak Legend', 'Log in for 100 consecutive days', 'fas fa-fire', 'epic', 300, 
 (SELECT id FROM achievement_categories WHERE name = 'login_streak'), 'login_streak', 100, NULL),

-- Total Login Days Achievements
('regular_visitor', 'Regular Visitor', 'Log in 10 times', 'fas fa-calendar-alt', 'common', 10, 
 (SELECT id FROM achievement_categories WHERE name = 'total_logins'), 'total_logins', 10, NULL),

('habitual_user', 'Habitual User', 'Log in 50 times', 'fas fa-calendar-alt', 'uncommon', 30, 
 (SELECT id FROM achievement_categories WHERE name = 'total_logins'), 'total_logins', 50, NULL),

('veteran', 'Veteran', 'Log in 200 times', 'fas fa-calendar-alt', 'rare', 150, 
 (SELECT id FROM achievement_categories WHERE name = 'total_logins'), 'total_logins', 200, NULL),

('lifetime_member', 'Lifetime Member', 'Log in 500 times', 'fas fa-calendar-alt', 'epic', 400, 
 (SELECT id FROM achievement_categories WHERE name = 'total_logins'), 'total_logins', 500, NULL),

-- Habit Creation Achievements
('getting_started', 'Getting Started', 'Create your first habit', 'fas fa-seedling', 'common', 5, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_creation'), 'habit_creation', 1, NULL),

('habit_builder', 'Habit Builder', 'Create 5 habits', 'fas fa-seedling', 'uncommon', 20, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_creation'), 'habit_creation', 5, NULL),

('habit_architect', 'Habit Architect', 'Create 20 habits', 'fas fa-seedling', 'rare', 60, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_creation'), 'habit_creation', 20, NULL),

('habit_tycoon', 'Habit Tycoon', 'Create 50 habits', 'fas fa-seedling', 'epic', 150, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_creation'), 'habit_creation', 50, NULL),

-- Habit Completion Achievements
('first_step', 'First Step', 'Complete a habit for the first time', 'fas fa-check', 'common', 5, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_completion'), 'habit_completion', 1, NULL),

('consistency_champ', 'Consistency Champ', 'Complete 100 habits', 'fas fa-check', 'uncommon', 40, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_completion'), 'habit_completion', 100, NULL),

('completionist', 'Completionist', 'Complete 500 habits', 'fas fa-check', 'rare', 120, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_completion'), 'habit_completion', 500, NULL),

('habit_hero', 'Habit Hero', 'Complete 1000 habits', 'fas fa-check', 'epic', 300, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_completion'), 'habit_completion', 1000, NULL),

-- Habit Streak Achievements
('mini_streak', 'Mini Streak', 'Complete a habit 7 days in a row', 'fas fa-link', 'uncommon', 20, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_streaks'), 'habit_streak', 7, NULL),

('mega_streak', 'Mega Streak', 'Complete a habit 30 days in a row', 'fas fa-link', 'rare', 80, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_streaks'), 'habit_streak', 30, NULL),

('ultimate_streak', 'Ultimate Streak', 'Complete a habit 100 days in a row', 'fas fa-link', 'epic', 200, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_streaks'), 'habit_streak', 100, NULL),

('streak_king', 'Streak King', 'Complete a habit 365 days in a row', 'fas fa-link', 'legendary', 1000, 
 (SELECT id FROM achievement_categories WHERE name = 'habit_streaks'), 'habit_streak', 365, NULL),

-- Perfect Day/Week/Month Achievements
('perfect_day', 'Perfect Day', 'Complete all habits in a day', 'fas fa-sun', 'uncommon', 15, 
 (SELECT id FROM achievement_categories WHERE name = 'perfect_periods'), 'perfect_day', 1, NULL),

('perfect_week', 'Perfect Week', 'Complete all habits every day for a week', 'fas fa-calendar-week', 'rare', 60, 
 (SELECT id FROM achievement_categories WHERE name = 'perfect_periods'), 'perfect_week', 1, NULL),

('perfect_month', 'Perfect Month', 'Complete all habits every day for a month', 'fas fa-trophy', 'epic', 200, 
 (SELECT id FROM achievement_categories WHERE name = 'perfect_periods'), 'perfect_month', 1, NULL),

('flawless_quarter', 'Flawless Quarter', 'Complete all habits every day for 3 months', 'fas fa-medal', 'legendary', 800, 
 (SELECT id FROM achievement_categories WHERE name = 'perfect_periods'), 'perfect_month', 3, NULL),

-- Category-Based Achievements
('fitness_fanatic', 'Fitness Fanatic', 'Complete 50 fitness habits', 'fas fa-dumbbell', 'uncommon', 30, 
 (SELECT id FROM achievement_categories WHERE name = 'category_based'), 'category_specific', 50, 
 JSON_OBJECT('category', 'fitness')),

('mindful_master', 'Mindful Master', 'Complete 50 mindfulness habits', 'fas fa-leaf', 'uncommon', 30, 
 (SELECT id FROM achievement_categories WHERE name = 'category_based'), 'category_specific', 50, 
 JSON_OBJECT('category', 'mindfulness')),

('productivity_pro', 'Productivity Pro', 'Complete 50 productivity habits', 'fas fa-chart-line', 'uncommon', 30, 
 (SELECT id FROM achievement_categories WHERE name = 'category_based'), 'category_specific', 50, 
 JSON_OBJECT('category', 'productivity')),

('balanced_life', 'Balanced Life', 'Complete 20 habits in each category', 'fas fa-balance-scale', 'rare', 100, 
 (SELECT id FROM achievement_categories WHERE name = 'category_based'), 'category_specific', 20, 
 JSON_OBJECT('requirement', 'all_categories')),

-- Special Achievements
('early_bird', 'Early Bird', 'Complete a habit before 7 AM', 'fas fa-sun', 'rare', 25, 
 (SELECT id FROM achievement_categories WHERE name = 'special'), 'special', 1, 
 JSON_OBJECT('time_condition', 'before_7am')),

('night_owl', 'Night Owl', 'Complete a habit after 10 PM', 'fas fa-moon', 'rare', 25, 
 (SELECT id FROM achievement_categories WHERE name = 'special'), 'special', 1, 
 JSON_OBJECT('time_condition', 'after_10pm')),

('comeback_kid', 'Comeback Kid', 'Resume a streak after breaking it', 'fas fa-redo', 'rare', 50, 
 (SELECT id FROM achievement_categories WHERE name = 'special'), 'special', 1, 
 JSON_OBJECT('requirement', 'resume_streak')),

('all_rounder', 'All-Rounder', 'Earn at least one achievement in every section', 'fas fa-star', 'legendary', 500, 
 (SELECT id FROM achievement_categories WHERE name = 'special'), 'special', 1, 
 JSON_OBJECT('requirement', 'all_sections'));

-- Create indexes for better performance
CREATE INDEX idx_user_achievements_progress ON user_achievements(user_id, current_progress);
CREATE INDEX idx_achievements_rarity ON achievements(rarity);
CREATE INDEX idx_user_achievements_earned_at ON user_achievements(earned_at);