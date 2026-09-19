<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    exit("Not logged in.");
}

$sender_email = $_SESSION['email'];  // Logged-in user's email
$receiver_email = $_GET['receiver_email'] ?? null;  // Receiver's email passed via GET

if (!$receiver_email) {
    exit("Receiver email not specified.");
}

$conn = connectDB();

// Fetch messages between the logged-in user and the selected recipient
$sql = "SELECT * FROM messages 
        WHERE (sender_email = ? AND receiver_email = ?) 
        OR (sender_email = ? AND receiver_email = ?) 
        ORDER BY created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $sender_email, $receiver_email, $receiver_email, $sender_email);
$stmt->execute();
$result = $stmt->get_result();

// Check if any messages are found
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Differentiate between sender and receiver messages using classes
        if ($row['sender_email'] === $sender_email) {
            // Message sent by the logged-in user
            echo "<div class='message sent'><strong>You:</strong> " . htmlspecialchars($row['message']) . "</div>";
        } else {
            // Message received from the other user
            echo "<div class='message received'><strong>" . htmlspecialchars($row['sender_email']) . ":</strong> " . htmlspecialchars($row['message']) . "</div>";
        }
    }

    // Mark all unread messages from the receiver as read
    $sql_update_read_status = "UPDATE messages SET is_read = 1 
                              WHERE sender_email = ? AND receiver_email = ? AND is_read = 0";
    $stmt_update = $conn->prepare($sql_update_read_status);
    $stmt_update->bind_param("ss", $receiver_email, $sender_email);
    $stmt_update->execute();
} else {
    echo "<p>No messages yet.</p>";
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
