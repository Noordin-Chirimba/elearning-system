<?php
session_start();
require 'config.php'; // Include the database connection

// Check if student is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: student_login.php");
    exit();
}

$conn = connectDB();

// Fetch all quizzes
$sql_quizzes = "SELECT * FROM quizzes";
$result = $conn->query($sql_quizzes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Quizzes</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            margin-top: 50px;
            color: #333;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        li {
            margin: 15px 0;
        }

        a {
            display: block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            font-size: 18px;
        }

        a:hover {
            background-color: #45a049;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        @media (max-width: 600px) {
            a {
                font-size: 16px;
                padding: 12px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Available Quizzes</h1>
        <ul>
            <?php while ($quiz = $result->fetch_assoc()): ?>
                <li>
                    <a href="take_quiz.php?quiz_id=<?php echo $quiz['id']; ?>">
                        <?php echo htmlspecialchars($quiz['title']); ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
</body>
</html>
