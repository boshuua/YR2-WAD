-- 02_add_mot_templates.sql
-- Add MOT Course Templates (prevents duplicates)

INSERT INTO courses (title, description, content, duration, required_hours, category, status, is_template, is_locked, instructor_id, created_at)
SELECT 'MOT Class 1 & 2 Training', 'Annual training for MOT Class 1 and 2 testers (Motorcycles).', '<h3>MOT Class 1 & 2 Annual Training</h3><p>This course covers the current DVSA curriculum for motorcycle testers.</p>', 180, 3.0, 'Technical', 'published', TRUE, FALSE, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE title = 'MOT Class 1 & 2 Training' AND is_template = TRUE);

INSERT INTO courses (title, description, content, duration, required_hours, category, status, is_template, is_locked, instructor_id, created_at)
SELECT 'MOT Class 4 & 7 Training', 'Annual training for MOT Class 4 and 7 testers (Cars and light commercial vehicles).', '<h3>MOT Class 4 & 7 Annual Training</h3><p>This course covers the current DVSA curriculum for car and van testers.</p>', 180, 3.0, 'Technical', 'published', TRUE, FALSE, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE title = 'MOT Class 4 & 7 Training' AND is_template = TRUE);

INSERT INTO courses (title, description, content, duration, required_hours, category, status, is_template, is_locked, instructor_id, created_at)
SELECT 'MOT Tester Annual Assessment', 'Mandatory annual assessment for all MOT testers.', '<h3>Annual Assessment</h3><p>Complete this assessment to maintain your testing status.</p>', 60, 1.0, 'Compliance', 'published', TRUE, FALSE, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE title = 'MOT Tester Annual Assessment' AND is_template = TRUE);
