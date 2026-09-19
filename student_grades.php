<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: student_login.php');
    exit;
}

$user_email = $_SESSION['email'];
$conn = connectDB();

// Get student info
$sql_student = "SELECT id, name, program FROM students WHERE email = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    die("Student not found");
}

$student_id = $student['id'];
$student_name = $student['name'] ?? 'Unknown Student';
$student_program = $student['program'] ?? 'Not Assigned';
$stmt->close();

// Get all grades for the student
$sql_grades = "
    SELECT 
        a.title as assignment_title,
        a.type as assignment_type,
        s.grade,
        s.submission_date,
        s.feedback,
        a.deadline
    FROM 
        submissions s
        JOIN assignments a ON s.assignment_id = a.id
    WHERE 
        s.student_id = ?
    ORDER BY 
        s.submission_date DESC";

$stmt = $conn->prepare($sql_grades);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$grades_result = $stmt->get_result();
$grades = [];
while ($row = $grades_result->fetch_assoc()) {
    $grades[] = $row;
}
$stmt->close();

// Calculate statistics
$total_grades = count($grades);
$sum_grades = 0;
$assignments_completed = 0;
$on_time_submissions = 0;
$late_submissions = 0;

foreach ($grades as $grade) {
    if ($grade['grade'] !== null) {
        $sum_grades += $grade['grade'];
        $assignments_completed++;
        
        if (strtotime($grade['submission_date']) <= strtotime($grade['deadline'])) {
            $on_time_submissions++;
        } else {
            $late_submissions++;
        }
    }
}

$average_grade = $assignments_completed > 0 ? round($sum_grades / $assignments_completed, 2) : 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grades - <?php echo htmlspecialchars($student_name); ?></title>
    <link rel="shortcut icon" href="assets/image/LCLOGO.png" type="image/x-icon" />
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--secondary-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar h2 {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: rgba(255,255,255,0.1);
        }

        /* Content Styles */
        .content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .header-section {
            background-color: var(--card-background);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
        }

        .header-section h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }

        .stat-card {
            background-color: var(--card-background);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
            border-top: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 15px 0;
        }

        /* Table Styles */
        .grades-table-container {
            background-color: var(--card-background);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow-x: auto;
            margin-top: 30px;
        }

        .grades-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 800px;
        }

        .grades-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .grades-table th:first-child {
            border-top-left-radius: 8px;
        }

        .grades-table th:last-child {
            border-top-right-radius: 8px;
        }

        .grades-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .grades-table tr:last-child td {
            border-bottom: none;
        }

        .grades-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Grade Badges */
        .grade-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
            min-width: 60px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .grade-a { background-color: #28a745; color: white; }
        .grade-b { background-color: #17a2b8; color: white; }
        .grade-c { background-color: #ffc107; color: #000; }
        .grade-d { background-color: #fd7e14; color: white; }
        .grade-f { background-color: #dc3545; color: white; }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }
        }

        /* Menu Toggle Button */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container">
        <div class="sidebar">
            <h2>Student Dashboard</h2>
            <a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="student_courses.php"><i class="fas fa-book"></i> My Courses</a>
            <a href="assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
            <a href="student_quizzes.php"><i class="fas fa-question-circle"></i> Quizzes</a>
            <a href="student_grades.php" class="active"><i class="fas fa-chart-line"></i> Grades</a>
            <a href="join_stream.php"><i class="fas fa-user-graduate"></i> Join Live Class</a>
            <a href="student_report.php"><i class="fas fa-file-alt"></i> Report</a>
            <a href="messages.php"><i class="fas fa-comments"></i> Messages</a>
            <a href="logout.php" onclick="return confirmLogout();"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="content">
            <div class="header-section">
                <h1>Grade Overview</h1>
                <p>Student: <?php echo htmlspecialchars($student_name); ?></p>
                <p>Program: <?php echo htmlspecialchars($student_program); ?></p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Overall Average</h3>
                    <div class="stat-value"><?php echo $average_grade; ?>%</div>
                </div>
                <div class="stat-card">
                    <h3>Assignments Completed</h3>
                    <div class="stat-value"><?php echo $assignments_completed; ?></div>
                </div>
                <div class="stat-card">
                    <h3>On-time Submissions</h3>
                    <div class="stat-value"><?php echo $on_time_submissions; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Late Submissions</h3>
                    <div class="stat-value"><?php echo $late_submissions; ?></div>
                </div>
            </div>

            <?php if (empty($grades)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No Grades Available</h3>
                    <p>Grades will appear here once you submit assignments.</p>
                </div>
            <?php else: ?>
                <div class="grades-table-container">
                    <table class="grades-table">
                        <thead>
                            <tr>
                                <th>Assignment</th>
                                <th>Type</th>
                                <th>Submission Date</th>
                                <th>Due Date</th>
                                <th>Grade</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $grade): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($grade['assignment_title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($grade['assignment_type']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($grade['submission_date'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($grade['deadline'])); ?></td>
                                    <td>
                                        <?php
                                        $grade_value = $grade['grade'];
                                        $grade_class = '';
                                        if ($grade_value >= 90) $grade_class = 'grade-a';
                                        elseif ($grade_value >= 80) $grade_class = 'grade-b';
                                        elseif ($grade_value >= 70) $grade_class = 'grade-c';
                                        elseif ($grade_value >= 60) $grade_class = 'grade-d';
                                        else $grade_class = 'grade-f';
                                        ?>
                                        <span class="grade-badge <?php echo $grade_class; ?>">
                                            <?php echo $grade_value; ?>%
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($grade['feedback'] ?? 'No feedback'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Logout confirmation
        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.getElementById('menuToggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html> 