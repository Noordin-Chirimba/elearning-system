<?php
session_start();
require 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = connectDB();

$sender_id = $_SESSION['user_id']; // From session
$sender_type = $_SESSION['user_type']; // From session ('student' or 'instructor')

if (isset($_POST['receiver_id']) && isset($_POST['receiver_type'])) {
    $receiver_id = $_POST['receiver_id'];
    $receiver_type = $_POST['receiver_type'];

    // Fetch messages from the database
    $query = "SELECT * FROM messages WHERE 
              (sender_id = ? AND receiver_id = ? AND sender_type = ? AND receiver_type = ?) OR 
              (sender_id = ? AND receiver_id = ? AND sender_type = ? AND receiver_type = ?)
              ORDER BY timestamp ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisiisiis", $sender_id, $receiver_id, $sender_type, $receiver_type, $receiver_id, $sender_id, $receiver_type, $sender_type);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $messages = array();
        
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        // Ensure that the result is valid JSON
        header('Content-Type: application/json');
        echo json_encode($messages);
    } else {
        // Log query error
        error_log("Query failed: " . $stmt->error);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Query failed']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
}
?>
