<?php
require 'config.php';

// Check if `sender_id` and `receiver_id` exist in the request
if (!isset($_GET['sender_id']) || !isset($_GET['receiver_id'])) {
    die('Error: Missing sender or receiver information.');
}

$sender_id = $_GET['sender_id'];
$receiver_id = $_GET['receiver_id'];

// Now, execute the query only if both sender_id and receiver_id are provided
$query = "SELECT * FROM messages 
          WHERE (sender_id = '$sender_id' AND receiver_id = '$receiver_id') 
          OR (sender_id = '$receiver_id' AND receiver_id = '$sender_id') 
          ORDER BY timestamp ASC";

$result = mysqli_query(connectDB(), $query);

if (!$result) {
    die('Error: ' . mysqli_error(connectDB()));
}

// Display the chat messages
while ($row = mysqli_fetch_assoc($result)) {
    $sender = $row['sender_id'];
    $message = $row['message'];
    echo "<div><strong>{$sender}:</strong> {$message}</div>";
}
?>
