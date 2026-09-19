<?php
require 'User.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($password !== $confirmPassword) {
        echo "Passwords do not match.";
        exit;
    }

    $user = new User($name, $email, $password);
    if ($user->save()) {
        header('Location: login.php');
        exit;
    } else {
        echo "Error registering user.";
    }
}
?>
