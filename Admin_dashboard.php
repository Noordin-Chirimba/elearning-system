<?php
session_start();
require 'config.php';

// Ensure user is logged in as admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Connect to the database
$conn = connectDB();

// Fetch the count of pending instructors
$query = "SELECT COUNT(*) AS pending_count FROM instructors WHERE admin_approved = 0";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$pendingCount = $row['pending_count'];

// Additional database queries for dashboard statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM students ) as total_students,
    (SELECT COUNT(*) FROM instructors) as total_instructors,
    (SELECT COUNT(*) FROM courses) as total_courses,
    (SELECT COUNT(*) FROM programs) as total_programs,
    (SELECT COUNT(*) FROM payments) as total_payments,
    (SELECT COUNT(*) FROM enrollments WHERE status = 'active') as active_enrollments";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Recent activities query (last 5)
$activities_query = "SELECT * FROM (
    SELECT 'New Student' as type, created_at, name FROM students
    UNION ALL
    SELECT 'New Payment' as type, created_at, amount FROM payments
    UNION ALL
    SELECT 'New Instructor' as type, created_at, name FROM instructors
) AS activities ORDER BY created_at DESC LIMIT 5";
$activities_result = $conn->query($activities_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Learning System</title>
    <link rel="shortcut icon" href="assets/image/hill.png" type="image/x-icon" />
    <link rel="apple-touch-icon" href="assets/image/hill.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f2f2f2;
        }
        .sidebar {
            position: fixed;
            left: 0;
            width: 250px;
            height: 100%;
            background-color: #333;
            color: #fff;
            padding-top: 20px;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #fff;
        }
        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .sidebar ul li {
            font-size: 18px;
        }
        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 15px;
            color: #fff;
            text-decoration: none;
            transition: background-color 0.3s ease, padding-left 0.3s ease;
        }
        .sidebar ul li a i {
            margin-right: 10px;
        }
        .sidebar ul li a:hover {
            background-color: #444;
            padding-left: 25px;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .content h2 {
            margin-bottom: 20px;
        }
        .badge {
            background-color: red;
            color: white;
            border-radius: 15px;
            padding: 5px 10px;
            margin-left: 10px;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card i {
            font-size: 2em;
            color: #333;
            margin-bottom: 10px;
        }
        .stat-card h3 {
            margin: 10px 0;
            font-size: 1.1em;
            color: #666;
        }
        .stat-card p {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        .quick-actions {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background: #333;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s;
        }
        .action-btn:hover {
            background: #444;
        }
        .action-btn i {
            margin-right: 8px;
        }
        .recent-activities {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .activity-item i {
            margin-right: 10px;
            color: #333;
        }
        .activity-item span {
            margin-right: 15px;
        }
        .activity-item span:last-child {
            margin-left: auto;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Dashboard</h2>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="approve_instructors.php"><i class="fas fa-users"></i> Instructors
                <span class="badge"><?php echo $pendingCount; ?></span></a>
            </li>
            <li><a href="users.php"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="courses.php"><i class="fas fa-book"></i> Courses</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="manage_programs.php"><i class="fas fa-graduation-cap"></i> Manage Programs</a></li>
            <li><a href="manage_payments.php"><i class="fas fa-dollar-sign"></i> Manage Payments</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <h2>Welcome to the Admin Dashboard</h2>
        
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-user-graduate"></i>
                <h3>Total Students</h3>
                <p><?php echo $stats['total_students']; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3>Total Instructors</h3>
                <p><?php echo $stats['total_instructors']; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-book"></i>
                <h3>Total Courses</h3>
                <p><?php echo $stats['total_courses']; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-graduation-cap"></i>
                <h3>Total Programs</h3>
                <p><?php echo $stats['total_programs']; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-dollar-sign"></i>
                <h3>Total Payments</h3>
                <p><?php echo $stats['total_payments']; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-graduate"></i>
                <h3>Active Enrollments</h3>
                <p><?php echo $stats['active_enrollments']; ?></p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-buttons">
                <a href="add_program.php" class="action-btn">
                    <i class="fas fa-plus"></i> Add Program
                </a>
                <a href="add_course.php" class="action-btn">
                    <i class="fas fa-plus"></i> Add Course
                </a>
                <a href="approve_instructors.php" class="action-btn">
                    <i class="fas fa-check"></i> Approve Instructors
                </a>
                <a href="manage_payments.php" class="action-btn">
                    <i class="fas fa-money-bill"></i> Process Payments
                </a>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="recent-activities">
            <h3>Recent Activities</h3>
            <div class="activity-list">
                <?php while($activity = $activities_result->fetch_assoc()): ?>
                <div class="activity-item">
                    <i class="fas fa-bell"></i>
                    <span><?php echo $activity['type']; ?></span>
                    <span><?php echo date('M d, Y', strtotime($activity['created_at'])); ?></span>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>
