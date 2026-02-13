-- debug_template_data.sql
-- Check what the template data actually contains
SELECT 
    id,
    title,
    description,
    CASE WHEN description = '' THEN 'EMPTY STRING' ELSE 'HAS VALUE' END as desc_status,
    content,
    CASE WHEN content = '' THEN 'EMPTY STRING' 
         WHEN content IS NULL THEN 'NULL' 
         ELSE 'HAS VALUE' END as content_status,
    category,
    CASE WHEN category = '' THEN 'EMPTY STRING' ELSE 'HAS VALUE' END as cat_status,
    duration,
    required_hours,
    is_template,
    is_locked,
    instructor_id
FROM courses 
WHERE is_template = TRUE;
