-- 02_add_mot_templates.sql
-- Add MOT Course Templates

INSERT INTO courses (title, description, content, duration, required_hours, category, status, is_template, is_locked, instructor_id, created_at)
VALUES 
('MOT Class 1 & 2 Training', 'Annual training for MOT Class 1 and 2 testers (Motorcycles).', '<h3>MOT Class 1 & 2 Annual Training</h3><p>This course covers the current DVSA curriculum for motorcycle testers.</p>', 180, 3.0, 'Technical', 'published', TRUE, FALSE, 1, NOW()),
('MOT Class 4 & 7 Training', 'Annual training for MOT Class 4 and 7 testers (Cars and light commercial vehicles).', '<h3>MOT Class 4 & 7 Annual Training</h3><p>This course covers the current DVSA curriculum for car and van testers.</p>', 180, 3.0, 'Technical', 'published', TRUE, FALSE, 1, NOW()),
('MOT Tester Annual Assessment', 'Mandatory annual assessment for all MOT testers.', '<h3>Annual Assessment</h3><p>Complete this assessment to maintain your testing status.</p>', 60, 1.0, 'Compliance', 'published', TRUE, FALSE, 1, NOW());
