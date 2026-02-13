<?php
// cpd-api/src/Controllers/DashboardController.php

namespace App\Controllers;

use PDO;

class DashboardController extends BaseController
{

    public function getUserDashboard()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin(); // Original script required admin, likely because it takes ?id= param. 
        // If users see their own dashboard, it should check if current_user_id == requested_id OR is_admin.
        // Original code: requireAdmin(). So only admins view other users' dashboards?
        // Let's stick to original logic: requireAdmin() and GET['id'].

        $userId = $_GET['id'] ?? null;

        if (!$userId) {
            $this->error("User ID is required.", 400);
        }

        // The original code had periodStart/End, but the new code doesn't use them for completed courses.
        // Keeping them for now in case they are used elsewhere or for future expansion.
        $periodStart = $_GET['start_date'] ?? date('Y-01-01');
        $periodEnd = $_GET['end_date'] ?? date('Y-12-31');

        try {
            // 1. Fetch User Info
            $stmtUser = $this->db->prepare("SELECT id, first_name, last_name, email, job_title, access_level, created_at FROM users WHERE id = :uid");
            $stmtUser->execute([':uid' => $userId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->error("User not found.", 404);
            }

            // 2. Fetch Active Courses with Lesson Progress
            $stmtActive = $this->db->prepare("
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
                ORDER BY ucp.enrolled_at DESC
            ");
            $stmtActive->execute([':uid' => $userId]);
            $activeCourses = $stmtActive->fetchAll(PDO::FETCH_ASSOC);

            // 3. Fetch Completed Courses (Training History)
            $stmtCompleted = $this->db->prepare("
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
                ORDER BY ucp.completion_date DESC
            ");
            $stmtCompleted->execute([':uid' => $userId]);
            $completedCourses = $stmtCompleted->fetchAll(PDO::FETCH_ASSOC);

            // 4. Fetch Attachments
            $stmtAtt = $this->db->prepare("SELECT id, file_name, file_type, created_at FROM user_attachments WHERE user_id = :uid ORDER BY created_at DESC");
            $stmtAtt->execute([':uid' => $userId]);
            $attachments = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);

            // 5. Calculate Overall Training Progress
            $stmtOverall = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT l.id) as total_lessons,
                    COUNT(DISTINCT CASE WHEN ulp.status = 'completed' THEN ulp.lesson_id END) as completed_lessons
                FROM user_course_progress ucp
                JOIN courses c ON ucp.course_id = c.id
                LEFT JOIN lessons l ON c.id = l.course_id
                LEFT JOIN user_lesson_progress ulp ON l.id = ulp.lesson_id AND ulp.user_id = ucp.user_id
                WHERE ucp.user_id = :uid 
                    AND c.is_template = FALSE
            ");
            $stmtOverall->execute([':uid' => $userId]);
            $overall = $stmtOverall->fetch(PDO::FETCH_ASSOC);

            $overallPercentage = $overall['total_lessons'] > 0
                ? round(($overall['completed_lessons'] / $overall['total_lessons']) * 100)
                : 0;

            $trainingSummary = [
                "overall_percentage" => $overallPercentage,
                "active_courses_count" => count($activeCourses)
            ];

            $response = [
                "user" => $user,
                "training_summary" => $trainingSummary,
                "active_courses" => $activeCourses,
                "completed_courses" => $completedCourses,
                "exam_history" => [], // Reserved for future end-of-course assessments
                "attachments" => $attachments
            ];

            $this->json($response);

        } catch (\Exception $e) {
            error_log("Dashboard error: " . $e->getMessage());
            $this->error("Failed to retrieve dashboard data: " . $e->getMessage(), 500);
        }
    }
}
