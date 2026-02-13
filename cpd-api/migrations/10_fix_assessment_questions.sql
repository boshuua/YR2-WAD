-- Fix existing assessment questions that have incorrect lesson_id values
-- This ensures assessment questions are visible to the quiz component

-- Update all questions for assessment courses to have lesson_id = NULL
UPDATE questions
SET lesson_id = NULL
WHERE course_id IN (
    SELECT id FROM courses WHERE category = 'Assessment'
);
