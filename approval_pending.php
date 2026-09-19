<?php
session_start();
if (!isset($_SESSION['approval_pending'])) {
    header('Location: instructor_login.php');
    exit();
}
$approvalMessage = $_SESSION['approval_pending'];
unset($_SESSION['approval_pending']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Pending</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f0f0f0;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        .container {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .container h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .container p {
            font-size: 18px;
            color: #666;
        }

        .container .icon {
            font-size: 50px;
            color: #ffa500;
            margin-bottom: 20px;
        }

        .footer {
            margin-top: 20px;
            color: #888;
            font-size: 14px;
        }

        .footer a {
            color: #ffa500;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon"><i class="fas fa-clock"></i></div>
        <h1>Approval Pending</h1>
        <p><?php echo $approvalMessage; ?></p>
        <p>Please check your email for updates or contact support for more information.</p>
        <div class="footer">
            <p>&copy; 2024 Hillview International School. All rights reserved.</p>
            <p>Need help? <a href="contact.php">Contact Support</a></p>
        </div>
    </div>
</body>
</html>
