-- 06_add_performance_indexes.sql
-- Add indexes to improve query performance for dashboard and course progress tracking

-- User course progress indexes
CREATE INDEX IF NOT EXISTS idx_ucp_user_status ON user_course_progress(user_id, status);
CREATE INDEX IF NOT EXISTS idx_ucp_user_course ON user_course_progress(user_id, course_id);
CREATE INDEX IF NOT EXISTS idx_ucp_course ON user_course_progress(course_id);

-- Lessons indexes
CREATE INDEX IF NOT EXISTS idx_lessons_course ON lessons(course_id);

-- User lesson progress indexes
CREATE INDEX IF NOT EXISTS idx_ulp_user_lesson ON user_lesson_progress(user_id, lesson_id);
CREATE INDEX IF NOT EXISTS idx_ulp_lesson ON user_lesson_progress(lesson_id);
CREATE INDEX IF NOT EXISTS idx_ulp_user_status ON user_lesson_progress(user_id, status);

-- Courses indexes
CREATE INDEX IF NOT EXISTS idx_courses_template ON courses(is_template);

-- User attachments index
CREATE INDEX IF NOT EXISTS idx_attachments_user ON user_attachments(user_id);

-- Composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_ucp_user_status_updated ON user_course_progress(user_id, status, updated_at);
