-- 1. Fix the HTML entity issue in course titles
UPDATE courses 
SET title = REPLACE(title, '&amp;', 'and') 
WHERE title LIKE '%&amp;%';

UPDATE courses 
SET title = REPLACE(title, '&', 'and') 
WHERE title LIKE '%&%';

-- 2. Add more questions and answers to the MOT Class 4 and 7 Assessment
DO $$
DECLARE
    v_assessment_id INT;
    v_q_id INT;
BEGIN
    -- Find the MOT Class 4 and 7 Assessment template
    SELECT id INTO v_assessment_id FROM courses WHERE title LIKE '%MOT Class 4 and 7 Annual Assessment%' AND is_template = TRUE LIMIT 1;
    
    IF v_assessment_id IS NOT NULL THEN
        -- Question 1
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_assessment_id, 'What is the minimum legal tread depth for a tyre on a Class 4 vehicle (excluding the central 3/4)?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '1.6mm', TRUE);
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '1.0mm', FALSE);
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '2.0mm', FALSE);

        -- Question 2
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_assessment_id, 'Which component is NOT checked during an emissions test?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Air filter', TRUE);
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Exhaust silencer', FALSE);
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Catalytic converter', FALSE);

        -- Question 3
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_assessment_id, 'A seatbelt retracting mechanism fails to retract the belt. What is the correct defect categorization?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Major', TRUE);
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Minor', FALSE);
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Dangerous', FALSE);
    END IF;
END $$;


-- 3. Create a new course for the Motor Industry (Hybrid and Electric Vehicle Safety)
DO $$
DECLARE
    v_new_course_id INT;
    v_q_id INT;
BEGIN
    -- Only insert if it doesn't already exist
    IF NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Hybrid and Electric Vehicle Safety Awareness' AND is_template = TRUE) THEN
        
        -- Insert Course (Removed external_course_id, cpd_hours, duration_minutes. Used duration and required_hours instead based on schema discovery)
        INSERT INTO courses (title, description, duration, required_hours, category, status, is_template, is_locked)
        VALUES ('Hybrid and Electric Vehicle Safety Awareness', 'Essential safety procedures for working near or on hybrid and electric vehicles. Suitable for all motor industry professionals.', 120, 2.0, 'Technical', 'published', TRUE, FALSE)
        RETURNING id INTO v_new_course_id;

        -- Insert Lessons
        INSERT INTO lessons (course_id, title, content, order_index) 
        VALUES (v_new_course_id, 'Introduction to EV/PHEV', '<h3>What are EVs and PHEVs?</h3><p>Electric Vehicles (EV) and Plug-in Hybrid Electric Vehicles (PHEV) use high voltage systems. This lesson covers the basic architecture and component locations.</p>', 1);

        INSERT INTO lessons (course_id, title, content, order_index) 
        VALUES (v_new_course_id, 'High Voltage Hazards', '<h3>Understanding the Risks</h3><p>High voltage systems operate between 200V and 800V DC. Touching these components without proper shutdown procedures can be fatal.</p>', 2);

        INSERT INTO lessons (course_id, title, content, order_index) 
        VALUES (v_new_course_id, 'Safe Power-Down Procedures', '<h3>Isolating the High Voltage System</h3><p>Always wear Class 0 electrically insulated gloves. Locate the service disconnect, remove it, and wait at least 10 minutes for capacitors to discharge before working on the vehicle.</p>', 3);

        -- Insert Assessment Questions
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_new_course_id, 'What is the minimum safe waiting time after removing the service disconnect on an EV before starting work?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '10 minutes', TRUE);
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '1 minute', FALSE);
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Immediately', FALSE);

        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_new_course_id, 'What class of insulated gloves must be worn when working on high voltage systems?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Class 0', TRUE);
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Class 1', FALSE);
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Standard mechanic gloves', FALSE);
        
    END IF;
END $$;
