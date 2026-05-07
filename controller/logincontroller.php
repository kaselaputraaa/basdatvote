<?php
class LoginController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login() {
        $data     = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (empty($username) || empty($password)) {
            echo json_encode([
                "status"  => "error",
                "message" => "Username dan password wajib diisi"
            ]);
            return;
        }

        $stmt = $this->conn->prepare("CALL sp_login(?, ?)");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $row = $result->fetch_assoc();

            if ($row['status'] === 'success') {
                echo json_encode([
                    "status" => "success",
                    "user"   => [
                        "id_user" => $row['id_user'],
                        "nama"    => $row['nama'],
                        "role"    => $row['role']
                    ]
                ]);

            } else if ($row['status'] === 'sudah_voting') {
                echo json_encode([
                    "status"  => "sudah_voting",
                    "message" => "Kamu sudah pernah voting"
                ]);

            } else {
                echo json_encode([
                    "status"  => "error",
                    "message" => "Username atau password salah"
                ]);
            }
        } else {
            echo json_encode([
                "status"  => "error",
                "message" => "Login gagal"
            ]);
        }

        $stmt->close();
    }
}
?>