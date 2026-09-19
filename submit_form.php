<?php
session_start();
require 'config.php';

$conn = connectDB();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate inputs
    if (empty($_POST['name']) || empty($_POST['phone']) || empty($_POST['address']) || empty($_POST['course'])) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }

    // Sanitize inputs
    $name = htmlspecialchars(trim($_POST['name']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $address = htmlspecialchars(trim($_POST['address']));
    $course = htmlspecialchars(trim($_POST['course']));

    // Prepare the statement to insert the student data
    $stmt = $conn->prepare("INSERT INTO students (name, phone, address, course) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $phone, $address, $course);

    if ($stmt->execute()) {
        // Get the auto-incremented ID of the student
        $student_id = $conn->insert_id;

        // Generate the student ID
        $current_year = date('Y');
        $program_code = ($course === 'IHHCM') ? 'IHHCM' : (($course === 'PAC') ? 'PAC' : 'PH');
        $generated_student_id = $current_year . '-' . $student_id . '-' . $program_code;

        // Update the student record with the generated student ID
        $update_stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE id = ?");
        $update_stmt->bind_param("si", $generated_student_id, $student_id);

        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'student_id' => $generated_student_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update student ID: ' . $update_stmt->error]);
        }

        $update_stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to insert student: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
