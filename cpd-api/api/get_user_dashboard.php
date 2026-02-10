<?php
// Load configuration
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';

// Handle CORS
handleCorsPrelight();

// Require GET
requireMethod('GET');

// Require Admin
requireAdmin();

if (!isset($_GET['id'])) {
    sendBadRequest("User ID is required.");
}

$userId = intval($_GET['id']);
$periodStart = $_GET['start_date'] ?? date('Y-01-01'); // Default to current year
$periodEnd = $_GET['end_date'] ?? date('Y-12-31');

$database = new Database();
$db = $database->getConn();

try {
    // 1. Fetch User Profile
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, job_title, access_level, created_at FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendNotFound("User not found.");
    }

    // 2. Fetch Enrolments (Active Courses)
    // We consider 'active' as courses not yet completed
    $stmtEnrol = $db->prepare("
        SELECT c.id, c.title, ucp.status, ucp.enrolled_at, ucp.updated_at
        FROM user_course_progress ucp
        JOIN courses c ON ucp.course_id = c.id
        WHERE ucp.user_id = :uid AND ucp.status != 'completed'
        ORDER BY ucp.enrolled_at DESC
    ");
    $stmtEnrol->execute([':uid' => $userId]);
    $enrolments = $stmtEnrol->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Exam History (Completed Courses)
    // We use completed courses as a proxy for exams for now
    $stmtExams = $db->prepare("
        SELECT c.id, c.title, ucp.score, ucp.completion_date, ucp.hours_completed
        FROM user_course_progress ucp
        JOIN courses c ON ucp.course_id = c.id
        WHERE ucp.user_id = :uid AND ucp.status = 'completed'
        AND (ucp.completion_date BETWEEN :start AND :end OR ucp.completion_date IS NULL)
        ORDER BY ucp.completion_date DESC
    ");
    $stmtExams->execute([':uid' => $userId, ':start' => $periodStart, ':end' => $periodEnd]);
    $exams = $stmtExams->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch Attachments
    $stmtAtt = $db->prepare("SELECT id, file_name, file_type, created_at FROM user_attachments WHERE user_id = :uid ORDER BY created_at DESC");
    $stmtAtt->execute([':uid' => $userId]);
    $attachments = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Calculate Training Summary (Mock logic for 'Group B 0%')
    // Real logic would depend on specific training requirements
    $trainingSummary = [
        "group_b_percentage" => 0, // Placeholder
        "total_hours" => 0
    ];

    foreach ($exams as $exam) {
        $trainingSummary['total_hours'] += floatval($exam['hours_completed']);
    }

    $response = [
        "user" => $user,
        "enrolments" => $enrolments,
        "exam_history" => $exams,
        "attachments" => $attachments,
        "training_summary" => $trainingSummary
    ];

    sendOk($response);

} catch (Exception $e) {
    sendServerError("Error fetching dashboard data: " . $e->getMessage());
}
?>