-- cleanup_duplicate_templates.sql
-- Remove duplicate MOT templates, keeping only one of each

-- Delete duplicate MOT Class 1 & 2 Training templates (keep the lowest ID)
DELETE FROM courses 
WHERE title = 'MOT Class 1 & 2 Training' 
  AND is_template = TRUE 
  AND id NOT IN (
    SELECT MIN(id) 
    FROM courses 
    WHERE title = 'MOT Class 1 & 2 Training' AND is_template = TRUE
  );

-- Delete duplicate MOT Class 4 & 7 Training templates (keep the lowest ID)
DELETE FROM courses 
WHERE title = 'MOT Class 4 & 7 Training' 
  AND is_template = TRUE 
  AND id NOT IN (
    SELECT MIN(id) 
    FROM courses 
    WHERE title = 'MOT Class 4 & 7 Training' AND is_template = TRUE
  );

-- Delete duplicate MOT Tester Annual Assessment templates (keep the lowest ID)
DELETE FROM courses 
WHERE title = 'MOT Tester Annual Assessment' 
  AND is_template = TRUE 
  AND id NOT IN (
    SELECT MIN(id) 
    FROM courses 
    WHERE title = 'MOT Tester Annual Assessment' AND is_template = TRUE
  );

-- Verify remaining templates
SELECT id, title, is_template, is_locked, created_at 
FROM courses 
WHERE title LIKE 'MOT%' AND is_template = TRUE
ORDER BY title, id;
