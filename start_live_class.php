<?php
session_start();
require 'config.php';

if (!isset($_SESSION['instructor_loggedin'])) {
    header('Location: instructor_login.php');
    exit;
}

$conn = connectDB();
$user_email = $_SESSION['email'];

// Handle stream creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_name = $_POST['session_name'];
    $room_id = uniqid('room_', true);
    
    $sql = "INSERT INTO live_sessions (instructor_email, session_name, room_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $user_email, $session_name, $room_id);
    $stmt->execute();
    
    // Redirect to the room
    header("Location: start_live_class.php?room=" . $room_id);
    exit;
}

$room_id = $_GET['room'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Live Class</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .stream-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .video-container {
            width: 100%;
            background: #000;
            aspect-ratio: 16/9;
            margin-bottom: 20px;
            position: relative;
        }

        #localVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .controls {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: #4a90e2;
            color: white;
        }

        button.end-stream {
            background: #e74c3c;
        }

        .room-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="stream-container">
        <h2><i class="fas fa-video"></i> Live Class Stream</h2>
        
        <?php if (!$room_id): ?>
            <form method="POST">
                <input type="text" name="session_name" placeholder="Enter session name" required>
                <button type="submit">Start New Stream</button>
            </form>
        <?php else: ?>
            <div class="video-container">
                <video id="localVideo" autoplay muted playsinline></video>
            </div>
            
            <div class="controls">
                <button id="toggleAudio"><i class="fas fa-microphone"></i> Mute</button>
                <button id="toggleVideo"><i class="fas fa-video"></i> Video</button>
                <button class="end-stream" onclick="endStream()">End Stream</button>
            </div>

            <div class="room-info">
                <h3>Room Information</h3>
                <p>Share this room ID with your students: <strong><?php echo htmlspecialchars($room_id); ?></strong></p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($room_id): ?>
    <script>
        let localStream;
        let isAudioMuted = false;
        let isVideoOff = false;

        // Add to both start_live_class.php and join_live_class.php in the script sections
if (!navigator.mediaDevices) {
            navigator.mediaDevices = {};
        }

        if (!navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.getUserMedia = function(constraints) {
        const getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
        
        if (!getUserMedia) {
            return Promise.reject(new Error('getUserMedia is not implemented in this browser'));
        }

        return new Promise((resolve, reject) => {
            getUserMedia.call(navigator, constraints, resolve, reject);
        });
            }
        }

        // Start the stream
        async function startStream() {
            try {
                // First check if media devices are supported
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Your browser does not support media devices');
                }

                // Request permissions with constraints
                const constraints = {
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        sampleRate: 44100
                    },
                    video: {
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        facingMode: 'user'
                    }
                };

                localStream = await navigator.mediaDevices.getUserMedia(constraints);
                document.getElementById('localVideo').srcObject = localStream;

                // Add error handling for the video element
                const videoElement = document.getElementById('localVideo');
                videoElement.onerror = (err) => {
                    console.error('Video element error:', err);
                    alert('Error displaying video stream');
                };

                // Add this to the script section
                const configuration = {
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' }
                    ]
                };

                let peerConnections = {};
                const ws = new WebSocket('ws://localhost:8080');

                ws.onopen = () => {
                    console.log('Connected to signaling server');
                    ws.send(JSON.stringify({
                        type: 'join',
                        room: '<?php echo $room_id; ?>',
                        role: 'instructor'
                    }));
                };

                ws.onmessage = async (event) => {
                    const data = JSON.parse(event.data);
                    
                    if (data.type === 'join' && data.role === 'student') {
                        const studentId = data.studentId;
                        const peerConnection = new RTCPeerConnection(configuration);
                        peerConnections[studentId] = peerConnection;
                        
                        // Add local stream
                        localStream.getTracks().forEach(track => {
                            peerConnection.addTrack(track, localStream);
                        });
                        
                        // Create and send offer
                        const offer = await peerConnection.createOffer();
                        await peerConnection.setLocalDescription(offer);
                        ws.send(JSON.stringify({
                            type: 'offer',
                            offer: offer,
                            studentId: studentId,
                            room: '<?php echo $room_id; ?>'
                        }));
                    }
                    
                    if (data.type === 'answer') {
                        const peerConnection = peerConnections[data.studentId];
                        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
                    }
                    
                    if (data.type === 'ice-candidate') {
                        const peerConnection = peerConnections[data.studentId];
                        if (peerConnection) {
                            await peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
                        }
                    }
                };

            } catch (err) {
                console.error('Detailed error:', err);
                
                // Provide more specific error messages
                if (err.name === 'NotAllowedError') {
                    alert('Please allow camera and microphone access to start streaming');
                } else if (err.name === 'NotFoundError') {
                    alert('No camera or microphone found. Please check your devices');
                } else if (err.name === 'NotReadableError') {
                    alert('Your camera or microphone is already in use');
                } else {
                    alert('Error accessing media devices: ' + err.message);
                }
            }
        }

        // Toggle audio
        document.getElementById('toggleAudio').addEventListener('click', () => {
            const audioTracks = localStream.getAudioTracks();
            isAudioMuted = !isAudioMuted;
            audioTracks.forEach(track => track.enabled = !isAudioMuted);
            document.getElementById('toggleAudio').innerHTML = 
                isAudioMuted ? '<i class="fas fa-microphone-slash"></i> Unmute' : '<i class="fas fa-microphone"></i> Mute';
        });

        // Toggle video
        document.getElementById('toggleVideo').addEventListener('click', () => {
            const videoTracks = localStream.getVideoTracks();
            isVideoOff = !isVideoOff;
            videoTracks.forEach(track => track.enabled = !isVideoOff);
            document.getElementById('toggleVideo').innerHTML = 
                isVideoOff ? '<i class="fas fa-video-slash"></i> Start Video' : '<i class="fas fa-video"></i> Stop Video';
        });

        // End stream
        function endStream() {
            if (confirm('Are you sure you want to end the stream?')) {
                fetch('end_stream.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        room_id: '<?php echo $room_id; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        localStream.getTracks().forEach(track => track.stop());
                        window.location.href = 'instructor_dashboard.php';
                    }
                });
            }
        }

        // Start streaming when page loads
        startStream();
    </script>
    <?php endif; ?>
</body>
</html> 