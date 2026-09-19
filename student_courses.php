<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: student_login.php');
    exit;
}

$user_email = $_SESSION['email'];
$conn = connectDB();

// First, get the student's program_id and other details
$sql_student = "SELECT s.id, s.program_id, p.title as program_title 
                FROM students s 
                JOIN programs p ON s.program_id = p.program_id 
                WHERE s.email = ?";
$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("s", $user_email);
$stmt_student->execute();
$student_result = $stmt_student->get_result();
$student = $student_result->fetch_assoc();
$stmt_student->close();

if (!$student) {
    die("Error: Student program information not found.");
}

// Fetch courses for the student's program with instructor's name and program info
$sql_courses = "SELECT c.id as course_id, c.title, c.description, 
                i.name as instructor_name, p.title as program_title
                FROM courses c
                JOIN instructors i ON c.instructor_id = i.id
                JOIN programs p ON c.program_id = p.program_id
                WHERE c.program_id = ?";
$stmt_courses = $conn->prepare($sql_courses);
$stmt_courses->bind_param("i", $student['program_id']);
$stmt_courses->execute();
$result_courses = $stmt_courses->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #3b82f6;
            --background-color: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --card-background: #ffffff;
            --border-color: #e2e8f0;
            --success-color: #10b981;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            margin-top: 1rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .course-card {
            background: var(--card-background);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        .course-header {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            padding: 1.5rem;
            color: white;
        }

        .course-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .instructor-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .instructor-info i {
            font-size: 1rem;
        }

        .course-body {
            padding: 1.5rem;
        }

        .course-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .content-section h3 {
            font-size: 1.1rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .content-list {
            list-style: none;
        }

        .content-list li {
            margin-bottom: 0.75rem;
        }

        .content-list a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .content-list a:hover {
            background-color: #f1f5f9;
        }

        .empty-content {
            color: var(--text-secondary);
            font-style: italic;
            padding: 1rem;
            text-align: center;
            background: #f8fafc;
            border-radius: 6px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
                margin-bottom: 2rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .courses-grid {
                grid-template-columns: 1fr;
            }
        }

        .program-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 0.5rem;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
        }

        .enroll-button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--success-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
            margin-top: 1rem;
            text-align: center;
        }

        .enroll-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .course-card[data-program="IHHCM"] .course-header {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
        }

        .course-card[data-program="PAC"] .course-header {
            background: linear-gradient(45deg, #3498db, #2980b9);
        }

        .course-card[data-program="PH"] .course-header {
            background: linear-gradient(45deg, #9b59b6, #8e44ad);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>My Program Courses</h1>
            <p>Explore courses for <?php echo htmlspecialchars($student['program_title'] ?? 'your program'); ?></p>
        </div>

        <div class="courses-grid">
            <?php if ($result_courses->num_rows > 0): ?>
                <?php while ($course = $result_courses->fetch_assoc()): ?>
                    <div class="course-card" data-program="<?php echo htmlspecialchars($student['program_title'] ?? ''); ?>">
                        <div class="course-header">
                            <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                            <div class="course-meta">
                                <div class="instructor-info">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    <span><?php echo htmlspecialchars($course['instructor_name']); ?></span>
                                </div>
                                <span class="program-badge">
                                    <i class="fas fa-graduation-cap"></i>
                                    <?php echo htmlspecialchars($course['program_title']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="course-body">
                            <p class="course-description">
                                <?php echo htmlspecialchars($course['description']); ?>
                            </p>

                            <div class="content-section">
                                <h3>
                                    <i class="fas fa-book-reader"></i>
                                    Course Materials
                                </h3>
                                <ul class="content-list">
                                    <?php
                                    $course_id = $course['course_id'];
                                    $sql_content = "SELECT * FROM course_contents WHERE course_id = ?";
                                    $stmt_content = $conn->prepare($sql_content);
                                    $stmt_content->bind_param("i", $course_id);
                                    $stmt_content->execute();
                                    $result_content = $stmt_content->get_result();
                                    if ($result_content->num_rows > 0):
                                        while ($content = $result_content->fetch_assoc()): ?>
                                            <li>
                                                <a href="<?php echo htmlspecialchars($content['file_path']); ?>" target="_blank">
                                                    <i class="fas fa-file-alt"></i>
                                                    <?php echo htmlspecialchars($content['file_name']); ?>
                                                </a>
                                            </li>
                                        <?php endwhile;
                                    else: ?>
                                        <li class="empty-content">
                                            <i class="fas fa-info-circle"></i>
                                            No content available for this course yet.
                                        </li>
                                    <?php endif;
                                    $stmt_content->close(); ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-content" style="grid-column: 1/-1; padding: 2rem; text-align: center;">
                    <i class="fas fa-info-circle"></i>
                    No courses are currently available for your program.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>