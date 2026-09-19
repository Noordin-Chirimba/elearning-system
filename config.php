<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'e-learningsystem');

function connectDB() {
    $link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($link === false) {
        die("ERROR: Could not connect. " . mysqli_connect_error());
    }
    return $link;
}

// For development only - allow media access on localhost
if (!in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']) && 
    (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

?>
