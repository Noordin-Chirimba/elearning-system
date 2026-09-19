// send_message.js

var conn = new WebSocket('ws://localhost:8080'); // WebSocket server address

// Connection established
conn.onopen = function(e) {
    console.log("Connection established!");
};

// Handle incoming messages
conn.onmessage = function(e) {
    var data = JSON.parse(e.data);
    var messageDiv = document.createElement('div');
    messageDiv.textContent = data.sender_id + ": " + data.message;
    document.getElementById('messages').appendChild(messageDiv);
};

// Function to send a message
function sendMessage() {
    var message = document.getElementById('messageInput').value;
    var senderId = document.getElementById('senderId').value;  // Sender's ID
    var receiverId = document.getElementById('receiverId').value;  // Receiver's ID

    // Prepare the message data
    var msg = {
        sender_id: senderId,
        receiver_id: receiverId,
        message: message
    };

    // Send the message over WebSocket
    conn.send(JSON.stringify(msg));

    // Clear the input field after sending
    document.getElementById('messageInput').value = '';
}
