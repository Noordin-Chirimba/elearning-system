<?php
session_start();
require 'config.php';
// Check if the instructor is logged in
if (!isset($_SESSION['instructor_loggedin']) || $_SESSION['instructor_loggedin'] !== true) {
    header('Location: instructor_login.php');
    exit;
}
// Get the logged-in instructor's ID
$instructor_id = $_SESSION['instructor_id'];
$conn = connectDB();
// Fetch all assignments created by the logged-in instructor
$sql_assignments = "SELECT id, title, description, type, deadline FROM assignments WHERE instructor_id = ?";
$stmt = $conn->prepare($sql_assignments);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Assignments</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 2rem;
        }
        .page-header {
            background-color: #fff;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .assignments-table {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        .grade-btn {
            text-decoration: none;
            padding: 0.375rem 0.75rem;
            border-radius: 4px;
            background-color: #0d6efd;
            color: white;
            transition: background-color 0.2s;
        }
        .grade-btn:hover {
            background-color: #0b5ed7;
            color: white;
        }
        .description-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .deadline-cell {
            white-space: nowrap;
        }
        .type-badge {
            padding: 0.35em 0.65em;
            border-radius: 4px;
            font-size: 0.875em;
            font-weight: 500;
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header text-center">
            <h2 class="display-6">Your Assignments</h2>
        </div>
        
        <div class="assignments-table">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Deadline</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($assignment = $result->fetch_assoc()) : ?>
                            <tr>
                                <td class="align-middle"><?php echo htmlspecialchars($assignment['title']); ?></td>
                                <td class="description-cell align-middle" title="<?php echo htmlspecialchars($assignment['description']); ?>">
                                    <?php echo htmlspecialchars($assignment['description']); ?>
                                </td>
                                <td class="align-middle">
                                    <span class="type-badge"><?php echo htmlspecialchars($assignment['type']); ?></span>
                                </td>
                                <td class="deadline-cell align-middle"><?php echo htmlspecialchars($assignment['deadline']); ?></td>
                                <td class="text-center align-middle">
                                    <a href="grade.php?assignment_id=<?php echo $assignment['id']; ?>" class="grade-btn">
                                        Grade Submissions
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    // Close the prepared statement and database connection
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>