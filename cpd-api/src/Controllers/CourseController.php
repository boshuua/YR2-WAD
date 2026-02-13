<?php
// cpd-api/src/Controllers/CourseController.php

namespace App\Controllers;

use PDO;

class CourseController extends BaseController
{

    public function index()
    {
        // Handle CORS prelight (handled by bootstrap, but method check needed)
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->error("Method Not Allowed", 405);
        }

        $type = $_GET['type'] ?? 'all';

        // Base Query
        $sql = "SELECT c.id, c.title, c.description, c.duration, c.category, c.status, 
                       c.instructor_id, c.start_date, c.end_date, c.is_locked, c.is_template,
                       c.max_attendees,
                       (SELECT COUNT(*) FROM user_course_progress ucp WHERE ucp.course_id = c.id AND ucp.status != 'cancelled') as enrolled_count
                FROM courses c
                WHERE 1=1";

        // Filter Logic
        if ($type === 'locked') {
            $sql .= " AND c.is_locked = TRUE";
        } else if ($type === 'library') {
            $sql .= " AND (c.is_template = TRUE OR c.is_locked = TRUE)";
        } else if ($type === 'active') {
            // Active usually means filtered instances (not templates)
            $sql .= " AND c.is_template = FALSE AND c.is_locked = FALSE";
        } else if ($type === 'upcoming') {
            // Only future courses
            $sql .= " AND c.is_template = FALSE AND c.start_date >= CURRENT_DATE";
        } else if ($type === 'past') {
            $sql .= " AND c.is_template = FALSE AND c.end_date < CURRENT_DATE";
        }

        $sql .= " ORDER BY c.start_date ASC, c.created_at DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Determine user ID for logging? (Optional, legacy log didn't use it)
            // log_activity($this->db, null, null, "Viewed Courses", "All courses listed.");

            if (empty($courses)) {
                // Legacy behavior: 404 if empty? 
                // Original code sent 404 with "No courses found." if empty array.
                // Keeping consistent, though 200 [] is often better.
                $this->error("No courses found.", 404);
            } else {
                // Format booleans if needed (Postgres returns 't'/'f' sometimes depending on driver setup, 
                // but fetchAll(PDO::FETCH_ASSOC) usually gives appropriate types if set up.
                // Legacy extracted and cast (bool) $is_locked.
                foreach ($courses as &$course) {
                    $course['is_locked'] = (bool) ($course['is_locked'] === true || $course['is_locked'] === 't' || $course['is_locked'] === 1);
                    $course['is_template'] = (bool) ($course['is_template'] === true || $course['is_template'] === 't' || $course['is_template'] === 1);
                    $course['description'] = html_entity_decode($course['description']);
                }
                $this->json($courses);
            }

        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage(), 500);
        }
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin(); // Helper from bootstrap

        $data = $this->getJsonInput();

        // Basic Validation
        if (!isset($data->title) || !isset($data->description)) {
            $this->error("Missing title or description.");
        }

        $title = sanitizeString($data->title);
        $description = sanitizeString($data->description);
        $content = $data->content ?? '';
        $duration = $data->duration ?? null;
        $required_hours = $data->required_hours ?? 3.00;
        $category = $data->category ?? null;
        $status = $data->status ?? 'draft';
        $instructor_id = $data->instructor_id ?? null;
        $start_date = $data->start_date ?? null;
        $end_date = $data->end_date ?? null;

        if ($status && !in_array($status, ['draft', 'published'])) {
            $this->error("Invalid status.");
        }

        $query = "INSERT INTO courses (title, description, content, duration, required_hours, category, status, instructor_id, start_date, end_date)
                  VALUES (:title, :description, :content, :duration, :required_hours, :category, :status, :instructor_id, :start_date, :end_date)";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
            $stmt->bindParam(':required_hours', $required_hours);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);

            if ($stmt->execute()) {
                log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "Course Created", "Course: {$title}");
                $this->json(["message" => "Course was created."], 201);
            } else {
                throw new \Exception("Insert failed");
            }
        } catch (\Exception $e) {
            log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "Course Creation Failed", "Error: " . $e->getMessage());
            $this->error("Unable to create course: " . $e->getMessage(), 500);
        }
    }

    public function createFromTemplate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $data = $this->getJsonInput();
        if (!isset($data->template_id) || !isset($data->start_date) || !isset($data->end_date)) {
            $this->error("Missing required fields (template_id, start_date, end_date).");
        }

        $templateId = $data->template_id;
        $startDate = $data->start_date;
        $endDate = $data->end_date;
        $newTitle = isset($data->title) ? sanitizeString($data->title) : null;

        try {
            $this->db->beginTransaction();

            // 1. Get Template
            $stmt = $this->db->prepare("SELECT * FROM courses WHERE id = :id");
            $stmt->execute([':id' => $templateId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                throw new \Exception("Template not found.");
            }

            $courseTitle = $newTitle ? $newTitle : $template['title'];

            // 2. Insert Course
            $insertCourse = $this->db->prepare("
                INSERT INTO courses 
                (title, description, content, duration, required_hours, category, status, instructor_id, is_template, start_date, end_date, is_locked)
                VALUES 
                (:title, :desc, :content, :duration, :req_hours, :cat, 'published', :inst_id, FALSE, :start, :end, FALSE)
            ");
            // Note: Added is_locked explicitly as false for scheduled instances based on previous context, 
            // though default might be false.

            $insertCourse->execute([
                ':title' => $courseTitle,
                ':desc' => $template['description'],
                ':content' => $template['content'],
                ':duration' => $template['duration'],
                ':req_hours' => $template['required_hours'],
                ':cat' => $template['category'],
                ':inst_id' => $template['instructor_id'],
                ':start' => $startDate,
                ':end' => $endDate
            ]);

            $newCourseId = $this->db->lastInsertId();

            // 3. Copy Lessons
            $getLessons = $this->db->prepare("SELECT * FROM lessons WHERE course_id = :tid ORDER BY order_index ASC");
            $getLessons->execute([':tid' => $templateId]);
            $lessons = $getLessons->fetchAll(PDO::FETCH_ASSOC);

            $lessonMap = [];
            foreach ($lessons as $lesson) {
                $insLesson = $this->db->prepare("
                    INSERT INTO lessons (course_id, title, content, order_index)
                    VALUES (:cid, :title, :content, :oi)
                ");
                $insLesson->execute([
                    ':cid' => $newCourseId,
                    ':title' => $lesson['title'],
                    ':content' => $lesson['content'],
                    ':oi' => $lesson['order_index']
                ]);
                $lessonMap[$lesson['id']] = $this->db->lastInsertId();
            }

            // 4. Copy Questions
            $getQuestions = $this->db->prepare("SELECT * FROM questions WHERE course_id = :tid");
            $getQuestions->execute([':tid' => $templateId]);
            $questions = $getQuestions->fetchAll(PDO::FETCH_ASSOC);

            foreach ($questions as $q) {
                $newLessonId = null;
                if ($q['lesson_id'] && isset($lessonMap[$q['lesson_id']])) {
                    $newLessonId = $lessonMap[$q['lesson_id']];
                }

                $insQ = $this->db->prepare("
                    INSERT INTO questions (course_id, lesson_id, question_text, question_type)
                    VALUES (:cid, :lid, :qtext, :qtype)
                ");
                $insQ->execute([
                    ':cid' => $newCourseId,
                    ':lid' => $newLessonId,
                    ':qtext' => $q['question_text'],
                    ':qtype' => $q['question_type']
                ]);
                $newQuestionId = $this->db->lastInsertId();

                // 5. Copy Options
                $getOpts = $this->db->prepare("SELECT * FROM question_options WHERE question_id = :qid");
                $getOpts->execute([':qid' => $q['id']]);
                $options = $getOpts->fetchAll(PDO::FETCH_ASSOC);

                foreach ($options as $opt) {
                    $insOpt = $this->db->prepare("
                        INSERT INTO question_options (question_id, option_text, is_correct)
                        VALUES (:qid, :text, :correct)
                    ");
                    $insOpt->execute([
                        ':qid' => $newQuestionId,
                        ':text' => $opt['option_text'],
                        ':correct' => $opt['is_correct']
                    ]);
                }
            }

            // 6. Enroll Users
            $userIds = [];
            if (isset($data->user_ids) && is_array($data->user_ids)) {
                $userIds = $data->user_ids;
            } elseif (isset($data->user_id) && $data->user_id) {
                $userIds[] = intval($data->user_id);
            }

            if (!empty($userIds)) {
                $enrollStmt = $this->db->prepare("
                    INSERT INTO user_course_progress (user_id, course_id, status, enrolled_at, hours_completed)
                    VALUES (:uid, :cid, 'not_started', :date, 0)
                ");
                foreach ($userIds as $uid) {
                    $enrollStmt->execute([
                        ':uid' => $uid,
                        ':cid' => $newCourseId,
                        ':date' => $startDate
                    ]);
                    // Logic to send email is in 'assign_course', but here we do bulk. 
                    // Could call EmailService here if refactored. 
                    // For now, logging creation is enough or basic implementation.
                }
            }

            $this->db->commit();
            log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "Course Scheduled", "Created '$courseTitle' from template ID $templateId");
            $this->json(["message" => "Course scheduled successfully!", "course_id" => $newCourseId], 201);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->error("Failed to schedule course: " . $e->getMessage(), 500);
        }
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $courseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($courseId <= 0) {
            $this->error("Invalid course ID provided.");
        }

        $data = $this->getJsonInput();
        if (!isset($data->title) || !isset($data->description)) {
            $this->error("Missing title or description.");
        }

        $title = sanitizeString($data->title);
        $description = sanitizeString($data->description);
        $content = $data->content ?? '';
        $duration = $data->duration ?? null;
        $required_hours = $data->required_hours ?? 3.00;
        $category = $data->category ?? null;
        $status = $data->status ?? 'draft';
        $instructor_id = $data->instructor_id ?? null;
        $start_date = $data->start_date ?? null;
        $end_date = $data->end_date ?? null;

        if ($status && !in_array($status, ['draft', 'published'])) {
            $this->error("Invalid status.");
        }

        $query = "UPDATE courses
                  SET title = :title,
                      description = :description,
                      content = :content,
                      duration = :duration,
                      required_hours = :required_hours,
                      category = :category,
                      status = :status,
                      instructor_id = :instructor_id,
                      start_date = :start_date,
                      end_date = :end_date,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
            $stmt->bindParam(':required_hours', $required_hours);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':id', $courseId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "Course Updated", "Course ID: {$courseId}, Title: {$title}");
                    $this->json(["message" => "Course updated successfully."]);
                } else {
                    // Check if exists
                    $check = $this->db->prepare("SELECT COUNT(*) FROM courses WHERE id = :id");
                    $check->execute([':id' => $courseId]);
                    if ($check->fetchColumn() > 0) {
                        $this->json(["message" => "No changes detected for the course."]);
                    } else {
                        $this->error("Course not found.", 404);
                    }
                }
            } else {
                throw new \Exception("Update failed");
            }

        } catch (\Exception $e) {
            log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "Course Update Failed", "Course ID: {$courseId}, Error: " . $e->getMessage());
            $this->error("Unable to update course: " . $e->getMessage(), 500);
        }
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $courseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($courseId <= 0) {
            $this->error("Invalid course ID provided.");
        }

        // Fetch title for logging
        $courseTitle = 'Unknown';
        try {
            $stmt = $this->db->prepare("SELECT title FROM courses WHERE id = :id");
            $stmt->execute([':id' => $courseId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $courseTitle = $row['title'];
            }
        } catch (\Exception $ignore) {
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM courses WHERE id = :id");
            $stmt->bindValue(":id", $courseId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), 'Course Deleted', "Course ID: {$courseId}, Title: {$courseTitle}");
                    $this->json(["message" => "Course deleted successfully."]);
                } else {
                    log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), 'Course Deletion Failed', "Course ID: {$courseId} not found.");
                    $this->error("Course not found or already deleted.", 404);
                }
            } else {
                throw new \Exception("Delete execution failed");
            }
        } catch (\Exception $e) {
            log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), 'Course Deletion Failed', "Course ID: {$courseId}, Error: " . $e->getMessage());
            $this->error("Unable to delete course: " . $e->getMessage(), 500);
        }
    }

    public function show()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->error("Method Not Allowed", 405);
        }

        $courseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($courseId <= 0) {
            $this->error("Invalid course ID provided.");
        }

        try {
            $stmt = $this->db->prepare("
                SELECT id, title, description, content, duration, category, status, instructor_id, start_date, end_date
                FROM courses
                WHERE id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $courseId]);

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $course = [
                    "id" => $row['id'],
                    "title" => $row['title'],
                    "description" => $row['description'] ? html_entity_decode($row['description']) : '',
                    "content" => $row['content'] ? html_entity_decode($row['content']) : '',
                    "duration" => $row['duration'],
                    "category" => $row['category'],
                    "status" => $row['status'],
                    "instructor_id" => $row['instructor_id'],
                    "start_date" => $row['start_date'],
                    "end_date" => $row['end_date']
                ];
                $this->json($course);
            } else {
                $this->error("Course not found.", 404);
            }
        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage(), 500);
        }
    }

    public function enroll()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAuth();
        require_once __DIR__ . '/../../helpers/email_helper.php';

        $input = $this->getJsonInput();
        if (!isset($input->course_id)) {
            $this->error("Course ID is required.");
        }

        $courseId = (int) $input->course_id;
        $userId = getCurrentUserId();

        try {
            // 1. Check if already enrolled
            $check = $this->db->prepare("SELECT id FROM user_course_progress WHERE user_id = :uid AND course_id = :cid");
            $check->execute([':uid' => $userId, ':cid' => $courseId]);
            if ($check->fetch()) {
                $this->error("You are already enrolled in this course.", 400);
            }

            // 2. Check Capacity
            $capStmt = $this->db->prepare("
                SELECT c.title, c.max_attendees,
                       (SELECT COUNT(*) FROM user_course_progress WHERE course_id = c.id AND status != 'cancelled') as current_count
                FROM courses c
                WHERE c.id = :cid
            ");
            $capStmt->execute([':cid' => $courseId]);
            $course = $capStmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                $this->error("Course not found.", 404);
            }

            if ($course['current_count'] >= $course['max_attendees']) {
                $this->error("Course is full. Maximum attendees reached.", 400);
            }

            // 3. Enroll
            $stmt = $this->db->prepare("
                INSERT INTO user_course_progress (user_id, course_id, status, enrolled_at)
                VALUES (:uid, :cid, 'not_started', NOW())
            ");
            $stmt->execute([':uid' => $userId, ':cid' => $courseId]);

            // 4. Email
            $userEmail = getCurrentUserEmail();
            $subject = "Course Enrollment Confirmation: " . $course['title'];
            $body = "
                <h1>Enrollment Confirmed</h1>
                <p>Dear User,</p>
                <p>You have been successfully enrolled in the course: <strong>{$course['title']}</strong>.</p>
                <p>Please log in to the portal to access your training materials.</p>
                <p>Regards,<br>CPD Admin Team</p>
            ";
            sendEmail($this->db, $userEmail, $subject, $body);

            log_activity($this->db, $userId, $userEmail, 'enroll_course', "Enrolled in course ID: $courseId");
            $this->json(["message" => "Successfully enrolled in the course.", "course_id" => $courseId]);

        } catch (\Exception $e) {
            $this->error("Failed to enroll: " . $e->getMessage(), 500);
        }
    }

    public function assign()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();
        require_once __DIR__ . '/../../helpers/email_helper.php';

        $data = $this->getJsonInput();
        if (!isset($data->user_id) || !isset($data->course_id) || !isset($data->start_date)) {
            $this->error("Missing required fields: user_id, course_id, start_date");
        }

        $userId = (int) $data->user_id;
        $courseId = (int) $data->course_id;
        $scheduledDate = $data->start_date;

        try {
            // Check Course
            $cStmt = $this->db->prepare("SELECT id, title FROM courses WHERE id = :id");
            $cStmt->execute([':id' => $courseId]);
            $course = $cStmt->fetch(PDO::FETCH_ASSOC);
            if (!$course)
                $this->error("Course not found.", 404);

            // Check User
            $uStmt = $this->db->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = :id");
            $uStmt->execute([':id' => $userId]);
            $user = $uStmt->fetch(PDO::FETCH_ASSOC);
            if (!$user)
                $this->error("User not found.", 404);

            // Check Existing
            $eStmt = $this->db->prepare("SELECT id FROM user_course_progress WHERE user_id = :uid AND course_id = :cid");
            $eStmt->execute([':uid' => $userId, ':cid' => $courseId]);
            $existing = $eStmt->fetch(PDO::FETCH_ASSOC);

            $subject = "Course Assignment Update: " . $course['title'];
            $body = "
                <h1>Training Assigned</h1>
                <p>Dear {$user['first_name']} {$user['last_name']},</p>
                <p>You have been assigned to the following training:</p>
                <p><strong>Course:</strong> {$course['title']}</p>
                <p><strong>Scheduled Date:</strong> " . date('d M Y', strtotime($scheduledDate)) . "</p>
                <p>Please log in to the portal to view details.</p>
                <p>Regards,<br>CPD Admin Team</p>
            ";

            if ($existing) {
                $upd = $this->db->prepare("UPDATE user_course_progress SET enrolled_at = :date, updated_at = NOW() WHERE id = :id");
                $upd->execute([':date' => $scheduledDate, ':id' => $existing['id']]);
                sendEmail($this->db, $user['email'], $subject, $body);
                $this->json(["message" => "Course assignment updated and email sent.", "course_id" => $courseId, "user_id" => $userId]);
            } else {
                $ins = $this->db->prepare("
                    INSERT INTO user_course_progress (user_id, course_id, status, enrolled_at, hours_completed)
                    VALUES (:uid, :cid, 'not_started', :date, 0)
                ");
                $ins->execute([
                    ':uid' => $userId,
                    ':cid' => $courseId,
                    ':date' => $scheduledDate
                ]);
                sendEmail($this->db, $user['email'], $subject, $body);
                $this->json(["message" => "Course assigned and email sent.", "course_id" => $courseId, "user_id" => $userId], 201);
            }

        } catch (\Exception $e) {
            $this->error("Error assigning course: " . $e->getMessage(), 500);
        }
    }

    public function updateProgress()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAuth();

        $data = $this->getJsonInput();
        if (!isset($data->course_id) || !isset($data->status)) {
            $this->error("Missing course_id or status.");
        }

        $userId = getCurrentUserId();
        $courseId = (int) $data->course_id;
        $status = $data->status;
        $score = $data->score ?? null;

        if (!in_array($status, ['not_started', 'in_progress', 'completed'])) {
            $this->error("Invalid status.");
        }

        try {
            $check = $this->db->prepare("SELECT id FROM user_course_progress WHERE user_id = :uid AND course_id = :cid");
            $check->execute([':uid' => $userId, ':cid' => $courseId]);

            if ($check->rowCount() > 0) {
                // Update
                $upd = $this->db->prepare("
                    UPDATE user_course_progress
                    SET status = :status,
                        completion_date = CASE WHEN :status_case = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END,
                        score = :score,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = :uid AND course_id = :cid
                ");
                $upd->execute([
                    ':status' => $status,
                    ':status_case' => $status,
                    ':score' => $score,
                    ':uid' => $userId,
                    ':cid' => $courseId
                ]);
            } else {
                // Insert
                $ins = $this->db->prepare("
                    INSERT INTO user_course_progress (user_id, course_id, status, completion_date, score)
                    VALUES (:uid, :cid, :status, CASE WHEN :status_case = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END, :score)
                ");
                $ins->execute([
                    ':uid' => $userId,
                    ':cid' => $courseId,
                    ':status' => $status,
                    ':status_case' => $status,
                    ':score' => $score
                ]);
            }

            log_activity($this->db, $userId, getCurrentUserEmail(), 'Course Progress Updated', "Course ID: {$courseId}, Status: {$status}");
            $this->json(["message" => "Course progress updated successfully."]);

        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage(), 500);
        }
    }

    public function getUserCourses()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->error("Method Not Allowed", 405);
        }

        requireAuth();

        $userId = getCurrentUserId();

        try {
            $query = "SELECT
                        c.id AS course_id,
                        c.title,
                        c.description,
                        ucp.status AS user_progress_status,
                        ucp.completion_date,
                        ucp.score
                      FROM courses c
                      LEFT JOIN user_course_progress ucp ON c.id = ucp.course_id AND ucp.user_id = :user_id
                      ORDER BY c.title ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $userId]);
            $courses = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $courses[] = [
                    "id" => $row['course_id'],
                    "title" => $row['title'],
                    "description" => html_entity_decode($row['description']),
                    "user_progress_status" => $row['user_progress_status'],
                    "completion_date" => $row['completion_date'],
                    "score" => $row['score']
                ];
            }

            if (empty($courses)) {
                $this->error("No courses found.", 404);
            } else {
                $this->json($courses);
            }

        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage(), 500);
        }
    }

    public function submitQuiz()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAuth();

        $input = $this->getJsonInput();
        if (!isset($input->course_id) || !isset($input->score)) {
            $this->error("Missing course_id or score.");
        }

        $userId = getCurrentUserId();
        $courseId = (int) $input->course_id;
        $score = $input->score;

        if ($score < 0 || $score > 100) {
            $this->error("Score must be between 0 and 100.");
        }

        try {
            $check = $this->db->prepare("SELECT id, status FROM user_course_progress WHERE user_id = :uid AND course_id = :cid");
            $check->execute([':uid' => $userId, ':cid' => $courseId]);
            $progress = $check->fetch(PDO::FETCH_ASSOC);

            if (!$progress) {
                $this->error("You must be enrolled in this course to submit a quiz.", 400);
            }

            $newStatus = $score >= 80 ? 'completed' : 'in_progress';
            // If already completed, keep as completed? Original code overwrites status based on new score.
            // But usually we don't downgrade completion unless retake.
            // Original code: $newStatus = $score >= 80 ? 'completed' : 'in_progress';

            $completionDate = $score >= 80 ? date('Y-m-d H:i:s') : null;

            $stmt = $this->db->prepare("
                UPDATE user_course_progress
                SET score = :score,
                    status = :status,
                    completion_date = :cdate,
                    updated_at = NOW()
                WHERE user_id = :uid AND course_id = :cid
            ");
            $stmt->execute([
                ':score' => $score,
                ':status' => $newStatus,
                ':cdate' => $completionDate,
                ':uid' => $userId,
                ':cid' => $courseId
            ]);

            $statusText = $score >= 80 ? "passed" : "attempted";
            log_activity($this->db, $userId, getCurrentUserEmail(), 'submit_quiz', "Quiz $statusText for course ID: $courseId with score: $score%");

            $message = $score >= 80
                ? "Congratulations! You passed the course with a score of {$score}%."
                : "Quiz submitted with score of {$score}%. You need 80% or higher to complete the course.";

            $this->json([
                "message" => $message,
                "score" => $score,
                "status" => $newStatus,
                "passed" => $score >= 80
            ]);

        } catch (\Exception $e) {
            $this->error("Failed to submit quiz: " . $e->getMessage(), 500);
        }
    }
}
