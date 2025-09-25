<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// --- START OF CHANGES ---
$page_title = 'My Profile'; // Set a title for the page
require_once '../includes/header.php'; // Include the new header
?>
<title><?php echo $page_title; ?></title>
<div class="app-container">
        <?php include '../includes/user_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>My Profile</h1></header>
            <div class="app-content">
                <div class="card">
                    <h3>Personal Details</h3>
                    <form action="profile_process.php" method="post">
                        <input type="hidden" name="action" value="update_details">
                        <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required></div>
                        <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required></div>
                        <div class="form-group"><label>Job Title</label><input type="text" name="job_title" value="<?php echo htmlspecialchars($user['job_title']); ?>"></div>
                        <div class="form-group"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled></div>
                        <button type="submit" class="btn">Save Details</button>
                    </form>
                </div>

                <div class="card" style="margin-top: 30px;">
                    <h3>Change Password</h3>
                    <form action="profile_process.php" method="post">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
                        <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
                        <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" required></div>
                        <button type="submit" class="btn">Update Password</button>
                    </form>
                </div>

                <div class="card" style="margin-top: 30px;">
                    <h3>Preferences</h3>
                    <form action="profile_process.php" method="post">
                        <input type="hidden" name="action" value="update_theme">
                        <div class="form-group"><label>Site Theme</label>
                            <select name="theme">
                                <option value="light" <?php if($user['theme'] == 'light') echo 'selected'; ?>>Light Mode</option>
                                <option value="dark" <?php if($user['theme'] == 'dark') echo 'selected'; ?>>Dark Mode</option>
                            </select>
                        </div>
                        <button type="submit" class="btn">Save Preferences</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>