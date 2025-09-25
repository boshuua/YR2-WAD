<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';
require_once '../includes/log_function.php';

$user_id_to_edit = $_GET['id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $job_title = trim($_POST['job_title']);
    $access_level = $_POST['access_level'];
    $password = trim($_POST['password']);

    // Prevent an admin from demoting themselves
    if ($user_id_to_edit == $_SESSION['user_id'] && $access_level == 'user') {
        $error = "You cannot remove your own admin privileges.";
    } else {
        if (!empty($password)) {
            // If new password is provided, hash it and update
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, job_title = ?, access_level = ?, password = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $job_title, $access_level, $hashed_password, $user_id_to_edit]);
        } else {
            // If password is blank, update everything else
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, job_title = ?, access_level = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $job_title, $access_level, $user_id_to_edit]);
        }
        log_activity("Edited user: '{$first_name} {$last_name}' (ID: {$user_id_to_edit}).");
        $success = "User updated successfully! <a href='users.php'>Back to list</a>";
    }
}

// Fetch the user's current data to pre-fill the form
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id_to_edit]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>Edit User: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1></header>
            <div class="app-content">
                <div class="card">
                    <?php if ($error): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>
                    <?php if ($success): ?><p class="success-message"><?php echo $success; ?></p><?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required></div>
                        <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required></div>
                        <div class="form-group"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled></div>
                        <div class="form-group"><label>Job Title</label><input type="text" name="job_title" value="<?php echo htmlspecialchars($user['job_title']); ?>"></div>
                        <div class="form-group"><label>New Password</label><input type="password" name="password" placeholder="Leave blank to keep current password"></div>
                        <div class="form-group"><label>Access Level</label>
                            <select name="access_level">
                                <option value="user" <?php if($user['access_level'] == 'user') echo 'selected'; ?>>User</option>
                                <option value="admin" <?php if($user['access_level'] == 'admin') echo 'selected'; ?>>Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn">Update User</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>