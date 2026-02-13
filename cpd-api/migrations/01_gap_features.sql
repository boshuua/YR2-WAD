-- Migration to add missing columns for Requirements Gap Filling

-- 1. Add max_attendees to courses
ALTER TABLE courses ADD COLUMN IF NOT EXISTS max_attendees INTEGER DEFAULT 20;

-- 2. Add login security columns to users
ALTER TABLE users ADD COLUMN IF NOT EXISTS failed_login_attempts INTEGER DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS lockout_until TIMESTAMP WITH TIME ZONE NULL;

-- 3. Add email_sent flag to user_progress (optional, for tracking)
-- ALTER TABLE user_course_progress ADD COLUMN IF NOT EXISTS welcome_email_sent BOOLEAN DEFAULT FALSE;
