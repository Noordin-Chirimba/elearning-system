<?php
session_start();
require 'config.php';
// Check if the instructor is logged in
if (!isset($_SESSION['instructor_loggedin']) || $_SESSION['instructor_loggedin'] !== true) {
    header('Location: instructor_login.php');
    exit;
}
$conn = connectDB();
// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $submission_id = $_POST['submission_id'];
    $grade = $_POST['grade'];
    $feedback = $_POST['feedback'];
    // Update the submission with the grade and feedback
    $stmt = $conn->prepare("UPDATE submissions SET grade = ?, feedback = ? WHERE id = ?");
    $stmt->bind_param("ssi", $grade, $feedback, $submission_id);
    if ($stmt->execute()) {
        $success_message = "Grade and feedback submitted successfully!";
    } else {
        $error_message = "Error submitting grade: " . $stmt->error;
    }
    $stmt->close();
}
// Get submissions for the specific assignment
$assignment_id = $_GET['assignment_id'];
$stmt = $conn->prepare("SELECT s.id AS submission_id, s.file_name, s.submission_date, st.name AS student_name, s.grade, s.feedback
                        FROM submissions s
                        JOIN students st ON s.student_id = st.id
                        WHERE s.assignment_id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submissions</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 2rem 0;
        }
        .page-header {
            background-color: #fff;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .grading-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }
        .submission-info {
            margin-bottom: 1rem;
        }
        .download-link {
            color: #0d6efd;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .download-link:hover {
            color: #0b5ed7;
        }
        .grade-input {
            width: 100px;
        }
        .feedback-textarea {
            min-height: 100px;
            resize: vertical;
        }
        .alert {
            margin-bottom: 2rem;
        }
        .submission-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .student-name {
            font-weight: 600;
            color: #212529;
        }
        .btn-submit-grade {
            background-color: #198754;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .btn-submit-grade:hover {
            background-color: #157347;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #6c757d;
            text-decoration: none;
        }
        .back-link:hover {
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <a href="assignments.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Assignments
        </a>

        <div class="page-header text-center">
            <h2 class="display-6">Grade Submissions</h2>
        </div>

        <?php while ($row = $result->fetch_assoc()) : ?>
            <div class="grading-card">
                <div class="submission-info">
                    <h4 class="student-name"><?php echo htmlspecialchars($row['student_name']); ?></h4>
                    <div class="submission-date">
                        <i class="far fa-clock"></i> Submitted on: <?php echo date('F j, Y, g:i a', strtotime($row['submission_date'])); ?>
                    </div>
                    <div class="mt-2">
                        <a href="uploads/<?php echo htmlspecialchars($row['file_name']); ?>" class="download-link" target="_blank">
                            <i class="fas fa-download"></i> Download Submission
                        </a>
                    </div>
                </div>

                <form action="" method="post" class="grading-form">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label for="grade-<?php echo $row['submission_id']; ?>" class="form-label">Grade</label>
                            <input type="text" 
                                   class="form-control grade-input" 
                                   id="grade-<?php echo $row['submission_id']; ?>"
                                   name="grade" 
                                   value="<?php echo htmlspecialchars($row['grade'] ?? ''); ?>" 
                                   required>
                        </div>
                        <div class="col-md-10">
                            <label for="feedback-<?php echo $row['submission_id']; ?>" class="form-label">Feedback</label>
                            <textarea class="form-control feedback-textarea" 
                                      id="feedback-<?php echo $row['submission_id']; ?>"
                                      name="feedback" 
                                      required><?php echo htmlspecialchars($row['feedback'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($row['submission_id']); ?>">
                        <button type="submit" class="btn-submit-grade">
                            <i class="fas fa-check"></i> Submit Grade
                        </button>
                    </div>
                </form>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>