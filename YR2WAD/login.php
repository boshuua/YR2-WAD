<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | Logical View Solutions</title>
    <link rel="stylesheet" href="css/style.css"> </head>
<body>
    <div class="login-container">
        <h2>Staff CPD Login</h2>
        <?php
            session_start();
            if (isset($_SESSION['error_message'])) {
                echo '<p class="error">' . $_SESSION['error_message'] . '</p>';
                unset($_SESSION['error_message']); // Clear the message
            }
        ?>
        <form action="validate.php" method="post">
        <div class="login-box">
            <h1>Login</h1>

            <div class="textbox">
                <i class="fa fa-user" aria-hidden="true"></i>
                <input type="text" placeholder="Username"
                         name="username" value="">
            </div>

            <div class="textbox">
                <i class="fa fa-lock" aria-hidden="true"></i>
                <input type="password" placeholder="Password"
                         name="password" value="">
            </div>

            <input class="button" type="submit"
                     name="login" value="Sign In">
        </div>
    </form>
    </div>
</body>
</html>