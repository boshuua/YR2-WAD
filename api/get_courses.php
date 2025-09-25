<?php
header('Content-Type: application/json');

require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$start_date = $_GET['start'];
$end_date = $_GET['end'];

try {
    // Select description along with other course data
    $stmt = $pdo->prepare(
        "SELECT id, title, course_date, end_date, description FROM courses WHERE course_date BETWEEN ? AND ?"
    );
    $stmt->execute([$start_date, $end_date]);
    $courses = $stmt->fetchAll();

    $events = [];
    foreach ($courses as $course) {
        $eventColor = '#3498db'; 
        if (stripos($course['title'], 'LEVEL 3') !== false) { $eventColor = '#e74c3c'; }

        $events[] = [
            'id'    => $course['id'],
            'title' => $course['title'],
            'start' => $course['course_date'],
            'end'   => $course['end_date'],
            'color' => $eventColor,
            'description' => $course['description'] // Add the description
        ];
    }

    echo json_encode($events);
} catch (PDOException $e) {
    echo json_encode([]);
}