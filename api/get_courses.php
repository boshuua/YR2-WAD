<?php
header('Content-Type: application/json');

require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// Get the start and end dates from FullCalendar's request
$start_date = $_GET['start'];
$end_date = $_GET['end'];

try {
    // Modify the SQL query to only select courses between the start and end dates
    $stmt = $pdo->prepare(
        "SELECT id, title, course_date FROM courses WHERE course_date BETWEEN ? AND ?"
    );
    $stmt->execute([$start_date, $end_date]);
    $courses = $stmt->fetchAll();

    $events = [];
    foreach ($courses as $course) {
        $eventColor = '#3498db'; 
        if (stripos($course['title'], 'LEVEL 3') !== false) {
            $eventColor = '#e74c3c';
        }

        $events[] = [
            'id'    => $course['id'],
            'title' => $course['title'],
            'start' => $course['course_date'],
            'color' => $eventColor 
        ];
    }

    echo json_encode($events);

} catch (PDOException $e) {
    echo json_encode([]);
}