<?php
header('Content-Type: application/json');

require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

try {
    // Select the course data without the location
    $stmt = $pdo->prepare("SELECT id, title, course_date FROM courses");
    $stmt->execute();
    $courses = $stmt->fetchAll();

    $events = [];
    foreach ($courses as $course) {
        // --- Logic for custom event colors ---
        $eventColor = '#3498db'; // Default blue color
        if (stripos($course['title'], 'LEVEL 3') !== false) {
            $eventColor = '#e74c3c'; // Red color for Level 3
        }

        $events[] = [
            'id'    => $course['id'],
            'title' => $course['title'], // Title is now just the course title
            'start' => $course['course_date'],
            'color' => $eventColor 
        ];
    }

    echo json_encode($events);

} catch (PDOException $e) {
    echo json_encode([]);
}