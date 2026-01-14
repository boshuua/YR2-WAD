-- ===============================================================
-- CPD PORTAL - COMPLETE DATABASE SETUP
-- Includes: Schema, Extensions, and Initial Seed Data
-- ===============================================================

-- 1. ENABLE EXTENSIONS
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- 2. CREATE USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password TEXT NOT NULL,
    job_title VARCHAR(100),
    access_level VARCHAR(20) DEFAULT 'user', -- 'admin' or 'user'
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 3. CREATE COURSES TABLE
CREATE TABLE IF NOT EXISTS courses (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content TEXT,
    duration INTEGER, -- in minutes
    category VARCHAR(100),
    status VARCHAR(20) DEFAULT 'draft', -- 'draft' or 'published'
    instructor_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 4. CREATE QUESTIONS TABLE
CREATE TABLE IF NOT EXISTS questions (
    id SERIAL PRIMARY KEY,
    course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) DEFAULT 'multiple_choice', -- 'multiple_choice' or 'true_false'
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 5. CREATE QUESTION OPTIONS TABLE
CREATE TABLE IF NOT EXISTS question_options (
    id SERIAL PRIMARY KEY,
    question_id INTEGER REFERENCES questions(id) ON DELETE CASCADE,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 6. CREATE USER COURSE PROGRESS (ENROLLMENTS)
CREATE TABLE IF NOT EXISTS user_course_progress (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'not_started', -- 'not_started', 'in_progress', 'completed'
    score INTEGER DEFAULT 0,
    enrolled_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    completion_date TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, course_id)
);

-- 7. CREATE USER LESSON PROGRESS
CREATE TABLE IF NOT EXISTS user_lesson_progress (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    lesson_id INTEGER NOT NULL,
    status VARCHAR(20) DEFAULT 'not_started', -- 'not_started', 'in_progress', 'completed'
    completion_date TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, lesson_id)
);

-- 8. CREATE ACTIVITY LOG TABLE
CREATE TABLE IF NOT EXISTS activity_log (
    id SERIAL PRIMARY KEY,
    user_id INTEGER,
    user_email VARCHAR(100),
    action VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 9. INSERT INITIAL SEED DATA
-- Add Admin User (Email: admin@test.com, Password: admin123)
-- Using gen_salt for better security as per your add_users.sql logic
INSERT INTO users (first_name, last_name, email, password, job_title, access_level)
SELECT 'Admin', 'User', 'admin@test.com', crypt('admin123', gen_salt('bf')), 'Administrator', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@test.com');

-- Add Sample Course
INSERT INTO courses (title, description, duration, category, status, start_date, end_date)
SELECT 'Introduction to CPD', 'Learn how to use the Professional Development Portal effectively.', 30, 'General', 'published', CURRENT_DATE, CURRENT_DATE + INTERVAL '1 year'
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Introduction to CPD');
