<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: student_login.php');
    exit;
}

$student_id = $_SESSION['user_id']; // Assuming student's ID is stored in the session
$conn = connectDB();

// Handle form submission for assignment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignment_id = $_POST['assignment_id'];
    
    // Check if file was uploaded without errors
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0) {
        // Define allowed file types and size
        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB

        // Get file info
        $file_name = $_FILES['submission_file']['name'];
        $file_tmp_name = $_FILES['submission_file']['tmp_name'];
        $file_size = $_FILES['submission_file']['size'];
        $file_type = pathinfo($file_name, PATHINFO_EXTENSION);

        // Validate file type and size
        if (in_array($file_type, $allowed_types) && $file_size <= $max_file_size) {
            // Specify the directory to save the file
            $upload_dir = 'uploads/'; // Ensure this directory exists and is writable
            $new_file_name = uniqid() . '.' . $file_type; // Create a unique file name
            
            // Move the uploaded file to the target directory
            if (move_uploaded_file($file_tmp_name, $upload_dir . $new_file_name)) {
                // Insert the submission into the database
                $stmt = $conn->prepare("INSERT INTO submissions (student_id, assignment_id, file_name, submission_date) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iis", $student_id, $assignment_id, $new_file_name);
                
                if ($stmt->execute()) {
                    echo "<p>Assignment submitted successfully!</p>";
                } else {
                    echo "<p>Error: " . $stmt->error . "</p>";
                }
                $stmt->close();
            } else {
                echo "<p>Error: Could not move the uploaded file.</p>";
            }
        } else {
            echo "<p>Error: Invalid file type or size exceeds limit.</p>";
        }
    } else {
        echo "<p>Error: " . $_FILES['submission_file']['error'] . "</p>";
    }
}

?>
