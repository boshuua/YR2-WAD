<?php
// Set the content type header to JSON
header('Content-Type: application/json');

// We still need the auth check to make sure only logged-in users can access this data
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

try {
    // Select all courses. The calendar will handle showing the correct month.
    $stmt = $pdo->prepare("SELECT id, title, course_date FROM courses");
    $stmt->execute();
    $courses = $stmt->fetchAll();

    $events = [];
    foreach ($courses as $course) {
        // FullCalendar expects data in a specific format with keys like 'id', 'title', and 'start'
        $events[] = [
            'id'    => $course['id'],
            'title' => $course['title'],
            'start' => $course['course_date'] // e.g., '2025-10-26 10:00:00'
        ];
    }

    // Output the events array as a JSON string
    echo json_encode($events);

} catch (PDOException $e) {
    // In case of an error, return an empty array
    echo json_encode([]);
}