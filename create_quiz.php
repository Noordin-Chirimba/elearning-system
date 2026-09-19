<?php
require 'config.php'; // Include the database connection

session_start();
if (!isset($_SESSION['instructor_loggedin'])) {
    header("Location: instructor_login.php");
    exit();
}

$conn = connectDB();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quiz_title = $_POST['title'];
    $quiz_desc = $_POST['description'];
    $instructor_id = $_SESSION['instructor_id'];

    // Insert quiz into database
    $sql = "INSERT INTO quizzes (title, description, instructor_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $quiz_title, $quiz_desc, $instructor_id);
    $stmt->execute();
    $quiz_id = $conn->insert_id;

    // Loop through submitted questions
    foreach ($_POST['questions'] as $index => $question) {
        $question_text = $question['text'];
        $correct_answer = $question['correct_answer'];

        // Insert question into database
        $sql_question = "INSERT INTO questions (quiz_id, question_text, question_type, correct_answer) VALUES (?, ?, 'multiple_choice', ?)";
        $stmt = $conn->prepare($sql_question);
        $stmt->bind_param("iss", $quiz_id, $question_text, $correct_answer);
        $stmt->execute();
        $question_id = $conn->insert_id;

        // Insert the four multiple choice answers
        foreach ($question['choices'] as $choice_index => $choice_text) {
            $sql_answer = "INSERT INTO answers (question_id, answer_text) VALUES (?, ?)";
            $stmt = $conn->prepare($sql_answer);
            $stmt->bind_param("is", $question_id, $choice_text);
            $stmt->execute();
        }
    }
    echo "<div class='alert alert-success'>Quiz created successfully!</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: .5rem;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            background-color: #28a745;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background-color: #218838;
        }
        .question {
            background-color: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert {
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">Create Quiz</h1>
        <form method="POST" action="create_quiz.php" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="title">Quiz Title:</label>
                <input type="text" class="form-control" name="title" id="title" required>
            </div>

            <div class="form-group">
                <label for="description">Quiz Description:</label>
                <textarea class="form-control" name="description" id="description" rows="3" required></textarea>
            </div>

            <div id="questions">
                <div class="question">
                    <h5>Question 1</h5>
                    <div class="form-group">
                        <label>Question Text:</label>
                        <input type="text" class="form-control" name="questions[0][text]" required>
                    </div>
                    <div class="form-group">
                        <label>Choices:</label>
                        <input type="text" class="form-control mb-2" name="questions[0][choices][a]" placeholder="Choice a" required>
                        <input type="text" class="form-control mb-2" name="questions[0][choices][b]" placeholder="Choice b" required>
                        <input type="text" class="form-control mb-2" name="questions[0][choices][c]" placeholder="Choice c" required>
                        <input type="text" class="form-control mb-2" name="questions[0][choices][d]" placeholder="Choice d" required>
                    </div>
                    <div class="form-group">
                        <label>Correct Answer:</label>
                        <select class="form-control" name="questions[0][correct_answer]" required>
                            <option value="">Select</option>
                            <option value="a">a</option>
                            <option value="b">b</option>
                            <option value="c">c</option>
                            <option value="d">d</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-secondary mb-3" onclick="addQuestion()">Add Another Question</button>
            <button type="submit" class="btn btn-primary">Create Quiz</button>
        </form>
    </div>

    <script>
        let questionIndex = 1;

        // Function to validate each question before adding a new one
        function addQuestion() {
            // Validate current question fields
            const currentQuestion = document.querySelector(`div.question:last-child`);
            if (!validateCurrentQuestion(currentQuestion)) {
                alert("Please fill out all fields in the current question before adding a new one.");
                return;
            }

            // Create a new question block if the current one is valid
            let questionDiv = document.createElement('div');
            questionDiv.className = 'question';
            questionDiv.innerHTML = `
                <h5>Question ${questionIndex + 1}</h5>
                <div class="form-group">
                    <label>Question Text:</label>
                    <input type="text" class="form-control" name="questions[${questionIndex}][text]" required>
                </div>
                <div class="form-group">
                    <label>Choices:</label>
                    <input type="text" class="form-control mb-2" name="questions[${questionIndex}][choices][a]" placeholder="Choice a" required>
                    <input type="text" class="form-control mb-2" name="questions[${questionIndex}][choices][b]" placeholder="Choice b" required>
                    <input type="text" class="form-control mb-2" name="questions[${questionIndex}][choices][c]" placeholder="Choice c" required>
                    <input type="text" class="form-control mb-2" name="questions[${questionIndex}][choices][d]" placeholder="Choice d" required>
                </div>
                <div class="form-group">
                    <label>Correct Answer:</label>
                    <select class="form-control" name="questions[${questionIndex}][correct_answer]" required>
                        <option value="">Select</option>
                        <option value="a">a</option>
                        <option value="b">b</option>
                        <option value="c">c</option>
                        <option value="d">d</option>
                    </select>
                </div>
            `;
            document.getElementById('questions').appendChild(questionDiv);
            questionIndex++;
        }

        // Validate the form before submission
        function validateForm() {
            let quizTitle = document.getElementById('title').value.trim();
            let quizDescription = document.getElementById('description').value.trim();

            if (!quizTitle || !quizDescription) {
                alert("Please fill out the quiz title and description.");
                return false;
            }

            let questions = document.querySelectorAll('.question');
            for (let i = 0; i < questions.length; i++) {
                if (!validateCurrentQuestion(questions[i])) {
                    return false;
                }
            }
            return true;
        }

        // Validate a single question block
        function validateCurrentQuestion(question) {
            let questionText = question.querySelector('input[name^="questions"]').value.trim();
            let choices = question.querySelectorAll('input[name*="[choices]"]');
            let correctAnswer = question.querySelector('select').value;

            if (!questionText) {
                alert(`Please fill out the text for the question.`);
                return false;
            }

            for (let j = 0; j < choices.length; j++) {
                if (!choices[j].value.trim()) {
                    alert(`Please fill out all choices for the question.`);
                    return false;
                }
            }

            if (!correctAnswer) {
                alert(`Please select the correct answer for the question.`);
                return false;
            }

            return true;
        }
    </script>
</body>
</html>
