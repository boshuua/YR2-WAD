<?php
session_start();
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
     <header class="header">
        <h1>CPD Training</h1>
    </header>

    <div class="main-container">
        <div class="card login-card">
            <h2>User Login</h2>
            <form action="login_process.php" method="post">
                <div class="form-group">
                    <label for="email">Username / Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-success">LOGIN</button>
            </form>
            <p class="forgot-password"><a href="#">Forgot your password?</a></p>
        </div>

        <div class="card info-card">
            <h2>Training & eLearning</h2>
            <p>We provide a range of courses designed for you. Enhance your skills and ensure compliance with our expert-led training.</p>
        </div>
    </div>

    <div id="notification">
        <div class="notification-header">
            <span class="icon"></span>
            <span class="title"></span>
        </div>
        <div class="notification-body">
            <span class="message"></span>
        </div>
    </div>

    <script>
        function showNotification(title, message, type, iconClass) {
            const notification = document.getElementById('notification');
            const notificationTitle = notification.querySelector('.title');
            const notificationMessage = notification.querySelector('.message');
            const notificationIcon = notification.querySelector('.icon');

            // Set the content
            notificationTitle.textContent = title;
            notificationMessage.textContent = message;
            notificationIcon.innerHTML = `<i class="${iconClass}"></i>`;

            // Set the class for styling (success/error) and show the modal
            notification.className = type + ' show';

            // Hide after 3.5 seconds
            setTimeout(() => {
                notification.className = notification.className.replace(' show', '');
            }, 3500);
        }

        window.onload = () => {
            const params = new URLSearchParams(window.location.search);
            if (params.has('status')) {
                const status = params.get('status');
                if (status === 'invalid') {
                    showNotification('Login Failed', 'The username or password you entered is incorrect.', 'error', 'fas fa-times-circle');
                } else if (status === 'logged_out') {
                    showNotification('Success', 'You have been logged out successfully.', 'success', 'fas fa-check-circle');
                }
            }
        };
    </script>
</body>
</html>