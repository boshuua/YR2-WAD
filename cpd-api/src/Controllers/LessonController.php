<?php

declare(strict_types=1);

// cpd-api/src/Controllers/LessonController.php

namespace App\Controllers;

use PDO;

class LessonController extends BaseController
{

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->error("Method Not Allowed", 405);
        }

        requireAuth();

        $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
        if ($courseId <= 0) {
            $this->error("Course ID is required and must be numeric.");
        }

        try {
            // Get all lessons for the course (simplified - no questions/quizzes)
            $stmt = $this->db->prepare("
                SELECT id, title, content, order_index
                FROM lessons
                WHERE course_id = :course_id
                ORDER BY order_index ASC, id ASC
            ");
            $stmt->execute([':course_id' => $courseId]);
            $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Return empty array if no lessons (not an error)
            $this->json($lessons);

        } catch (\Exception $e) {
            error_log("Get course lessons failed: " . $e->getMessage());
            $this->error("Failed to retrieve lessons.", 500);
        }
    }

    public function updateProgress()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAuth();

        $data = $this->getJsonInput();
        if (!isset($data->lesson_id) || !isset($data->status)) {
            $this->error("Missing lesson_id or status.");
        }

        $userId = getCurrentUserId();
        $lessonId = (int) $data->lesson_id;
        $status = $data->status;

        if (!in_array($status, ['not_started', 'in_progress', 'completed'])) {
            $this->error("Invalid status.");
        }

        try {
            $check = $this->db->prepare("SELECT id FROM user_lesson_progress WHERE user_id = :uid AND lesson_id = :lid");
            $check->execute([':uid' => $userId, ':lid' => $lessonId]);

            if ($check->rowCount() > 0) {
                $upd = $this->db->prepare("
                    UPDATE user_lesson_progress
                    SET status = :status,
                        completion_date = CASE WHEN :status_case = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = :uid AND lesson_id = :lid
                ");
                $upd->execute([
                    ':status' => $status,
                    ':status_case' => $status,
                    ':uid' => $userId,
                    ':lid' => $lessonId
                ]);
            } else {
                $ins = $this->db->prepare("
                    INSERT INTO user_lesson_progress (user_id, lesson_id, status, completion_date)
                    VALUES (:uid, :lid, :status, CASE WHEN :status_case = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END)
                ");
                $ins->execute([
                    ':uid' => $userId,
                    ':lid' => $lessonId,
                    ':status' => $status,
                    ':status_case' => $status
                ]);
            }

            log_activity($this->db, $userId, getCurrentUserEmail(), 'Lesson Progress Updated', "Lesson ID: {$lessonId}, Status: {$status}");
            $this->json(["message" => "Lesson progress updated successfully."]);

        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage(), 500);
        }
    }

    public function saveProgress()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAuth();

        $data = $this->getJsonInput();
        if (!isset($data->course_id) || !isset($data->lesson_id)) {
            $this->error("Missing course_id or lesson_id.");
        }

        $userId = getCurrentUserId();
        $courseId = (int) $data->course_id;
        $lessonId = (int) $data->lesson_id;

        try {
            // Update the last_accessed_lesson_id in user_course_progress
            $stmt = $this->db->prepare("
                UPDATE user_course_progress 
                SET last_accessed_lesson_id = :lid, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE user_id = :uid AND course_id = :cid
            ");
            $stmt->execute([
                ':lid' => $lessonId,
                ':uid' => $userId,
                ':cid' => $courseId
            ]);

            // Also ensure the lesson is marked as completed if needed, but the frontend
            // usually calls update_lesson_progress for that.

            $this->json(["message" => "Lesson progress saved."]);

        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage(), 500);
        }
    }
}
