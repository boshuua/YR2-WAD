<?php
// Load configuration
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS
handleCorsPrelight();

// Require POST
requireMethod('POST');

// Require Admin
requireAdmin();

$data = getJsonInput();
requireFields($data, ['template_id', 'start_date', 'end_date']);

$templateId = $data->template_id;
$startDate = $data->start_date;
$endDate = $data->end_date;
$newTitle = isset($data->title) ? sanitizeString($data->title) : null; // Optional override

$database = new Database();
$db = $database->getConn();

try {
    $db->beginTransaction();

    // 1. Get Template Details
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = :id");
    $stmt->execute([':id' => $templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        throw new Exception("Template not found.");
    }

    // 2. Create New Course (Deep Copy)
    // Use provided title or append year/date to template title? 
    // For now, let's use the provided title or specific logic if needed. 
    // The UI should probably provide the full title.
    $courseTitle = $newTitle ? $newTitle : $template['title'] . " (Scheduled)";

    $insertCourse = $db->prepare("
        INSERT INTO courses 
        (title, description, content, duration, required_hours, category, status, instructor_id, is_template, start_date, end_date)
        VALUES 
        (:title, :desc, :content, :duration, :req_hours, :cat, 'published', :inst_id, FALSE, :start, :end)
    ");

    $insertCourse->execute([
        ':title' => $courseTitle,
        ':desc' => $template['description'],
        ':content' => $template['content'],
        ':duration' => $template['duration'],
        ':req_hours' => $template['required_hours'],
        ':cat' => $template['category'],
        ':inst_id' => $template['instructor_id'],
        ':start' => $startDate,
        ':end' => $endDate
    ]);

    $newCourseId = $db->lastInsertId();

    // 3. Copy Lessons
    $getLessons = $db->prepare("SELECT * FROM lessons WHERE course_id = :tid ORDER BY order_index ASC");
    $getLessons->execute([':tid' => $templateId]);
    $lessons = $getLessons->fetchAll(PDO::FETCH_ASSOC);

    // Map old lesson ID -> new lesson ID
    $lessonMap = [];

    foreach ($lessons as $lesson) {
        $insLesson = $db->prepare("
            INSERT INTO lessons (course_id, title, content, order_index)
            VALUES (:cid, :title, :content, :oi)
        ");
        $insLesson->execute([
            ':cid' => $newCourseId,
            ':title' => $lesson['title'],
            ':content' => $lesson['content'],
            ':oi' => $lesson['order_index']
        ]);
        $newLessonId = $db->lastInsertId();
        $lessonMap[$lesson['id']] = $newLessonId;
    }

    // 4. Copy Questions
    // We need to handle both Course-level questions (lesson_id IS NULL) and Lesson-level questions
    $getQuestions = $db->prepare("SELECT * FROM questions WHERE course_id = :tid");
    $getQuestions->execute([':tid' => $templateId]);
    $questions = $getQuestions->fetchAll(PDO::FETCH_ASSOC);

    foreach ($questions as $q) {
        $newLessonId = null;
        if ($q['lesson_id'] && isset($lessonMap[$q['lesson_id']])) {
            $newLessonId = $lessonMap[$q['lesson_id']];
        } else if ($q['lesson_id']) {
            // Lesson was not mapped? Should not happen if referential integrity holds
            continue;
        }

        $insQ = $db->prepare("
            INSERT INTO questions (course_id, lesson_id, question_text, question_type)
            VALUES (:cid, :lid, :qtext, :qtype)
        ");
        $insQ->execute([
            ':cid' => $newCourseId,
            ':lid' => $newLessonId,
            ':qtext' => $q['question_text'],
            ':qtype' => $q['question_type']
        ]);
        $newQuestionId = $db->lastInsertId();

        // 5. Copy Options for this Question
        $getOpts = $db->prepare("SELECT * FROM question_options WHERE question_id = :qid");
        $getOpts->execute([':qid' => $q['id']]);
        $options = $getOpts->fetchAll(PDO::FETCH_ASSOC);

        foreach ($options as $opt) {
            $insOpt = $db->prepare("
                INSERT INTO question_options (question_id, option_text, is_correct)
                VALUES (:qid, :text, :correct)
            ");
            $insOpt->execute([
                ':qid' => $newQuestionId,
                ':text' => $opt['option_text'],
                ':correct' => $opt['is_correct']
            ]);
        }
    }

    $db->commit();

    log_activity($db, getCurrentUserId(), getCurrentUserEmail(), "Course Scheduled", "Created '$courseTitle' from template ID $templateId");

    sendCreated(["message" => "Course scheduled successfully!", "course_id" => $newCourseId]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    sendServerError("Failed to schedule course: " . $e->getMessage());
}
?>