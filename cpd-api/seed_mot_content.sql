-- SQL Script to Seed MOT Annual Training 2025-26 Content
-- Run this in your PostgreSQL database tool (pgAdmin, psql, etc.)

DO $$
DECLARE
    v_course_id INTEGER;
    v_question_id INTEGER;
BEGIN
    -- 1. Get or Create the Course
    -- We try to find the 'Template' course first.
    SELECT id INTO v_course_id FROM courses WHERE title = 'MOT Annual Training 2025-26' LIMIT 1;

    IF v_course_id IS NULL THEN
        INSERT INTO courses (title, description, duration_minutes, is_locked, is_template, status, created_at, updated_at)
        VALUES (
            'MOT Annual Training 2025-26', 
            'Mandatory annual training for MOT testers. Covers Brakes, Lights, and Body Structure.', 
            180, 
            true, 
            true, 
            'published',
            NOW(), 
            NOW()
        ) RETURNING id INTO v_course_id;
        RAISE NOTICE 'Created new Course ID: %', v_course_id;
    ELSE
        RAISE NOTICE 'Updating existing Course ID: %', v_course_id;
    END IF;

    -- 2. Clean up existing content for this course
    DELETE FROM lessons WHERE course_id = v_course_id;
    DELETE FROM questions WHERE course_id = v_course_id; 
    -- Deleting from questions should cascade to question_options. 
    -- If not cascading, we would need: DELETE FROM question_options WHERE question_id IN (SELECT id FROM questions WHERE course_id = v_course_id);

    -- 3. Insert Lessons
    -- Schema: course_id, title, content, order_index
    INSERT INTO lessons (course_id, title, content, order_index)
    VALUES (
        v_course_id, 
        'Module 1: Corrosion & Structural Integrity', 
        '<h2>The Core Concept</h2>
<p>You must identify "Prescribed Areas" and "Structural Members." The golden rule is the 30cm rule, but the exam focuses on applying this to complex or ambiguous situations.</p>
<h3>1. Key Technical Definitions</h3>
<ul>
    <li><strong>Prescribed Area:</strong> Any structure within 30cm of a load-bearing point.</li>
    <li><strong>The "Gap" Rule:</strong> A gap between a corroded panel and the mount might not be a failure unless rigidity is compromised.</li>
</ul>
<h3>Bonded Repairs</h3>
<p><strong>General Rule:</strong> Bonded repairs on structural areas are a Major Fail unless manufacturer approved.</p>
<h3>Towbar Mounting Corrosion</h3>
<ul>
    <li><strong>Major Defect:</strong> Corrosion within 30cm reducing strength.</li>
    <li><strong>Dangerous Defect:</strong> Component likely to detach.</li>
</ul>
<h3>2. Scenario-Based Learning</h3>
<p><strong>Scenario A:</strong> Patch repair on sill, stitch welded on one side. <strong>Verdict: Fail (Major).</strong> Must be continuous seam welded.</p>
<p><strong>Scenario B:</strong> Surface rust on subframe, sounds solid. <strong>Verdict: Pass and Advise.</strong></p>',
        1
    );

    INSERT INTO lessons (course_id, title, content, order_index)
    VALUES (
        v_course_id, 
        'Module 2: Vehicle Classification', 
        '<h2>The "Grey Areas"</h2>
<h3>1. Dual Purpose Vehicles (Pickups)</h3>
<p>Must have 2 rows of seats, windows, under 2040kg unladen OR be 4WD. Most pickups are Class 4.</p>
<h3>2. Quadricycles (L7e)</h3>
<p>Tested as Class 4. Use Decelerometer for brakes due to narrow track.</p>
<h3>3. Goods Vehicle vs. Motor Caravan</h3>
<p><strong>Scenario:</strong> Transit converted to camper, V5C still "Panel Van", 3200kg DGW. <strong>Verdict: Class 7.</strong> It is still a Goods Vehicle over 3000kg.</p>',
        2
    );

    -- 4. Insert Questions and Options (Relational)

    -- Q1
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q1. You are inspecting a visible separate chassis body mount (outrigger) that is heavily corroded. The chassis itself is sound. How do you assess this?', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Pass and advise "Body mount corroded"', false),
    (v_question_id, 'Fail - Major (Body mounting prescribed area is excessively corroded)', true),
    (v_question_id, 'Fail - Dangerous (Body security imminent failure)', false),
    (v_question_id, 'Fail - Major (Chassis strength significantly reduced)', false);

    -- Q2
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q2. On a monocoque vehicle (unibody), you find a small hole (approx 5mm) in the floor pan. It is located 25cm away from the seat belt anchorage point. The metal surrounding the hole is solid.', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Pass and advise "Floor corroded"', false),
    (v_question_id, 'Fail - Major (Prescribed area is within 30cm of a seat belt anchorage)', true),
    (v_question_id, 'Fail - Major (Structure weakened)', false),
    (v_question_id, 'Pass - The hole is too small to affect structural rigidity', false);

    -- Q3
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q3. A customer presents a 2010 Ford Transit. You notice a repair patch on the sill (prescribed area). The patch is stitch-welded (tack welded) every 20mm. The original manufacturer panel was spot welded.', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Pass and advise', false),
    (v_question_id, 'Fail - Major (Inappropriate repair: Stitch welding is not acceptable for patch repairs on prescribed areas)', true),
    (v_question_id, 'Fail - Dangerous', false),
    (v_question_id, 'Pass - Stitch welding mimics spot welding', false);

    -- Q4
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q4. You are testing a Tesla Model 3. You notice the orange high-voltage cable running under the floor has a damaged outer insulation (orange covering), revealing the metal shielding underneath. The inner core is NOT visible.', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Pass and advise "High voltage cable insulation damaged"', false),
    (v_question_id, 'Fail - Major (High voltage wiring / insulation damaged)', true),
    (v_question_id, 'Fail - Dangerous (Risk of electric shock / short circuit)', false),
    (v_question_id, 'Pass - Only the inner core exposure is a fail', false);

    -- Q5
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q5. When testing the brakes on a Hybrid vehicle with "Regenerative Braking," what specific precaution must you take before using the roller brake tester (RBT)?', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'You must disconnect the 12V battery', false),
    (v_question_id, 'You must ensure the vehicle is in "Tow Mode" or "Neutral" with the ignition OFF to prevent regenerative braking from engaging', true),
    (v_question_id, 'You must always use a decelerometer; RBT is banned for Hybrids', false),
    (v_question_id, 'You must test it in "Drive" to test the regenerative system', false);

    -- Q6
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q6. An EV is presented for test. The dashboard displays a "System Fault" warning light related to the high-voltage battery system. The car still drives.', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Pass and advise', false),
    (v_question_id, 'Fail - Major (Electrical system warning lamp indicates a fault)', false),
    (v_question_id, 'Fail - Dangerous', false),
    (v_question_id, 'Not a testable item (unless specifically ABS/SRS/ESC/PAS)', true);

    -- Q7
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q7. A 2018 vehicle has had its factory halogen headlamp bulbs replaced with LED bulbs. The beam pattern is correct and does not dazzle.', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Pass', false),
    (v_question_id, 'Fail - Major (Light source and lamp not compatible)', true),
    (v_question_id, 'Fail - Major (Headlamp aim incorrect)', false),
    (v_question_id, 'Pass and advise "Non-standard bulbs fitted"', false);

    -- Q8
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q8. A 2015 car has a TPMS (Tyre Pressure Monitoring System) warning light illuminated on the dashboard. The tyres all look fine.', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Pass and advise', false),
    (v_question_id, 'Fail - Major (TPMS system malfunctioning or indicating a tyre fault)', true),
    (v_question_id, 'Fail - Minor', false),
    (v_question_id, 'Fail - Dangerous', false);

    -- Q9
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q9. You are testing a vehicle with "Sequential" indicators. On the rear, the sweep moves from the outside towards the inside (center) of the vehicle.', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Pass', false),
    (v_question_id, 'Fail - Major (Direction indicator shows light movement towards the wrong side)', true),
    (v_question_id, 'Fail - Major (Sequential indicators are illegal)', false),
    (v_question_id, 'Pass and advise', false);

    -- Q10
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q10. You are testing a Class 7 van (3,500kg DGW). The plate is missing, but you find the technical data online. You perform a roller brake test. What is the minimum efficiency for the Service Brake?', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, '50%', true),
    (v_question_id, '45%', false),
    (v_question_id, '58%', false),
    (v_question_id, '16%', false);

    -- Q11
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q11. During a brake test on a 2014 car, the front left wheel locks up at 150kgf. The front right wheel keeps turning and reaches 300kgf. The vehicle weighs 1000kg.', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Pass', false),
    (v_question_id, 'Fail - Major (Brake imbalance across an axle exceeds 50%)', true),
    (v_question_id, 'Fail - Major (Wheel lockup occurred too early)', false),
    (v_question_id, 'Fail - Dangerous', false);

    -- Q12
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q12. A customer brings a vehicle back for a retest 9 working days after it failed. The failure was for a worn tyre and a blown headlight bulb. The repairer has fixed them. Do you need to perform a full test?', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'No, just check the failed items (Partial Retest)', true),
    (v_question_id, 'Yes, because it is over 24 hours', false),
    (v_question_id, 'Yes, because it has been more than 10 working days', false),
    (v_question_id, 'No, but you must charge half the fee', false);

    -- Q13
    INSERT INTO questions (course_id, question_text, question_type) 
    VALUES (v_course_id, 'Q13. You log a vehicle on to the MTS. 10 minutes later, you realize you logged the wrong vehicle (wrong VIN). What should you do?', 'multiple_choice') 
    RETURNING id INTO v_question_id;
    
    INSERT INTO question_options (question_id, option_text, is_correct) VALUES 
    (v_question_id, 'Continue the test and edit the VIN at the end', false),
    (v_question_id, 'Abort the test immediately (Reason: Registered in Error)', true),
    (v_question_id, 'Fail the vehicle and start again', false),
    (v_question_id, 'Call DVSA to cancel it', false);

END $$;
