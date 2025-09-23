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
     <header class="header">
        <h1>Dashboard</h1>
    </header>

    <div class="dashboard-content">
        <h2>Welcome, <?php echo $firstName; ?>!</h2>
        <p>You have successfully logged in to your dashboard.</p>
        <a href="logout.php" class="logout-button">Logout</a>
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
            notificationTitle.textContent = title;
            notificationMessage.textContent = message;
            notificationIcon.innerHTML = `<i class="${iconClass}"></i>`;
            notification.className = type + ' show';
            setTimeout(() => {
                notification.className = notification.className.replace(' show', '');
            }, 3500);
        }

        window.onload = () => {
            const params = new URLSearchParams(window.location.search);
            if (params.get('status') === 'login_success') {
                showNotification('Success', 'You have logged in successfully.', 'success', 'fas fa-check-circle');
            }
        };
    </script>
</body>
</html>