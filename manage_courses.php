<?php
session_start();
require 'config.php'; // Include your database connection file

// Connect to the database
$conn = connectDB();

if (!isset($_SESSION['instructor_loggedin']) || $_SESSION['instructor_loggedin'] !== true) {
    header('Location: instructor_login.php');
    exit;
}

if (!isset($_SESSION['instructor_id'])) {
    die("Instructor ID is not set in the session.");
}

$instructorId = $_SESSION['instructor_id'];

// Debugging output
echo "Instructor ID: $instructorId";

// Fetch instructor's courses
$query = "SELECT c.*, p.title as program_title 
          FROM courses c 
          LEFT JOIN programs p ON c.program_id = p.program_id 
          WHERE c.instructor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $instructorId);
$stmt->execute();
$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submissions for adding courses
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_course'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $program_id = $_POST['program_id'];

        // Check for non-empty values
        if (empty($instructorId)) {
            die("Instructor ID cannot be empty.");
        }

        if (!empty($title) && !empty($description) && !empty($program_id)) {
            $stmt = $conn->prepare("INSERT INTO courses (title, description, instructor_id, program_id) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssii", $title, $description, $instructorId, $program_id);
                if ($stmt->execute()) {
                    echo "<script>alert('Course added successfully');</script>";
                    header('Location: manage_courses.php');
                } else {
                    echo "Error executing statement: " . $stmt->error;
                }
                $stmt->close();
            } else {
                echo "Error preparing statement: " . $conn->error;
            }
        } else {
            echo "All fields are required.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Instructor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --sidebar-bg: #2c3e50;
            --hover-color: #34495e;
            --text-light: #ecf0f1;
            --text-dark: #2c3e50;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --card-bg: #ffffff;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --border-color: #e1e8ed;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100%;
            background-color: var(--sidebar-bg);
            color: var(--text-light);
            padding-top: 2rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            z-index: 1000;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 2rem;
            padding: 0 1rem;
            font-size: 1.5rem;
            color: var(--text-light);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 1rem;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0.2rem 0;
        }

        .sidebar ul li a i {
            margin-right: 1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar ul li a:hover {
            background-color: var(--hover-color);
            border-left: 4px solid var(--primary-color);
        }

        .content {
            margin-left: 280px;
            padding: 2rem;
            max-width: 1200px;
            margin-right: auto;
        }

        .content h2 {
            margin-bottom: 2rem;
            color: var(--text-dark);
            font-size: 2rem;
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 0.5rem;
            display: inline-block;
        }

        .courses {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .course {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
            border-top: 4px solid var(--primary-color);
        }

        .course:hover {
            transform: translateY(-5px);
        }

        .course h4 {
            color: var(--text-dark);
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .course p {
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .actions a {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .actions a:nth-child(1) {
            background-color: var(--primary-color);
            color: white;
        }

        .actions a:nth-child(2) {
            background-color: var(--danger-color);
            color: white;
        }

        .actions a:nth-child(3) {
            background-color: var(--success-color);
            color: white;
        }

        .actions a:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .uploaded-files {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
        }

        .uploaded-files h5 {
            color: var(--text-dark);
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .file {
            padding: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .file:last-child {
            border-bottom: none;
        }

        .file a {
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file a i {
            font-size: 1.1rem;
        }

        .add-course {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-top: 2rem;
        }

        .add-course h3 {
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #357abd;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .sidebar h2, .sidebar ul li a span {
                display: none;
            }

            .content {
                margin-left: 70px;
            }

            .courses {
                grid-template-columns: 1fr;
            }
        }

        .program-tag {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            display: inline-block;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }

        .program-tag:hover {
            transform: translateY(-2px);
        }

        .program-tag[data-program="IHHCM"] {
            background-color: #2ecc71; /* Green */
        }

        .program-tag[data-program="PAC"] {
            background-color: #3498db; /* Blue */
        }

        .program-tag[data-program="PH"] {
            background-color: #9b59b6; /* Purple */
        }

        .program-tag i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Instructor Dashboard</h2>
        <ul>
            <li><a href="instructor_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="manage_courses.php"><i class="fas fa-chalkboard-teacher"></i> <span>My Courses</span></a></li>
            <li><a href="create_assignment.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
            <li><a href="grade_submission.php"><i class="fas fa-check-circle"></i> <span>Grade assignments</span></a></li>
            <li><a href="create_quiz.php"><i class="fas fa-question-circle"></i> <span>Quizzes</span></a></li>
            <li><a href="student_progress.php"><i class="fas fa-user-graduate"></i> <span>Student Progress</span></a></li>
            <li><a href="messages.php">
                <i class="fas fa-comments"></i> 
                <span>Messages</span>
                <?php if (isset($unread_count) && $unread_count > 0): ?>
                    <span class="badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <div class="content">
        <h2>Manage My Courses</h2>
        
        <div class="courses">
            <?php foreach ($courses as $course): ?>
                <div class="course">
                    <h4><i class="fas fa-book"></i> <?php echo htmlspecialchars($course['title']); ?></h4>
                    <p class="program-tag" data-program="<?php echo htmlspecialchars($course['program_id']); ?>">
                        <i class="fas fa-graduation-cap"></i> 
                        Program: <?php echo htmlspecialchars($course['program_title'] ?? 'Not Assigned'); ?>
                    </p>
                    <p><?php echo htmlspecialchars($course['description']); ?></p>
                    <div class="actions">
                        <a href="edit_course.php?id=<?php echo $course['id']; ?>"><i class="fas fa-edit"></i> Edit</a>
                        <a href="delete_course.php?id=<?php echo $course['id']; ?>" onclick="return confirm('Are you sure you want to delete this course?');"><i class="fas fa-trash-alt"></i> Delete</a>
                        <a href="upload_content.php?id=<?php echo $course['id']; ?>"><i class="fas fa-upload"></i> Upload</a>
                    </div>
                    
                    <div class="uploaded-files">
                        <h5><i class="fas fa-file-alt"></i> Course Materials</h5>
                        <?php
                        $courseId = $course['id'];
                        $fileQuery = "SELECT * FROM course_contents WHERE course_id = ?";
                        $fileStmt = $conn->prepare($fileQuery);
                        $fileStmt->bind_param("i", $courseId);
                        $fileStmt->execute();
                        $fileResult = $fileStmt->get_result();
                        if ($fileResult->num_rows > 0):
                            while ($file = $fileResult->fetch_assoc()): ?>
                                <div class="file">
                                    <a href="<?php echo $file['file_path']; ?>" target="_blank">
                                        <i class="fas fa-file"></i>
                                        <?php echo htmlspecialchars($file['file_name']); ?>
                                    </a>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <p><i class="fas fa-info-circle"></i> No files uploaded yet.</p>
                        <?php endif;
                        $fileStmt->close();
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="add-course">
            <h3><i class="fas fa-plus-circle"></i> Add New Course</h3>
            <form action="manage_courses.php" method="post">
                <div class="form-group">
                    <label for="title">Course Title:</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="description">Course Description:</label>
                    <textarea id="description" name="description" rows="4" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="program">Select Program:</label>
                    <select id="program" name="program_id" class="form-control" required>
                        <option value="">Select a program</option>
                        <?php
                        // Fetch programs from database
                        $programQuery = "SELECT program_id, title FROM programs";
                        $programResult = $conn->query($programQuery);
                        while ($program = $programResult->fetch_assoc()) {
                            echo "<option value='" . $program['program_id'] . "'>" . htmlspecialchars($program['title']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" name="add_course" class="btn-primary">
                    <i class="fas fa-plus"></i> Add Course
                </button>
            </form>
        </div>
    </div>
</body>
</html>