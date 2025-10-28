<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../helpers/log_helper.php';

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$data = json_decode(file_get_contents("php://input"));

// Basic validation
if (empty($data->title) || empty($data->description) || empty($data->category)) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create course. Data is incomplete."]);
    log_activity($db, null, null, "Course Creation Failed", "Incomplete data provided.");
    exit();
}

// Prepare SQL query
$query = "INSERT INTO courses (
            title, 
            description, 
            code, 
            duration, 
            category, 
            instructor_id, 
            status
          ) VALUES (
            :title, 
            :description, 
            :code, 
            :duration, 
            :category, 
            :instructor_id, 
            :status
          )";

$stmt = $db->prepare($query);

// Sanitize and bind values
$title = htmlspecialchars(strip_tags($data->title));
$description = htmlspecialchars(strip_tags($data->description));
$code = isset($data->code) ? htmlspecialchars(strip_tags($data->code)) : null;
$duration = isset($data->duration) ? (int)$data->duration : null;
$category = htmlspecialchars(strip_tags($data->category));
$instructor_id = isset($data->instructor_id) ? (int)$data->instructor_id : null;
$status = isset($data->status) ? htmlspecialchars(strip_tags($data->status)) : 'draft';

$stmt->bindParam(':title', $title);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':code', $code, PDO::PARAM_STR);
$stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
$stmt->bindParam(':category', $category);
$stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmt->bindParam(':status', $status);

// Execute query
if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(["message" => "Course was created."]);
    log_activity($db, null, null, "Course Created", "Course: {$title}");
} else {
    http_response_code(500);
    echo json_encode(["message" => "Unable to create course."]);
    log_activity($db, null, null, "Course Creation Failed", "Course: {$title}");
}

// Note: User ID and Email for log_activity should ideally come from authenticated session.
// For now, using null as authentication is not yet fully implemented for API calls.
?>