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
    <div class="login-container">
        <h2>Login</h2>
        <form action="login_process.php" method="post">
            <p><input type="email" name="email" placeholder="Email Address" required></p>
            <p><input type="password" name="password" placeholder="Password" required></p>
            <button type="submit">Login</button>
        </form>
    </div>

    <div id="notification"></div>

    <script>
        // Function to show the notification
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = type + ' show'; // Add 'show' class to make it visible

            // After 3 seconds, remove the show class to hide it
            setTimeout(() => {
                notification.className = notification.className.replace(' show', '');
            }, 3000);
        }

        // Check the URL for a status parameter when the page loads
        window.onload = () => {
            const params = new URLSearchParams(window.location.search);
            if (params.has('status')) {
                const status = params.get('status');
                if (status === 'invalid') {
                    showNotification('Invalid credentials. Please try again.', 'error');
                } else if (status === 'logged_out') {
                    showNotification('You have been logged out.', 'success');
                }
            }
        };
    </script>
</body>
</html>