-- 04_add_mot_lessons_questions.sql
-- Create structured lessons and questions for MOT templates
-- This replaces the HTML content approach with proper database structure

-- MOT Class 1 & 2 Training Lessons
DO $$
DECLARE
    v_course_id INTEGER;
    v_lesson_id INTEGER;
    v_question_id INTEGER;
BEGIN
    -- Get MOT Class 1 & 2 Training template ID
    SELECT id INTO v_course_id FROM courses WHERE title = 'MOT Class 1 & 2 Training' AND is_template = TRUE LIMIT 1;
    
    IF v_course_id IS NOT NULL THEN
        -- Delete existing lessons/questions if any
        DELETE FROM lessons WHERE course_id = v_course_id;
        
        -- Lesson 1: Legislative Updates
        INSERT INTO lessons (course_id, title, content, order_index)
        VALUES (v_course_id, 'Module 1: Legislative Updates', 
        '<h3>Current DVSA Regulations</h3>
<p>This module covers recent changes to motorcycle testing standards and regulatory requirements.</p>

<h4>Road Traffic Act Updates</h4>
<p>Recent amendments to the Road Traffic Act have introduced new safety requirements for motorcycles. Testers must be aware of:</p>
<ul>
  <li>Updated lighting regulations for LED headlights</li>
  <li>New requirements for electronic stability systems on newer models</li>
  <li>Changes to exhaust emissions standards</li>
</ul>

<h4>Emissions Standards</h4>
<p>Euro 5 standards now apply to all motorcycles manufactured after 2020. Key points:</p>
<ul>
  <li>Lower CO emissions limits</li>
  <li>Particulate matter restrictions</li>
  <li>Enhanced emissions testing procedures</li>
</ul>

<h4>Safety Equipment Requirements</h4>
<p>All motorcycles must have:</p>
<ul>
  <li>Working horn</li>
  <li>Compliant mirrors (minimum one on left side)</li>
  <li>Proper lighting systems</li>
  <li>Chain guards where applicable</li>
</ul>', 1)
        RETURNING id INTO v_lesson_id;
        
        -- Checkpoint question for Lesson 1
        INSERT INTO questions (course_id, lesson_id, question_text, question_type)
        VALUES (v_course_id, v_lesson_id, 'What is the minimum number of mirrors required on a motorcycle?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, 'One mirror on the left side', TRUE),
        (v_question_id, 'Two mirrors (both sides)', FALSE),
        (v_question_id, 'No mirrors required', FALSE),
        (v_question_id, 'Only rear-view mirror', FALSE);
        
        -- Lesson 2: Technical Inspection
        INSERT INTO lessons (course_id, title, content, order_index)
        VALUES (v_course_id, 'Module 2: Technical Inspection', 
        '<h3>Motorcycle Systems Inspection Procedures</h3>

<h4>Brake System Testing</h4>
<p>Proper brake inspection is critical for motorcycle safety:</p>
<ul>
  <li>Front brake: Must achieve minimum 50% efficiency</li>
  <li>Rear brake: Must achieve minimum 25% efficiency</li>
  <li>Combined efficiency: Must reach 50% overall</li>
  <li>Check for fluid leaks, worn pads, and disc condition</li>
</ul>

<h4>Steering and Suspension</h4>
<p>Examine for:</p>
<ul>
  <li>Excessive play in steering head bearings</li>
  <li>Fork seal leaks</li>
  <li>Shock absorber effectiveness</li>
  <li>Frame damage or cracks</li>
</ul>

<h4>Lighting and Electrical Systems</h4>
<p>All lights must function correctly:</p>
<ul>
  <li>Headlight (high and low beam)</li>
  <li>Tail light and brake light</li>
  <li>Turn indicators (where fitted)</li>
  <li>Number plate light</li>
  <li>Reflectors</li>
</ul>

<h4>Exhaust and Emissions</h4>
<p>Check for:</p>
<ul>
  <li>Secure mounting</li>
  <li>No excessive noise (legal limit: 80dB for post-1985 bikes)</li>
  <li>Emissions within legal limits</li>
  <li>No serious leaks</li>
</ul>', 2)
        RETURNING id INTO v_lesson_id;
        
        -- Checkpoint question for Lesson 2
        INSERT INTO questions (course_id, lesson_id, question_text, question_type)
        VALUES (v_course_id, v_lesson_id, 'What is the minimum front brake efficiency required for motorcycles?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, '50%', TRUE),
        (v_question_id, '25%', FALSE),
        (v_question_id, '60%', FALSE),
        (v_question_id, '40%', FALSE);
        
        -- Final Assessment Questions (not linked to specific lesson)
        INSERT INTO questions (course_id, question_text, question_type)
        VALUES (v_course_id, 'Euro 5 emissions standards apply to motorcycles manufactured after which year?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, '2020', TRUE),
        (v_question_id, '2018', FALSE),
        (v_question_id, '2022', FALSE),
        (v_question_id, '2015', FALSE);
        
        INSERT INTO questions (course_id, question_text, question_type)
        VALUES (v_course_id, 'What is the legal noise limit for motorcycles manufactured after 1985?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, '80dB', TRUE),
        (v_question_id, '90dB', FALSE),
        (v_question_id, '70dB', FALSE),
        (v_question_id, '85dB', FALSE);
    END IF;

    -- Get MOT Class 4 & 7 Training template ID
    SELECT id INTO v_course_id FROM courses WHERE title = 'MOT Class 4 & 7 Training' AND is_template = TRUE LIMIT 1;
    
    IF v_course_id IS NOT NULL THEN
        -- Delete existing lessons/questions if any
        DELETE FROM lessons WHERE course_id = v_course_id;
        
        -- Lesson 1: Regulatory Framework
        INSERT INTO lessons (course_id, title, content, order_index)
        VALUES (v_course_id, 'Module 1: Regulatory Framework', 
        '<h3>DVSA Testing Standards</h3>
<p>Understanding current requirements for passenger cars and light commercial vehicles.</p>

<h4>MOT Testing Standards</h4>
<p>The MOT test checks that vehicles meet minimum road safety and environmental standards:</p>
<ul>
  <li>Vehicles must be tested annually after 3 years old</li>
  <li>Test covers major systems: brakes, lights, steering, suspension, exhaust</li>
  <li>Defects categorized as Dangerous, Major, or Minor</li>
</ul>

<h4>Legislative Updates</h4>
<p>Recent changes include:</p>
<ul>
  <li>Enhanced emissions testing for diesel vehicles</li>
  <li>Stricter requirements for electronic systems</li>
  <li>Updated defect categorization system</li>
  <li>ADAS (Advanced Driver Assistance Systems) checks</li>
</ul>

<h4>Record Keeping</h4>
<p>Proper documentation is essential:</p>
<ul>
  <li>Digital test records must be submitted immediately</li>
  <li>Defects must be accurately described</li>
  <li>Advisory items should help customers maintain their vehicles</li>
  <li>Photographic evidence may be required for certain failures</li>
</ul>', 1)
        RETURNING id INTO v_lesson_id;
        
        INSERT INTO questions (course_id, lesson_id, question_text, question_type)
        VALUES (v_course_id, v_lesson_id, 'At what age must a car first undergo an MOT test?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, '3 years', TRUE),
        (v_question_id, '1 year', FALSE),
        (v_question_id, '5 years', FALSE),
        (v_question_id, '2 years', FALSE);
        
        -- Lesson 2: Vehicle Systems Inspection
        INSERT INTO lessons (course_id, title, content, order_index)
        VALUES (v_course_id, 'Module 2: Vehicle Systems Inspection', 
        '<h3>Comprehensive Testing Procedures</h3>

<h4>Braking Systems</h4>
<p>Critical safety checks:</p>
<ul>
  <li>Service brake efficiency: minimum 50%</li>
  <li>Secondary brake (parking brake): minimum 16%</li>
  <li>No brake fluid leaks</li>
  <li>Brake pad thickness within limits</li>
  <li>Brake discs not excessively corroded or scored</li>
</ul>

<h4>Steering and Suspension</h4>
<p>Check for:</p>
<ul>
  <li>Excessive play in steering components</li>
  <li>Worn track rod ends or ball joints</li>
  <li>Damaged or leaking shock absorbers</li>
  <li>Correct wheel alignment</li>
  <li>No excessive corrosion on subframes</li>
</ul>

<h4>Lights and Electrical</h4>
<p>All lighting must function correctly:</p>
<ul>
  <li>Headlights (dipped and main beam)</li>
  <li>Correct beam aim</li>
  <li>All external lights working</li>
  <li>No damaged lenses</li>
  <li>Warning lights functioning</li>
</ul>

<h4>Tyres and Wheels</h4>
<p>Minimum requirements:</p>
<ul>
  <li>Tread depth: 1.6mm across central 3/4 of tyre</li>
  <li>No cuts or bulges</li>
  <li>Correct tyre size for vehicle</li>
  <li>Wheel nuts secure and not damaged</li>
</ul>

<h4>Exhaust Emissions</h4>
<p>Testing procedures:</p>
<ul>
  <li>Petrol: Fast idle emissions test</li>
  <li>Diesel: Smoke opacity test (max 1.5m⁻¹ for most vehicles)</li>
  <li>DPF check (must not be removed or tampered with)</li>
</ul>

<h4>Body Structure and Corrosion</h4>
<p>Examine prescribed areas:</p>
<ul>
  <li>Within 30cm of major component mountings</li>
  <li>Load-bearing structures</li>
  <li>Seatbelt anchorage points</li>
  <li>Sharp edges that could cause injury</li>
</ul>', 2)
        RETURNING id INTO v_lesson_id;
        
        INSERT INTO questions (course_id, lesson_id, question_text, question_type)
        VALUES (v_course_id, v_lesson_id, 'What is the minimum tread depth required across the central 3/4 of a tyre?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, '1.6mm', TRUE),
        (v_question_id, '2.0mm', FALSE),
        (v_question_id, '1.0mm', FALSE),
        (v_question_id, '3.0mm', FALSE);
        
        -- Final Assessment Questions
        INSERT INTO questions (course_id, question_text, question_type)
        VALUES (v_course_id, 'What is the minimum service brake efficiency required?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, '50%', TRUE),
        (v_question_id, '40%', FALSE),
        (v_question_id, '60%', FALSE),
        (v_question_id, '45%', FALSE);
        
        INSERT INTO questions (course_id, question_text, question_type)
        VALUES (v_course_id, 'Corrosion must be checked within what distance of major component mountings?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, '30cm', TRUE),
        (v_question_id, '20cm', FALSE),
        (v_question_id, '40cm', FALSE),
        (v_question_id, '50cm', FALSE);
    END IF;

    -- Get MOT Tester Annual Assessment template ID
    SELECT id INTO v_course_id FROM courses WHERE title = 'MOT Tester Annual Assessment' AND is_template = TRUE LIMIT 1;
    
    IF v_course_id IS NOT NULL THEN
        -- Delete existing questions if any
        DELETE FROM questions WHERE course_id = v_course_id;
        
        -- Assessment has no lessons, just questions
        INSERT INTO questions (course_id, question_text, question_type)
        VALUES (v_course_id, 'What action should be taken if a DPF has been removed from a diesel vehicle?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, 'Major failure - DPF removal is illegal', TRUE),
        (v_question_id, 'Advisory only', FALSE),
        (v_question_id, 'Minor defect', FALSE),
        (v_question_id, 'No action needed', FALSE);
        
        INSERT INTO questions (course_id, question_text, question_type)
        VALUES (v_course_id, 'A vehicle has a brake efficiency of 45%. What is the test result?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, 'Fail - below 50% minimum', TRUE),
        (v_question_id, 'Pass', FALSE),
        (v_question_id, 'Advisory', FALSE),
        (v_question_id, 'Retest required', FALSE);
        
        INSERT INTO questions (course_id, question_text, question_type)
        VALUES (v_course_id, 'When must MOT test records be submitted to DVSA?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, 'Immediately after test completion', TRUE),
        (v_question_id, 'Within 24 hours', FALSE),
        (v_question_id, 'Within 7 days', FALSE),
        (v_question_id, 'At end of working day', FALSE);
        
        INSERT INTO questions (course_id, question_text, question_type)
        VALUES (v_course_id, 'What is the maximum smoke opacity reading for most diesel vehicles?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, '1.5m⁻¹', TRUE),
        (v_question_id, '2.0m⁻¹', FALSE),
        (v_question_id, '1.0m⁻¹', FALSE),
        (v_question_id, '2.5m⁻¹', FALSE);
        
        INSERT INTO questions (course_id, question_text, question_type)
        VALUES (v_course_id, 'A tyre has a cut exposing the cords. How should this be categorized?', 'multiple_choice')
        RETURNING id INTO v_question_id;
        
        INSERT INTO question_options (question_id, option_text, is_correct) VALUES
        (v_question_id, 'Dangerous defect', TRUE),
        (v_question_id, 'Major defect', FALSE),
        (v_question_id, 'Minor defect', FALSE),
        (v_question_id, 'Advisory', FALSE);
    END IF;
END $$;

-- Clear the content column as it's no longer needed
-- Content is now in structured lessons
UPDATE courses 
SET content = NULL 
WHERE title IN ('MOT Class 1 & 2 Training', 'MOT Class 4 & 7 Training', 'MOT Tester Annual Assessment') 
AND is_template = TRUE;
