<?php
require 'config.php';

$host = 'localhost';
$port = 8080;
$null = NULL; // null var

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, $host, $port);
socket_listen($socket);

$clients = array($socket);

while (true) {
    $changed = $clients;
    socket_select($changed, $null, $null, 0, 10);

    if (in_array($socket, $changed)) {
        $socket_new = socket_accept($socket);
        $clients[] = $socket_new;
        $header = socket_read($socket_new, 1024);
        perform_handshaking($header, $socket_new, $host, $port);
        socket_getpeername($socket_new, $ip);
        $changed = array_diff($changed, [$socket]);
    }

    foreach ($changed as $client) {
        while (socket_recv($client, $buf, 1024, 0) >= 1) {
            $msg = unmask($buf);
            $msgData = json_decode($msg);
            $message = $msgData->message;
            $sender_id = $msgData->sender_id;
            $receiver_id = $msgData->receiver_id;
            $sender_type = $msgData->sender_type;
            $receiver_type = $msgData->receiver_type;

            $query = "INSERT INTO messages (sender_id, receiver_id, message, sender_type, receiver_type) VALUES ('$sender_id', '$receiver_id', '$message', '$sender_type', '$receiver_type')";
            mysqli_query(connectDB(), $query);

            $response = mask(json_encode(['message' => $message, 'sender_id' => $sender_id, 'receiver_id' => $receiver_id]));
            foreach ($clients as $client) {
                @socket_write($client, $response, strlen($response));
            }
            break 2;
        }

        $buf = @socket_read($client, 1024, PHP_NORMAL_READ);
        if ($buf === false) {
            $index = array_search($client, $clients);
            unset($clients[$index]);
            socket_close($client);
        }
    }
}

socket_close($socket);

function perform_handshaking($header, $client_conn, $host, $port) {
    // WebSocket Handshake headers here (Code omitted for brevity)
}

function mask($text) {
    $b1 = 0x81;
    $length = strlen($text);
    $header = '';
    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif ($length > 125 && $length < 65536) {
        $header = pack('CCn', $b1, 126, $length);
    }
    return $header.$text;
}

function unmask($text) {
    $length = ord($text[1]) & 127;
    if ($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    } elseif ($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    } else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = '';
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}
?>
