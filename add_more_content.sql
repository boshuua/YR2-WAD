-- 1. Add extra lessons to MOT Class 4 and 7 Training to cover the new questions
DO $$
DECLARE
    v_training_id INT;
BEGIN
    SELECT id INTO v_training_id FROM courses WHERE title LIKE '%MOT Class 4 and 7 Training%' AND is_template = TRUE LIMIT 1;
    
    IF v_training_id IS NOT NULL THEN
        INSERT INTO lessons (course_id, title, content, order_index) VALUES
        (v_training_id, 'Braking Systems & Performance', '<h3>Braking Performance</h3><p>The maximum acceptable variation in braking effort between wheels on the same steered axle is <strong>30%</strong>. For parking brakes on a single line system, a minimum efficiency of <strong>16%</strong> is required.</p>', 50),
        (v_training_id, 'Wheels and Tyres', '<h3>Tyre Requirements</h3><p>The minimum legal tread depth for a tyre on a Class 4 vehicle is <strong>1.6mm</strong> (excluding the central 3/4). If a <strong>space-saver spare wheel</strong> is fitted to the front axle during a test, the vehicle must be <strong>failed</strong>.</p>', 51),
        (v_training_id, 'Emissions and Exhaust', '<h3>Emissions Standards</h3><p>During an emissions test, the standard CO limit for a vehicle first used on or after 1st September 2002 at fast idle is <strong>0.2%</strong>. Note that the <strong>air filter</strong> is NOT checked during an emissions test.</p>', 52),
        (v_training_id, 'Interior Checks', '<h3>Seatbelts and Controls</h3><p>A seatbelt retracting mechanism that fails to retract the belt is categorized as a <strong>Major</strong> defect. Additionally, a <strong>brake pedal anti-slip provision</strong> must be checked during <strong>every MOT test</strong>.</p>', 53),
        (v_training_id, 'Registration Plates & Suspension', '<h3>Plates and Shocks</h3><p>A registration plate where the <strong>background overprints the characters</strong> is a reason for rejection. A shock absorber that has a slight fluid leak (weeping) but is still functioning correctly should be categorised as <strong>Minor (Pass and advise)</strong>.</p>', 54);
    END IF;
END $$;

-- 2. Add the 7 new questions to the MOT Class 4 and 7 Assessment
DO $$
DECLARE
    v_assessment_id INT;
    v_q_id INT;
BEGIN
    SELECT id INTO v_assessment_id FROM courses WHERE title LIKE '%MOT Class 4 and 7 Annual Assessment%' AND is_template = TRUE LIMIT 1;
    
    IF v_assessment_id IS NOT NULL THEN
        -- Question 4
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_assessment_id, 'When inspecting the braking system, what is the maximum acceptable variation in braking effort between wheels on the same steered axle?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '30%', TRUE), (v_q_id, '25%', FALSE), (v_q_id, '50%', FALSE);

        -- Question 5
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_assessment_id, 'A vehicle is presented for an MOT test with a space-saver spare wheel fitted to the front axle. What action should the tester take?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Fail the vehicle', TRUE), (v_q_id, 'Pass with an advisory', FALSE), (v_q_id, 'Refuse to test', FALSE);

        -- Question 6
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_assessment_id, 'During an emissions test, what is the standard CO limit for a vehicle first used on or after 1st September 2002 at fast idle?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '0.2%', TRUE), (v_q_id, '0.5%', FALSE), (v_q_id, '0.3%', FALSE);

        -- Question 7
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_assessment_id, 'How often must a brake pedal anti-slip provision be checked?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Every MOT test', TRUE), (v_q_id, 'Only if requested by the presenter', FALSE), (v_q_id, 'Only on vehicles over 10 years old', FALSE);

        -- Question 8
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_assessment_id, 'What is the minimum required efficiency for the parking brake on a Class 4 vehicle (single line system)?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, '16%', TRUE), (v_q_id, '25%', FALSE), (v_q_id, '50%', FALSE);

        -- Question 9
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_assessment_id, 'Which of the following is a reason for rejection regarding a vehicle''s registration plates?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'The background overprints the characters', TRUE), (v_q_id, 'They are dirty but legible', FALSE), (v_q_id, 'They are screwed on rather than stuck on', FALSE);

        -- Question 10
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_assessment_id, 'A shock absorber that has a slight fluid leak (weeping) but is still functioning correctly should be categorised as:', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Minor (Pass and advise)', TRUE), (v_q_id, 'Major', FALSE), (v_q_id, 'Dangerous', FALSE);
    END IF;
END $$;

-- 3. Add extra lessons to the Hybrid and Electric Vehicle Safety Awareness course
DO $$
DECLARE
    v_ev_course_id INT;
BEGIN
    SELECT id INTO v_ev_course_id FROM courses WHERE title = 'Hybrid and Electric Vehicle Safety Awareness' AND is_template = TRUE LIMIT 1;
    
    IF v_ev_course_id IS NOT NULL THEN
        INSERT INTO lessons (course_id, title, content, order_index) VALUES
        (v_ev_course_id, 'High Voltage Components & Wiring', '<h3>Identifying HV Systems</h3><p>High voltage cables are typically sheathed in <strong>Orange</strong>. The <strong>Inverter</strong> is a critical component that converts high voltage DC from the battery to AC for the electric motor. An <strong>interlock circuit</strong> is used to monitor the integrity of the high voltage system connections.</p>', 4),
        (v_ev_course_id, 'Tools and Proving Dead', '<h3>Testing for Voltage</h3><p>Before beginning work on an isolated high voltage system, you must test with a known working <strong>two-pole voltage indicator</strong> to ensure it is dead. Never use a standard multimeter unless it is appropriately CAT rated (e.g., <strong>CAT III 1000V</strong>).</p>', 5),
        (v_ev_course_id, 'Emergency Procedures', '<h3>Dealing with EV Incidents</h3><p>If an electric vehicle is involved in a collision, a primary concern regarding the battery is <strong>thermal runaway</strong>. In case of battery fires, use Dry Powder, CO2, or a specialized <strong>Lithium-ion fire extinguisher</strong>.</p>', 6),
        (v_ev_course_id, 'Battery Pack Maintenance', '<h3>Internal Repairs</h3><p>Repairs on the internal components of a high voltage battery pack must be performed <strong>ONLY by specifically trained and authorized personnel</strong>.</p>', 7);
    END IF;
END $$;


-- 4. Add the 8 new questions to the Hybrid and Electric Vehicle Safety Awareness course
DO $$
DECLARE
    v_course_id INT;
    v_q_id INT;
BEGIN
    SELECT id INTO v_course_id FROM courses WHERE title = 'Hybrid and Electric Vehicle Safety Awareness' AND is_template = TRUE LIMIT 1;
    
    IF v_course_id IS NOT NULL THEN
        -- Question 3
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_course_id, 'What color are high voltage cables typically sheathed in?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Orange', TRUE), (v_q_id, 'Red', FALSE), (v_q_id, 'Blue', FALSE);

        -- Question 4
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_course_id, 'Which component converts high voltage DC from the battery to AC for the electric motor?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Inverter', TRUE), (v_q_id, 'DC-DC Converter', FALSE), (v_q_id, 'Onboard Charger', FALSE);

        -- Question 5
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_course_id, 'What is the purpose of the interlock circuit in an EV?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'To monitor the integrity of the high voltage system connections', TRUE), (v_q_id, 'To lock the doors securely', FALSE), (v_q_id, 'To prevent the vehicle from rolling away', FALSE);

        -- Question 6
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_course_id, 'Before beginning work on an isolated high voltage system, what must you do to ensure it is dead?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Test with a known working two-pole voltage indicator', TRUE), (v_q_id, 'Assume it is dead after 10 minutes', FALSE), (v_q_id, 'Check the dashboard display', FALSE);

        -- Question 7
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_course_id, 'What type of fire extinguisher is recommended for fires involving high voltage batteries?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Dry Powder, CO2 or specialized Lithium-ion extinguisher', TRUE), (v_q_id, 'Water', FALSE), (v_q_id, 'Foam', FALSE);

        -- Question 8
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_course_id, 'If an electric vehicle is involved in a collision, what is a primary concern regarding the battery?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Thermal runaway', TRUE), (v_q_id, 'Rapid loss of charge', FALSE), (v_q_id, 'Increased radio interference', FALSE);

        -- Question 9
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_course_id, 'Can a standard multimeter be used to check for high voltage in an EV?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'No, it must be appropriately CAT rated (e.g., CAT III 1000V)', TRUE), (v_q_id, 'Yes, any multimeter is fine', FALSE), (v_q_id, 'Yes, if set to AC voltage', FALSE);

        -- Question 10
        INSERT INTO questions (course_id, question_text, question_type) 
        VALUES (v_course_id, 'Who should perform repairs on the internal components of a high voltage battery pack?', 'multiple_choice') 
        RETURNING id INTO v_q_id;
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES (v_q_id, 'Only specifically trained and authorized personnel', TRUE), (v_q_id, 'Any competent mechanic', FALSE), (v_q_id, 'The vehicle owner', FALSE);
    END IF;
END $$;
