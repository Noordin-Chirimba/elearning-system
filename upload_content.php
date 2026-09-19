<?php
session_start();
require 'config.php';

// Establish the database connection
$conn = connectDB();

// Check if instructor is logged in
if (!isset($_SESSION['instructor_loggedin']) || $_SESSION['instructor_loggedin'] !== true) {
    header('Location: instructor_login.php');
    exit;
}

$user_email = $_SESSION['email'];

// Query to count unread messages for the logged-in user
$sql_unread_count = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_email = ? AND is_read = 0";
$stmt = $conn->prepare($sql_unread_count);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$unread_count = $result->fetch_assoc()['unread_count'];

// Get course ID from the query string
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage_courses.php');
    exit;
}

$courseId = $_GET['id'];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['content_file'])) {
    $file = $_FILES['content_file'];
    
    if ($file['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $file['tmp_name'];
        $fileName = basename($file['name']);
        $fileSize = $file['size'];
        $fileType = $file['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        // Define allowed file extensions and directory for uploads
        $allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];
        $uploadDir = 'uploads/';
        
        if (in_array($fileExtension, $allowedExts)) {
            $destFilePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($fileTmpPath, $destFilePath)) {
                $stmt = $conn->prepare("INSERT INTO course_contents (course_id, file_name, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issis", $courseId, $fileName, $destFilePath, $fileSize, $fileType);
                $stmt->execute();
                $stmt->close();
                
                $success_message = 'File uploaded successfully!';
            } else {
                $error_message = 'There was an error uploading the file. Please try again.';
            }
        } else {
            $error_message = 'Invalid file type. Only PDF, DOC, DOCX, PPT, PPTX, XLS, and XLSX files are allowed.';
        }
    } else {
        $error_message = 'Error in file upload. Please try again.';
    }
}

// Fetch uploaded files
$query = "SELECT * FROM course_contents WHERE course_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $courseId);
$stmt->execute();
$uploadedFiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Content - Instructor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

        .badge {
            background-color: var(--danger-color);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: auto;
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

        .upload-form {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #357abd;
        }

        .files-table {
            width: 100%;
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-top: 2rem;
        }

        .files-table th,
        .files-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .files-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: var(--success-color);
            color: white;
        }

        .alert-danger {
            background-color: var(--danger-color);
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .sidebar h2 {
                display: none;
            }

            .sidebar ul li a span {
                display: none;
            }

            .content {
                margin-left: 70px;
            }
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
                <?php if ($unread_count > 0): ?>
                    <span class="badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <div class="content">
        <h2>Upload Course Content</h2>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="upload-form">
            <form action="upload_content.php?id=<?php echo htmlspecialchars($courseId); ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="content_file">Select File:</label>
                    <input type="file" id="content_file" name="content_file" class="form-control" required>
                    <small class="text-muted">Allowed file types: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX</small>
                </div>
                <button type="submit" class="btn btn-primary">Upload Content</button>
            </form>
        </div>

        <?php if (count($uploadedFiles) > 0): ?>
            <table class="files-table">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>File Size</th>
                        <th>File Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uploadedFiles as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                            <td><?php echo number_format($file['file_size'] / 1024, 2); ?> KB</td>
                            <td><?php echo htmlspecialchars($file['file_type']); ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($file['file_path']); ?>" class="btn btn-primary" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">No files have been uploaded for this course yet.</div>
        <?php endif; ?>
    </div>
</body>
</html>