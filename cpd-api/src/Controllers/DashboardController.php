<?php
// cpd-api/src/Controllers/DashboardController.php

namespace App\Controllers;

use PDO;

class DashboardController extends BaseController
{

    public function getUserDashboard()
    {
        requireAuth();

        $userId = $_GET['id'] ?? $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $this->error("User ID is required.", 400);
        }

        // Check cache first (5 minute cache)
        $cacheKey = "dashboard_user_{$userId}";
        $cacheFile = sys_get_temp_dir() . "/{$cacheKey}.json";
        $cacheLifetime = 300; // 5 minutes

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheLifetime)) {
            $cachedData = json_decode(file_get_contents($cacheFile), true);
            if ($cachedData) {
                header('X-Cache: HIT');
                $this->json($cachedData);
                return;
            }
        }

        header('X-Cache: MISS');

        try {
            // Use standard SQL queries (MySQL compatible) but batch them efficiently

            // 1. Fetch User Info
            $stmtUser = $this->db->prepare("SELECT id, first_name, last_name, email, job_title, access_level, created_at FROM users WHERE id = :uid");
            $stmtUser->execute([':uid' => $userId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->error("User not found.", 404);
            }

            // 2. Fetch Active AND Completed Courses in ONE query
            $stmtCourses = $this->db->prepare("
                SELECT 
                    c.id,
                    c.title,
                    c.category,
                    ucp.status,
                    ucp.enrolled_at,
                    ucp.completion_date,
                    ucp.hours_completed,
                    ucp.score,
                    ucp.last_accessed_lesson_id,
                    COUNT(DISTINCT l.id) as total_lessons,
                    COUNT(DISTINCT CASE WHEN ulp.status = 'completed' THEN ulp.lesson_id END) as completed_lessons
                FROM user_course_progress ucp
                JOIN courses c ON ucp.course_id = c.id
                LEFT JOIN lessons l ON c.id = l.course_id
                LEFT JOIN user_lesson_progress ulp ON l.id = ulp.lesson_id AND ulp.user_id = ucp.user_id
                WHERE ucp.user_id = :uid 
                    AND c.is_template = FALSE
                GROUP BY c.id, c.title, c.category, ucp.status, ucp.enrolled_at, ucp.completion_date, ucp.hours_completed, ucp.score, ucp.last_accessed_lesson_id
                ORDER BY 
                    CASE 
                        WHEN ucp.status IN ('enrolled', 'in_progress') THEN ucp.enrolled_at
                        ELSE ucp.completion_date
                    END DESC
            ");
            $stmtCourses->execute([':uid' => $userId]);
            $allCourses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

            // Split into active, completed, and exams
            $activeCourses = [];
            $completedCourses = [];
            $examHistory = [];

            $totalLessons = 0;
            $totalCompletedLessons = 0;

            foreach ($allCourses as $course) {
                // If it's an assessment, add to exam history
                if ($course['category'] === 'Assessment' || strpos($course['title'], 'Assessment') !== false) {
                    // Only add to exam history if completed? Or all? User said "Exams History".
                    // Usually history implies completed.
                    // But if they are failing they might want to see it?
                    // Let's add all assessments to exam history.
                    $examHistory[] = [
                        'id' => $course['id'],
                        'title' => $course['title'],
                        'status' => $course['status'],
                        'completion_date' => $course['completion_date'],
                        'score' => $course['score'],
                        'passed' => $course['score'] >= 80
                    ];
                    continue; // Skip adding to active/completed lists
                }

                if ($course['status'] === 'completed') {
                    $completedCourses[] = [
                        'id' => $course['id'],
                        'title' => $course['title'],
                        'completion_date' => $course['completion_date'],
                        'hours_completed' => $course['hours_completed']
                    ];
                } else {
                    $activeCourses[] = [
                        'id' => $course['id'],
                        'title' => $course['title'],
                        'status' => $course['status'],
                        'enrolled_at' => $course['enrolled_at'],
                        'last_accessed_lesson_id' => $course['last_accessed_lesson_id'],
                        'total_lessons' => (int) $course['total_lessons'],
                        'completed_lessons' => (int) $course['completed_lessons']
                    ];
                }
                $totalLessons += (int) $course['total_lessons'];
                $totalCompletedLessons += (int) $course['completed_lessons'];
            }

            // 3. Fetch Attachments
            $stmtAtt = $this->db->prepare("SELECT id, file_name, file_type, created_at FROM user_attachments WHERE user_id = :uid ORDER BY created_at DESC");
            $stmtAtt->execute([':uid' => $userId]);
            $attachments = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate training summary
            $trainingSummary = [
                'active_courses_count' => count($activeCourses),
                'completed_courses_count' => count($completedCourses),
                'overall_percentage' => $totalLessons > 0
                    ? round(($totalCompletedLessons / $totalLessons) * 100)
                    : 0
            ];

            $response = [
                "user" => $user,
                "training_summary" => $trainingSummary,
                "active_courses" => $activeCourses,
                "completed_courses" => $completedCourses,
                "exam_history" => $examHistory,
                "attachments" => $attachments
            ];

            // Cache the result
            file_put_contents($cacheFile, json_encode($response));

            $this->json($response);

        } catch (\Exception $e) {
            error_log("Dashboard error: " . $e->getMessage());
            $this->error("Failed to retrieve dashboard data: " . $e->getMessage(), 500);
        }
    }
}
