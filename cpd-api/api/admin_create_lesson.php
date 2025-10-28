<?php
session_start();
include_once '../config/database.php';
include_once '../helpers/log_helper.php';

// --- Security Check ---
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access Denied: Admin privileges required."]);
    exit();
}

// --- Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$data = json_decode(file_get_contents("php://input"));

// Basic validation
if (empty($data->course_id) || empty($data->title)) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create lesson. Course ID and title are required."]);
    log_activity($db, null, null, "Lesson Creation Failed", "Incomplete data provided.");
    exit();
}

// Prepare SQL query
$query = "INSERT INTO lessons (
            course_id, 
            title, 
            description, 
            order_index
          ) VALUES (
            :course_id, 
            :title, 
            :description, 
            :order_index
          )";

$stmt = $db->prepare($query);

// Sanitize and bind values
$course_id = (int)$data->course_id;
$title = htmlspecialchars(strip_tags($data->title));
$description = isset($data->description) ? htmlspecialchars(strip_tags($data->description)) : null;
$order_index = isset($data->order_index) ? (int)$data->order_index : 0;

$stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmt->bindParam(':title', $title);
$stmt->bindParam(':description', $description, PDO::PARAM_STR);
$stmt->bindParam(':order_index', $order_index, PDO::PARAM_INT);

// Execute query
if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(["message" => "Lesson was created.", "id" => $db->lastInsertId()]);
    log_activity($db, null, null, "Lesson Created", "Lesson: {$title} for Course ID: {$course_id}");
} else {
    http_response_code(500);
    echo json_encode(["message" => "Unable to create lesson."]);
    log_activity($db, null, null, "Lesson Creation Failed", "Lesson: {$title} for Course ID: {$course_id}");
}

?>