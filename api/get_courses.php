<?php

header('Content-Type: application/json');

require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$start_date = $_GET['start'];
$end_date = $_GET['end'];

try {
    
    $stmt = $pdo->prepare(
        "SELECT 
            id, 
            title, 
            course_date, 
            end_date, 
            description,
            CASE
                WHEN end_date < NOW() THEN 'completed'
                ELSE 'upcoming'
            END AS status,
            CASE
                WHEN end_date < NOW() THEN '#6c757d' -- A grey color for completed
                ELSE '#3498db' -- Original blue color
            END AS color
        FROM courses 
        WHERE course_date BETWEEN ? AND ?"
    );
    $stmt->execute([$start_date, $end_date]);
    $courses = $stmt->fetchAll();

    $events = [];
    foreach ($courses as $course) {
        $events[] = [
            'id' => $course['id'],
            'title' => $course['title'],
            'start' => $course['course_date'],
            'end' => $course['end_date'],
            'color' => $course['color'], 
            'description' => $course['description'],
            'status' => $course['status'] 
        ];
    }

    echo json_encode($events);
} catch (PDOException $e) {
    echo json_encode([]);
}