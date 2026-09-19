<?php
session_start();
require 'config.php'; // Ensure this is correct

// Establish the database connection
$conn = connectDB(); // Call the function to get the connection

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
$unread_count = $result->fetch_assoc()['unread_count']; // Get the unread message count

// Get dynamic data (for total courses, students, profile views, etc.)
$total_courses = 5; // Example value
$total_students = 120; // Example value
$profile_views = 300; // Example value
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
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

        .dashboard-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .dashboard-info div {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: transform 0.3s ease;
            border-top: 4px solid var(--primary-color);
        }

        .dashboard-info div:hover {
            transform: translateY(-5px);
        }

        .dashboard-info div h3 {
            color: var(--text-dark);
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .dashboard-info div p {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .recent-activities {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .recent-activities h3 {
            color: var(--text-dark);
            margin-bottom: 1rem;
            font-size: 1.3rem;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
        }

        .recent-activities ul li {
            background-color: #f8f9fa;
            margin-bottom: 0.8rem;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .recent-activities ul li:hover {
            transform: translateX(5px);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
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

            .dashboard-info {
                grid-template-columns: 1fr;
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
            <li><a href="start_live_class.php"><i class="fas fa-video"></i> Start Live Class</a></li>
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
        <h2>Welcome, Instructor</h2>
        <div class="dashboard-info">
            <div>
                <h3><i class="fas fa-book mr-2"></i> Total Courses</h3>
                <p><?php echo $total_courses; ?></p>
            </div>
            <div>
                <h3><i class="fas fa-users mr-2"></i> Total Students</h3>
                <p><?php echo $total_students; ?></p>
            </div>
            <div>
                <h3><i class="fas fa-eye mr-2"></i> Profile Views</h3>
                <p><?php echo $profile_views; ?></p>
            </div>
        </div>
        <div class="recent-activities">
            <h3><i class="fas fa-history mr-2"></i> Recent Activities</h3>
            <ul>
                <li><i class="fas fa-edit mr-2"></i> Course "Introduction to Python" was updated.</li>
                <li><i class="fas fa-user-plus mr-2"></i> New student enrolled in "Data Science Basics".</li>
                <li><i class="fas fa-check-circle mr-2"></i> Assignment for Week 1 has been graded.</li>
            </ul>
        </div>
    </div>
</body>
</html>