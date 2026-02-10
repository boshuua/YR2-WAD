<?php
// Load configuration
// Manually define database params to avoid issues with config/database.php if it has web-specific code
class Database
{
    private $host = "6.tcp.eu.ngrok.io";
    private $db_name = "myprojectdb";
    private $username = "admin1";
    private $password = "rjRqJ0MKbGu2kyp4mZZ1oQ";
    private $port = "15008";
    public $conn;

    public function getConn()
    {
        $this->conn = null;
        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

echo "Starting Database Migration for Course Templates...\n";

$database = new Database();
$db = $database->getConn();

if (!$db) {
    die("Could not connect to database.\n");
}

try {
    // 1. Add is_template column if it doesn't exist
    echo "Checking for 'is_template' column...\n";
    $checkCol = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name='courses' AND column_name='is_template'");
    if ($checkCol->rowCount() == 0) {
        echo "Adding 'is_template' column to 'courses' table...\n";
        $db->exec("ALTER TABLE courses ADD COLUMN is_template BOOLEAN DEFAULT FALSE");
        echo "Column added successfully.\n";
    } else {
        echo "'is_template' column already exists.\n";
    }

    // 2. Insert Seed Data (MOT 2024-25)
    $title = 'MOT Annual Training 2024-25 - Class 4 & 7';
    echo "Checking if template course '$title' exists...\n";

    $checkCourse = $db->prepare("SELECT id FROM courses WHERE title = :title");
    $checkCourse->execute([':title' => $title]);

    if ($checkCourse->rowCount() == 0) {
        echo "Creating Template Course...\n";

        $db->beginTransaction();

        // Insert Course
        $stmt = $db->prepare("INSERT INTO courses (title, description, duration, required_hours, category, status, is_template, start_date, end_date) 
                              VALUES (:title, :desc, 180, 3.00, 'MOT Training', 'published', 'true', '2024-04-01', '2025-03-31')");
        $stmt->execute([
            ':title' => $title,
            ':desc' => 'Official annual training curriculum for 2024-25 covering Corrosion, Vehicle Classification, and Test Procedures. This is the master template.'
        ]);
        $courseId = $db->lastInsertId();

        // Lesson 1
        $l1 = $db->prepare("INSERT INTO lessons (course_id, title, content, order_index) VALUES (:cid, :title, :content, 1)");
        $l1->execute([
            ':cid' => $courseId,
            ':title' => 'Section 1: Corrosion & Standards of Repair',
            ':content' => '<h3>Corrosion Assessment</h3><p>Corrosion is a major cause of MOT failure. Testers must be able to distinguish between surface corrosion and structural weakness.</p><h4>Key Areas to Check:</h4><ul><li>Prescribed areas (within 30cm of steering, suspension, or braking components).</li><li>Load-bearing structures.</li><li>Tow bar mountings.</li></ul><h4>Standards of Repair:</h4><p>Repairs to structural members must be by welding or by a method that is at least as strong as the original construction. Bonded repairs are generally not acceptable for structural parts unless specified by the manufacturer.</p>'
        ]);
        $l1Id = $db->lastInsertId();

        // Lesson 1 Quiz
        $q1 = $db->prepare("INSERT INTO questions (course_id, lesson_id, question_text) VALUES (:cid, :lid, 'Structural repairs to a vehicle must be made by:')");
        $q1->execute([':cid' => $courseId, ':lid' => $l1Id]);
        $q1Id = $db->lastInsertId();

        $qo = $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (:qid, :text, :correct)");
        $qo->execute([':qid' => $q1Id, ':text' => 'Weld or a method at least as strong as original', ':correct' => 'true']);
        $qo->execute([':qid' => $q1Id, ':text' => 'Industrial adhesive/bonding', ':correct' => 'false']);

        // Lesson 2
        $l2 = $db->prepare("INSERT INTO lessons (course_id, title, content, order_index) VALUES (:cid, :title, :content, 2)");
        $l2->execute([
            ':cid' => $courseId,
            ':title' => 'Section 2: Vehicle Classification',
            ':content' => '<h3>Class 4 vs Class 7</h3><p>Correctly identifying the vehicle class is critical for applying the correct test standards.</p><ul><li><strong>Class 4:</strong> Cars, motor caravans, and small goods vehicles (up to 3,000kg DGW).</li><li><strong>Class 7:</strong> Goods vehicles over 3,000kg up to 3,500kg DGW.</li></ul>'
        ]);
        $l2Id = $db->lastInsertId();

        // Lesson 2 Quiz
        $q2 = $db->prepare("INSERT INTO questions (course_id, lesson_id, question_text) VALUES (:cid, :lid, 'A goods vehicle with a Design Gross Weight (DGW) of 3,200kg is classified as:')");
        $q2->execute([':cid' => $courseId, ':lid' => $l2Id]);
        $q2Id = $db->lastInsertId();

        $qo->execute([':qid' => $q2Id, ':text' => 'Class 4', ':correct' => 'false']);
        $qo->execute([':qid' => $q2Id, ':text' => 'Class 7', ':correct' => 'true']);

        $db->commit();
        echo "Template seeded successfully.\n";
    } else {
        echo "Template course already exists. Skipping seed.\n";
    }

    echo "Migration Complete!\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
?>