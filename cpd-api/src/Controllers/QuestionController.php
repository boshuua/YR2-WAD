<?php
// cpd-api/src/Controllers/QuestionController.php

namespace App\Controllers;

use PDO;

class QuestionController extends BaseController
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
            // Get all questions for the course (Lesson IS NULL implies Course Level Exam)
            // The original get_course_questions.php filtered by lesson_id IS NULL
            // We should probably keep that behavior or make it optional.
            // Let's keep it consistent: Course Questions = Exam Questions (no lesson).

            $stmt = $this->db->prepare("
                SELECT q.id, q.course_id, q.question_text, q.question_type, q.created_at
                FROM questions q
                WHERE q.course_id = :course_id AND q.lesson_id IS NULL
                ORDER BY q.id ASC
            ");
            $stmt->execute([':course_id' => $courseId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($questions)) {
                $this->json([]);
                return;
            }

            // Get options
            $optStmt = $this->db->prepare("
                SELECT id, question_id, option_text, is_correct
                FROM question_options
                WHERE question_id = :question_id
                ORDER BY id ASC
            ");

            foreach ($questions as &$question) {
                $optStmt->execute([':question_id' => $question['id']]);
                $question['options'] = $optStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $this->json($questions);

        } catch (\Exception $e) {
            $this->error("Failed to retrieve questions.", 500);
        }
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $input = $this->getJsonInput();
        if (!isset($input->course_id) || !isset($input->question_text) || !isset($input->question_type) || !isset($input->options)) {
            $this->error("Missing required fields.");
        }

        $courseId = $input->course_id;
        $questionText = trim($input->question_text);
        $questionType = trim($input->question_type);
        $options = $input->options;

        if (!in_array($questionType, ['multiple_choice', 'true_false'])) {
            $this->error("Invalid question type.");
        }

        if (!is_array($options) || count($options) < 2) {
            $this->error("At least 2 options are required.");
        }

        $hasCorrect = false;
        foreach ($options as $opt) {
            // Check object or array - getJsonInput returns object by default usually, but sometimes assoc array if true passed to json_decode
            // Our helper returns object.
            $isCorrect = (isset($opt->is_correct) && $opt->is_correct) || (isset($opt->is_correct) && $opt->is_correct === 'true');
            // Wait, if it's an object use ->, if array use []
            // The existing helper `getJsonInput` usually returns object.
            // But let's check validation. The original code used $input['options'] because it called getJsonInput(true).
            // Our BaseController::getJsonInput() returns object.
            // Let's assume object access.
            if (isset($opt->is_correct) && $opt->is_correct == true) {
                $hasCorrect = true;
                break;
            }
        }

        if (!$hasCorrect) {
            $this->error("At least one option must be marked as correct.");
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO questions (course_id, question_text, question_type)
                VALUES (:course_id, :question_text, :question_type)
            ");
            $stmt->execute([
                ':course_id' => $courseId,
                ':question_text' => $questionText,
                ':question_type' => $questionType
            ]);

            $questionId = $this->db->lastInsertId();

            $optStmt = $this->db->prepare("
                INSERT INTO question_options (question_id, option_text, is_correct)
                VALUES (:question_id, :option_text, :is_correct)
            ");

            foreach ($options as $opt) {
                $optStmt->execute([
                    ':question_id' => $questionId,
                    ':option_text' => trim($opt->option_text),
                    ':is_correct' => (isset($opt->is_correct) && $opt->is_correct) ? 't' : 'f'
                ]);
            }

            $this->db->commit();
            log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), 'create_question', "Created question for course ID: $courseId");
            $this->json(["message" => "Question created successfully.", "question_id" => $questionId]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->error("Failed to create question: " . $e->getMessage(), 500);
        }
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $questionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($questionId <= 0) {
            $this->error("Invalid question ID.");
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("DELETE FROM question_options WHERE question_id = :id");
            $stmt->execute([':id' => $questionId]);

            $stmt = $this->db->prepare("DELETE FROM questions WHERE id = :id");
            $stmt->execute([':id' => $questionId]);

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                $this->error("Question not found.", 404);
            }

            $this->db->commit();
            log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), 'delete_question', "Deleted question ID: $questionId");
            $this->json(["message" => "Question deleted successfully."]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->error("Failed to delete question: " . $e->getMessage(), 500);
        }
    }
}
