<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: student_login.php');
    exit;
}

$user_email = $_SESSION['email'];
$conn = connectDB();

// 1. Get Student Basic Info
$sql_student = "SELECT s.id, s.name, s.email, p.title as program_title 
                FROM students s 
                LEFT JOIN programs p ON s.program_id = p.program_id 
                WHERE s.email = ?";
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
$student_program = $student['program_title'] ?? 'Not Assigned';
$stmt->close();

// 2. Overall Performance Statistics
$sql_overall = "
    SELECT 
        COUNT(DISTINCT a.id) as total_assignments,
        ROUND(AVG(CASE WHEN s.grade IS NOT NULL THEN s.grade ELSE 0 END), 2) as overall_average,
        COUNT(DISTINCT s.id) as completed_assignments,
        COUNT(DISTINCT CASE 
            WHEN s.submission_date > a.deadline THEN s.id 
        END) as late_submissions,
        COUNT(DISTINCT CASE 
            WHEN s.submission_date <= a.deadline THEN s.id 
        END) as ontime_submissions
    FROM students st
    LEFT JOIN submissions s ON st.id = s.student_id
    LEFT JOIN assignments a ON s.assignment_id = a.id
    WHERE st.id = ?";
$stmt = $conn->prepare($sql_overall);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$overall_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3. Course Performance (based on student's course)
$sql_courses = "
    SELECT 
        c.id,
        c.title as course_name,
        COUNT(DISTINCT a.id) as total_assignments,
        COUNT(DISTINCT s.id) as completed_assignments,
        ROUND(AVG(CASE WHEN s.grade IS NOT NULL THEN s.grade ELSE 0 END), 2) as average_grade
    FROM courses c
    LEFT JOIN assignments a ON c.instructor_id = a.instructor_id
    LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
    WHERE c.title = ?
    GROUP BY c.id, c.title";
$stmt = $conn->prepare($sql_courses);
$stmt->bind_param("is", $student_id, $student['course']);
$stmt->execute();
$courses_result = $stmt->get_result();
$courses_performance = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses_performance[] = $row;
}
$stmt->close();

// 4. Recent Assignment Submissions
$sql_recent = "
    SELECT 
        a.title as assignment_title,
        c.title as course_title,
        s.submission_date,
        COALESCE(s.grade, 'Not graded') as grade,
        COALESCE(s.feedback, '') as feedback,
        a.deadline,
        CASE 
            WHEN s.submission_date > a.deadline THEN 'Late'
            WHEN s.submission_date <= a.deadline THEN 'On Time'
            ELSE 'Not Submitted'
        END as submission_status
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.student_id = ?
    ORDER BY s.submission_date DESC
    LIMIT 10";
$stmt = $conn->prepare($sql_recent);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$recent_submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 5. Assignment Statistics by Type
$sql_assignment_stats = "
    SELECT 
        a.type as assignment_type,
        COUNT(DISTINCT a.id) as total_assignments,
        COUNT(DISTINCT s.id) as completed_assignments,
        ROUND(AVG(CASE WHEN s.grade IS NOT NULL THEN s.grade ELSE 0 END), 2) as average_grade
    FROM assignments a
    LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
    GROUP BY a.type";
$stmt = $conn->prepare($sql_assignment_stats);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$assignment_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 6. Monthly Performance Trend
$sql_monthly = "
    SELECT 
        DATE_FORMAT(s.submission_date, '%Y-%m') as month,
        ROUND(AVG(s.grade), 2) as average_grade,
        COUNT(DISTINCT s.id) as submissions
    FROM submissions s
    WHERE s.student_id = ?
    GROUP BY DATE_FORMAT(s.submission_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6";
$stmt = $conn->prepare($sql_monthly);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$monthly_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 7. Quiz Performance
$sql_quiz = "
    SELECT 
        q.title as quiz_title,
        qa.score,
        qa.total_questions,
        qa.attempt_date,
        ROUND((qa.score / qa.total_questions) * 100, 2) as percentage,
        q.description
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    WHERE qa.student_id = ?
    ORDER BY qa.attempt_date DESC";
$stmt = $conn->prepare($sql_quiz);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$quiz_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate quiz statistics
$total_quizzes = count($quiz_results);
$average_quiz_score = 0;
if ($total_quizzes > 0) {
    $total_percentage = array_sum(array_column($quiz_results, 'percentage'));
    $average_quiz_score = round($total_percentage / $total_quizzes, 2);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Report - <?php echo htmlspecialchars($student_name); ?></title>
    <link rel="shortcut icon" href="assets/image/LCLOGO.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .report-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #008751;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #008751;
        }

        .submissions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .submissions-table th, 
        .submissions-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .submissions-table th {
            background-color: #f8f9fa;
            color: #008751;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
        }

        .status-ontime {
            background-color: #d4edda;
            color: #155724;
        }

        .status-late {
            background-color: #f8d7da;
            color: #721c24;
        }

        .chart-container {
            height: 300px;
            margin-top: 20px;
        }

        .print-button {
            background-color: #008751;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            float: right;
        }

        @media print {
            .sidebar, .print-button {
                display: none;
            }
            .report-container {
                margin: 0;
                padding: 0;
            }
        }

        .progress-bar-container {
            width: 100%;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar {
            background-color: #008751;
            color: white;
            text-align: center;
            padding: 4px;
            min-width: 30px;
            font-size: 0.85em;
        }

        .info-button {
            background: none;
            border: none;
            color: #008751;
            cursor: pointer;
            padding: 5px;
            font-size: 1.2em;
        }

        .info-button:hover {
            color: #006c41;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 12px;
            width: 80%;
            max-width: 600px;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }

        .no-data-message {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }

        @media screen and (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .report-section p {
            margin: 8px 0;
            color: var(--text-secondary);
        }

        .report-section p i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        .report-section h1 {
            color: var(--text-primary);
            margin-bottom: 15px;
        }

        .submissions-table td:nth-child(2) { /* Course column */
            color: #008751;
            font-weight: 500;
        }
    </style>
</head>
<body>
   
    
    <div class="report-container">
        <button onclick="window.print()" class="print-button">
            <i class="fas fa-print"></i> Print Report
        </button>

        <div class="report-section">
            <h1>Academic Report for <?php echo htmlspecialchars($student_name); ?></h1>
            <p><i class="fas fa-graduation-cap"></i> Program: <?php echo htmlspecialchars($student_program); ?></p>
            <p><i class="fas fa-calendar"></i> Report Generated: <?php echo date('F j, Y'); ?></p>
        </div>

        <!-- Overall Performance Section -->
        <div class="report-section">
            <h2><i class="fas fa-chart-line"></i> Overall Performance</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Overall Average</h3>
                    <div class="stat-value"><?php echo $overall_stats['overall_average']; ?>%</div>
                </div>
                <div class="stat-card">
                    <h3>Assignments Completed</h3>
                    <div class="stat-value"><?php echo $overall_stats['completed_assignments']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>On-time Submissions</h3>
                    <div class="stat-value"><?php echo $overall_stats['ontime_submissions']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Late Submissions</h3>
                    <div class="stat-value"><?php echo $overall_stats['late_submissions']; ?></div>
                </div>
            </div>
        </div>

        <!-- Recent Submissions Section -->
        <div class="report-section">
            <h2><i class="fas fa-history"></i> Recent Submissions</h2>
            <table class="submissions-table">
                <thead>
                    <tr>
                        <th>Assignment</th>
                        <th>Course</th>
                        <th>Submission Date</th>
                        <th>Status</th>
                        <th>Grade</th>
                        <th>Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_submissions as $submission): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($submission['assignment_title']); ?></td>
                        <td><?php echo htmlspecialchars($submission['course_title']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($submission['submission_date'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo strtolower($submission['submission_status']) === 'on time' ? 'status-ontime' : 'status-late'; ?>">
                                <?php echo $submission['submission_status']; ?>
                            </span>
                        </td>
                        <td><?php echo isset($submission['grade']) ? $submission['grade'] . '%' : 'Not graded'; ?></td>
                        <td><?php echo isset($submission['feedback']) && $submission['feedback'] !== null ? 
                            htmlspecialchars($submission['feedback']) : 
                            '<em>No feedback provided</em>'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Assignment Types Performance -->
        <div class="report-section">
            <h2><i class="fas fa-tasks"></i> Performance by Assignment Type</h2>
            <table class="submissions-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Total Assignments</th>
                        <th>Completed</th>
                        <th>Average Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignment_stats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['assignment_type']); ?></td>
                        <td><?php echo $stat['total_assignments']; ?></td>
                        <td><?php echo $stat['completed_assignments']; ?></td>
                        <td><?php echo $stat['average_grade']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Monthly Performance Trend -->
        <div class="report-section">
            <h2><i class="fas fa-chart-bar"></i> Monthly Performance Trend</h2>
            <div class="chart-container">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <!-- Course Performance -->
        <div class="report-section">
            <h2><i class="fas fa-graduation-cap"></i> Course Performance</h2>
            <?php foreach ($courses_performance as $course): ?>
            <div class="course-performance">
                <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h4>Average Grade</h4>
                        <div class="stat-value"><?php echo $course['average_grade']; ?>%</div>
                    </div>
                    <div class="stat-card">
                        <h4>Assignments</h4>
                        <div class="stat-value"><?php echo $course['completed_assignments']; ?>/<?php echo $course['total_assignments']; ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Quiz Performance Section -->
        <div class="report-section">
            <h2><i class="fas fa-question-circle"></i> Quiz Performance</h2>
            
            <!-- Quiz Overview Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Quizzes Taken</h3>
                    <div class="stat-value"><?php echo $total_quizzes; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Average Quiz Score</h3>
                    <div class="stat-value"><?php echo $average_quiz_score; ?>%</div>
                </div>
            </div>

            <!-- Quiz Details Table -->
            <?php if (!empty($quiz_results)): ?>
                <div class="table-responsive">
                    <table class="submissions-table">
                        <thead>
                            <tr>
                                <th>Quiz Title</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Date Taken</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quiz_results as $quiz): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($quiz['quiz_title']); ?></td>
                                    <td><?php echo $quiz['score']; ?>/<?php echo $quiz['total_questions']; ?></td>
                                    <td>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar" style="width: <?php echo $quiz['percentage']; ?>%;">
                                                <?php echo $quiz['percentage']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo date('M j, Y, g:i a', strtotime($quiz['attempt_date'])); ?></td>
                                    <td>
                                        <button class="info-button" onclick="showQuizDetails(this)" 
                                                data-title="<?php echo htmlspecialchars($quiz['quiz_title']); ?>"
                                                data-description="<?php echo htmlspecialchars($quiz['description']); ?>"
                                                data-score="<?php echo $quiz['score']; ?>"
                                                data-total="<?php echo $quiz['total_questions']; ?>"
                                                data-percentage="<?php echo $quiz['percentage']; ?>"
                                                data-date="<?php echo date('F j, Y, g:i a', strtotime($quiz['attempt_date'])); ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data-message">
                    <p>No quizzes have been taken yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quiz Details Modal -->
        <div id="quizDetailsModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modalQuizTitle"></h2>
                <div id="modalQuizContent"></div>
            </div>
        </div>
    </div>

    <script>
        // Monthly Trend Chart
        const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_trend); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month),
                datasets: [{
                    label: 'Average Grade',
                    data: monthlyData.map(item => item.average_grade),
                    borderColor: '#008751',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Monthly Grade Progression'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Grade (%)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                }
            }
        });

        // Quiz Details Modal functionality
        const modal = document.getElementById("quizDetailsModal");
        const span = document.getElementsByClassName("close")[0];

        function showQuizDetails(button) {
            const data = button.dataset;
            document.getElementById("modalQuizTitle").textContent = data.title;
            
            const content = `
                <div class="quiz-details">
                    <p><strong>Description:</strong> ${data.description}</p>
                    <p><strong>Score:</strong> ${data.score}/${data.total}</p>
                    <p><strong>Percentage:</strong> ${data.percentage}%</p>
                    <p><strong>Date Taken:</strong> ${data.date}</p>
                </div>
            `;
            
            document.getElementById("modalQuizContent").innerHTML = content;
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html> 