<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | Logical View Solutions</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-page-wrapper">
        <div class="login-container">
            <form action="includes/login_process.php" method="post">
                <h2>Staff CPD Login</h2>
                <?php
                    session_start();
                    if (isset($_SESSION['error_message'])) {
                        echo '<p class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
                        unset($_SESSION['error_message']);
                    }
                ?>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>