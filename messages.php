<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: student_login.php');
    exit;
}

$user_email = $_SESSION['email'];
$conn = connectDB();

// Fetch all students except the logged-in user and count unread messages
$students = $conn->query("SELECT s.id, s.name, s.email, COUNT(m.id) AS unread_count 
                          FROM students s
                          LEFT JOIN messages m ON s.email = m.sender_email 
                              AND m.receiver_email = '$user_email' AND m.is_read = 0
                          WHERE s.email != '$user_email'
                          GROUP BY s.id");

// Fetch all instructors except the logged-in user and count unread messages
$instructors = $conn->query("SELECT i.id, i.name, i.email, COUNT(m.id) AS unread_count 
                             FROM instructors i
                             LEFT JOIN messages m ON i.email = m.sender_email 
                                 AND m.receiver_email = '$user_email' AND m.is_read = 0
                             WHERE i.email != '$user_email'
                             GROUP BY i.id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #128C7E;
            --secondary-color: #075E54;
            --background-color: #E5DDD5;
            --text-color: #333;
            --card-background: #fff;
            --message-sent: #DCF8C6;
            --message-received: #FFFFFF;
        }

        body {
            font-family: 'Segoe UI', 'Helvetica Neue', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 300px;
            background-color: var(--card-background);
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }

        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--background-color);
        }

        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            text-align: left;
        }

        .header h1 {
            margin: 0;
            font-size: 1.2rem;
        }

        .user-list {
            padding: 10px;
        }

        .user-list h3 {
            margin: 15px 10px;
            color: var(--secondary-color);
            font-size: 1rem;
        }

        .user-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .user-list li {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .user-list li:hover {
            background-color: #f5f5f5;
        }

        .user-list a {
            text-decoration: none;
            color: var(--text-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .badge {
            background-color: var(--primary-color);
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .chat-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            background-color: var(--background-color);
        }

        .message-box {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
            margin-bottom: 20px;
        }

        .message {
            max-width: 65%;
            padding: 8px 15px;
            margin-bottom: 8px;
            position: relative;
            border-radius: 7.5px;
            box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
            word-wrap: break-word;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .chat-area .message.sent {
            background-color: var(--message-sent);
            margin-left: auto;
            border-top-right-radius: 0;
        }

        .chat-area .message.received {
            background-color: var(--message-received);
            margin-right: auto;
            border-top-left-radius: 0;
        }

        /* Tail adjustments */
        .chat-area .message.sent::before {
            content: "";
            position: absolute;
            top: 0;
            right: -8px;
            width: 8px;
            height: 13px;
            background-color: var(--message-sent);
            clip-path: polygon(0 0, 100% 100%, 0 100%);
        }

        .chat-area .message.received::before {
            content: "";
            position: absolute;
            top: 0;
            left: -8px;
            width: 8px;
            height: 13px;
            background-color: var(--message-received);
            clip-path: polygon(100% 0, 100% 100%, 0 100%);
        }

        .message-form {
            display: flex;
            gap: 10px;
            background-color: #F0F0F0;
            padding: 10px;
            border-radius: 8px;
        }

        .message-form textarea {
            flex-grow: 1;
            padding: 12px;
            border: none;
            border-radius: 20px;
            resize: none;
            font-family: inherit;
            font-size: 0.95rem;
            background-color: white;
        }

        .message-form textarea:focus {
            outline: none;
        }

        .message-form button {
            padding: 0 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.2s;
            font-weight: 500;
        }

        .message-form button:hover {
            background-color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                max-height: 300px;
            }

            .message {
                max-width: 85%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <div class="user-list">
                <h3>Students</h3>
                <ul>
                    <?php while ($row = $students->fetch_assoc()): ?>
                        <li>
                            <a href="?receiver_email=<?php echo htmlspecialchars($row['email']); ?>">
                                <span><?php echo htmlspecialchars($row['name']); ?></span>
                                <?php if ($row['unread_count'] > 0): ?>
                                    <span class="badge"><?php echo $row['unread_count']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>

                <h3>Instructors</h3>
                <ul>
                    <?php while ($row = $instructors->fetch_assoc()): ?>
                        <li>
                            <a href="?receiver_email=<?php echo htmlspecialchars($row['email']); ?>">
                                <span><?php echo htmlspecialchars($row['name']); ?></span>
                                <?php if ($row['unread_count'] > 0): ?>
                                    <span class="badge"><?php echo $row['unread_count']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <h1>Messages</h1>
            </header>

            <section class="chat-area">
                <?php if (isset($_GET['receiver_email'])): 
                    $receiver_email = htmlspecialchars($_GET['receiver_email']); ?>
                    <div class="message-box" id="messageBox">
                        <!-- Messages will be loaded here via AJAX -->
                    </div>

                    <form id="messageForm" class="message-form">
                        <input type="hidden" name="receiver_email" value="<?php echo $receiver_email; ?>">
                        <textarea name="message" placeholder="Type your message..." required></textarea>
                        <button type="submit">Send</button>
                    </form>
                <?php else: ?>
                    <p>Select a user to start chatting.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        function loadMessages() {
            const receiverEmail = '<?php echo isset($_GET['receiver_email']) ? htmlspecialchars($_GET['receiver_email']) : ''; ?>';
            if (receiverEmail) {
                $.ajax({
                    url: 'load_messages.php',
                    type: 'GET',
                    data: { receiver_email: receiverEmail },
                    success: function(data) {
                        $('#messageBox').html(data);
                        $('#messageBox').scrollTop($('#messageBox')[0].scrollHeight);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error loading messages:", error);
                    }
                });
            }
        }

        setInterval(loadMessages, 5000);

        $('#messageForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'send_message.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    loadMessages();
                    $('textarea[name="message"]').val('');
                },
                error: function(xhr, status, error) {
                    console.error("Error sending message:", error);
                }
            });
        });

        loadMessages();
    </script>
</body>
</html>
