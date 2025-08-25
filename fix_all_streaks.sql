-- Fix all streaks based on actual completion data

-- Update existing records and insert missing ones with correct streak values

-- Habit 1: Watering - should be 2 days (2025-08-23, 2025-08-24)
UPDATE habit_streaks SET streak = 2, last_completion_date = '2025-08-24' WHERE habit_id = 1;

-- Habit 2: Projects - missing, should be 2 days (2025-08-23, 2025-08-24)
INSERT INTO habit_streaks (habit_id, streak, last_completion_date) VALUES (2, 2, '2025-08-24');

-- Habit 3: LinkedIn - already correct at 2 days
-- No change needed

-- Habit 4: Skin routine - already correct at 2 days  
-- No change needed

-- Habit 5: Yoga - should be 2 days (2025-08-23, 2025-08-24)
UPDATE habit_streaks SET streak = 2, last_completion_date = '2025-08-24' WHERE habit_id = 5;

-- Habit 6: Aptitude - should be 1 day (2025-08-24 only)
UPDATE habit_streaks SET streak = 1, last_completion_date = '2025-08-24' WHERE habit_id = 6;

-- Show the corrected data
SELECT * FROM habit_streaks ORDER BY habit_id;