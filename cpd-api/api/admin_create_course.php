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

$query = "INSERT INTO courses (title, description, content) VALUES (:title, :description, :content)";

$stmt = $db->prepare($query);

$title = htmlspecialchars(strip_tags($data->title));
$description = htmlspecialchars(strip_tags($data->description));
$content = isset($data->content) ? htmlspecialchars($data->content) : '';

$stmt->bindParam(':title', $title);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':content', $content);

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