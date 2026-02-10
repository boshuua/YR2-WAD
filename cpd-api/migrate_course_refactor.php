<?php
include_once 'config/database.php';

$database = new Database();
$db = $database->getConn();

try {
    // 1. Add is_locked column if it doesn't exist
    $sql = "ALTER TABLE courses ADD COLUMN IF NOT EXISTS is_locked BOOLEAN DEFAULT FALSE;";
    $db->exec($sql);
    echo "Added is_locked column to courses table.\n";

    // 2. Add duration_minutes column if it doesn't exist (useful for calendar)
    $sql = "ALTER TABLE courses ADD COLUMN IF NOT EXISTS duration_minutes INT DEFAULT 60;"; // Default 1 hour
    $db->exec($sql);
    echo "Added duration_minutes column to courses table.\n";

    // 3. Seed the 'MOT Annual Training 2025-26' Course
    // Check if it already exists to avoid duplicates
    $stmt = $db->prepare("SELECT id FROM courses WHERE title = 'MOT Annual Training 2025-26' LIMIT 1");
    $stmt->execute();
    $existingCourse = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingCourse) {
        // Create Course
        $stmt = $db->prepare("INSERT INTO courses (title, description, duration_minutes, is_locked, created_at) VALUES (:title, :desc, :dur, :locked, NOW()) RETURNING id");
        $stmt->execute([
            ':title' => 'MOT Annual Training 2025-26',
            ':desc' => 'Mandatory annual training for MOT testers. Covers Brakes, Lights, and Body Structure.',
            ':dur' => 180, // 3 hours
            ':locked' => 'true'
        ]);
        $courseId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
        echo "Created Course: MOT Annual Training 2025-26 (ID: $courseId)\n";

        // Create Lessons (Sections)
        $lessons = [
            ['title' => 'Section 1: Brakes & Tyres', 'content' => 'Content covering inspection of braking systems and tyre condition requirements.'],
            ['title' => 'Section 2: Lights & Signalling', 'content' => 'Content covering headlamps, stop lamps, indicators, and reflectors.'],
            ['title' => 'Section 3: Body & Structure', 'content' => 'Content covering vehicle structure, corrosion assessment, and general condition.']
        ];

        foreach ($lessons as $index => $lesson) {
            $stmt = $db->prepare("INSERT INTO lessons (course_id, title, content, order_index, created_at) VALUES (:cid, :title, :content, :order, NOW())");
            $stmt->execute([
                ':cid' => $courseId,
                ':title' => $lesson['title'],
                ':content' => $lesson['content'],
                ':order' => $index + 1
            ]);
        }
        echo "Added 3 Lessons to Course ID: $courseId\n";

        // Create Quiz Questions
        // 10 Mock Questions
        $questions = [
            ['text' => 'What is the minimum tread depth for a passenger car tyre?', 'type' => 'multiple_choice', 'options' => json_encode(['1.0mm', '1.6mm', '2.0mm', '3.0mm']), 'answer' => '1.6mm'],
            ['text' => 'Which of these lights is NOT mandatory on the rear of a vehicle?', 'type' => 'multiple_choice', 'options' => json_encode(['Stop Lamp', 'Fog Lamp', 'Reversing Lamp', 'Reflector']), 'answer' => 'Reversing Lamp'], // Technically reversing lamps aren't testable on older cars, simplifying
            ['text' => 'A brake pipe is excessively corroded if...', 'type' => 'multiple_choice', 'options' => json_encode(['It has surface rust', 'It is pitted', 'It is weakened so that its wall thickness is reduced by 1/3', 'It is painted']), 'answer' => 'It is weakened so that its wall thickness is reduced by 1/3'],
            ['text' => 'How often should MOT training be completed?', 'type' => 'multiple_choice', 'options' => json_encode(['Monthly', 'Annually', 'Every 5 years', 'Once only']), 'answer' => 'Annually'],
            ['text' => 'What class of vehicle includes passenger cars?', 'type' => 'multiple_choice', 'options' => json_encode(['Class 1', 'Class 4', 'Class 7', 'Class 5']), 'answer' => 'Class 4'],
            ['text' => 'When is the first MOT due for a new car?', 'type' => 'multiple_choice', 'options' => json_encode(['1 year', '3 years', '5 years', '4 years']), 'answer' => '3 years'],
            ['text' => 'What colour must a front indicator show?', 'type' => 'multiple_choice', 'options' => json_encode(['Red', 'White', 'Amber', 'Blue']), 'answer' => 'Amber'],
            ['text' => 'Is a missing oil filler cap an MOT failure?', 'type' => 'multiple_choice', 'options' => json_encode(['Yes, Major', 'Yes, Minor', 'No', 'Advisory']), 'answer' => 'Yes, Major'],
            ['text' => 'Can you test a vehicle with a dangerous defect?', 'type' => 'multiple_choice', 'options' => json_encode(['Yes, but refuse to road test', 'No, refuse to test', 'Yes, proceed as normal', 'Only if the owner is present']), 'answer' => 'Yes, but refuse to road test'],
            ['text' => 'What is the pass mark for the MOT Annual Assessment?', 'type' => 'multiple_choice', 'options' => json_encode(['50%', '70%', '80%', '100%']), 'answer' => '80%']
        ];

        foreach ($questions as $q) {
            $stmt = $db->prepare("INSERT INTO questions (course_id, question_text, question_type, options, correct_answer, created_at) VALUES (:cid, :txt, :type, :opts, :ans, NOW())");
            $stmt->execute([
                ':cid' => $courseId,
                ':txt' => $q['text'],
                ':type' => $q['type'],
                ':opts' => $q['options'],
                ':ans' => $q['answer']
            ]);
        }
        echo "Added 10 Quiz Questions to Course ID: $courseId\n";

    } else {
        echo "MOT Annual Training course already exists. Skipping seed.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>