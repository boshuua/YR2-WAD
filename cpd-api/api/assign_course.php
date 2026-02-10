<?php
// Load configuration
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';

// Handle CORS
handleCorsPrelight();

// Require POST
requireMethod('POST');

// Require Admin
requireAdmin();

// Get Input Data
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->user_id) || !isset($data->course_id) || !isset($data->start_date)) { // We can call it start_date or scheduled_date
    sendBadRequest("Missing required fields: user_id, course_id, start_date");
}

$userId = intval($data->user_id);
$courseId = intval($data->course_id);
$scheduledDate = $data->start_date; // YYYY-MM-DD

$database = new Database();
$db = $database->getConn();

try {
    // 1. Check if course exists
    $stmt = $db->prepare("SELECT id, title, duration, required_hours FROM courses WHERE id = :id");
    $stmt->execute([':id' => $courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        sendNotFound("Course not found.");
    }

    // 2. Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendNotFound("User not found.");
    }

    // 3. Check if already enrolled
    $stmt = $db->prepare("SELECT id FROM user_course_progress WHERE user_id = :uid AND course_id = :cid");
    $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Option: Update the scheduled date if already exists? Or just error?
        // Let's update the enrollment date to the new scheduled date
        $updateStmt = $db->prepare("UPDATE user_course_progress SET enrolled_at = :date, updated_at = NOW() WHERE id = :id");
        $updateStmt->execute([':date' => $scheduledDate, ':id' => $existing['id']]);

        sendOk(["message" => "Course assignment updated to new date.", "course_id" => $courseId, "user_id" => $userId]);
    } else {
        // 4. Enroll User (Create Progress Record)
        // enrolled_at acts as the "Scheduled Date" for this purpose
        $stmt = $db->prepare("
            INSERT INTO user_course_progress (user_id, course_id, status, enrolled_at, hours_completed)
            VALUES (:uid, :cid, 'not_started', :date, 0)
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
            ':date' => $scheduledDate
        ]);

        sendCreated(["message" => "Course assigned successfully.", "course_id" => $courseId, "user_id" => $userId]);
    }

} catch (Exception $e) {
    sendServerError("Error assigning course: " . $e->getMessage());
}
?>