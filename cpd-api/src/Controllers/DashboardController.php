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

        $userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($userId <= 0) {
            $this->error("User ID is required.");
        }

        $periodStart = $_GET['start_date'] ?? date('Y-01-01');
        $periodEnd = $_GET['end_date'] ?? date('Y-12-31');

        try {
            // 1. Fetch User Profile
            $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, job_title, access_level, created_at FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->error("User not found.", 404);
            }

            // 2. Fetch Enrolments (Active) - Exclude templates
            $stmtEnrol = $this->db->prepare("
                SELECT c.id, c.title, ucp.status, ucp.enrolled_at, ucp.updated_at
                FROM user_course_progress ucp
                JOIN courses c ON ucp.course_id = c.id
                WHERE ucp.user_id = :uid AND ucp.status != 'completed' AND c.is_template = FALSE
                ORDER BY ucp.enrolled_at DESC
            ");
            $stmtEnrol->execute([':uid' => $userId]);
            $enrolments = $stmtEnrol->fetchAll(PDO::FETCH_ASSOC);

            // 3. Fetch Exam History (Completed) - Exclude templates
            $stmtExams = $this->db->prepare("
                SELECT c.id, c.title, ucp.score, ucp.completion_date, ucp.hours_completed
                FROM user_course_progress ucp
                JOIN courses c ON ucp.course_id = c.id
                WHERE ucp.user_id = :uid AND ucp.status = 'completed' AND c.is_template = FALSE
                AND (ucp.completion_date BETWEEN :start AND :end OR ucp.completion_date IS NULL)
                ORDER BY ucp.completion_date DESC
            ");
            $stmtExams->execute([':uid' => $userId, ':start' => $periodStart, ':end' => $periodEnd]);
            $exams = $stmtExams->fetchAll(PDO::FETCH_ASSOC);

            // 4. Fetch Attachments
            $stmtAtt = $this->db->prepare("SELECT id, file_name, file_type, created_at FROM user_attachments WHERE user_id = :uid ORDER BY created_at DESC");
            $stmtAtt->execute([':uid' => $userId]);
            $attachments = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);

            // 5. Calculate Training Summary
            $totalProgress = 0;
            $courseCount = 0;

            // Reuse existing logic (calculating progress via lesson completion)
            // Note: This logic seems heavy for a loop, but kept for fidelity.
            $stmtProg = $this->db->prepare("
                SELECT COUNT(*) as total, 
                       SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM user_lesson_progress
                WHERE user_id = :uid AND lesson_id IN (SELECT id FROM lessons WHERE course_id = :cid)
            ");

            foreach ($enrolments as $enrol) {
                $stmtProg->execute([':uid' => $userId, ':cid' => $enrol['id']]);
                $prog = $stmtProg->fetch(PDO::FETCH_ASSOC);

                if ($prog && $prog['total'] > 0) {
                    $percentage = ($prog['completed'] / $prog['total']) * 100;
                    $totalProgress += $percentage;
                }
                $courseCount++;
            }

            $overallPercentage = ($courseCount > 0) ? round($totalProgress / $courseCount) : 0;

            $trainingSummary = [
                "overall_percentage" => $overallPercentage,
                "active_courses_count" => $courseCount
            ];

            $response = [
                "user" => $user,
                "enrolments" => $enrolments,
                "exam_history" => $exams,
                "attachments" => $attachments,
                "training_summary" => $trainingSummary
            ];

            $this->json($response);

        } catch (\Exception $e) {
            $this->error("Error fetching dashboard data: " . $e->getMessage(), 500);
        }
    }
}
