-- Migration Script: Add Missing Fields for Assignment Requirements
-- 1. Max Attendees
ALTER TABLE courses ADD COLUMN max_attendees INTEGER DEFAULT 0;

-- 2. Login Lockouts
ALTER TABLE users ADD COLUMN failed_login_attempts INTEGER DEFAULT 0;
ALTER TABLE users ADD COLUMN lockout_until TIMESTAMP;
