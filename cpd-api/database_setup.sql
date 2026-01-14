-- ===============================================================
-- CPD PORTAL - COMPLETE DATABASE SETUP (UPDATED FOR MECHANIC TRAINING)
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
    content TEXT, -- General overview content
    duration INTEGER, -- in minutes
    required_hours DECIMAL(5,2) DEFAULT 3.00, -- e.g., 3 hours annual training
    category VARCHAR(100),
    status VARCHAR(20) DEFAULT 'draft', -- 'draft' or 'published'
    instructor_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 4. CREATE LESSONS TABLE
CREATE TABLE IF NOT EXISTS lessons (
    id SERIAL PRIMARY KEY,
    course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL, -- The training text
    order_index INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 5. CREATE QUESTIONS TABLE (UPDATED)
CREATE TABLE IF NOT EXISTS questions (
    id SERIAL PRIMARY KEY,
    course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
    lesson_id INTEGER REFERENCES lessons(id) ON DELETE CASCADE, -- If set, it's a checkpoint quiz for a lesson
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) DEFAULT 'multiple_choice', -- 'multiple_choice' or 'true_false'
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 6. CREATE QUESTION OPTIONS TABLE
CREATE TABLE IF NOT EXISTS question_options (
    id SERIAL PRIMARY KEY,
    question_id INTEGER REFERENCES questions(id) ON DELETE CASCADE,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 7. CREATE USER COURSE PROGRESS (ENROLLMENTS)
CREATE TABLE IF NOT EXISTS user_course_progress (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'not_started', -- 'not_started', 'in_progress', 'completed'
    score INTEGER DEFAULT 0, -- Final assessment score
    hours_completed DECIMAL(5,2) DEFAULT 0.00,
    enrolled_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    completion_date TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, course_id)
);

-- 8. CREATE USER LESSON PROGRESS
CREATE TABLE IF NOT EXISTS user_lesson_progress (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    lesson_id INTEGER REFERENCES lessons(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'not_started', -- 'not_started', 'in_progress', 'completed'
    completion_date TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, lesson_id)
);

-- 9. CREATE ACTIVITY LOG TABLE
CREATE TABLE IF NOT EXISTS activity_log (
    id SERIAL PRIMARY KEY,
    user_id INTEGER,
    user_email VARCHAR(100),
    action VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 10. INSERT INITIAL SEED DATA
-- Add Admin User (Email: admin@test.com, Password: admin123)
INSERT INTO users (first_name, last_name, email, password, job_title, access_level)
SELECT 'Admin', 'User', 'admin@test.com', crypt('admin123', gen_salt('bf')), 'Administrator', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@test.com');

-- Add Annual MOT Training Course 2024/25
INSERT INTO courses (title, description, duration, required_hours, category, status, start_date, end_date)
VALUES (
    'MOT Annual Training 2024/25 - Class 4 & 7', 
    'Official annual training covering Corrosion, Standards of Repair, and Vehicle Classification.', 
    180, 
    3.00, 
    'MOT Training', 
    'published', 
    '2024-04-01', 
    '2025-03-31'
);

-- Get the ID of the course we just created
DO $$
DECLARE
    v_course_id INTEGER;
    v_lesson_id INTEGER;
    v_question_id INTEGER;
BEGIN
    SELECT id INTO v_course_id FROM courses WHERE title = 'MOT Annual Training 2024/25 - Class 4 & 7' LIMIT 1;

    -- LESSON 1: Corrosion & Standards of Repair
    INSERT INTO lessons (course_id, title, content, order_index)
    VALUES (v_course_id, 'Section 1: Corrosion & Standards of Repair', 
    '<h3>Corrosion Assessment</h3><p>Corrosion is a major cause of MOT failure. Testers must be able to distinguish between surface corrosion and structural weakness.</p><h4>Key Areas to Check:</h4><ul><li>Prescribed areas (within 30cm of steering, suspension, or braking components).</li><li>Load-bearing structures.</li><li>Tow bar mountings.</li></ul><h4>Standards of Repair:</h4><p>Repairs to structural members must be by welding or by a method that is at least as strong as the original construction. Bonded repairs are generally not acceptable for structural parts unless specified by the manufacturer.</p>', 1)
    RETURNING id INTO v_lesson_id;

    -- Checkpoint Quiz for Lesson 1
    INSERT INTO questions (course_id, lesson_id, question_text)
    VALUES (v_course_id, v_lesson_id, 'Structural repairs to a vehicle must be made by:')
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Weld or a method at least as strong as original', TRUE),
    (v_question_id, 'Industrial adhesive/bonding', FALSE),
    (v_question_id, 'Pop rivets and sealant', FALSE),
    (v_question_id, 'Cable ties and tape', FALSE);

    -- LESSON 2: Vehicle Classification
    INSERT INTO lessons (course_id, title, content, order_index)
    VALUES (v_course_id, 'Section 2: Vehicle Classification', 
    '<h3>Class 4 vs Class 7</h3><p>Correctly identifying the vehicle class is critical for applying the correct test standards.</p><ul><li><strong>Class 4:</strong> Cars, motor caravans, and small goods vehicles (up to 3,000kg DGW).</li><li><strong>Class 7:</strong> Goods vehicles over 3,000kg up to 3,500kg DGW.</li></ul><p>If the weight is unknown, testers should refer to the vehicle plate or technical data. Converted vehicles, like motor caravans, must be tested as Class 4 regardless of weight, provided they meet the definition of a motor caravan.</p>', 2)
    RETURNING id INTO v_lesson_id;

    -- Checkpoint Quiz for Lesson 2
    INSERT INTO questions (course_id, lesson_id, question_text)
    VALUES (v_course_id, v_lesson_id, 'A goods vehicle with a Design Gross Weight (DGW) of 3,200kg is classified as:')
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Class 4', FALSE),
    (v_question_id, 'Class 5', FALSE),
    (v_question_id, 'Class 7', TRUE),
    (v_question_id, 'Class 1', FALSE);

    -- FINAL ASSESSMENT (Not linked to a specific lesson)
    INSERT INTO questions (course_id, question_text)
    VALUES (v_course_id, 'What is the maximum DGW for a Class 4 goods vehicle?')
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, '3,000kg', TRUE),
    (v_question_id, '3,500kg', FALSE),
    (v_question_id, '2,500kg', FALSE),
    (v_question_id, '4,000kg', FALSE);

END $$;