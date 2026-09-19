<?php
session_start();
require 'User.php';
require 'vendor/autoload.php'; // Ensure you have autoload.php from Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$registerError = '';
$loginError = '';
$registerSuccess = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['register'])) {
        // Handle registration
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($password !== $confirmPassword) {
            $registerError = "Passwords do not match.";
        } else {
            // Check if user already exists
            $existingUser = User::getByEmail($email);
            if ($existingUser) {
                $registerError = "User already exists with this email.";
            } else {
                $user = new User($name, $email, $password);
                $userId = $user->save();
                if ($userId) {
                    // Send registration email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
                        $mail->SMTPAuth = true;
                        $mail->Username = 'ncnoord1nchir1mba@gmail.com'; // SMTP username
                        $mail->Password = 'bqnc nsse iuof mfhy '; // SMTP password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        // Recipients
                        $mail->setFrom('Noordin@gmail.com', 'HillView International School');
                        $mail->addAddress($email, $name); // Add a recipient

                        // Content
                        $mail->isHTML(true); // Set email format to HTML
                        $mail->Subject = 'Registration Successful';
                        $mail->Body    = 'Dear ' . $name . ',<br><br>Thank you for registering on our site!<br><br>Best regards,<br>HillView International School Team';

                        $mail->send();
                    } catch (Exception $e) {
                        $registerError = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    }

                    if (empty($registerError)) {
                        // Automatically log the user in after registration
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['email'] = $email;
                        $_SESSION['register_success'] = "Registration successful! You are now logged in.";

                        // Check payment status
                        $user = User::getById($userId);
                        if ($user && $user['payment']) {
                            header('Location: Student_dashboard.php');
                        } else {
                            header('Location: Bprocess.php');
                        }
                        exit;
                    }
                } else {
                    $registerError = "Error registering user.";
                }
            }
        }
    } elseif (isset($_POST['login'])) {
        // Handle login
        $email = $_POST['email'];
        $password = $_POST['password'];

        $user = User::getByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = 'student';
           

            // Check payment status
            if ($user['payment']) {
                header('Location: Student_dashboard.php');
            } else {
                header('Location: Bprocess.php');
            }
            exit;
        } else {
            $loginError = "Invalid email or password.";
        }
    }
}

// Check for registration success message
if (isset($_SESSION['register_success'])) {
    $registerSuccess = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in/up Form</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="shortcut icon" href="assets/image/hill.png" type="image/x-icon" />
    <link rel="apple-touch-icon" href="assets/image/hill.png">
    <style>
        /* Add your loader styles here */
        .loader {
            border: 16px solid #f3f3f3; /* Light grey */
            border-top: 16px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
            display: none; /* Hidden by default */
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .overlay-active .loader {
            display: block; /* Show the loader */
        }
    </style>
</head>
<body class="form-page">
    <h2>Login or Registration below</h2>
    <div class="loader" id="loader"></div>
    <div class="container" id="container">
        <div class="form-container sign-up-container">
            <form id="register-form" action="Student_Login.php" method="post">
                <h1>Create Account</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span>or use your email for registration</span>
                <input type="text" name="name" placeholder="Name" required />
                <input type="email" name="email" placeholder="Email" required />
                <input type="password" name="password" placeholder="Password" required />
                <input type="password" name="confirm_password" placeholder="Confirm Password" required />
                <button type="submit" name="register">Sign Up</button>
            </form>
        </div>
        <div class="form-container sign-in-container">
            <form id="login-form" action="Student_Login.php" method="post">
                <h1>Sign in</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span>or use your account</span>
                <input type="email" name="email" placeholder="Email" required />
                <input type="password" name="password" placeholder="Password" required />
                <a href="#">Forgot your password?</a>
                <button type="submit" name="login">Sign In</button>
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
                    <h1>Hello, Friend!</h1>
                    <p>Enter your personal details and start journey with us</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <div id="popup" class="popup">
        <?php echo $registerSuccess; ?>
    </div>
    <div id="popup-error" class="popup-error">
        <?php echo $loginError; ?>
    </div>
    <div id="popup-register-error" class="popup-register-error">
        <?php echo $registerError; ?>
    </div>
    <footer>
        <p>&copy; 2024 Hillview International School. All rights reserved.</p>
    </footer>
    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');
        const loader = document.getElementById('loader');
        const registerForm = document.getElementById('register-form');
        const loginForm = document.getElementById('login-form');

        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });

        registerForm.addEventListener('submit', () => {
            loader.style.display = 'block';
        });

        loginForm.addEventListener('submit', () => {
            loader.style.display = 'block';
        });

        document.addEventListener('DOMContentLoaded', function () {
            const registerSuccess = '<?php echo $registerSuccess; ?>';
            if (registerSuccess) {
                const popup = document.getElementById('popup');
                popup.classList.add('show');
                setTimeout(() => {
                    popup.classList.remove('show');
                }, 3000); // The popup will disappear after 3 seconds
            }

            const loginError = '<?php echo $loginError; ?>';
            if (loginError) {
                const popupError = document.getElementById('popup-error');
                popupError.classList.add('show');
                setTimeout(() => {
                    popupError.classList.remove('show');
                }, 3000); // The popup will disappear after 3 seconds
            }

            const registerError = '<?php echo $registerError; ?>';
            if (registerError) {
                const popupRegisterError = document.getElementById('popup-register-error');
                popupRegisterError.classList.add('show');
                setTimeout(() => {
                    popupRegisterError.classList.remove('show');
                }, 3000); // The popup will disappear after 3 seconds
            }
        });
    </script>
</body>
</html>
