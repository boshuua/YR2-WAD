<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';
require_once '../includes/log_function.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $job_title = trim($_POST['job_title']);
    $access_level = $_POST['access_level']; // This correctly gets the selected role

    if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = "Name, email, and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "A user with this email address already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // The INSERT statement is correct and does not need to change
            $insert_stmt = $pdo->prepare(
                "INSERT INTO users (email, password, first_name, last_name, job_title, access_level) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            if ($insert_stmt->execute([$email, $hashed_password, $first_name, $last_name, $job_title, $access_level])) {
                $new_user_id = $pdo->lastInsertId();
                log_activity("Created new user: '{$first_name} {$last_name}' (ID: {$new_user_id}).");
                $success = "User created successfully! <a href='/admin/users'>View Users</a>";
            } else {
                $error = "Failed to create user. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add User</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>Add New User</h1></header>
            <div class="app-content">
                <div class="card">
                    <?php if ($error): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>
                    <?php if ($success): ?><p class="success-message"><?php echo $success; ?></p><?php endif; ?>
                    
                    <form action="user_add.php" method="post">
                        <div class="form-group"><label>First Name</label><input type="text" name="first_name" required></div>
                        <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                        <div class="form-group"><label>Job Title</label><input type="text" name="job_title"></div>
                        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                        
                        <div class="form-group"><label>Access Level</label>
                            <select name="access_level">
                                <option value="user">User</option>
                                <option value="trainer">Trainer</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <button type="submit" class="btn">Create User</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>