<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: student_login.php');
    exit;
}

$student_id = $_SESSION['user_id']; // Assuming student's ID is stored in the session
$conn = connectDB();

// Fetch all assignments from the database, including the document path
$sql_assignments = "SELECT a.id, a.title, a.description, a.type, a.deadline, a.document_path, i.name AS instructor_name 
                    FROM assignments a
                    JOIN instructors i ON a.instructor_id = i.id
                    ORDER BY a.deadline ASC";

$result_assignments = $conn->query($sql_assignments);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            line-height: 1.5;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary-color), #4f46e5);
            color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            opacity: 0.9;
        }

        .assignments-grid {
            display: grid;
            gap: 1.5rem;
        }

        .assignment-card {
            background: var(--card-background);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            transition: transform 0.2s;
        }

        .assignment-card:hover {
            transform: translateY(-2px);
        }

        .assignment-header {
            background: linear-gradient(to right, var(--primary-color), #4f46e5);
            padding: 1.5rem;
            color: white;
            position: relative;
        }

        .assignment-type {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.875rem;
        }

        .assignment-body {
            padding: 1.5rem;
        }

        .instructor-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }

        .deadline-info {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .time-remaining {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .time-remaining.urgent {
            color: var(--danger-color);
        }

        .time-remaining.warning {
            color: var(--warning-color);
        }

        .time-remaining.safe {
            color: var(--success-color);
        }

        .file-section {
            margin: 1.5rem 0;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background: #eff6ff;
            transition: background 0.2s;
        }

        .download-btn:hover {
            background: #dbeafe;
        }

        .upload-section {
            margin-top: 1.5rem;
        }

        .file-drop-area {
            border: 2px dashed var(--border-color);
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s;
            margin-bottom: 1rem;
        }

        .file-drop-area:hover {
            border-color: var(--primary-color);
        }

        .file-input {
            display: none;
        }

        .submit-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
            justify-content: center;
        }

        .submit-btn:hover {
            background: var(--primary-hover);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: var(--card-background);
            border-radius: 12px;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
                margin-bottom: 2rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }
        }
        .submit-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s, opacity 0.2s;
            width: 100%;
            justify-content: center;
        }

        .submit-btn:hover:not(:disabled) {
            background: var(--primary-hover);
        }

        .submit-btn:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .file-drop-area.disabled {
            border-color: var(--text-secondary);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .file-drop-area.disabled:hover {
            border-color: var(--text-secondary);
        }

        .deadline-passed-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>Assignments</h1>
            <p>View and submit your course assignments</p>
        </div>

        <div class="assignments-grid">
            <?php if ($result_assignments->num_rows > 0): ?>
                <?php while ($assignment = $result_assignments->fetch_assoc()): ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <h2><?php echo htmlspecialchars($assignment['title']); ?></h2>
                            <span class="assignment-type">
                                <?php echo ucfirst(htmlspecialchars($assignment['type'])); ?>
                            </span>
                        </div>

                        <div class="assignment-body">
                            <div class="instructor-info">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span><?php echo htmlspecialchars($assignment['instructor_name']); ?></span>
                            </div>

                            <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>

                            <div class="deadline-info">
                                <div class="time-remaining" id="remaining-<?php echo $assignment['id']; ?>">
                                    <i class="fas fa-clock"></i>
                                    <span>Calculating time remaining...</span>
                                </div>
                            </div>

                            <div class="file-section">
                                <?php if (!empty($assignment['document_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($assignment['document_path']); ?>" class="download-btn" download>
                                        <i class="fas fa-download"></i>
                                        Download Assignment
                                    </a>
                                <?php else: ?>
                                    <p class="text-secondary">
                                        <i class="fas fa-info-circle"></i>
                                        No document attached to this assignment
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div id="deadline-message-<?php echo $assignment['id']; ?>" style="display: none;" class="deadline-passed-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>Deadline has passed. Submissions are no longer accepted.</span>
                            </div>

                            <form action="submit_assignment.php" method="POST" enctype="multipart/form-data" class="upload-section" id="form-<?php echo $assignment['id']; ?>">
                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                
                                <div class="file-drop-area" id="drop-area-<?php echo $assignment['id']; ?>" onclick="document.getElementById('submission_<?php echo $assignment['id']; ?>').click()">
                                    <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                    <p>Drop your file here or click to browse</p>
                                    <span class="selected-file" id="selected-file-<?php echo $assignment['id']; ?>"></span>
                                </div>
                                
                                <input type="file" 
                                       id="submission_<?php echo $assignment['id']; ?>" 
                                       name="submission_file" 
                                       class="file-input"
                                       required>
                                
                                <button type="submit" class="submit-btn" id="submit-btn-<?php echo $assignment['id']; ?>">
                                    <i class="fas fa-paper-plane"></i>
                                    Submit Assignment
                                </button>
                            </form>
                        </div>
                    </div>

                    <script>
                        (function() {
                            const deadline = '<?php echo $assignment['deadline']; ?>';
                            const elementId = 'remaining-<?php echo $assignment['id']; ?>';
                            const fileInput = document.getElementById('submission_<?php echo $assignment['id']; ?>');
                            const fileLabel = document.getElementById('selected-file-<?php echo $assignment['id']; ?>');
                            const submitBtn = document.getElementById('submit-btn-<?php echo $assignment['id']; ?>');
                            const form = document.getElementById('form-<?php echo $assignment['id']; ?>');
                            const dropArea = document.getElementById('drop-area-<?php echo $assignment['id']; ?>');
                            const deadlineMessage = document.getElementById('deadline-message-<?php echo $assignment['id']; ?>');

                            fileInput.addEventListener('change', function(e) {
                                const fileName = e.target.files[0]?.name;
                                if (fileName) {
                                    fileLabel.textContent = `Selected: ${fileName}`;
                                }
                            });

                            function checkDeadline() {
                                const deadlineDate = new Date(deadline).getTime();
                                const now = new Date().getTime();
                                return deadlineDate < now;
                            }

                            function disableSubmission() {
                                submitBtn.disabled = true;
                                fileInput.disabled = true;
                                dropArea.classList.add('disabled');
                                deadlineMessage.style.display = 'flex';
                                
                                // Prevent form submission
                                form.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    return false;
                                });
                            }

                            function calculateTimeRemaining(deadline, elementId) {
                                const deadlineDate = new Date(deadline).getTime();
                                const now = new Date().getTime();
                                const timeRemaining = deadlineDate - now;

                                const element = document.getElementById(elementId);

                                if (timeRemaining > 0) {
                                    const days = Math.floor(timeRemaining / (1000 * 60 * 60 * 24));
                                    const hours = Math.floor((timeRemaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                    const minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
                                    const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);

                                    element.className = 'time-remaining ' + 
                                        (days === 0 && hours < 12 ? 'urgent' : 
                                         days === 0 ? 'warning' : 'safe');

                                    element.innerHTML = `
                                        <i class="fas fa-clock"></i>
                                        <span>${days}d ${hours}h ${minutes}m ${seconds}s remaining</span>
                                    `;
                                } else {
                                    element.className = 'time-remaining urgent';
                                    element.innerHTML = `
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>Deadline passed!</span>
                                    `;
                                    disableSubmission();
                                }
                            }

                            // Check deadline immediately
                            if (checkDeadline()) {
                                disableSubmission();
                            }

                            calculateTimeRemaining(deadline, elementId);
                            setInterval(() => calculateTimeRemaining(deadline, elementId), 1000);
                        })();
                    </script>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tasks fa-3x"></i>
                    <h3>No Assignments Yet</h3>
                    <p>There are no assignments available at this time. Check back later!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>