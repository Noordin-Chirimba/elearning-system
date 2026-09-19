<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    exit('You are not logged in');
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];

// Save the last contact in the session
$_SESSION['last_contact'] = $receiver_id;

$conn = connectDB();
$query = "
    SELECT messages.*, 
           IFNULL(students.name, instructors.name) AS sender_name 
    FROM messages
    LEFT JOIN students ON messages.sender_id = students.id
    LEFT JOIN instructors ON messages.sender_id = instructors.id
    WHERE (messages.sender_id = ? AND messages.receiver_id = ?) 
       OR (messages.sender_id = ? AND messages.receiver_id = ?)
    ORDER BY timestamp";
    
$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $isSender = $row['sender_id'] == $sender_id;
    echo "<p><strong>" . ($isSender ? 'You' : htmlspecialchars($row['sender_name'])) . ":</strong> " . htmlspecialchars($row['message']) . "</p>";
}
$stmt->close();
?>
