<?php
session_start();
require 'config.php'; // Include your database connection file
require 'vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Connect to the database
$conn = connectDB();

// Handle instructor approval
if (isset($_GET['approve_id'])) {
    $instructorId = $_GET['approve_id'];
    
    // Approve instructor in the database
    $stmt = $conn->prepare("UPDATE instructors SET admin_approved = 1 WHERE id = ?");
    $stmt->bind_param("i", $instructorId);
    if ($stmt->execute()) {
        // Fetch instructor details for the email
        $stmt = $conn->prepare("SELECT name, email FROM instructors WHERE id = ?");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $stmt->bind_result($name, $email);
        $stmt->fetch();
        
        // Send welcome email
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ncnoord1nchir1mba@gmail.com'; // SMTP username
            $mail->Password = 'bqnc nsse iuof mfhy '; // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('admin@gmail.com', 'Admin'); // Replace with your email and name
            $mail->addAddress($email, $name); // Recipient's email and name

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Welcome to Hillview E-learning system';
            $mail->Body = "Dear $name,<br><br>Your application to become an instructor has been approved you can now go ahead and log in. Welcome to our E-Learning System!<br><br>Best regards,<br>E-Learning System Team";

            $mail->send();
            $message = "Instructor approved successfully and email sent.";
        } catch (Exception $e) {
            $message = "Instructor approved, but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

    } else {
        $message = "Error approving instructor.";
    }
    $stmt->close();
}

// Fetch pending instructors
$query = "SELECT id, name, email, id_number, specialization FROM instructors WHERE admin_approved = 0";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Approve Instructors</title>
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
        .message {
            margin: 10px 0;
            color: green;
            font-weight: bold;
        }
        .pending-list {
            margin-top: 20px;
        }
        .pending-list h3 {
            margin-bottom: 15px;
        }
        .pending-list table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ddd;
        }
        .pending-list th, .pending-list td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .pending-list th {
            background-color: #f8f9fa;
        }
        .approve-btn {
            color: white;
            background-color: green;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
            text-decoration: none;
        }
        .approve-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Dashboard</h2>
        <ul>
            <li><a href="#"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="#"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="#"><i class="fas fa-book"></i> Courses</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="#"><i class="fas fa-dollar-sign"></i> Manage payments</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <h2>Approve Instructors</h2>
        <?php if (isset($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <div class="pending-list">
            <h3>Pending Instructors</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Id number</th>
                        <th>Specialization</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                            <td>
                                <a href="approve_instructors.php?approve_id=<?php echo $row['id']; ?>" class="approve-btn">Approve</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
