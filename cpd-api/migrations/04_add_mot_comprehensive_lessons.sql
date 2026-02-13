-- 04_add_mot_comprehensive_lessons.sql
-- Add comprehensive lesson structure for MOT Class 1 & 2 Training
-- Based on 2025-2026 training content (excluding quizzes)

DO $$
DECLARE
    v_course_id INTEGER;
BEGIN
    -- Get MOT Class 1 & 2 Training template ID
    SELECT id INTO v_course_id FROM courses WHERE title = 'MOT Class 1 & 2 Training' AND is_template = TRUE LIMIT 1;
    
    IF v_course_id IS NOT NULL THEN
        -- Delete existing lessons
        DELETE FROM lessons WHERE course_id = v_course_id;
        
        -- SECTION 1: Introduction (3 lessons)
        INSERT INTO lessons (course_id, title, content, order_index) VALUES 
        (v_course_id, 'Introduction – The MOT Club', 
         '<h3>Introduction – The MOT Club</h3>
         <p>Welcome to the MOT Club training portal. This comprehensive course will guide you through the annual training requirements for MOT Class 1 & 2 motorcycle testing.</p>
         <p>Throughout this course, you will cover essential updates, regulations, and best practices required to maintain your MOT testing certification.</p>', 1),
        
        (v_course_id, 'MOT Tester Annual Training Topics 2025-2026', 
         '<h3>MOT Tester Annual Training Topics 2025-2026</h3>
         <p>This year''s training covers the following key areas:</p>
         <ul>
           <li>Electric, Hybrid, and Mild Hybrid Vehicles</li>
           <li>Disabled Rider Controls</li>
           <li>Updated MOT Testing Guide Information</li>
           <li>Test Procedures and Documentation</li>
           <li>Legislative Changes</li>
         </ul>', 2),
        
        (v_course_id, 'Navigating DVSA Documents', 
         '<h3>Navigating DVSA Documents</h3>
         <p>Understanding how to navigate and reference DVSA documentation is crucial for accurate MOT testing.</p>
         <p>Key documents you should be familiar with:</p>
         <ul>
           <li>MOT Testing Guide</li>
           <li>MOT Inspection Manual</li>
           <li>DVSA Enforcement Guidelines</li>
           <li>Annual Training Updates</li>
         </ul>', 3);

        -- SECTION 2: Electric, Hybrid/Mild Hybrid Vehicles (4 lessons - excluding quiz)
        INSERT INTO lessons (course_id, title, content, order_index) VALUES 
        (v_course_id, 'Identifying Hybrid & Mild Hybrid Vehicles', 
         '<h3>Identifying Hybrid & Mild Hybrid Vehicles</h3>
         <p>Learn to identify different types of hybrid motorcycles:</p>
         <ul>
           <li><strong>Full Hybrid:</strong> Can run on electric motor alone or in combination with combustion engine</li>
           <li><strong>Mild Hybrid:</strong> Electric motor assists the combustion engine but cannot power the vehicle alone</li>
         </ul>
         <p>Common identification features include badges, charging ports, and vehicle documentation.</p>', 4),
        
        (v_course_id, 'Identifying Electric Vehicles', 
         '<h3>Identifying Electric Vehicles</h3>
         <p>Electric motorcycles are becoming increasingly common. Key identification points:</p>
         <ul>
           <li>No exhaust system</li>
           <li>Charging port present</li>
           <li>Distinctive powertrain design</li>
           <li>Battery pack visible or documented</li>
         </ul>', 5),
        
        (v_course_id, 'EV & Hybrid Health & Safety', 
         '<h3>EV & Hybrid Health & Safety</h3>
         <p><strong>Critical Safety Information:</strong></p>
         <ul>
           <li>Always check for high-voltage warning labels</li>
           <li>Never work on high-voltage systems without proper training and equipment</li>
           <li>Be aware of orange-colored cables indicating high voltage</li>
           <li>Follow manufacturer-specific safety procedures</li>
         </ul>
         <p class="warning">⚠️ High-voltage systems can be lethal. If you are not qualified to work on these systems, do not proceed with testing components connected to the high-voltage system.</p>', 6),
        
        (v_course_id, 'Risks From High-Voltage Systems', 
         '<h3>Risks From High-Voltage Systems</h3>
         <p>Understanding the specific risks associated with high-voltage motorcycle systems:</p>
         <ul>
           <li><strong>Electric Shock:</strong> Voltages typically range from 60V to 650V DC</li>
           <li><strong>Chemical Hazards:</strong> Battery electrolyte can be hazardous</li>
           <li><strong>Fire Risk:</strong> Damaged batteries can ignite</li>
           <li><strong>Silent Operation:</strong> Electric motors operate silently, creating unique hazards</li>
         </ul>', 7);

        -- SECTION 3: Electric, Hybrid Vehicles & Disabled Rider Controls (3 lessons - excluding quiz)
        INSERT INTO lessons (course_id, title, content, order_index) VALUES 
        (v_course_id, 'Carrying out MOT tests – Electric & Hybrid Vehicles', 
         '<h3>MOT Testing Electric & Hybrid Vehicles - Part 1</h3>
         <p>Specific considerations when testing electric and hybrid motorcycles:</p>
         <ul>
           <li>No exhaust emissions test required for pure electric vehicles</li>
           <li>Visual inspection of battery condition and mounting</li>
           <li>Check for fluid leaks (cooling system in some models)</li>
           <li>Inspect high-voltage cable condition and routing</li>
         </ul>', 8),
        
        (v_course_id, 'Carrying out MOT tests – Electric & Hybrid Vehicles – Part 2', 
         '<h3>MOT Testing Electric & Hybrid Vehicles - Part 2</h3>
         <p>Additional testing procedures:</p>
         <ul>
           <li>Brake testing considerations (regenerative braking systems)</li>
           <li>Warning light checks (battery warnings, charging system)</li>
           <li>Speedometer calibration (digital displays)</li>
           <li>Steering and suspension checks (heavier battery weight)</li>
         </ul>', 9),
        
        (v_course_id, 'Disabled Driver Controls', 
         '<h3>Disabled Rider Controls</h3>
         <p>Motorcycles may be fitted with specialist controls for disabled riders:</p>
         <ul>
           <li>Hand-operated rear brakes</li>
           <li>Modified clutch systems</li>
           <li>Adapted throttle controls</li>
           <li>Custom seat and footpeg modifications</li>
         </ul>
         <p>These modifications must be securely fitted, operate correctly, and not present a hazard.</p>', 10);

        -- SECTION 4: Information in the MOT Testing Guide (8 lessons - excluding quiz)
        INSERT INTO lessons (course_id, title, content, order_index) VALUES 
        (v_course_id, 'Contacting DVSA & VT20 Certificates', 
         '<h3>Contacting DVSA & VT20 Certificates</h3>
         <p>How to contact DVSA for support and understanding VT20 certificates:</p>
         <ul>
           <li>DVSA Helpline: Available for testing queries</li>
           <li>VT20 Certificates: Issued for vehicles that pass the MOT</li>
           <li>When to issue notifications instead of certificates</li>
         </ul>', 11),
        
        (v_course_id, 'Replacement, Duplication & Alterations of Test Documents', 
         '<h3>Replacement, Duplication & Alterations of Test Documents</h3>
         <p>Procedures for handling test documentation:</p>
         <ul>
           <li>Never duplicate or alter test certificates</li>
           <li>Replacement certificates must be requested through proper channels</li>
           <li>All test records are stored electronically and cannot be modified after submission</li>
         </ul>', 12),
        
        (v_course_id, 'MOT Test Documents', 
         '<h3>MOT Test Documents</h3>
         <p>Understanding the various documents used in MOT testing:</p>
         <ul>
           <li>VT20 - Pass Certificate</li>
           <li>VT30 - Failure Notice</li>
           <li>VT32 - Refusal Notice</li>
           <li>Advisory notices and defect categorization</li>
         </ul>', 13),
        
        (v_course_id, 'Document Retention', 
         '<h3>Document Retention</h3>
         <p>Legal requirements for retaining MOT testing records:</p>
         <ul>
           <li>Electronic records are maintained centrally by DVSA</li>
           <li>Test stations must maintain backup records</li>
           <li>Retention periods and compliance requirements</li>
         </ul>', 14),
        
        (v_course_id, 'MOT Contingency Testing Procedures', 
         '<h3>MOT Contingency Testing Procedures - Part 1</h3>
         <p>What to do when the MOT Testing Service (MTS) is unavailable:</p>
         <ul>
           <li>When contingency procedures apply</li>
           <li>How to activate contingency mode</li>
           <li>Paper-based testing procedures</li>
         </ul>', 15),
        
        (v_course_id, 'MOT Contingency Procedures – Part 2', 
         '<h3>MOT Contingency Procedures - Part 2</h3>
         <p>Continuing contingency operations:</p>
         <ul>
           <li>Issuing CT20 certificates</li>
           <li>Recording test results on paper</li>
           <li>Customer notification procedures</li>
         </ul>', 16),
        
        (v_course_id, 'MOT Contingency Procedures – CT20 & CT30', 
         '<h3>CT20 & CT30 Contingency Certificates</h3>
         <p>Understanding contingency certificate types:</p>
         <ul>
           <li><strong>CT20:</strong> Contingency Pass Certificate</li>
           <li><strong>CT30:</strong> Contingency Failure Notice</li>
           <li>When and how to issue each type</li>
         </ul>', 17),
        
        (v_course_id, 'MOT Contingency Procedures – CT Catch up', 
         '<h3>Contingency Catch-Up Procedures</h3>
         <p>After the MTS is restored:</p>
         <ul>
           <li>All contingency tests must be entered into MTS within 24 hours</li>
           <li>Verification procedures</li>
           <li>Handling discrepancies</li>
         </ul>', 18);

        -- SECTION 5: Information in the MOT Testing Guide – Part 2 (8 lessons - excluding quiz)
        INSERT INTO lessons (course_id, title, content, order_index) VALUES 
        (v_course_id, 'Convictions & Repute', 
         '<h3>Convictions & Repute Requirements</h3>
         <p>MOT testers must maintain good professional standing:</p>
         <ul>
           <li>Certain criminal convictions disqualify individuals from testing</li>
           <li>Must maintain professional reputation</li>
           <li>Obligation to declare relevant convictions</li>
         </ul>', 19),
        
        (v_course_id, 'Cessation Periods', 
         '<h3>Cessation Periods</h3>
         <p>Understanding suspension and cessation from MOT testing:</p>
         <ul>
           <li>Temporary suspension vs. permanent cessation</li>
           <li>Reasons for cessation</li>
           <li>Reinstatement procedures</li>
         </ul>', 20),
        
        (v_course_id, 'Appendix 8: Disciplinary Procedures', 
         '<h3>DVSA Disciplinary Procedures</h3>
         <p>The disciplinary process for MOT testing infractions:</p>
         <ul>
           <li>Investigation procedures</li>
           <li>Rights of appeal</li>
           <li>Sanction levels and consequences</li>
         </ul>', 21),
        
        (v_course_id, 'Other Very Serious Offences', 
         '<h3>Very Serious Offences in MOT Testing</h3>
         <p>Offences that result in immediate action:</p>
         <ul>
           <li>Issuing fraudulent certificates</li>
           <li>Testing without proper authorization</li>
           <li>Intentionally failing to identify dangerous defects</li>
         </ul>', 22),
        
        (v_course_id, 'Disciplinary – Testers', 
         '<h3>Disciplinary Actions for Testers</h3>
         <p>Specific disciplinary measures that can be applied to testers:</p>
         <ul>
           <li>Warning letters</li>
           <li>Suspension of testing privileges</li>
           <li>Permanent removal from the scheme</li>
         </ul>', 23),
        
        (v_course_id, 'Incorrect Testing Standards & Operation of Testing Scheme', 
         '<h3>Testing Standard Violations</h3>
         <p>Common violations and how to avoid them:</p>
         <ul>
           <li>Failing to follow correct testing procedures</li>
           <li>Incorrect defect categorization</li>
           <li>Equipment calibration failures</li>
         </ul>', 24),
        
        (v_course_id, 'Normal Sanction Levels for Testers', 
         '<h3>Sanction Levels</h3>
         <p>Understanding the progressive nature of disciplinary sanctions:</p>
         <ul>
           <li>Level 1: Advisory letter</li>
           <li>Level 2: Formal warning</li>
           <li>Level 3: Suspension</li>
           <li>Level 4: Cessation</li>
         </ul>', 25),
        
        (v_course_id, 'Other CPD Training Information', 
         '<h3>Continuing Professional Development</h3>
         <p>MOT testers must complete annual CPD training:</p>
         <ul>
           <li>Minimum 3 hours of structured training annually</li>
           <li>Training must cover current year topics</li>
           <li>Records must be maintained</li>
         </ul>', 26);

        -- SECTION 6: Test Procedures (7 lessons - excluding quiz)
        INSERT INTO lessons (course_id, title, content, order_index) VALUES 
        (v_course_id, 'Register a Vehicle for Test – Pre-Checks', 
         '<h3>Pre-Test Checks</h3>
         <p>Before registering a vehicle for test:</p>
         <ul>
           <li>Verify vehicle identity (VIN/frame number)</li>
           <li>Check registration document matches vehicle</li>
           <li>Ensure vehicle is safe to test</li>
           <li>Confirm test fee has been paid</li>
         </ul>', 27),
        
        (v_course_id, 'Registering a Vehicle for Test', 
         '<h3>Vehicle Registration Process</h3>
         <p>Step-by-step registration in MTS:</p>
         <ul>
           <li>Enter registration number or VIN</li>
           <li>Confirm vehicle details</li>
           <li>Record odometer reading</li>
           <li>Begin test sequence</li>
         </ul>', 28),
        
        (v_course_id, 'Suspension Checks', 
         '<h3>Suspension System Inspection</h3>
         <p>Checking motorcycle suspension components:</p>
         <ul>
           <li>Fork seal condition</li>
           <li>Shock absorber effectiveness</li>
           <li>Suspension linkage wear</li>
           <li>Spring condition</li>
         </ul>', 29),
        
        (v_course_id, 'Wheel Alignment', 
         '<h3>Wheel Alignment Inspection</h3>
         <p>Checking motorcycle wheel alignment:</p>
         <ul>
           <li>Front and rear wheel alignment</li>
           <li>Chain alignment and tension</li>
           <li>Swingarm condition</li>
         </ul>', 30),
        
        (v_course_id, 'Structure and Attachments', 
         '<h3>Frame and Attachments</h3>
         <p>Inspecting the motorcycle structure:</p>
         <ul>
           <li>Frame condition (cracks, corrosion, damage)</li>
           <li>Subframe security</li>
           <li>Side panels and bodywork security</li>
           <li>Seat mounting</li>
         </ul>', 31),
        
        (v_course_id, 'Reflectors & Exhaust', 
         '<h3>Reflectors and Exhaust Systems</h3>
         <p>Legal requirements and inspection criteria:</p>
         <ul>
           <li>Reflector presence and condition</li>
           <li>Exhaust system security and condition</li>
           <li>Noise limits (legal requirements)</li>
           <li>Emissions standards</li>
         </ul>', 32),
        
        (v_course_id, 'Fuel & Battery', 
         '<h3>Fuel and Battery Systems</h3>
         <p>Safety checks for fuel and electrical systems:</p>
         <ul>
           <li>Fuel tank security and condition</li>
           <li>No fuel leaks present</li>
           <li>Battery security</li>
           <li>Electrical system integrity</li>
         </ul>', 33);

        -- SECTION 7: Other Information (3 lessons - incomplete section in source, excluding quiz)
        INSERT INTO lessons (course_id, title, content, order_index) VALUES 
        (v_course_id, '4.2 Front & Rear Position Lamps', 
         '<h3>Position Lamps Requirements</h3>
         <p>Legal requirements for motorcycle position lamps:</p>
         <ul>
           <li>Front position lamps (if fitted)</li>
           <li>Rear position lamps (required)</li>
           <li>Color requirements (white front, red rear)</li>
           <li>Operation and visibility</li>
         </ul>', 34),
        
        (v_course_id, 'Number Plate Lamps & Indicators', 
         '<h3>Number Plate Illumination and Indicators</h3>
         <p>Requirements for number plate lighting and turn signals:</p>
         <ul>
           <li>Number plate must be adequately illuminated</li>
           <li>No auxiliary lamps should obscure the plate</li>
           <li>Indicators must function correctly (if fitted)</li>
           <li>Flash rate requirements</li>
         </ul>', 35),
        
        (v_course_id, 'Clutch & Throttle Controls and Linked Brakes', 
         '<h3>Control Systems</h3>
         <p>Inspecting motorcycle control systems:</p>
         <ul>
           <li>Clutch operation smooth and effective</li>
           <li>Throttle returns to closed position</li>
           <li>No excessive cable play</li>
           <li>Linked brake systems operate correctly</li>
         </ul>', 36),
        
        (v_course_id, 'Steering Linkage and Bearings', 
         '<h3>Steering System Inspection</h3>
         <p>Critical safety checks for steering components:</p>
         <ul>
           <li>Steering head bearing condition (no excessive play)</li>
           <li>Handlebar mounting security</li>
           <li>Steering lock operation</li>
           <li>No damage to steering components</li>
         </ul>', 37);

    END IF;
END $$;

-- Clear the content column as it's no longer needed
UPDATE courses 
SET content = NULL 
WHERE title = 'MOT Class 1 & 2 Training' 
AND is_template = TRUE;
