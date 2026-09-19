<?php
session_start();
require 'config.php'; // Include the database connection

if (!isset($_SESSION['loggedin'])) {
    header("Location: student_login.php");
    exit();
}

$conn = connectDB();
$quiz_id = $_GET['quiz_id'];

// Fetch quiz and its questions
$sql_quiz = "SELECT * FROM quizzes WHERE id = ?";
$stmt = $conn->prepare($sql_quiz);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

$sql_questions = "SELECT * FROM questions WHERE quiz_id = ?";
$stmt = $conn->prepare($sql_questions);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$questions = $stmt->get_result();

// Add this near the top after database connection
$student_id = $_SESSION['user_id'];

// Check if student has already submitted this quiz
$sql_check = "SELECT * FROM quiz_attempts WHERE student_id = ? AND quiz_id = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("ii", $student_id, $quiz_id);
$stmt->execute();
$previous_attempt = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 40px 20px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        div {
            margin-bottom: 15px;
        }
        p {
            margin: 15px 0;
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 500;
        }
        label {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
            margin: 8px 0;
            cursor: pointer;
        }
        input[type="radio"] {
            margin-right: 10px;
        }
        label:hover {
            background-color: #e0e0e0;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            margin-top: 20px;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: #0056b3;
        }
        #result {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 12px;
            font-weight: 500;
        }
        .popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .popup-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .correct-answer {
            background-color: #d4edda !important;
            border: 1px solid #c3e6cb;
        }

        .incorrect-answer {
            background-color: #f8d7da !important;
            border: 1px solid #f5c6cb;
        }

        .secondary-button {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            margin: 10px;
            transition: background-color 0.2s;
        }

        .secondary-button:hover {
            background-color: #5a6268;
        }

        #scoreDisplay {
            font-size: 1.2rem;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .warning-text {
            color: #dc3545;
            font-weight: 500;
            margin: 15px 0;
        }

        .quiz-submitted-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            text-align: center;
            font-weight: 500;
        }

        .primary-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            margin: 10px;
            transition: background-color 0.2s;
        }

        .primary-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
    <form id="quizForm">
        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
        <?php $i = 1; while ($question = $questions->fetch_assoc()): ?>
            <div>
                <p><?php echo $i . ". " . htmlspecialchars($question['question_text']); ?></p>
                <?php
                // Fetch the four choices for each question
                $sql_answers = "SELECT * FROM answers WHERE question_id = ?";
                $stmt = $conn->prepare($sql_answers);
                $stmt->bind_param("i", $question['id']);
                $stmt->execute();
                $answers = $stmt->get_result();
                $choices = ['a', 'b', 'c', 'd'];
                $j = 0;
                while ($answer = $answers->fetch_assoc()):
                ?>
                    <label>
                        <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="<?php echo $choices[$j]; ?>" required>
                        <?php echo strtoupper($choices[$j]) . ". " . htmlspecialchars($answer['answer_text']); ?>
                    </label>
                <?php $j++; endwhile; ?>
            </div>
        <?php $i++; endwhile; ?>
        <?php if ($previous_attempt): ?>
            <div class="quiz-submitted-message">
                You have already submitted this quiz. Your score was: <?php echo $previous_attempt['score']; ?>/<?php echo $previous_attempt['total_questions']; ?>
            </div>
            <button type="button" id="showAnswersBtn" class="secondary-button">Show Correct Answers</button>
        <?php else: ?>
            <button type="submit">Submit Quiz</button>
        <?php endif; ?>
    </form>
    <div id="resultPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <h2>Quiz Results</h2>
            <div id="scoreDisplay"></div>
            <button id="showAnswersBtn" class="secondary-button">Show Correct Answers</button>
            <button id="closePopup" class="secondary-button">Close</button>
        </div>
    </div>
    <div id="confirmSubmitPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <h2>Confirm Submission</h2>
            <p>Are you sure you want to submit this quiz?</p>
            <p class="warning-text">You will only get one chance to take this quiz!</p>
            <button id="confirmSubmit" class="primary-button">Yes, Submit Quiz</button>
            <button id="cancelSubmit" class="secondary-button">Cancel</button>
        </div>
    </div>

    <script>
        const quizForm = document.getElementById('quizForm');
        const confirmSubmitPopup = document.getElementById('confirmSubmitPopup');
        
        <?php if (!$previous_attempt): ?>
        quizForm.addEventListener('submit', function(e) {
            e.preventDefault();
            confirmSubmitPopup.style.display = 'flex';
        });

        // Handle confirm submission
        document.getElementById('confirmSubmit').addEventListener('click', function() {
            const formData = new FormData(quizForm);
            
            fetch('submit_quiz.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                confirmSubmitPopup.style.display = 'none';
                
                // Show results popup
                const popup = document.getElementById('resultPopup');
                const scoreDisplay = document.getElementById('scoreDisplay');
                popup.style.display = 'flex';
                scoreDisplay.innerHTML = `Your score: ${data.score}/${data.total}`;

                // Replace submit button with message
                const submitButton = quizForm.querySelector('button[type="submit"]');
                const messageDiv = document.createElement('div');
                messageDiv.className = 'quiz-submitted-message';
                messageDiv.textContent = 'Quiz has been submitted';
                submitButton.parentNode.replaceChild(messageDiv, submitButton);

                // Disable the form
                quizForm.style.pointerEvents = 'none';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the quiz. Please try again.');
            });
        });

        // Handle cancel submission
        document.getElementById('cancelSubmit').addEventListener('click', function() {
            confirmSubmitPopup.style.display = 'none';
        });
        <?php endif; ?>

        // Close result popup when clicking the close button
        document.getElementById('closePopup').addEventListener('click', function() {
            document.getElementById('resultPopup').style.display = 'none';
        });

        // Show correct answers functionality
        document.getElementById('showAnswersBtn').addEventListener('click', function() {
            const quiz_id = <?php echo $quiz_id; ?>;
            
            fetch(`get_answers.php?quiz_id=${quiz_id}`)
                .then(response => response.json())
                .then(answers => {
                    if (document.getElementById('resultPopup')) {
                        document.getElementById('resultPopup').style.display = 'none';
                    }
                    
                    // Highlight correct and incorrect answers
                    answers.forEach(answer => {
                        const questionDiv = document.querySelector(`input[name="answers[${answer.question_id}]"]`).closest('div');
                        const labels = questionDiv.querySelectorAll('label');
                        const selectedAnswer = document.querySelector(`input[name="answers[${answer.question_id}]"]:checked`);
                        
                        labels.forEach(label => {
                            const input = label.querySelector('input');
                            const choiceLabel = input.value;
                            
                            if (choiceLabel === answer.correct_answer) {
                                label.classList.add('correct-answer');
                            } else if (selectedAnswer && selectedAnswer.value === choiceLabel) {
                                label.classList.add('incorrect-answer');
                            }
                        });
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading correct answers');
                });
        });
    </script>
</body>
</html>