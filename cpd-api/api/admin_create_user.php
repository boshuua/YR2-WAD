<?php
session_start();
include_once '../config/database.php';
include_once '../helpers/log_helper.php';
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access Denied: Admin privileges required."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->first_name) || !isset($data->email) || !isset($data->password) || !isset($data->access_level)) {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data."]);
    exit();
}

$database = new Database();
$db = $database->getConn(); 

$query = "INSERT INTO users (first_name, last_name, email, password, job_title, access_level)
          VALUES (:first_name, :last_name, :email, crypt(:password, gen_salt('bf')), :job_title, :access_level)";

$stmt = $db->prepare($query);

// Handle optional fields
$last_name = $data->last_name ?? '';
$job_title = $data->job_title ?? '';

$stmt->bindParam(':first_name', $data->first_name);
$stmt->bindParam(':last_name', $last_name);
$stmt->bindParam(':email', $data->email);
$stmt->bindParam(':password', $data->password); 
$stmt->bindParam(':job_title', $job_title);
$stmt->bindParam(':access_level', $data->access_level);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(["message" => "User created successfully."]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to create user."]);
}
?>