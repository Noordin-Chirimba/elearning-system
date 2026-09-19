<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: student_login.php');
    exit;
}

$user_email = $_SESSION['email'];
$conn = connectDB();

// Fetch the student's details from the students table
$sql_student = "SELECT name FROM students WHERE email = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

// Check if the query returned any result
if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $student_name = $student['name'];  // Get the student name
} else {
    // Handle the case where no student is found
    $student_name = "Student";
    // Optionally, log this or display an error message
    error_log("No student found for email: $user_email");
}

$stmt->close();

// Query to count unread messages for the logged-in user
$sql_unread_count = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_email = ? AND is_read = 0";
$stmt = $conn->prepare($sql_unread_count);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$unread_count = $result->fetch_assoc()['unread_count'] ?? 0;  // Get the unread message count
$stmt->close();

// Add this query after the existing queries, before closing the connection
$sql_grades = "SELECT submissions.*, assignments.title 
               FROM submissions 
               JOIN assignments ON submissions.assignment_id = assignments.id 
               WHERE submissions.student_id = (SELECT id FROM students WHERE email = ?) 
               ORDER BY submissions.id DESC 
               LIMIT 4";
$stmt = $conn->prepare($sql_grades);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$grades_result = $stmt->get_result();
$grades = [];
while ($row = $grades_result->fetch_assoc()) {
    $grades[] = $row;
}
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="shortcut icon" href="assets/image/hill.png" type="image/x-icon" />
    <link rel="apple-touch-icon" href="assets/image/hill.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #008751;
            --secondary-color: #2c3e50;
            --background-color: #ecf0f1;
            --text-color: #333;
            --card-background: #fff;
            --sidebar-width: 250px;
        }

        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--secondary-color);
            color: white;
            padding-top: 10px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar h2 {
            padding: 0 20px;
            margin-top: 5px;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: white;
            padding: 15px 20px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .badge {
            background-color: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-left: auto;
        }

        .content {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .card {
            background-color: var(--card-background);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .card h2 {
            color: var(--primary-color);
            margin-top: 0;
        }

        .card p {
            color: #7f8c8d;
        }

        .progress-bar {
            background-color: #e0e0e0;
            border-radius: 10px;
            height: 10px;
            margin-top: 10px;
        }

        .progress {
            background-color: var(--primary-color);
            height: 100%;
            border-radius: 10px;
            width: 0;
            transition: width 1s ease-in-out;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: static;
            }

            .content {
                margin-left: 0;
            }
        }

        .logo-container {
            padding: 10px;
            text-align: center;
            margin-bottom: 15px;
        }

        .logo {
            max-width: 100%;
            width: 220px;  /* Adjusted to match sidebar width while accounting for padding */
            height: auto;
            margin: 0 auto;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo-container">
                <img src="assets/image/LCLOGO.png" alt="LC Logo" class="logo">
            </div>
            <h2>Student Dashboard</h2>
            <a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="student_courses.php"><i class="fas fa-book"></i> My Courses</a>
            <a href="assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
            <a href="student_quizzes.php"><i class="fas fa-question-circle"></i> Quizzes</a>
            <a href="student_grades.php"><i class="fas fa-chart-line"></i> Grades</a>
            <a href="join_live_class.php"><i class="fas fa-user-graduate"></i> Join Live Class</a>
            <a href="student_report.php"><i class="fas fa-file-alt"></i> Report</a>
            <a href="messages.php">
                <i class="fas fa-comments"></i> Messages 
                <?php if ($unread_count > 0): ?>
                    <span class="badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="logout.php" onclick="return confirmLogout();"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        <div class="content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($student_name); ?></h1>
            </div>
            <div class="card-container">
                <div class="card">
                    <h2>Your Courses</h2>
                    <p>Track your progress in enrolled courses.</p>
                    <div class="progress-bar">
                        <div class="progress" style="width: 75%;"></div>
                    </div>
                </div>
                <div class="card">
                    <h2>Upcoming Assignments</h2>
                    <p>Stay updated with deadlines and submissions.</p>
                    <ul id="assignments-list">
                        <li>Math Assignment - Due in 2 days</li>
                        <li>History Essay - Due in 5 days</li>
                    </ul>
                </div>
                <div class="card">
                    <h2>Grades and Feedback</h2>
                    <p>View your recent grades and instructor feedback.</p>
                    <div id="grades-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }

        // Animate progress bars
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
        });

        // Simple grades chart (you can replace this with a proper charting library)
        const gradesChart = document.getElementById('grades-chart');
        const grades = <?php echo json_encode($grades); ?>;

        if (grades.length === 0) {
            gradesChart.innerHTML = '<p>No submissions yet.</p>';
        } else {
            grades.forEach(submission => {
                // Main container
                const bar = document.createElement('div');
                bar.style.marginBottom = '20px';

                // Title row
                const titleRow = document.createElement('div');
                titleRow.style.marginBottom = '5px';
                
                const label = document.createElement('span');
                label.textContent = submission.title;
                label.style.fontWeight = 'bold';
                label.style.color = '#2c3e50';
                
                titleRow.appendChild(label);
                bar.appendChild(titleRow);

                // Progress bar row
                const progressRow = document.createElement('div');
                progressRow.style.display = 'flex';
                progressRow.style.alignItems = 'center';
                progressRow.style.marginBottom = '5px';

                const progressBar = document.createElement('div');
                progressBar.className = 'progress-bar';
                progressBar.style.flex = '1';
                progressBar.style.marginRight = '10px';

                const progress = document.createElement('div');
                progress.className = 'progress';
                progress.style.width = `${submission.grade}%`;

                const gradeLabel = document.createElement('span');
                gradeLabel.textContent = `${submission.grade}%`;
                gradeLabel.style.minWidth = '50px';
                gradeLabel.style.textAlign = 'right';

                progressBar.appendChild(progress);
                progressRow.appendChild(progressBar);
                progressRow.appendChild(gradeLabel);
                bar.appendChild(progressRow);

                // Feedback row
                if (submission.feedback) {
                    const feedbackRow = document.createElement('div');
                    feedbackRow.style.fontSize = '0.9em';
                    feedbackRow.style.color = '#666';
                    feedbackRow.style.paddingLeft = '10px';
                    feedbackRow.style.borderLeft = '3px solid #e0e0e0';
                    feedbackRow.textContent = `Feedback: ${submission.feedback}`;
                    bar.appendChild(feedbackRow);
                }

                gradesChart.appendChild(bar);
            });
        }
    </script>
</body>
</html>
