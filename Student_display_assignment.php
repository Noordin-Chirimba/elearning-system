<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: student_login.php');
    exit;
}

$student_id = $_SESSION['student_id']; // Assuming student's ID is stored in the session
$conn = connectDB();

// Fetch all assignments from the database
$sql_assignments = "SELECT a.id, a.title, a.description, a.type, a.deadline, i.name as instructor_name 
                    FROM assignments a
                    JOIN instructors i ON a.instructor_id = i.id
                    ORDER BY a.deadline ASC";
$result_assignments = $conn->query($sql_assignments);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .assignment {
            background-color: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .assignment h3 {
            margin: 0;
            font-size: 18px;
        }
        .assignment p {
            margin: 5px 0;
            color: #555;
        }
        .assignment .deadline {
            color: #e74c3c;
        }
        .submit-btn {
            background-color: #2980b9;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background-color: #3498db;
        }
    </style>
</head>
<body>

<h2>Available Assignments</h2>

<?php while ($assignment = $result_assignments->fetch_assoc()): ?>
    <div class="assignment">
        <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($assignment['instructor_name']); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($assignment['description']); ?></p>
        <p><strong>Type:</strong> <?php echo htmlspecialchars($assignment['type']); ?></p>
        <p><strong>Deadline:</strong> <span class="deadline"><?php echo htmlspecialchars($assignment['deadline']); ?></span></p>
        <form action="submit_assignment.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
            <label for="submission_file">Upload Assignment:</label>
            <input type="file" id="submission_file" name="submission_file" required>
            <input type="submit" value="Submit Assignment" class="submit-btn">
        </form>
    </div>
<?php endwhile; ?>

</body>
</html>
