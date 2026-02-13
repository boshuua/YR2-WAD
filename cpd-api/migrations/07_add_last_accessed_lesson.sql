
-- 07_add_last_accessed_lesson.sql
-- Add column to track the last lesson a user accessed in a course
-- This enables the "Resume" functionality to take them back to where they left off

ALTER TABLE user_course_progress
ADD COLUMN IF NOT EXISTS last_accessed_lesson_id INT NULL;

-- Add foreign key constraint (optional but good practice)
ALTER TABLE user_course_progress
ADD CONSTRAINT fk_ucp_last_lesson FOREIGN KEY (last_accessed_lesson_id) REFERENCES lessons(id) ON DELETE SET NULL;

-- Add index for performance
CREATE INDEX IF NOT EXISTS idx_ucp_last_lesson ON user_course_progress(last_accessed_lesson_id);
