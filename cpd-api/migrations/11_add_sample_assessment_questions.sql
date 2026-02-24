-- Add sample questions to MOT Class 1 & 2 Annual Assessment Template
-- These questions will be copied to new assessment instances

DO $$
DECLARE
    template_id INT;
    q1_id INT;
    q2_id INT;
    q3_id INT;
    q4_id INT;
    q5_id INT;
    q6_id INT;
    q7_id INT;
    q8_id INT;
    q9_id INT;
    q10_id INT;
BEGIN
    -- Get the template course ID
    SELECT id INTO template_id 
    FROM courses 
    WHERE title = 'MOT Class 1 & 2 Annual Assessment' 
    AND is_template = TRUE 
    LIMIT 1;

    IF template_id IS NULL THEN
        RAISE EXCEPTION 'MOT Class 1 & 2 Annual Assessment template not found';
    END IF;

    -- Question 1
    INSERT INTO questions (course_id, lesson_id, question_text, question_type)
    VALUES (template_id, NULL, 'What is the minimum brake efficiency requirement for the service brake on a Class 4 vehicle?', 'multiple_choice')
    RETURNING id INTO q1_id;

    INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (q1_id, '25%', false),
        (q1_id, '50%', true),
        (q1_id, '58%', false),
        (q1_id, '65%', false);

    -- Question 2
    INSERT INTO questions (course_id, lesson_id, question_text, question_type)
    VALUES (template_id, NULL, 'High voltage cables in electric vehicles are usually which color?', 'multiple_choice')
    RETURNING id INTO q2_id;

    INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (q2_id, 'Black', false),
        (q2_id, 'Red', false),
        (q2_id, 'Orange', true),
        (q2_id, 'Blue', false);

    -- Question 3
    INSERT INTO questions (course_id, lesson_id, question_text, question_type)
    VALUES (template_id, NULL, 'How long must MOT contingency records be retained by the testing station?', 'multiple_choice')
    RETURNING id INTO q3_id;

    INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (q3_id, '3 months', false),
        (q3_id, '6 months', false),
        (q3_id, '12 months', false),
        (q3_id, '15 months', true);

    -- Question 4
    INSERT INTO questions (course_id, lesson_id, question_text, question_type)
    VALUES (template_id, NULL, 'An alternator warning lamp that is working correctly should be:', 'multiple_choice')
    RETURNING id INTO q4_id;

    INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (q4_id, 'On constantly', false),
        (q4_id, 'Off when the engine is running', true),
        (q4_id, 'Flashing when driving', false),
        (q4_id, 'On only at high speeds', false);

    -- Question 5
    INSERT INTO questions (course_id, lesson_id, question_text, question_type)
    VALUES (template_id, NULL, 'When using a decelerometer to test a 4x4 vehicle, you should set it up:', 'multiple_choice')
    RETURNING id INTO q5_id;

    INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (q5_id, 'In line with the vehicle', false),
        (q5_id, 'At 45 degrees', false),
        (q5_id, 'Perpendicular to the vehicle', false),
        (q5_id, 'Following manufacturer instructions', true);

    -- Question 6
    INSERT INTO questions (course_id, lesson_id, question_text, question_type)
    VALUES (template_id, NULL, 'For a vehicle first used after 1st Jan 2012, what is the check for the steering lock?', 'multiple_choice')
    RETURNING id INTO q6_id;

    INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (q6_id, 'Must operate smoothly', true),
        (q6_id, 'Must lock within 5 degrees', false),
        (q6_id, 'Must be removable', false),
        (q6_id, 'No check required', false);

    -- Question 7
    INSERT INTO questions (course_id, lesson_id, question_text, question_type)
    VALUES (template_id, NULL, 'What is the simplified retention policy for general MOT correspondence?', 'multiple_choice')
    RETURNING id INTO q7_id;

    INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (q7_id, '1 month', false),
        (q7_id, '3 months', false),
        (q7_id, '6 months', true),
        (q7_id, '12 months', false);

    -- Question 8
    INSERT INTO questions (course_id, lesson_id, question_text, question_type)
    VALUES (template_id, NULL, 'Which of these is NOT a valid reason to refuse to test a vehicle?', 'multiple_choice')
    RETURNING id INTO q8_id;

    INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (q8_id, 'Vehicle is too dirty to inspect', true),
        (q8_id, 'Vehicle emits substantial smoke', false),
        (q8_id, 'Vehicle has insufficient fuel', false),
        (q8_id, 'Vehicle is in dangerous condition', false);

    -- Question 9
    INSERT INTO questions (course_id, lesson_id, question_text, question_type)
    VALUES (template_id, NULL, 'Mild Hybrid Electric Vehicles (MHEV) usually operate at what voltage?', 'multiple_choice')
    RETURNING id INTO q9_id;

    INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (q9_id, '12V', false),
        (q9_id, '48V', true),
        (q9_id, '240V', false),
        (q9_id, '400V', false);

    -- Question 10
    INSERT INTO questions (course_id, lesson_id, question_text, question_type)
    VALUES (template_id, NULL, 'If the MOT Testing Service is down, you must:', 'multiple_choice')
    RETURNING id INTO q10_id;

    INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (q10_id, 'Stop testing immediately', true),
        (q10_id, 'Continue testing and upload later', false),
        (q10_id, 'Use contingency codes', false),
        (q10_id, 'Wait 1 hour then resume', false);

    RAISE NOTICE 'Successfully added 10 questions to template course ID: %', template_id;
END $$;
