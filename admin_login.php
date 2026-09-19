<?php
session_start();
require 'Admin.php';

$loginError = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $admin = new Admin();
        $loginResult = $admin->login($username, $password);

        if ($loginResult === true) {
            $_SESSION['admin_loggedin'] = true;
            $_SESSION['admin_username'] = $username;
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $loginError = $loginResult;
        }
    }
}
?>

<!-- end here -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/image/hill.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="apple-touch-icon" href="assets/image/hill.png">
</head>
<body class="form-page">
    <div class="container" id="container">
        <div class="form-container sign-in-container">
            <form action="admin_login.php" method="post">
                <h1>Admin Sign In</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span>or use your admin account</span>
                <input type="text" name="username" placeholder="Username" required />
                <input type="password" name="password" placeholder="Password" required />
                <button type="submit" name="login">Sign In</button>
                <?php if ($loginError): ?>
                    <p class="error"><?php echo $loginError; ?></p>
                <?php endif; ?>
            </form>
        </div>
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us please login with your personal info</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Admin!</h1>
                    <p>Enter your personal details and start teaching with us</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>


    <footer>
        <p>&copy; 2024 Hillview International School. All rights reserved.</p>
    </footer>
    
    <?php if ($loginError): ?>
        <div class="popup-error show" id="popupError">
            <p><?php echo $loginError; ?></p>
        </div>
    <?php endif; ?>

    <footer></footer>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var popupError = document.getElementById('popupError');
            if (popupError) {
                setTimeout(function() {
                    popupError.style.display = 'none';
                }, 3000); 
            }
        });
    </script>
</body>
</html>
