<?php
session_start();
require 'config.php';

if (!isset($_SESSION['instructor_loggedin'])) {
    header('Location: instructor_login.php');
    exit;
}

$instructor_id = $_SESSION['instructor_id'];
$conn = connectDB();

// Fetch courses taught by this instructor
$stmt_courses = $conn->prepare("SELECT id, title FROM courses WHERE instructor_id = ?");
$stmt_courses->bind_param("i", $instructor_id);
$stmt_courses->execute();
$courses_result = $stmt_courses->get_result();
$courses = $courses_result->fetch_all(MYSQLI_ASSOC);
$stmt_courses->close();

// Handle form submission for assignment creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $course_id = $_POST['course_id'];
    $deadline = $_POST['deadline'] . ' ' . $_POST['deadline_time']; // Combine date and time

    // Handle file upload
    $file_path = null;
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['assignment_file']['tmp_name'];
        $file_name = $_FILES['assignment_file']['name'];
        $file_size = $_FILES['assignment_file']['size'];
        $file_type = $_FILES['assignment_file']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_file_types = ['pdf', 'doc', 'docx', 'txt'];

        if (in_array($file_ext, $allowed_file_types)) {
            $new_file_name = uniqid('assignment_', true) . '.' . $file_ext;
            $upload_dir = 'uploads/assignments/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_path = $upload_dir . $new_file_name;
            if (!move_uploaded_file($file_tmp_path, $file_path)) {
                $error_message = "Error uploading file.";
            }
        } else {
            $error_message = "Invalid file type. Only PDF, DOC, DOCX, and TXT files are allowed.";
        }
    }

    if (!isset($error_message)) {
        $stmt = $conn->prepare("INSERT INTO assignments (instructor_id, course_id, title, description, type, deadline, document_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssss", $instructor_id, $course_id, $title, $description, $type, $deadline, $file_path);
        
        if ($stmt->execute()) {
            $success_message = "Assignment created successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            line-height: 1.5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary-color), #4f46e5);
            color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            opacity: 0.9;
        }

        .form-card {
            background: var(--card-background);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .form-header {
            background: linear-gradient(to right, var(--primary-color), #4f46e5);
            padding: 1.5rem;
            color: white;
        }

        .form-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .file-drop-area {
            border: 2px dashed var(--border-color);
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s;
            margin-bottom: 1rem;
        }

        .file-drop-area:hover {
            border-color: var(--primary-color);
        }

        .submit-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
            justify-content: center;
        }

        .submit-btn:hover {
            background: var(--primary-hover);
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: var(--success-color);
        }

        .alert-error {
            background: #fef2f2;
            color: var(--danger-color);
        }

        .deadline-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .deadline-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
            background-color: white;
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Add styles for disabled submit button */
        .submit-btn:disabled {
            background-color: var(--text-secondary);
            cursor: not-allowed;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>Create Assignment</h1>
            <p>Create a new assignment for your students</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-header">
                <h2>Assignment Details</h2>
            </div>
            <div class="form-body">
                <form action="create_assignment.php" method="POST" enctype="multipart/form-data" id="assignment-form">
                    <div class="form-group">
                        <label for="title" class="form-label">Assignment Title</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Assignment Description</label>
                        <textarea id="description" name="description" class="form-control" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="type" class="form-label">Assignment Type</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="weekly">Weekly Assignment</option>
                            <option value="end_of_semester">End of Semester</option>
                            <option value="project">Project</option>
                            <option value="quiz">Quiz</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="course_id" class="form-label">Select Course</label>
                        <?php if (count($courses) > 0): ?>
                            <select id="course_id" name="course_id" class="form-control" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course['id']); ?>">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                You need to create a course first before creating assignments.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="deadline-row">
                        <div class="form-group">
                            <label for="deadline" class="form-label">Deadline Date</label>
                            <input type="date" id="deadline" name="deadline" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="deadline_time" class="form-label">Deadline Time</label>
                            <input type="time" id="deadline_time" name="deadline_time" class="form-control" required value="23:59">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Assignment Document</label>
                        <div class="file-drop-area" onclick="document.getElementById('assignment_file').click()">
                            <i class="fas fa-cloud-upload-alt fa-2x"></i>
                            <p>Drop your file here or click to browse</p>
                            <p class="text-sm text-secondary">Supported formats: PDF, DOC, DOCX, TXT</p>
                            <span id="selected-file"></span>
                        </div>
                        <input type="file" id="assignment_file" name="assignment_file" accept=".pdf,.doc,.docx,.txt" style="display: none;">
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Create Assignment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('assignment_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                document.getElementById('selected-file').textContent = `Selected: ${fileName}`;
            }
        });

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('deadline').min = today;

        // Form validation
        document.getElementById('assignment-form').addEventListener('submit', function(e) {
            const courseId = document.getElementById('course_id').value;
            if (!courseId) {
                e.preventDefault();
                alert('Please select a course for this assignment');
                return;
            }
            
            const deadline = new Date(
                document.getElementById('deadline').value + 'T' + 
                document.getElementById('deadline_time').value
            );
            const now = new Date();

            if (deadline <= now) {
                e.preventDefault();
                alert('Deadline must be in the future');
            }
        });

        // Disable submit button if no courses are available
        window.onload = function() {
            const courseSelect = document.getElementById('course_id');
            const submitBtn = document.querySelector('.submit-btn');
            
            if (courseSelect && courseSelect.options.length <= 1) {
                submitBtn.disabled = true;
            }
        }
    </script>
</body>
</html>