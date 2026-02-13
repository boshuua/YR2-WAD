-- 08_add_mot_class_4_7_content.sql
-- Includes Table Creation for Questions if missing

-- 1. Create Tables if not exist (Fix for missing schema)
CREATE TABLE IF NOT EXISTS questions (
    id SERIAL PRIMARY KEY,
    course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
    lesson_id INTEGER REFERENCES lessons(id) ON DELETE CASCADE,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) DEFAULT 'multiple_choice',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS question_options (
    id SERIAL PRIMARY KEY,
    question_id INTEGER REFERENCES questions(id) ON DELETE CASCADE,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

DO $$
DECLARE
    v_training_id INTEGER;
    v_assessment_id INTEGER;
    v_c1_assessment_id INTEGER;
    v_q_id INTEGER;
BEGIN
    -------------------------------------------------------
    -- 1. Rename existing assessment to Class 1 & 2
    -------------------------------------------------------
    UPDATE courses 
    SET title = 'MOT Class 1 & 2 Annual Assessment' 
    WHERE title = 'MOT Tester Annual Assessment' AND is_template = TRUE;

    -------------------------------------------------------
    -- 2. Create MOT Class 4 & 7 Training Template
    -------------------------------------------------------
    SELECT id INTO v_training_id FROM courses WHERE title = 'MOT Class 4 & 7 Training' AND is_template = TRUE;
    
    IF v_training_id IS NULL THEN
        INSERT INTO courses (title, description, duration, category, status, is_template, is_locked, required_hours)
        VALUES ('MOT Class 4 & 7 Training', 'Comprehensive annual training for Class 4 and 7 MOT Testers.', 180, 'MOT', 'published', TRUE, FALSE, 3.0)
        RETURNING id INTO v_training_id;
    END IF;

    -- Clear existing lessons to ensure clean state
    DELETE FROM lessons WHERE course_id = v_training_id;

    -- Insert Lessons
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Introduction – The MOT Club', '<h3>Introduction</h3><p>Welcome to the MOT Class 4 & 7 Annual Training for 2025-2026. This course covers all required topics set by the DVSA.</p>', 1);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'MOT Tester Annual Training Topics 2025-2026', '<h3>Annual Training Topics</h3><p>This year''s training focuses on:</p><ul><li>Electric, Hybrid and Mild Hybrid Vehicles</li><li>Information in the MOT Testing Guide</li><li>Test Procedures (Emissions, Fuel, Brake Testing)</li></ul>', 2);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Navigating DVSA Documents', '<h3>DVSA Documentation</h3><p>Learn how to effectively use the MOT Testing Guide and Inspection Manuals to find relevant information quickly.</p>', 3);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Identifying Hybrid & Mild Hybrid Vehicles', '<h3>Hybrid Systems</h3><p>Understanding the difference between HEV, PHEV, and MHEV systems.</p>', 4);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Identifying Electric Vehicles', '<h3>Electric Vehicles (BEV)</h3><p>Identifying Battery Electric Vehicles involves checking for charging ports and specific indicators.</p>', 5);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'EV & Hybrid Health & Safety', '<h3>Health & Safety</h3><p>High voltage systems pose significant risks. Always follow safety protocols.</p>', 6);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Risks From High-Voltage Systems', '<h3>Voltage Risks</h3><p>Electric vehicles operate at voltages capable of causing fatal shocks.</p>', 7);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'MOT Special Notice 03-24 – Opening Mercedes EQE & EQS bonnets', '<h3>Special Notice 03-24</h3><p>Specific procedures are required for opening bonnets on Mercedes EQE and EQS models.</p>', 8);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Carrying out MOT tests – Electric & Hybrid Vehicles', '<h3>Testing Procedures</h3><p>Focus on battery security, wiring condition, and propulsion system checks.</p>', 10);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Testing of Hybrid Emissions', '<h3>Hybrid Emissions</h3><p>Hybrids must be tested for emissions when the ICE is running.</p>', 12);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Disabled Driver Controls', '<h3>Adaptations</h3><p>Check the operation and security of any adaptations for disabled drivers.</p>', 13);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Contacting DVSA & VT20 Certificates', '<h3>DVSA Contact</h3><p>Procedures for contacting DVSA and issuance of VT20 certificates.</p>', 15);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'MOT Test Documents', '<h3>Forms and Docs</h3><p>Overview of VT20, VT30, VT32 documents.</p>', 17);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'MOT Contingency Testing Procedures', '<h3>Contingency Planning</h3><p>Procedures for when the MOT testing service is unavailable.</p>', 19);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Convictions & Repute', '<h3>Good Repute</h3><p>Testers and AEs must maintain good repute.</p>', 24);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Register a Vehicle for Test – Pre-Checks', '<h3>Pre-Checks</h3><p>Verify VIN, VRM, and that the vehicle is fit to be tested.</p>', 28);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Headlamp Alignment', '<h3>Headlamps</h3><p>Setting up the beam tester and checking alignment tolerances.</p>', 32);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Emissions Testing', '<h3>Petrol Emissions</h3><p>BET Test and full CAT test procedures.</p>', 35);
    INSERT INTO lessons (course_id, title, content, order_index) VALUES (v_training_id, 'Brake Test Procedures – Requirements', '<h3>Efficiency</h3><p>Calculating brake efficiency. Pass requirements for Class 4 & 7.</p>', 39);

    -------------------------------------------------------
    -- 3. Create MOT Class 4 & 7 Assessment
    -------------------------------------------------------
    SELECT id INTO v_assessment_id FROM courses WHERE title = 'MOT Class 4 & 7 Annual Assessment' AND is_template = TRUE;

    IF v_assessment_id IS NULL THEN
        INSERT INTO courses (title, description, duration, category, status, is_template, is_locked, required_hours)
        VALUES ('MOT Class 4 & 7 Annual Assessment', 'End of course assessment for Class 4 & 7 Testers.', 45, 'Assessment', 'published', TRUE, FALSE, 0.5)
        RETURNING id INTO v_assessment_id;
    END IF;

    -- Clear existing questions if any (to avoid duplicates on re-run)
    DELETE FROM questions WHERE course_id = v_assessment_id;

    -- Add Questions
    
    -- Q1
    INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'What is the minimum brake efficiency requirement for the service brake on a Class 4 vehicle?') RETURNING id INTO v_q_id;
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_q_id, '45%', FALSE), (v_q_id, '50%', TRUE), (v_q_id, '55%', FALSE), (v_q_id, '60%', FALSE);

    -- Q2
    INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'High voltage cables in electric vehicles are usually which color?') RETURNING id INTO v_q_id;
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_q_id, 'Red', FALSE), (v_q_id, 'Blue', FALSE), (v_q_id, 'Orange', TRUE), (v_q_id, 'Yellow', FALSE);

    -- Q3
    INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'How long must MOT contingency records be retained by the testing station?') RETURNING id INTO v_q_id;
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_q_id, '1 Month', FALSE), (v_q_id, '3 Months', FALSE), (v_q_id, '12 Months', TRUE), (v_q_id, '3 Years', FALSE);

    -- Q4
    INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'A malfunction indicator lamp (MIL) indicating an airbag defect is a:') RETURNING id INTO v_q_id;
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_q_id, 'Minor Defect', FALSE), (v_q_id, 'Major Defect', TRUE), (v_q_id, 'Dangerous Defect', FALSE), (v_q_id, 'Advisory', FALSE);

    -- Q5
    INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'When using a decelerometer to test a 4x4 vehicle, you should set it up:') RETURNING id INTO v_q_id;
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_q_id, 'On the passenger seat', FALSE), (v_q_id, 'On the floor well secured', TRUE), (v_q_id, 'Held by the assistant', FALSE), (v_q_id, 'On the dashboard', FALSE);

    -- Q6
    INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'For a vehicle first used after 1st Jan 2012, what is the check for the steering lock?') RETURNING id INTO v_q_id;
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_q_id, 'It must function', TRUE), (v_q_id, 'It must not be present', FALSE), (v_q_id, 'It is not tested', FALSE), (v_q_id, 'It is advisory only', FALSE);

    -- Q7
    INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'What is the simplified retention period for general MOT correspondence?') RETURNING id INTO v_q_id;
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_q_id, '6 Months', FALSE), (v_q_id, '1 Year', TRUE), (v_q_id, '2 Years', FALSE), (v_q_id, '5 Years', FALSE);

    -- Q8
    INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'Which of these is NOT a valid reason to refuse to test a vehicle?') RETURNING id INTO v_q_id;
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_q_id, 'The vehicle is too dirty to inspect', FALSE), (v_q_id, 'The boot won''t open', FALSE), (v_q_id, 'The fuel cap is missing', TRUE), (v_q_id, 'The vehicle emits substantial smoke', FALSE);

    -- Q9
    INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'Mild Hybrid Electric Vehicles (MHEV) usually operate at what voltage?') RETURNING id INTO v_q_id;
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_q_id, '12V', FALSE), (v_q_id, '24V', FALSE), (v_q_id, '48V', TRUE), (v_q_id, '400V', FALSE);

    -- Q10
    INSERT INTO questions (course_id, question_text) VALUES (v_assessment_id, 'If the MOT Testing Service is down, you must:') RETURNING id INTO v_q_id;
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_q_id, 'Stop testing immediately', FALSE), (v_q_id, 'Use contingency codes and manual certificates', TRUE), (v_q_id, 'Wait 1 hour then resume', FALSE), (v_q_id, 'Call the DVSA immediately', FALSE);

END $$;
