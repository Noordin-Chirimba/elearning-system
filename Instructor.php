<?php
class Instructor {
    private $name;
    private $email;
    private $password;
    private $specialization;
    private $id_number;

    public function __construct($name, $email, $password, $specialization, $id_number) {
        $this->name = $name;
        $this->email = $email;
        $this->password = password_hash($password, PASSWORD_BCRYPT);
        $this->specialization = $specialization;
        $this->id_number = $id_number;
    }

    public function save() {
        $conn = new mysqli('localhost', 'root', '', 'e-learningsystem');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("INSERT INTO instructors (name, email, password, specialization, id_number) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Prepare statement failed: " . $conn->error);
        }
        
        $stmt->bind_param("sssss", $this->name, $this->email, $this->password, $this->specialization, $this->id_number);
        $result = $stmt->execute();

        if (!$result) {
            die("Execute statement failed: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();

        return $result;
    }

    public static function getByEmail($email) {
        $conn = new mysqli('localhost', 'root', '', 'e-learningsystem');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT * FROM instructors WHERE email = ?");
        if (!$stmt) {
            die("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $stmt->close();
            $conn->close();
            return null;
        }

        $instructor = $result->fetch_assoc();

        $stmt->close();
        $conn->close();

        return $instructor;
    }
}
?>
