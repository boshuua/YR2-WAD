-- Migration to add assignment required fields

ALTER TABLE courses ADD COLUMN IF NOT EXISTS max_attendees INTEGER DEFAULT 20;

ALTER TABLE users ADD COLUMN IF NOT EXISTS failed_login_attempts INTEGER DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS lockout_until TIMESTAMP WITH TIME ZONE;
