<?php
session_start();
require 'config.php';

$conn = connectDB();
$current_user_id = $_SESSION['user_id']; // Logged-in user's ID

// Fetch all users except the current logged-in user
$stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id != ?");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users); // Return the users as JSON
?>
