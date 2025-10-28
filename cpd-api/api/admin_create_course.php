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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->title) || empty($data->description)) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create course. Data is incomplete."]);
    log_activity($db, null, null, "Course Creation Failed", "Incomplete data provided.");
    exit();
}

$query = "INSERT INTO courses (title, description, content, duration, category, status, instructor_id, start_date, end_date) VALUES (:title, :description, :content, :duration, :category, :status, :instructor_id, :start_date, :end_date)";

$stmt = $db->prepare($query);

$title = htmlspecialchars(strip_tags($data->title));
$description = htmlspecialchars(strip_tags($data->description));
$content = isset($data->content) ? htmlspecialchars($data->content) : '';
$duration = isset($data->duration) ? (int)$data->duration : null;
$category = isset($data->category) ? htmlspecialchars(strip_tags($data->category)) : null;
$status = isset($data->status) ? htmlspecialchars(strip_tags($data->status)) : 'draft';
$instructor_id = isset($data->instructor_id) ? (int)$data->instructor_id : null;
$start_date = isset($data->start_date) ? $data->start_date : null;
$end_date = isset($data->end_date) ? $data->end_date : null;

$stmt->bindParam(':title', $title);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':content', $content);
$stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
$stmt->bindParam(':category', $category);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(["message" => "Course was created."]);
    log_activity($db, null, null, "Course Created", "Course: {$title}");
} else {
    http_response_code(500);
    echo json_encode(["message" => "Unable to create course."]);
    log_activity($db, null, null, "Course Creation Failed", "Course: {$title}");
}
?>