<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$conn = connectDB();
$quiz_id = $_POST['quiz_id'];
$submitted_answers = $_POST['answers'];
$score = 0;

// Get all questions for this quiz
$sql = "SELECT id, correct_answer FROM questions WHERE quiz_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();

$total_questions = $result->num_rows;

while ($question = $result->fetch_assoc()) {
    if (isset($submitted_answers[$question['id']]) && 
        $submitted_answers[$question['id']] === $question['correct_answer']) {
        $score++;
    }
}

// Record the attempt
$sql_record_attempt = "INSERT INTO quiz_attempts (student_id, quiz_id, score, total_questions) 
                      VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql_record_attempt);
$stmt->bind_param("iiii", $_SESSION['user_id'], $quiz_id, $score, $total_questions);
$stmt->execute();

// Return the results
header('Content-Type: application/json');
echo json_encode([
    'score' => $score,
    'total' => $total_questions
]);