<?php
session_start();
require 'config.php';

if (!isset($_SESSION['instructor_loggedin'])) {
    header('Location: instructor_login.php');
    exit;
}

$instructor_id = $_SESSION['instructor_id'];
$conn = connectDB();

// Fetch all submissions for the instructor's assignments
$sql_submissions = "SELECT s.id, s.submitted_at, s.submission_file, s.grade, s.feedback, 
                    a.title as assignment_title, st.name as student_name 
                    FROM submissions s
                    JOIN assignments a ON s.assignment_id = a.id
                    JOIN students st ON s.student_id = st.id
                    WHERE a.instructor_id = ?
                    ORDER BY s.submitted_at DESC";
$stmt = $conn->prepare($sql_submissions);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result_submissions = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Submissions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

<h2>Student Submissions</h2>

<?php while ($submission = $result_submissions->fetch_assoc()): ?>
    <div>
        <h3><?php echo htmlspecialchars($submission['student_name']); ?> - <?php echo htmlspecialchars($submission['assignment_title']); ?></h3>
        <p>Submitted at: <?php echo htmlspecialchars($submission['submitted_at']); ?></p>
        <p><a href="<?php echo htmlspecialchars($submission['submission_file']); ?>" target="_blank">View Submission</a></p>
        
        <!-- Form for grading and feedback -->
        <form action="grade_submission.php" method="POST">
            <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
            <label for="grade">Grade:</label>
            <input type="text" name="grade" value="<?php echo $submission['grade']; ?>"><br><br>
            <label for="feedback">Feedback:</label><br>
            <textarea name="feedback" rows="4"><?php echo $submission['feedback']; ?></textarea><br><br>
            <input type="submit" value="Submit Grade and Feedback">
        </form>
    </div>
<?php endwhile; ?>

</body>
</html>
