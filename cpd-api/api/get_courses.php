<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../helpers/log_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$query = "SELECT id, title, description FROM courses ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$num = $stmt->rowCount();

$courses_arr = array();

if ($num > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        $course_item = array(
            "id" => $id,
            "title" => $title,
            "description" => html_entity_decode($description)
        );
        array_push($courses_arr, $course_item);
    }
    http_response_code(200);
    echo json_encode($courses_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "No courses found."));
}

log_activity($db, null, null, "Viewed Courses", "All courses listed.");

?>