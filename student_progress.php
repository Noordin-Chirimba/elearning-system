<?php
session_start();
require 'config.php';

// Check if instructor is logged in
if (!isset($_SESSION['instructor_loggedin']) || $_SESSION['instructor_loggedin'] !== true) {
    header('Location: instructor_login.php');
    exit;
}

$conn = connectDB();
$instructor_email = $_SESSION['email'];

// Modified SQL query to debug
$sql_students = "
    SELECT DISTINCT s.id, s.name, s.email, s.program_id, 
           COUNT(DISTINCT sub.id) as total_submissions,
           ROUND(AVG(sub.grade), 2) as average_grade
    FROM students s
    LEFT JOIN submissions sub ON s.id = sub.student_id
    JOIN courses c ON s.program_id = c.program_id
    JOIN instructors i ON c.instructor_id = i.id
    WHERE i.email = ?
    GROUP BY s.id, s.name, s.email, s.program_id
    ORDER BY s.name";

$stmt = $conn->prepare($sql_students);
$stmt->bind_param("s", $instructor_email);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Add debug information
if (empty($students)) {
    echo "Debug Information:<br>";
    echo "Instructor Email: " . htmlspecialchars($instructor_email) . "<br>";
    
    // Check if instructor exists
    $check_instructor = "SELECT id, name FROM instructors WHERE email = ?";
    $stmt = $conn->prepare($check_instructor);
    $stmt->bind_param("s", $instructor_email);
    $stmt->execute();
    $instructor_result = $stmt->get_result()->fetch_assoc();
    
    if ($instructor_result) {
        echo "Instructor found with ID: " . $instructor_result['id'] . "<br>";
        
        // Check courses for this instructor
        $check_courses = "SELECT id, title FROM courses WHERE instructor_id = ?";
        $stmt = $conn->prepare($check_courses);
        $stmt->bind_param("i", $instructor_result['id']);
        $stmt->execute();
        $courses_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($courses_result)) {
            echo "No courses found for this instructor<br>";
        } else {
            echo "Courses found:<br>";
            foreach ($courses_result as $course) {
                echo "- " . htmlspecialchars($course['title']) . "<br>";
                
                // Check students in each course
                $check_students = "SELECT id, name FROM students WHERE program_id = ?";
                $stmt = $conn->prepare($check_students);
                $stmt->bind_param("i", $course['program_id']);
                $stmt->execute();
                $students_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                if (empty($students_result)) {
                    echo "&nbsp;&nbsp;No students in this course<br>";
                } else {
                    echo "&nbsp;&nbsp;Students found: " . count($students_result) . "<br>";
                }
            }
        }
    } else {
        echo "No instructor found with this email<br>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Progress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Inherit your dashboard styles */
        .student-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .student-table th,
        .student-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .student-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
        }

        .student-table tr:hover {
            background-color: #f8f9fa;
        }

        .view-button {
            background-color: #4a90e2;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .view-button:hover {
            background-color: #357abd;
        }

        .progress-bar {
            width: 100%;
            background-color: #e9ecef;
            border-radius: 4px;
            height: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: #4a90e2;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }

        .search-box {
            margin-bottom: 20px;
            padding: 10px;
            width: 100%;
            max-width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <!-- Copy your sidebar code from instructor_dashboard.php -->
    </div>

    <div class="content">
        <h2>Student Progress Overview</h2>
        
        <div class="student-list">
            <input type="text" id="searchStudent" class="search-box" placeholder="Search students...">
            
            <table class="student-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Submissions</th>
                        <th>Average Grade</th>
                        <th>Progress</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['course']); ?></td>
                            <td><?php echo $student['total_submissions']; ?></td>
                            <td><?php echo $student['average_grade'] ?? 'N/A'; ?>%</td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $student['average_grade'] ?? 0; ?>%">
                                        <?php echo $student['average_grade'] ?? 0; ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="view_student_report.php?student_id=<?php echo $student['id']; ?>" 
                                   class="view-button">
                                    <i class="fas fa-chart-line"></i> View Report
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchStudent').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.getElementById('studentTableBody').getElementsByTagName('tr');

            for (let row of rows) {
                const name = row.cells[0].textContent.toLowerCase();
                const course = row.cells[1].textContent.toLowerCase();
                
                if (name.includes(searchValue) || course.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html> 