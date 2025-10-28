<?php
session_start();
include_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Access Denied: User not logged in."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$userId = $_SESSION['user_id'];

$query = "SELECT 
            c.id AS course_id, 
            c.title, 
            c.description, 
            ucp.status AS user_progress_status,
            ucp.completion_date,
            ucp.score
          FROM courses c
          LEFT JOIN user_course_progress ucp ON c.id = ucp.course_id AND ucp.user_id = :user_id
          ORDER BY c.title ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();

$num = $stmt->rowCount();

$courses_arr = array();

if ($num > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        $course_item = array(
            "id" => $course_id,
            "title" => $title,
            "description" => html_entity_decode($description),
            "user_progress_status" => $user_progress_status ?? 'not_started',
            "completion_date" => $completion_date,
            "score" => $score
        );
        array_push($courses_arr, $course_item);
    }
    http_response_code(200);
    echo json_encode($courses_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "No courses found."));
}

?>