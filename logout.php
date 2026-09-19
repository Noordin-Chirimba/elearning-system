<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page (you can change the file as needed)
header("Location: student_login.php");  // Redirect to the login page
exit;
?>
