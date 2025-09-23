<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}
$firstName = htmlspecialchars($_SESSION['first_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        /* --- We add the same notification styles here --- */
        #notification {
            visibility: hidden; min-width: 250px; background-color: #333; color: #fff;
            text-align: center; border-radius: 5px; padding: 16px; position: fixed;
            z-index: 1; right: 30px; bottom: 30px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            opacity: 0; transition: opacity 0.5s, visibility 0.5s;
        }
        #notification.success { background-color: #4CAF50; }
        #notification.show { visibility: visible; opacity: 1; }
    </style>
</head>
<body>
    <h1>Welcome, <?php echo $firstName; ?>!</h1>
    <p>This is your secure dashboard.</p>
    <p><a href="logout.php">Logout</a></p>

    <div id="notification"></div>

    <script>
        // Same notification logic as the index page
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = type + ' show';
            setTimeout(() => {
                notification.className = notification.className.replace(' show', '');
            }, 3000);
        }

        // Check for the login success status on page load
        window.onload = () => {
            const params = new URLSearchParams(window.location.search);
            if (params.get('status') === 'login_success') {
                showNotification('Login successful!', 'success');
            }
        };
    </script>
</body>
</html>