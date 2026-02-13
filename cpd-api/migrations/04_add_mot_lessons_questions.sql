-- 04_add_mot_lessons_questions.sql
-- UPDATED: Create structured lessons for MOT templates (NO QUESTIONS)
-- This replaces the HTML content approach with proper database structure

DO $$
DECLARE
    v_course_id INTEGER;
    v_lesson_id INTEGER;
BEGIN
    -- Get MOT Class 1 & 2 Training template ID
    SELECT id INTO v_course_id FROM courses WHERE title = 'MOT Class 1 & 2 Training' AND is_template = TRUE LIMIT 1;
    
    IF v_course_id IS NOT NULL THEN
        -- Delete existing lessons if any
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
</ul>', 1);
        
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
</ul>', 2);
    END IF;

    -- Get MOT Class 4 & 7 Training template ID
    SELECT id INTO v_course_id FROM courses WHERE title = 'MOT Class 4 & 7 Training' AND is_template = TRUE LIMIT 1;
    
    IF v_course_id IS NOT NULL THEN
        -- Delete existing lessons if any
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
</ul>', 1);
        
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
</ul>', 2);
    END IF;

    -- MOT Tester Annual Assessment - NO LESSONS, just completion
    -- Users simply mark it complete after reading

END $$;

-- Clear the content column as it's no longer needed
-- Content is now in structured lessons
UPDATE courses 
SET content = NULL 
WHERE title IN ('MOT Class 1 & 2 Training', 'MOT Class 4 & 7 Training') 
AND is_template = TRUE;
