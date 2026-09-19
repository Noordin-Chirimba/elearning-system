<?php
session_start();
require 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: student_login.php');
    exit;
}

$conn = connectDB();

// Get active streams
$sql = "SELECT ls.*, i.name as instructor_name 
        FROM live_sessions ls 
        JOIN instructors i ON ls.instructor_email = i.email 
        WHERE ls.status = 'active'";
$result = $conn->query($sql);

$room_id = $_GET['room'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Live Class</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .stream-list {
            margin-bottom: 20px;
        }

        .stream-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .video-container {
            width: 100%;
            background: #000;
            aspect-ratio: 16/9;
            margin: 20px 0;
        }

        #remoteVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: #4a90e2;
            color: white;
        }

        .room-join {
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .room-join input {
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-video"></i> Join Live Class</h2>

        <?php if (!$room_id): ?>
            <div class="room-join">
                <h3>Join with Room ID</h3>
                <form action="" method="GET">
                    <input type="text" name="room" placeholder="Enter room ID" required>
                    <button type="submit">Join</button>
                </form>
            </div>

            <div class="stream-list">
                <h3>Active Classes</h3>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($stream = $result->fetch_assoc()): ?>
                        <div class="stream-card">
                            <h3><?php echo htmlspecialchars($stream['session_name']); ?></h3>
                            <p>Instructor: <?php echo htmlspecialchars($stream['instructor_name']); ?></p>
                            <p>Started: <?php echo date('g:i A', strtotime($stream['created_at'])); ?></p>
                            <a href="?room=<?php echo $stream['room_id']; ?>">
                                <button>Join Stream</button>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No active streams available.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="video-container">
                <video id="remoteVideo" autoplay playsinline></video>
            </div>
            <button onclick="leaveStream()">Leave Stream</button>
        <?php endif; ?>
    </div>

    <?php if ($room_id): ?>
    <script>
        let peerConnection;
        const configuration = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' }
            ]
        };

        const ws = new WebSocket('ws://localhost:8080');
        const studentId = 'student_' + Math.random().toString(36).substr(2, 9);

        ws.onopen = () => {
            console.log('Connected to signaling server');
            ws.send(JSON.stringify({
                type: 'join',
                room: '<?php echo $room_id; ?>',
                role: 'student',
                studentId: studentId
            }));
        };

        ws.onmessage = async (event) => {
            const data = JSON.parse(event.data);
            
            if (data.type === 'offer') {
                peerConnection = new RTCPeerConnection(configuration);
                
                peerConnection.ontrack = (event) => {
                    const videoElement = document.getElementById('remoteVideo');
                    if (videoElement.srcObject !== event.streams[0]) {
                        videoElement.srcObject = event.streams[0];
                    }
                };
                
                peerConnection.onicecandidate = (event) => {
                    if (event.candidate) {
                        ws.send(JSON.stringify({
                            type: 'ice-candidate',
                            candidate: event.candidate,
                            room: '<?php echo $room_id; ?>',
                            studentId: studentId
                        }));
                    }
                };
                
                await peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer));
                const answer = await peerConnection.createAnswer();
                await peerConnection.setLocalDescription(answer);
                
                ws.send(JSON.stringify({
                    type: 'answer',
                    answer: answer,
                    room: '<?php echo $room_id; ?>',
                    studentId: studentId
                }));
            }
            
            if (data.type === 'ice-candidate') {
                if (peerConnection) {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
                }
            }
        };

        function leaveStream() {
            if (confirm('Are you sure you want to leave the stream?')) {
                if (peerConnection) {
                    peerConnection.close();
                }
                ws.close();
                window.location.href = 'join_live_class.php';
            }
        }
    </script>
    <?php endif; ?>
</body>
</html> 