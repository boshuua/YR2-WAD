-- ===============================================================
-- CPD PORTAL - COMPLETE DATABASE SETUP
-- This is a consolidated script for setting up the database from scratch.
-- It includes the schema, extensions, and initial seed data.
-- ===============================================================

BEGIN;

-- 1. ENABLE EXTENSIONS
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- 2. CREATE SEQUENCES
CREATE SEQUENCE IF NOT EXISTS public.activity_log_id_seq;
CREATE SEQUENCE IF NOT EXISTS public.users_id_seq;
CREATE SEQUENCE IF NOT EXISTS public.courses_id_seq;
CREATE SEQUENCE IF NOT EXISTS public.course_cohorts_id_seq;
CREATE SEQUENCE IF NOT EXISTS public.lessons_id_seq;
CREATE SEQUENCE IF NOT EXISTS public.questions_id_seq;
CREATE SEQUENCE IF NOT EXISTS public.question_options_id_seq;
CREATE SEQUENCE IF NOT EXISTS public.user_attachments_id_seq;
CREATE SEQUENCE IF NOT EXISTS public.user_course_progress_id_seq;
CREATE SEQUENCE IF NOT EXISTS public.user_lesson_progress_id_seq;

-- 3. CREATE TABLES
-- Table: public.activity_log
CREATE TABLE IF NOT EXISTS public.activity_log ( id integer NOT NULL DEFAULT nextval('activity_log_id_seq'::regclass), user_id integer, user_email character varying(100) COLLATE pg_catalog."default", action character varying(100) COLLATE pg_catalog."default", details text COLLATE pg_catalog."default", ip_address character varying(45) COLLATE pg_catalog."default", "timestamp" timestamp with time zone DEFAULT CURRENT_TIMESTAMP, CONSTRAINT activity_log_pkey PRIMARY KEY (id) );

-- Table: public.users
CREATE TABLE IF NOT EXISTS public.users ( id integer NOT NULL DEFAULT nextval('users_id_seq'::regclass), first_name character varying(50) COLLATE pg_catalog."default" NOT NULL, last_name character varying(50) COLLATE pg_catalog."default" NOT NULL, email character varying(100) COLLATE pg_catalog."default" NOT NULL, password text COLLATE pg_catalog."default" NOT NULL, job_title character varying(100) COLLATE pg_catalog."default", access_level character varying(20) COLLATE pg_catalog."default" DEFAULT 'user'::character varying, created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, failed_login_attempts integer DEFAULT 0, lockout_until timestamp with time zone, requires_password_reset boolean DEFAULT false, CONSTRAINT users_pkey PRIMARY KEY (id), CONSTRAINT users_email_key UNIQUE (email) );

-- Table: public.courses
CREATE TABLE IF NOT EXISTS public.courses ( id integer NOT NULL DEFAULT nextval('courses_id_seq'::regclass), title character varying(255) COLLATE pg_catalog."default" NOT NULL, description text COLLATE pg_catalog."default", content text COLLATE pg_catalog."default", duration integer, category character varying(100) COLLATE pg_catalog."default", status character varying(20) COLLATE pg_catalog."default" DEFAULT 'draft'::character varying, instructor_id integer, created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, required_hours numeric(5,2) DEFAULT 3.00, updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, is_locked boolean DEFAULT false, duration_minutes integer DEFAULT 60, is_template boolean DEFAULT false, start_date date, end_date date, max_attendees integer DEFAULT 20, CONSTRAINT courses_pkey PRIMARY KEY (id), CONSTRAINT courses_instructor_id_fkey FOREIGN KEY (instructor_id) REFERENCES public.users (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE NO ACTION );

-- Table: public.course_cohorts
CREATE TABLE IF NOT EXISTS public.course_cohorts ( id integer NOT NULL DEFAULT nextval('course_cohorts_id_seq'::regclass), course_id integer, name character varying(255) COLLATE pg_catalog."default" NOT NULL, start_date date NOT NULL, end_date date NOT NULL, max_attendees integer DEFAULT 20, status character varying(20) COLLATE pg_catalog."default" DEFAULT 'published'::character varying, created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, CONSTRAINT course_cohorts_pkey PRIMARY KEY (id), CONSTRAINT course_cohorts_course_id_fkey FOREIGN KEY (course_id) REFERENCES public.courses (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE CASCADE );

-- Table: public.lessons
CREATE TABLE IF NOT EXISTS public.lessons ( id integer NOT NULL DEFAULT nextval('lessons_id_seq'::regclass), course_id integer, title character varying(255) COLLATE pg_catalog."default" NOT NULL, content text COLLATE pg_catalog."default" NOT NULL, order_index integer DEFAULT 0, created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, CONSTRAINT lessons_pkey PRIMARY KEY (id), CONSTRAINT lessons_course_id_fkey FOREIGN KEY (course_id) REFERENCES public.courses (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE CASCADE );
CREATE INDEX IF NOT EXISTS idx_lessons_course ON public.lessons USING btree (course_id ASC NULLS LAST) WITH (fillfactor=100, deduplicate_items=True);

-- Table: public.questions
CREATE TABLE IF NOT EXISTS public.questions ( id integer NOT NULL DEFAULT nextval('questions_id_seq'::regclass), course_id integer, lesson_id integer, question_text text COLLATE pg_catalog."default" NOT NULL, question_type character varying(50) COLLATE pg_catalog."default" DEFAULT 'multiple_choice'::character varying, created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, CONSTRAINT questions_pkey PRIMARY KEY (id), CONSTRAINT questions_course_id_fkey FOREIGN KEY (course_id) REFERENCES public.courses (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE CASCADE, CONSTRAINT questions_lesson_id_fkey FOREIGN KEY (lesson_id) REFERENCES public.lessons (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE CASCADE );

-- Table: public.question_options
CREATE TABLE IF NOT EXISTS public.question_options ( id integer NOT NULL DEFAULT nextval('question_options_id_seq'::regclass), question_id integer, option_text text COLLATE pg_catalog."default" NOT NULL, is_correct boolean DEFAULT false, created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, CONSTRAINT question_options_pkey PRIMARY KEY (id), CONSTRAINT question_options_question_id_fkey FOREIGN KEY (question_id) REFERENCES public.questions (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE CASCADE );

-- Table: public.system_settings
CREATE TABLE IF NOT EXISTS public.system_settings ( setting_key character varying(100) COLLATE pg_catalog."default" NOT NULL, setting_value text COLLATE pg_catalog."default" NOT NULL, description character varying(255) COLLATE pg_catalog."default", updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP, CONSTRAINT system_settings_pkey PRIMARY KEY (setting_key) );

-- Table: public.user_attachments
CREATE TABLE IF NOT EXISTS public.user_attachments ( id integer NOT NULL DEFAULT nextval('user_attachments_id_seq'::regclass), user_id integer, file_name character varying(255) COLLATE pg_catalog."default" NOT NULL, file_path text COLLATE pg_catalog."default" NOT NULL, file_type character varying(50) COLLATE pg_catalog."default", created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, CONSTRAINT user_attachments_pkey PRIMARY KEY (id), CONSTRAINT user_attachments_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE CASCADE );
CREATE INDEX IF NOT EXISTS idx_attachments_user ON public.user_attachments USING btree (user_id ASC NULLS LAST) WITH (fillfactor=100, deduplicate_items=True);

-- Table: public.user_course_progress
CREATE TABLE IF NOT EXISTS public.user_course_progress ( id integer NOT NULL DEFAULT nextval('user_course_progress_id_seq'::regclass), user_id integer, course_id integer, status character varying(20) COLLATE pg_catalog."default" DEFAULT 'not_started'::character varying, enrolled_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, completion_date timestamp with time zone, updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, hours_completed numeric(5,2) DEFAULT 0.00, last_accessed_lesson_id integer, score integer DEFAULT 0, cohort_id integer, CONSTRAINT user_course_progress_pkey PRIMARY KEY (id), CONSTRAINT user_course_progress_user_id_cohort_id_key UNIQUE (user_id, cohort_id), CONSTRAINT fk_ucp_last_lesson FOREIGN KEY (last_accessed_lesson_id) REFERENCES public.lessons (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE SET NULL, CONSTRAINT user_course_progress_cohort_id_fkey FOREIGN KEY (cohort_id) REFERENCES public.course_cohorts (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE CASCADE, CONSTRAINT user_course_progress_course_id_fkey FOREIGN KEY (course_id) REFERENCES public.courses (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE CASCADE, CONSTRAINT user_course_progress_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE CASCADE );
CREATE INDEX IF NOT EXISTS idx_ucp_course ON public.user_course_progress USING btree (course_id ASC NULLS LAST) WITH (fillfactor=100, deduplicate_items=True);
CREATE INDEX IF NOT EXISTS idx_ucp_last_lesson ON public.user_course_progress USING btree (last_accessed_lesson_id ASC NULLS LAST) WITH (fillfactor=100, deduplicate_items=True);
CREATE INDEX IF NOT EXISTS idx_ucp_user_course ON public.user_course_progress USING btree (user_id ASC NULLS LAST, course_id ASC NULLS LAST) WITH (fillfactor=100, deduplicate_items=True);
CREATE INDEX IF NOT EXISTS idx_ucp_user_status ON public.user_course_progress USING btree (user_id ASC NULLS LAST, status COLLATE pg_catalog."default" ASC NULLS LAST) WITH (fillfactor=100, deduplicate_items=True);
CREATE INDEX IF NOT EXISTS idx_ucp_user_status_updated ON public.user_course_progress USING btree (user_id ASC NULLS LAST, status COLLATE pg_catalog."default" ASC NULLS LAST, updated_at ASC NULLS LAST) WITH (fillfactor=100, deduplicate_items=True);

-- Table: public.user_lesson_progress
CREATE TABLE IF NOT EXISTS public.user_lesson_progress ( id integer NOT NULL DEFAULT nextval('user_lesson_progress_id_seq'::regclass), user_id integer, lesson_id integer NOT NULL, status character varying(20) COLLATE pg_catalog."default" DEFAULT 'not_started'::character varying, completion_date timestamp with time zone, updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP, CONSTRAINT user_lesson_progress_pkey PRIMARY KEY (id), CONSTRAINT user_lesson_progress_user_id_lesson_id_key UNIQUE (user_id, lesson_id), CONSTRAINT user_lesson_progress_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users (id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE CASCADE );
CREATE INDEX IF NOT EXISTS idx_ulp_lesson ON public.user_lesson_progress USING btree (lesson_id ASC NULLS LAST) WITH (fillfactor=100, deduplicate_items=True);
CREATE INDEX IF NOT EXISTS idx_ulp_user_lesson ON public.user_lesson_progress USING btree (user_id ASC NULLS LAST, lesson_id ASC NULLS LAST) WITH (fillfactor=100, deduplicate_items=True);
CREATE INDEX IF NOT EXISTS idx_ulp_user_status ON public.user_lesson_progress USING btree (user_id ASC NULLS LAST, status COLLATE pg_catalog."default" ASC NULLS LAST) WITH (fillfactor=100, deduplicate_items=True);

-- 5. INSERT SEED DATA
-- Add Admin User
INSERT INTO users (first_name, last_name, email, password, job_title, access_level)
SELECT 'Admin', 'User', 'admin@test.com', crypt('admin123', gen_salt('bf')), 'Administrator', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@test.com');

-- System Settings
INSERT INTO system_settings (setting_key, setting_value, description)
VALUES
    ('site_name', 'CPD Portal', 'Global Platform Name'),
    ('support_email', 'support@cpd-portal.local', 'Contact email for support inquiries'),
    ('enable_welcome_emails', 'true', 'Send email to new users upon registration (true/false)'),
    ('enable_password_reset_emails', 'true', 'Send approval emails for password resets (true/false)'),
    ('default_access_level', 'user', 'Default role for new signups (user/manager/admin)'),
    ('maintenance_mode', 'false', 'Lock out non-admin users (true/false)')
ON CONFLICT (setting_key) DO NOTHING;

-- Course Templates and Content
DO $$
DECLARE
    v_course_id INTEGER;
    v_lesson_id INTEGER;
    v_question_id INTEGER;
    v_training_id INTEGER;
    v_assessment_id INTEGER;
    v_c1_assessment_id INTEGER;
    v_q_id INTEGER;
BEGIN
    INSERT INTO courses (title, description, duration, required_hours, category, status, is_template, is_locked)
    SELECT 'MOT Class 1 & 2 Training', 'Annual training for MOT Class 1 and 2 testers (Motorcycles).', 180, 3.0, 'Technical', 'published', TRUE, TRUE
    WHERE NOT EXISTS (SELECT 1 FROM courses WHERE title = 'MOT Class 1 & 2 Training' AND is_template = TRUE);

    INSERT INTO courses (title, description, duration, required_hours, category, status, is_template, is_locked)
    SELECT 'MOT Class 4 & 7 Training', 'Annual training for MOT Class 4 and 7 testers (Cars and light commercial vehicles).', 180, 3.0, 'Technical', 'published', TRUE, TRUE
    WHERE NOT EXISTS (SELECT 1 FROM courses WHERE title = 'MOT Class 4 & 7 Training' AND is_template = TRUE);

    INSERT INTO courses (title, description, duration, required_hours, category, status, is_template, is_locked)
    SELECT 'MOT Class 1 & 2 Annual Assessment', 'Mandatory annual assessment for all MOT Class 1 and 2 testers.', 60, 1.0, 'Assessment', 'published', TRUE, TRUE
    WHERE NOT EXISTS (SELECT 1 FROM courses WHERE title = 'MOT Class 1 & 2 Annual Assessment' AND is_template = TRUE);
    
    INSERT INTO courses (title, description, duration, required_hours, category, status, is_template, is_locked)
    SELECT 'MOT Class 4 & 7 Annual Assessment', 'Mandatory annual assessment for all MOT Class 4 and 7 testers.', 60, 1.0, 'Assessment', 'published', TRUE, TRUE
    WHERE NOT EXISTS (SELECT 1 FROM courses WHERE title = 'MOT Class 4 & 7 Annual Assessment' AND is_template = TRUE);

    INSERT INTO courses (title, description, duration, required_hours, category, status, is_template, is_locked)
    SELECT 'Hybrid and Electric Vehicle Safety Awareness', 'Essential safety procedures for working near or on hybrid and electric vehicles.', 120, 2.0, 'Technical', 'published', TRUE, FALSE
    WHERE NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Hybrid and Electric Vehicle Safety Awareness' AND is_template = TRUE);

    INSERT INTO courses (title, description, duration, required_hours, category, status, is_template, is_locked)
    SELECT 'Introduction to ADAS', 'An introduction to Advanced Driver-Assistance Systems (ADAS).', 90, 1.5, 'Technical', 'published', TRUE, FALSE
    WHERE NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Introduction to ADAS' AND is_template = TRUE);

    -- Add Lessons for MOT Class 1 & 2
    SELECT id INTO v_course_id FROM courses WHERE title = 'MOT Class 1 & 2 Training' AND is_template = TRUE LIMIT 1;
    IF v_course_id IS NOT NULL THEN
        DELETE FROM lessons WHERE course_id = v_course_id;
        INSERT INTO lessons (course_id, title, content, order_index) VALUES 
        (v_course_id, 'Introduction – The MOT Club', '<h3>Introduction</h3><p>Welcome to the MOT Club training portal for Class 1 & 2.</p>', 1),
        (v_course_id, 'MOT Tester Annual Training Topics 2025-2026', '<h3>Training Topics</h3><p>This year covers Electric/Hybrid Vehicles, Rider Controls, and DVSA Guide updates.</p>', 2),
        (v_course_id, 'Navigating DVSA Documents', '<h3>DVSA Documents</h3><p>Familiarize yourself with the MOT Testing Guide and Inspection Manual.</p>', 3);
    END IF;

    -- Add Lessons for MOT Class 4 & 7
    SELECT id INTO v_training_id FROM courses WHERE title = 'MOT Class 4 & 7 Training' AND is_template = TRUE;
    IF v_training_id IS NOT NULL THEN
        DELETE FROM lessons WHERE course_id = v_training_id;
        INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Introduction – The MOT Club', '<h3>Introduction</h3><p>Welcome to the MOT Class 4 & 7 Annual Training for 2025-2026.</p>', 1);
        INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'MOT Tester Annual Training Topics 2025-2026', '<h3>Annual Training Topics</h3><p>Focuses on Electric/Hybrid Vehicles, MOT Guide Info, and Test Procedures.</p>', 2);
        INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Headlamp Alignment', '<h3>Headlamps</h3><p>Setting up the beam tester and checking alignment tolerances.</p>', 3);
    END IF;
    
    -- Add Questions for MOT Class 1 & 2 Assessment
    SELECT id INTO v_c1_assessment_id FROM courses WHERE title = 'MOT Class 1 & 2 Annual Assessment' AND is_template = TRUE;
    IF v_c1_assessment_id IS NOT NULL THEN
        DELETE FROM questions WHERE course_id = v_c1_assessment_id;
        
        INSERT INTO questions (course_id, question_text) VALUES (v_c1_assessment_id, 'A motorcycle''s drive chain is correctly tensioned if the total up and down movement is:') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '10-15mm', FALSE), (v_q_id, '25-35mm', TRUE), (v_q_id, '45-55mm', FALSE);

        INSERT INTO questions (course_id, question_text) VALUES (v_c1_assessment_id, 'When must a motorcycle''s daytime running lamp extinguish?') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'When the engine is off', FALSE), (v_q_id, 'When the position lamps are switched on', FALSE), (v_q_id, 'When the headlamp is switched on', TRUE);

        INSERT INTO questions (course_id, question_text) VALUES (v_c1_assessment_id, 'A defect for a brake hose being "fouling" means it is:') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Likely to become trapped or damaged by other components', TRUE), (v_q_id, 'Leaking fluid', FALSE), (v_q_id, 'Swollen or bulging', FALSE);

        INSERT INTO questions (course_id, question_text) VALUES (v_c1_assessment_id, 'What is the minimum legal tread depth for a motorcycle tyre?') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '1.6mm', FALSE), (v_q_id, '1.0mm over the central 3/4 of the tread', TRUE), (v_q_id, '0.8mm', FALSE);
        
        INSERT INTO questions (course_id, question_text) VALUES (v_c1_assessment_id, 'A steering damper is considered a defect if:') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'It is fitted at all', FALSE), (v_q_id, 'It is leaking fluid', TRUE), (v_q_id, 'It is adjustable', FALSE);

        INSERT INTO questions (course_id, question_text) VALUES (v_c1_assessment_id, 'Which of these is a reason for failing a motorcycle exhaust system?') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'It is not the original manufacturer part', FALSE), (v_q_id, 'It is marked "Not for road use"', TRUE), (v_q_id, 'It has surface discolouration', FALSE);

        INSERT INTO questions (course_id, question_text) VALUES (v_c1_assessment_id, 'A sidecar wheel must be checked for which defect?') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Incorrect tyre pressure', FALSE), (v_q_id, 'Excessive bearing play', TRUE), (v_q_id, 'Non-matching tyre', FALSE);

        INSERT INTO questions (course_id, question_text) VALUES (v_c1_assessment_id, 'A motorcycle horn must be:') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'A continuous single tone', TRUE), (v_q_id, 'A multi-tone novelty horn', FALSE), (v_q_id, 'Louder than 110 decibels', FALSE);
        
        INSERT INTO questions (course_id, question_text) VALUES (v_c1_assessment_id, 'A missing passenger footrest on a bike with a pillion seat is:') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'An advisory', FALSE), (v_q_id, 'A major defect', TRUE), (v_q_id, 'Not a testable item', FALSE);
    END IF;

    -- Add Questions for MOT Class 4 & 7 Assessment
    SELECT id INTO v_assessment_id FROM courses WHERE title = 'MOT Class 4 & 7 Annual Assessment' AND is_template = TRUE;
    IF v_assessment_id IS NOT NULL THEN
        DELETE FROM questions WHERE course_id = v_assessment_id;
        
        INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'What is the minimum brake efficiency requirement for the service brake on a Class 4 vehicle?') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '45%', FALSE), (v_q_id, '50%', TRUE), (v_q_id, '55%', FALSE);
        
        INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'High voltage cables in electric vehicles are usually which color?') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Red', FALSE), (v_q_id, 'Blue', FALSE), (v_q_id, 'Orange', TRUE);

        INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'A "prescribed area" for corrosion inspection is within what distance of a load-bearing component?') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '15cm', FALSE), (v_q_id, '30cm', TRUE), (v_q_id, '50cm', FALSE);

        INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'A Tyre Pressure Monitoring System (TPMS) warning light illuminated is a:') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Minor defect', FALSE), (v_q_id, 'Major defect', TRUE), (v_q_id, 'Advisory', FALSE);

        INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'Which of these is a reason for rejecting a vehicle''s registration plate?') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'It has a non-reflective border', FALSE), (v_q_id, 'It has a screw in a character, obscuring it', TRUE), (v_q_id, 'It has a national flag emblem', FALSE);
        
        INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'A diesel vehicle fails its emissions test if the smoke reading is above:') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'The manufacturer''s plate value or default limit', TRUE), (v_q_id, '1.5 m-1 regardless of age', FALSE), (v_q_id, '3.0 m-1 for all turbo-diesels', FALSE);

        INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'When checking a steering lock, you should verify that:') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'It can be removed by force', FALSE), (v_q_id, 'It engages and disengages correctly', TRUE), (v_q_id, 'The key is original', FALSE);

        INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'A cracked windscreen is a major defect if the crack is:') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'More than 10mm wide in Zone A', TRUE), (v_q_id, 'Anywhere on the passenger side', FALSE), (v_q_id, 'Longer than 50mm in Zone B', FALSE);

        INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'A ball joint is considered to have excessive play if:') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'There is any perceptible movement', FALSE), (v_q_id, 'Movement is more than specified in the manual', TRUE), (v_q_id, 'It makes a noise on turning', FALSE);

        INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'An airbag warning light that stays on is a:') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Major defect', TRUE), (v_q_id, 'Minor defect', FALSE), (v_q_id, 'Pass with advisory', FALSE);
    END IF;

    -- Add Questions for Hybrid and EV Safety Course
    SELECT id INTO v_course_id FROM courses WHERE title = 'Hybrid and Electric Vehicle Safety Awareness' AND is_template = TRUE LIMIT 1;
    IF v_course_id IS NOT NULL THEN
        DELETE FROM lessons WHERE course_id = v_course_id;
        INSERT INTO lessons (course_id, title, content, order_index) 
        VALUES (v_course_id, 'Introduction to EV/PHEV', '<h3>What are EVs and PHEVs?</h3><p>Covering the basic architecture and component locations.</p>', 1);
        DELETE FROM questions WHERE course_id = v_course_id;
        INSERT INTO questions (course_id, question_text) VALUES (v_course_id, 'What is the minimum safe waiting time after removing the service disconnect on an EV?') RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '1 minute', FALSE), (v_q_id, '10 minutes', TRUE);
    END IF;

END $$;

-- 6. FINAL CLEANUP
UPDATE questions SET lesson_id = NULL WHERE course_id IN (SELECT id FROM courses WHERE category = 'Assessment');

UPDATE courses SET content = NULL WHERE is_template = TRUE AND title LIKE 'MOT%';

COMMIT;
