<?php
session_start();
require 'config.php';

if (!isset($_SESSION['instructor_loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$room_id = $data['room_id'];

$conn = connectDB();
$sql = "UPDATE live_sessions SET status = 'ended' WHERE room_id = ? AND instructor_email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $room_id, $_SESSION['email']);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?> 