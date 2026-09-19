<?php
session_start();
require 'config.php';

// Check if instructor is logged in
if (!isset($_SESSION['instructor_loggedin'])) {
    header('Location: instructor_login.php');
    exit;
}

$quiz_id = $_GET['quiz_id']; // Quiz ID from query parameter
$conn = connectDB();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = $_POST['question_text'];
    $question_type = $_POST['question_type'];

    // Insert the question
    $stmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, question_type, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $quiz_id, $question_text, $question_type);
    if ($stmt->execute()) {
        $question_id = $stmt->insert_id;

        // If it's multiple choice, insert the options
        if ($question_type == 'multiple_choice') {
            foreach ($_POST['choices'] as $choice_text) {
                $is_correct = $_POST['correct_choice'] == $choice_text ? 1 : 0;
                $stmt_choice = $conn->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");
                $stmt_choice->bind_param("isi", $question_id, $choice_text, $is_correct);
                $stmt_choice->execute();
                $stmt_choice->close();
            }
        }
    }
    header("Location: add_questions.php?quiz_id=$quiz_id");
    exit;
}
?>

<!-- Form for adding questions -->
<form action="add_questions.php?quiz_id=<?php echo $quiz_id; ?>" method="POST">
    <label for="question_text">Question:</label>
    <textarea id="question_text" name="question_text" required></textarea>

    <label for="question_type">Type:</label>
    <select id="question_type" name="question_type">
        <option value="multiple_choice">Multiple Choice</option>
        <option value="structured">Structured</option>
    </select>

    <!-- Multiple Choice Options (shown conditionally) -->
    <div id="multiple_choice_options" style="display:none;">
        <label for="choices">Choices:</label>
        <input type="text" name="choices[]" placeholder="Choice 1">
        <input type="text" name="choices[]" placeholder="Choice 2">
        <input type="text" name="choices[]" placeholder="Choice 3">
        <input type="text" name="choices[]" placeholder="Choice 4">
        <label for="correct_choice">Correct Choice:</label>
        <input type="text" name="correct_choice" placeholder="Correct choice">
    </div>

    <input type="submit" value="Add Question">
</form>

<script>
// Show or hide the multiple-choice options based on the selected question type
document.getElementById('question_type').addEventListener('change', function() {
    if (this.value === 'multiple_choice') {
        document.getElementById('multiple_choice_options').style.display = 'block';
    } else {
        document.getElementById('multiple_choice_options').style.display = 'none';
    }
});
</script>
