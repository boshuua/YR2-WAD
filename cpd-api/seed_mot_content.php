<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConn();

echo "Starting Content Seed...\n";

// 1. Define Content
$modules = [
    [
        'title' => 'Module 1: Corrosion & Structural Integrity',
        'content' => '
<h2>The Core Concept</h2>
<p>You must identify "Prescribed Areas" and "Structural Members." The golden rule is the 30cm rule, but the exam focuses on applying this to complex or ambiguous situations.</p>

<h3>1. Key Technical Definitions</h3>
<ul>
    <li><strong>Prescribed Area:</strong> Any structure within 30cm of a load-bearing point (suspension, seat belt, braking, or steering attachment).</li>
    <li><strong>The "Gap" Rule:</strong> If there is a gap between a corroded panel and the load-bearing mount, it might not be a failure unless the corrosion compromises the mounting\'s rigidity.</li>
</ul>

<h3>Bonded Repairs</h3>
<p><strong>General Rule:</strong> You generally cannot accept a bonded (glued) repair patch on a structural area unless the manufacturer specifically allows it.</p>
<p><strong>Exam Protocol:</strong> If you see a bonded panel on a prescribed area, you Fail (Major) unless you have evidence it is an approved repair. However, if the repair is covered in underseal and you cannot determine the method (welded vs bonded), the official guidance is often Pass and Advise ("Repair method could not be determined"), provided it appears solid.</p>

<h3>Towbar Mounting Corrosion</h3>
<p>The failure is not just the towbar itself; it is the vehicle structure it attaches to.</p>
<ul>
    <li><strong>Major Defect:</strong> Corrosion within 30cm of a towbar mounting that reduces its strength.</li>
    <li><strong>Dangerous Defect:</strong> The component is likely to detach.</li>
</ul>

<h3>2. Scenario-Based Learning</h3>
<p><strong>Scenario A:</strong> You find a patch repair on a structural sill. It is continuous seam welded on three sides but stitch welded (tack welded) on the fourth side.</p>
<p><strong>Verdict:</strong> Fail (Major).</p>
<p><strong>Reasoning:</strong> Structural patch repairs must be continuous seam welded around the entire perimeter to maintain structural integrity. Stitch welding is acceptable only if the original panel was stitch welded (rare for sills).</p>

<p><strong>Scenario B:</strong> A subframe mounting point has heavy surface rust. When you tap it with the corrosion assessment tool, the metal sounds solid and does not crumble or flex.</p>
<p><strong>Verdict:</strong> Pass and Advise.</p>
<p><strong>Reasoning:</strong> Surface corrosion is not a failure. To fail, the metal must be perforated (holes) or significantly weakened (crunchy/flexing).</p>
'
    ],
    [
        'title' => 'Module 2: Vehicle Classification',
        'content' => '
<h2>The "Grey Areas"</h2>
<p>The syllabus emphasizes vehicles that "blur the lines" between classes.</p>

<h3>1. Dual Purpose Vehicles (Pickups)</h3>
<p><strong>Definition:</strong> To be "Dual Purpose" (and thus Class 4), a vehicle must have:</p>
<ul>
    <li>A row of seats behind the driver.</li>
    <li>Side and rear windows.</li>
    <li>Unladen weight under 2,040kg.</li>
    <li>OR be 4WD (constructed to use it off-road).</li>
</ul>
<p><strong>The "Weight" Trap:</strong> Most pickups (Navara, Ranger, Hilux) are Class 4. You usually test them at 50% efficiency (Class 4 standard). If a pickup has a DGW over 3,000kg, it serves as Class 7 unless it meets the "Dual Purpose" definition.</p>

<h3>2. Quadricycles (L7e)</h3>
<p>Examples: Citroen Ami, Renault Twizy.</p>
<p><strong>Testing Class:</strong> Usually Class 4 (if unladen weight <400kg, or <550kg for goods).</p>
<p><strong>Specific Check Differences:</strong></p>
<ul>
    <li><strong>Tyres:</strong> Operate to different standards than cars.</li>
    <li><strong>Lights:</strong> May not have all the lights a car has.</li>
    <li><strong>Brakes:</strong> You usually cannot put them on a roller brake tester due to narrow track width. Use a Decelerometer.</li>
</ul>

<h3>3. Goods Vehicle vs. Motor Caravan</h3>
<p><strong>Question:</strong> A Ford Transit van has been converted into a camper. The V5C still says "Panel Van". It has a bed and table fixed in place. The DGW is 3,200kg.</p>
<p><strong>Verdict:</strong> Test as Class 7.</p>
<p><strong>Reasoning:</strong> Unless the V5C has been updated to "Motor Caravan," it is technically still a Goods Vehicle. Since it is over 3,000kg DGW, it falls into Class 7.</p>
'
    ]
];

$questions = [
    [
        'question' => "Q1. You are inspecting a visible separate chassis body mount (outrigger) that is heavily corroded. The chassis itself is sound. How do you assess this?",
        'options' => [
            "Pass and advise 'Body mount corroded'",
            "Fail - Major (Body mounting prescribed area is excessively corroded)",
            "Fail - Dangerous (Body security imminent failure)",
            "Fail - Major (Chassis strength significantly reduced)"
        ],
        'correct' => 1 // B (0-indexed => 1)
    ],
    [
        'question' => "Q2. On a monocoque vehicle (unibody), you find a small hole (approx 5mm) in the floor pan. It is located 25cm away from the seat belt anchorage point. The metal surrounding the hole is solid.",
        'options' => [
            "Pass and advise 'Floor corroded'",
            "Fail - Major (Prescribed area is within 30cm of a seat belt anchorage)",
            "Fail - Major (Structure weakened)",
            "Pass - The hole is too small to affect structural rigidity"
        ],
        'correct' => 1 // B
    ],
    [
        'question' => "Q3. A customer presents a 2010 Ford Transit. You notice a repair patch on the sill (prescribed area). The patch is stitch-welded (tack welded) every 20mm. The original manufacturer's panel was spot welded.",
        'options' => [
            "Pass and advise",
            "Fail - Major (Inappropriate repair: Stitch welding is not acceptable for patch repairs on prescribed areas)",
            "Fail - Dangerous",
            "Pass - Stitch welding mimics spot welding"
        ],
        'correct' => 1 // B
    ],
    [
        'question' => "Q4. You are testing a Tesla Model 3. You notice the orange high-voltage cable running under the floor has a damaged outer insulation (orange covering), revealing the metal shielding underneath. The inner core is NOT visible.",
        'options' => [
            "Pass and advise 'High voltage cable insulation damaged'",
            "Fail - Major (High voltage wiring / insulation damaged)",
            "Fail - Dangerous (Risk of electric shock / short circuit)",
            "Pass - Only the inner core exposure is a fail"
        ],
        'correct' => 1 // B
    ],
    [
        'question' => "Q5. When testing the brakes on a Hybrid vehicle with 'Regenerative Braking,' what specific precaution must you take before using the roller brake tester (RBT)?",
        'options' => [
            "You must disconnect the 12V battery",
            "You must ensure the vehicle is in 'Tow Mode' or 'Neutral' with the ignition OFF to prevent regenerative braking from engaging",
            "You must always use a decelerometer; RBT is banned for Hybrids",
            "You must test it in 'Drive' to test the regenerative system"
        ],
        'correct' => 1 // B
    ],
    [
        'question' => "Q6. An EV is presented for test. The dashboard displays a 'System Fault' warning light related to the high-voltage battery system. The car still drives.",
        'options' => [
            "Pass and advise",
            "Fail - Major (Electrical system warning lamp indicates a fault)",
            "Fail - Dangerous",
            "Not a testable item (unless specifically ABS/SRS/ESC/PAS)"
        ],
        'correct' => 3 // D
    ],
    [
        'question' => "Q7. A 2018 vehicle has had its factory halogen headlamp bulbs replaced with LED bulbs. The beam pattern is correct and does not dazzle.",
        'options' => [
            "Pass",
            "Fail - Major (Light source and lamp not compatible)",
            "Fail - Major (Headlamp aim incorrect)",
            "Pass and advise 'Non-standard bulbs fitted'"
        ],
        'correct' => 1 // B
    ],
    [
        'question' => "Q8. A 2015 car has a TPMS (Tyre Pressure Monitoring System) warning light illuminated on the dashboard. The tyres all look fine.",
        'options' => [
            "Pass and advise",
            "Fail - Major (TPMS system malfunctioning or indicating a tyre fault)",
            "Fail - Minor",
            "Fail - Dangerous"
        ],
        'correct' => 1 // B
    ],
    [
        'question' => "Q9. You are testing a vehicle with 'Sequential' indicators. On the rear, the sweep moves from the outside towards the inside (center) of the vehicle.",
        'options' => [
            "Pass",
            "Fail - Major (Direction indicator shows light movement towards the wrong side)",
            "Fail - Major (Sequential indicators are illegal)",
            "Pass and advise"
        ],
        'correct' => 1 // B
    ],
    [
        'question' => "Q10. You are testing a Class 7 van (3,500kg DGW). The plate is missing, but you find the technical data online. You perform a roller brake test. What is the minimum efficiency for the Service Brake?",
        'options' => [
            "50%",
            "45%",
            "58%",
            "16%"
        ],
        'correct' => 0 // A
    ],
    [
        'question' => "Q11. During a brake test on a 2014 car, the front left wheel locks up at 150kgf. The front right wheel keeps turning and reaches 300kgf. The vehicle weighs 1000kg.",
        'options' => [
            "Pass",
            "Fail - Major (Brake imbalance across an axle exceeds 50%)",
            "Fail - Major (Wheel lockup occurred too early)",
            "Fail - Dangerous"
        ],
        'correct' => 1 // B
    ],
    [
        'question' => "Q12. A customer brings a vehicle back for a retest 9 working days after it failed. The failure was for a worn tyre and a blown headlight bulb. The repairer has fixed them. Do you need to perform a full test?",
        'options' => [
            "No, just check the failed items (Partial Retest)",
            "Yes, because it is over 24 hours",
            "Yes, because it has been more than 10 working days",
            "No, but you must charge half the fee"
        ],
        'correct' => 0 // A
    ],
    [
        'question' => "Q13. You log a vehicle on to the MTS. 10 minutes later, you realize you logged the wrong vehicle (wrong VIN). What should you do?",
        'options' => [
            "Continue the test and edit the VIN at the end",
            "Abort the test immediately (Reason: Registered in Error)",
            "Fail the vehicle and start again",
            "Call DVSA to cancel it"
        ],
        'correct' => 1 // B
    ]
];

// 2. Find Target Courses
// We want to update:
// - The "Locked" Template (Title 'MOT Annual Training 2025-26')
// - Any "Scheduled" instances (Title contains 'MOT Annual Training 2025-26')

$stmtObj = $db->prepare("SELECT id, title FROM courses WHERE title LIKE :term");
$stmtObj->execute([':term' => '%MOT Annual Training 2025-26%']);
$courses = $stmtObj->fetchAll(PDO::FETCH_ASSOC);

if (!$courses) {
    echo "No matching courses found. Creating the Template course first...\n";
    $stmtCreate = $db->prepare("INSERT INTO courses (title, description, duration, is_locked, is_template) VALUES (?, ?, ?, 1, 1)");
    $stmtCreate->execute([
        'MOT Annual Training 2025-26',
        'Mandatory annual training for MOT testers. Covers Brakes, Lights, and Body Structure.',
        60
    ]);
    $newId = $db->lastInsertId();
    $courses = [['id' => $newId, 'title' => 'MOT Annual Training 2025-26']];
    echo "Created Template Course ID: $newId\n";
}

foreach ($courses as $course) {
    $courseId = $course['id'];
    echo "Updating Course: " . $course['title'] . " (ID: $courseId)...\n";

    // 3. Clear Existing Lessons (Optional: depends if you want to wipe clean)
    // For now, let's delete existing lessons to ensure order and no dupes
    $db->prepare("DELETE FROM lessons WHERE course_id = ?")->execute([$courseId]);
    $db->prepare("DELETE FROM questions WHERE course_id = ?")->execute([$courseId]);

    // 4. Insert Lessons
    $stmtLesson = $db->prepare("INSERT INTO lessons (course_id, title, content, type, `order`) VALUES (?, ?, ?, 'text', ?)");
    $order = 1;
    foreach ($modules as $index => $mod) {
        echo "    Processing Module " . ($index + 1) . ": " . ($mod['title'] ?? 'NO TITLE') . "\n";
        try {
            $stmtLesson->execute([$courseId, $mod['title'], $mod['content'], $order++]);
        } catch (Exception $e) {
            echo "    ERROR inserting lesson: " . $e->getMessage() . "\n";
        }
    }
    echo "  - Added " . count($modules) . " lessons.\n";

    // 5. Insert Questions
    $stmtQ = $db->prepare("INSERT INTO questions (course_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, 'multiple_choice', ?, ?, ?, ?, ?)");
    foreach ($questions as $index => $q) {
        echo "    Processing Question " . ($index + 1) . "\n";
        try {
            // Validate Keys
            if (!isset($q['question']) || !isset($q['options']) || !isset($q['correct'])) {
                echo "    ERROR: Missing keys in Question " . ($index + 1) . "\n";
                print_r($q);
                continue;
            }
            $correctChar = ['A', 'B', 'C', 'D'][$q['correct']] ?? null;
            if (!$correctChar) {
                echo "    ERROR: Invalid correct index " . $q['correct'] . "\n";
                continue;
            }

            $stmtQ->execute([
                $courseId,
                $q['question'],
                $q['options'][0],
                $q['options'][1],
                $q['options'][2],
                $q['options'][3],
                $correctChar
            ]);
        } catch (Exception $e) {
            echo "    ERROR inserting question: " . $e->getMessage() . "\n";
        }
    }
    echo "  - Added " . count($questions) . " questions.\n";
}

echo "Seeding Complete.\n";
?>