-- Populate missing streak records for all habits

-- Insert missing habit_streaks records for habits that don't have them yet
INSERT INTO habit_streaks (habit_id, streak, last_completion_date)
SELECT h.id, 0, NULL
FROM habits h
LEFT JOIN habit_streaks hs ON h.id = hs.habit_id
WHERE hs.habit_id IS NULL;

-- Show all habit_streaks records after insertion
SELECT * FROM habit_streaks ORDER BY habit_id;