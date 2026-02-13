-- debug_insert.sql
-- Test insert to identify the problematic field
-- Run this manually to see which field is causing the boolean error

-- First, let's see what the template looks like
SELECT id, title, description, content, duration, required_hours, category, status, is_template, is_locked, instructor_id
FROM courses 
WHERE title = 'MOT Class 1 & 2 Training' AND is_template = TRUE;

-- Try a minimal insert to see what fails
-- This mimics what createFromTemplate does
INSERT INTO courses 
(title, description, content, duration, required_hours, category, status, is_template, start_date, end_date, is_locked)
VALUES 
('Test Course', 'Test Description', NULL, 180, 3.00, 'Technical', 'published', FALSE, '2026-02-13', '2026-03-13', FALSE)
RETURNING id;

-- If the above works, the problem is with the template data
-- If it fails, there's a schema issue
