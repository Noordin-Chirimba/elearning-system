<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Not authorized');
}

$quiz_id = $_GET['quiz_id'];
$conn = connectDB();

$sql = "SELECT id as question_id, correct_answer FROM questions WHERE quiz_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();

$answers = [];
while ($row = $result->fetch_assoc()) {
    $answers[] = $row;
}

header('Content-Type: application/json');
echo json_encode($answers); 