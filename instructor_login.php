<?php
session_start();
require 'Instructor.php';

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
        $specialization = $_POST['specialization'];
        $idNumber = $_POST['id_number']; // New field for ID number

        if ($password !== $confirmPassword) {
            $registerError = "Passwords do not match.";
        } else {
            $instructor = new Instructor($name, $email, $password, $specialization, $idNumber); // Include ID number
            if ($instructor->save()) {
                $_SESSION['register_success'] = "Registration successful! You can now log in.";
                header('Location: instructor_login.php');
                exit;
            } else {
                $registerError = "Error registering user.";
            }
        }
    } elseif (isset($_POST['login'])) {
        // Handle login
        $email = $_POST['email'];
        $password = $_POST['password'];

        $instructor = Instructor::getByEmail($email);
        if ($instructor && password_verify($password, $instructor['password'])) {
            if ($instructor['admin_approved']) {
                // Store instructor ID in session
                $_SESSION['loggedin'] = true;
                $_SESSION['email'] = $instructor['email'];
                $_SESSION['instructor_loggedin'] = true;
                $_SESSION['instructor_id'] = $instructor['id']; // Storing the instructor's ID
                $_SESSION['user_type'] = 'instructor';
               
                header('Location: instructor_dashboard.php');
                exit;
            } else {
                $_SESSION['approval_pending'] = "Your account is pending approval. Please wait for admin approval.";
                header('Location: approval_pending.php'); // Redirect to a page informing the instructor
                exit;
            }
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
    <title>Instructor Login/Registration</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/image/hill.png" type="image/x-icon">
    <link rel="apple-touch-icon" href="assets/image/hill.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body.form-page {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f2f2f2;
            margin: 0;
        }
        .container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
            overflow: hidden;
            width: 768px;
            max-width: 100%;
            min-height: 480px;
        }
        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }
        .sign-up-container {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }
        .container.right-panel-active .sign-up-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: show 0.6s;
        }
        .sign-in-container {
            left: 0;
            width: 50%;
            z-index: 2;
        }
        .container.right-panel-active .sign-in-container {
            transform: translateX(100%);
        }
        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.6s ease-in-out;
            z-index: 100;
        }
        .container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }
        .overlay {
            background: #ff416c;
            background: -webkit-linear-gradient(to right, #ff4b2b, #ff416c);
            background: linear-gradient(to right, #ff4b2b, #ff416c);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 0 0;
            color: #ffffff;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }
        .container.right-panel-active .overlay {
            transform: translateX(50%);
        }
        .overlay-panel {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            top: 0;
            height: 100%;
            width: 50%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }
        .overlay-left {
            transform: translateX(-20%);
        }
        .container.right-panel-active .overlay-left {
            transform: translateX(0);
        }
        .overlay-right {
            right: 0;
            transform: translateX(0);
        }
        .container.right-panel-active .overlay-right {
            transform: translateX(20%);
        }
        .form-container form {
            background: #fff;
            display: flex;
            flex-direction: column;
            padding: 0 50px;
            height: 100%;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .form-container h1 {
            font-weight: bold;
            margin: 0;
        }
        .form-container span {
            font-size: 12px;
            color: #555;
        }
        .form-container input {
            background: #eee;
            border: none;
            padding: 12px 15px;
            margin: 8px 0;
            width: 100%;
        }
        .form-container button {
            border-radius: 20px;
            border: 1px solid #ff4b2b;
            background: #ff4b2b;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: transform 80ms ease-in;
        }
        .form-container .social-container {
            margin: 20px 0;
        }
        .form-container .social-container a {
            border: 1px solid #ddd;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin: 0 5px;
            height: 40px;
            width: 40px;
        }
        .overlay-container .ghost {
            background-color: transparent;
            border-color: #fff;
        }
    </style>
</head>
<body class="form-page">
    <h2>Login or Registration for Instructors</h2>
    <div class="container" id="container">
        <div class="form-container sign-up-container">
            <form action="instructor_login.php" method="post">
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
                <input type="text" name="specialization" placeholder="Specialization" required />
                <input type="text" name="id_number" placeholder="Identification Card Number" required />
                <button type="submit" name="register">Sign Up</button>
                <?php if ($registerError): ?>
                    <p><?php echo $registerError; ?></p>
                <?php endif; ?>
            </form>
        </div>

        <div class="form-container sign-in-container">
            <form action="instructor_login.php" method="post">
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
                <?php if ($loginError): ?>
                    <p><?php echo $loginError; ?></p>
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
                    <h1>Hello, Instructor!</h1>
                    <p>Enter your personal details and start teaching with us</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($registerSuccess): ?>
        <div class="popup show">
            <p><?php echo $registerSuccess; ?></p>
        </div>
    <?php endif; ?>

    <?php if ($loginError): ?>
        <div class="popup-error show">
            <p><?php echo $loginError; ?></p>
        </div>
    <?php endif; ?>

    <footer>
        <p>&copy; 2024 Hillview International School. All rights reserved.</p>
    </footer>
    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });
    </script>
</body>
</html>
