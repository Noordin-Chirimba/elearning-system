<?php
session_start();
require 'config.php';

// Ensure user is logged in as admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$conn = connectDB();

// Handle Program Actions (Add/Edit/Delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = $conn->real_escape_string($_POST['title']);
                $description = $conn->real_escape_string($_POST['description']);
                $price = floatval($_POST['price']);
                $duration = intval($_POST['duration']);
                
                $sql = "INSERT INTO programs (title, description, price, duration) 
                        VALUES ('$title', '$description', $price, $duration)";
                $conn->query($sql);
                break;

            case 'edit':
                $id = intval($_POST['program_id']);
                $title = $conn->real_escape_string($_POST['title']);
                $description = $conn->real_escape_string($_POST['description']);
                $price = floatval($_POST['price']);
                $duration = intval($_POST['duration']);
                
                $sql = "UPDATE programs 
                        SET title='$title', description='$description', 
                            price=$price, duration=$duration 
                        WHERE program_id=$id";
                $conn->query($sql);
                break;

            case 'delete':
                $id = intval($_POST['program_id']);
                $sql = "DELETE FROM programs WHERE program_id=$id";
                $conn->query($sql);
                break;
        }
    }
}

// Fetch all programs
$sql = "SELECT * FROM programs ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Programs - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Include your existing admin dashboard styles */
        
        .program-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .add-program-btn {
            background: #333;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .programs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .programs-table th, 
        .programs-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .programs-table th {
            background: #f5f5f5;
        }

        .action-btn {
            padding: 5px 10px;
            margin: 0 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .edit-btn {
            background: #4CAF50;
            color: white;
        }

        .delete-btn {
            background: #f44336;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            width: 50%;
            margin: 100px auto;
            padding: 20px;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Include your admin dashboard sidebar -->

    <div class="content">
        <div class="program-container">
            <h2>Manage Programs</h2>
            <button class="add-program-btn" onclick="showAddModal()">
                <i class="fas fa-plus"></i> Add New Program
            </button>

            <table class="programs-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Price (MK)</th>
                        <th>Duration (Days)</th>
                        <th>Enrollments</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($program = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($program['title']); ?></td>
                        <td><?php echo htmlspecialchars(substr($program['description'], 0, 100)) . '...'; ?></td>
                        <td><?php echo number_format($program['price'], 2); ?></td>
                        <td><?php echo $program['duration']; ?></td>
                        <td><?php echo $program['total_enrollments']; ?></td>
                        <td>
                            <button class="action-btn edit-btn" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($program)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn delete-btn" onclick="deleteProgram(<?php echo $program['program_id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Program Modal -->
    <div id="programModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New Program</h3>
            <form id="programForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="program_id" id="programId">
                
                <div class="form-group">
                    <label for="title">Program Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Price (MK)</label>
                    <input type="number" id="price" name="price" required>
                </div>

                <div class="form-group">
                    <label for="duration">Duration (Days)</label>
                    <input type="number" id="duration" name="duration" required>
                </div>

                <button type="submit" class="add-program-btn">Save Program</button>
                <button type="button" onclick="closeModal()" class="action-btn">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Program';
            document.getElementById('formAction').value = 'add';
            document.getElementById('programForm').reset();
            document.getElementById('programModal').style.display = 'block';
        }

        function showEditModal(program) {
            document.getElementById('modalTitle').textContent = 'Edit Program';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('programId').value = program.program_id;
            document.getElementById('title').value = program.title;
            document.getElementById('description').value = program.description;
            document.getElementById('price').value = program.price;
            document.getElementById('duration').value = program.duration;
            document.getElementById('programModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('programModal').style.display = 'none';
        }

        function deleteProgram(programId) {
            if (confirm('Are you sure you want to delete this program?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="program_id" value="${programId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('programModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html> 