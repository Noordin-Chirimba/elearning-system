<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    exit("Not logged in.");
}

$sender_email = $_SESSION['email'];  // Sender's email from session
$receiver_email = $_POST['receiver_email'];  // Receiver's email from form submission
$message = $_POST['message'];

if (empty($message)) {
    exit("Message cannot be empty.");
}

$conn = connectDB();
$sql = "INSERT INTO messages (sender_email, receiver_email, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $sender_email, $receiver_email, $message);

if ($stmt->execute()) {
    echo "Message sent successfully.";
} else {
    echo "Error sending message: " . $stmt->error;
}
?>
