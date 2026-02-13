-- cleanup_course_content.sql
-- Removes HTML content from courses.content column
-- Run this AFTER 04_add_mot_lessons_questions.sql has been executed

-- Clear content from all MOT templates (data is now in lessons table)
UPDATE courses 
SET content = NULL 
WHERE is_template = TRUE 
AND title LIKE 'MOT%';

-- Optionally, clear content from all courses if you want
-- Uncomment the line below if needed:
-- UPDATE courses SET content = NULL;
