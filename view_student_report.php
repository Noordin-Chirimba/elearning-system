<?php
session_start();
require 'config.php';

// Check if instructor is logged in
if (!isset($_SESSION['instructor_loggedin']) || $_SESSION['instructor_loggedin'] !== true) {
    header('Location: instructor_login.php');
    exit;
}

// Check if student_id is provided
if (!isset($_GET['student_id'])) {
    header('Location: student_progress.php');
    exit;
}

$student_id = $_GET['student_id'];
$conn = connectDB();

// Verify that this student belongs to one of the instructor's courses
$instructor_email = $_SESSION['email'];
$sql_verify = "
    SELECT s.* FROM students s
    JOIN courses c ON s.course = c.title
    JOIN instructors i ON c.instructor_id = i.id
    WHERE s.id = ? AND i.email = ?";

$stmt = $conn->prepare($sql_verify);
$stmt->bind_param("is", $student_id, $instructor_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: student_progress.php');
    exit;
}

// Include the existing student report code
include 'student_report.php';
?> 