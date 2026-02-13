<?php
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
            // Get all lessons for the course
            $stmt = $this->db->prepare("
                SELECT id, title, content, order_index
                FROM lessons
                WHERE course_id = :course_id
                ORDER BY order_index ASC, id ASC
            ");
            $stmt->execute([':course_id' => $courseId]);
            $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($lessons)) {
                $this->json([]); // Return empty array if no lessons
                return;
            }

            // Get checkpoint quiz questions for each lesson
            // Optimization: Could fetch all questions for course in one go and map them in PHP to reduce queries
            // But for now, replicating logic is safer. Let's optimize slightly by preparing statements once.

            $qStmt = $this->db->prepare("
                SELECT id, question_text, question_type
                FROM questions
                WHERE lesson_id = :lesson_id
                ORDER BY id ASC
            ");

            $optStmt = $this->db->prepare("
                SELECT id, option_text, is_correct
                FROM question_options
                WHERE question_id = :question_id
                ORDER BY id ASC
            ");

            foreach ($lessons as &$lesson) {
                $qStmt->execute([':lesson_id' => $lesson['id']]);
                $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($questions as &$question) {
                    $optStmt->execute([':question_id' => $question['id']]);
                    $question['options'] = $optStmt->fetchAll(PDO::FETCH_ASSOC);
                }

                $lesson['checkpoint_quiz'] = $questions;
            }

            $this->json($lessons);

        } catch (\Exception $e) {
            // error_log("Get course lessons failed: " . $e->getMessage()); // BaseController doesn't log to file by default but global error handler might
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
}
