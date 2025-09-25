<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';
require_once '../includes/breadcrumb.php';

// Fetch all users from the database
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY last_name, first_name");
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header">
                <h1>Manage Users</h1>
            </header>
            <div class="app-content">
                <?php display_breadcrumbs(['/admin/dashboard' => 'Dashboard', '#' => 'Manage Users']); ?>
                <a href="/admin/user_add.php" class="btn" style="width: auto; margin-bottom: 20px;">Add New User</a>
                <div class="card">
                    <h3>All Staff Accounts</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Job Title</th>
                                <th>Access Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['job_title']); ?></td>
                                <td><?php echo ucfirst($user['access_level']); ?></td>
                                <td>
                                    <a href="/admin/user_edit.php?id=<?php echo $user['id']; ?>">Edit</a>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        | <a href="/admin/user_delete.php?id=<?php echo $user['id']; ?>"
                                           class="open-confirm-modal"
                                           data-message="Are you sure you want to delete the user '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>'? This cannot be undone."
                                           data-title="Confirm Deletion"
                                           data-btn-text="Yes, Delete User"
                                           data-btn-class="btn-danger">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>