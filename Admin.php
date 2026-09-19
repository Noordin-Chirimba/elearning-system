<?php
require 'config.php'; // Ensure this file includes your database connection function

class Admin {
    private $conn;

    public function __construct() {
        $this->conn = connectDB(); // Establish database connection
    }

    // Properties
    private $username;
    private $password;

    // Setter for username
    public function setUsername($username) {
        $this->username = $username;
    }

    // Setter for password (hashes the password)
    public function setPassword($password) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $this->password = $hashed_password;
    }

    public function insertAdmin() {
        $sql = "INSERT INTO admins (username, password) VALUES (?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $this->username, $this->password);

        if ($stmt->execute()) {
            return "Admin account created successfully.";
        } else {
            return "Error creating admin account: " . $stmt->error;
        }
    }

    // Validate admin login
    public function login($username, $password) {
        $sql = "SELECT * FROM admins WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                return true; // Login successful
            } else {
                return "Invalid username or password.";
            }
        } else {
            return "Invalid username or password.";
        }
    }

    // Destructor to close database connection when object is destroyed
    public function __destruct() {
        $this->conn->close(); // Close database connection
    }
}
?>
