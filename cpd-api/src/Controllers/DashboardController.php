<?php
// cpd-api/src/Controllers/DashboardController.php

namespace App\Controllers;

use PDO;

class DashboardController extends BaseController
{

    public function getUserDashboard()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
                // SINGLE OPTIMIZED QUERY - Fetch everything at once using CTEs
                $stmt = $this->db->prepare("
                WITH user_info AS (
                    SELECT id, first_name, last_name, email, job_title, access_level, created_at 
                    FROM users 
                    WHERE id = :uid
                ),
                active_courses AS (
                    SELECT 
                        c.id,
                        c.title,
                        ucp.status,
                        ucp.enrolled_at,
                        COUNT(DISTINCT l.id) as total_lessons,
                        COUNT(DISTINCT CASE WHEN ulp.status = 'completed' THEN ulp.lesson_id END) as completed_lessons
                    FROM user_course_progress ucp
                    JOIN courses c ON ucp.course_id = c.id
                    LEFT JOIN lessons l ON c.id = l.course_id
                    LEFT JOIN user_lesson_progress ulp ON l.id = ulp.lesson_id AND ulp.user_id = ucp.user_id
                    WHERE ucp.user_id = :uid 
                        AND ucp.status IN ('enrolled', 'in_progress')
                        AND c.is_template = FALSE
                    GROUP BY c.id, c.title, ucp.status, ucp.enrolled_at
                ),
                completed_courses AS (
                    SELECT 
                        c.id,
                        c.title,
                        ucp.completion_date,
                        ucp.hours_completed
                    FROM user_course_progress ucp
                    JOIN courses c ON ucp.course_id = c.id
                    WHERE ucp.user_id = :uid 
                        AND ucp.status = 'completed'
                        AND c.is_template = FALSE
                ),
                overall_progress AS (
                    SELECT 
                        COUNT(DISTINCT l.id) as total_lessons,
                        COUNT(DISTINCT CASE WHEN ulp.status = 'completed' THEN ulp.lesson_id END) as completed_lessons
                    FROM user_course_progress ucp
                    JOIN courses c ON ucp.course_id = c.id
                    LEFT JOIN lessons l ON c.id = l.course_id
                    LEFT JOIN user_lesson_progress ulp ON l.id = ulp.lesson_id AND ulp.user_id = ucp.user_id
                    WHERE ucp.user_id = :uid 
                        AND c.is_template = FALSE
                ),
                user_attachments_list AS (
                    SELECT id, file_name, file_type, created_at 
                    FROM user_attachments 
                    WHERE user_id = :uid 
                    ORDER BY created_at DESC
                )
                SELECT 
                    (SELECT row_to_json(user_info.*) FROM user_info) as user_data,
                    (SELECT json_agg(row_to_json(active_courses.*)) FROM active_courses) as active_courses_data,
                    (SELECT json_agg(row_to_json(completed_courses.*)) FROM completed_courses) as completed_courses_data,
                    (SELECT row_to_json(overall_progress.*) FROM overall_progress) as overall_data,
                    (SELECT json_agg(row_to_json(user_attachments_list.*)) FROM user_attachments_list) as attachments_data
            ");

                $stmt->execute([':uid' => $userId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$result || !$result['user_data']) {
                    $this->error("User not found.", 404);
                }

                // Parse JSON results
                $user = json_decode($result['user_data'], true);
                $activeCourses = json_decode($result['active_courses_data'] ?? '[]', true) ?: [];
                $completedCourses = json_decode($result['completed_courses_data'] ?? '[]', true) ?: [];
                $overall = json_decode($result['overall_data'], true) ?: ['total_lessons' => 0, 'completed_lessons' => 0];
                $attachments = json_decode($result['attachments_data'] ?? '[]', true) ?: [];

                // Calculate training summary
                $trainingSummary = [
                    'active_courses_count' => count($activeCourses),
                    'completed_courses_count' => count($completedCourses),
                    'overall_percentage' => $overall['total_lessons'] > 0
                        ? round(($overall['completed_lessons'] / $overall['total_lessons']) * 100)
                        : 0
                ];

                $response = [
                    "user" => $user,
                    "training_summary" => $trainingSummary,
                    "active_courses" => $activeCourses,
                    "completed_courses" => $completedCourses,
                    "exam_history" => [], // Reserved for future end-of-course assessments
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
}
