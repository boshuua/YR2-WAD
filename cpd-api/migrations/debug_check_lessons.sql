-- Check if lessons exist for scheduled courses
SELECT 
    c.id as course_id,
    c.title as course_title,
    c.is_template,
    COUNT(l.id) as lesson_count
FROM courses c
LEFT JOIN lessons l ON c.id = l.course_id
WHERE c.is_template = FALSE
GROUP BY c.id, c.title, c.is_template
ORDER BY c.created_at DESC
LIMIT 10;

-- Check lessons for templates
SELECT 
    c.id as course_id,
    c.title as course_title,
    c.is_template,
    COUNT(l.id) as lesson_count
FROM courses c
LEFT JOIN lessons l ON c.id = l.course_id
WHERE c.is_template = TRUE
GROUP BY c.id, c.title, c.is_template;
