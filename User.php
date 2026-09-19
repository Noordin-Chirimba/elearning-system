<?php
class User {
    private $name;
    private $email;
    private $password;
    private $id;

    public function __construct($name, $email, $password) {
        $this->name = $name;
        $this->email = $email;
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    public function save() {
        $conn = new mysqli('localhost', 'root', '', 'e-learningsystem');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("INSERT INTO students (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $this->name, $this->email, $this->password);
        $result = $stmt->execute();

        if ($result) {
            $this->id = $conn->insert_id;
        }

        $stmt->close();
        $conn->close();

        return $result ? $this->id : false;
    }

    public static function getByEmail($email) {
        $conn = new mysqli('localhost', 'root', '', 'e-learningsystem');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $user = $result->fetch_assoc();

        $stmt->close();
        $conn->close();

        return $user;
    }

    public static function getById($id) {
        $conn = new mysqli('localhost', 'root', '', 'e-learningsystem');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $user = $result->fetch_assoc();

        $stmt->close();
        $conn->close();

        return $user;
    }
}
?>
