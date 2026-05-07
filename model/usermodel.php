<?php
class usermodel {
    private $conn;
    public function __construct($conn) { $this->conn = $conn; }

    public function insert($data) {
        $stmt = $this->conn->prepare("CALL sp_insert_user(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('siissssss',
            $data['role'], $data['id_siswa'], $data['id_guru'],
            $data['username'], $data['password'], $data['email'],
            $data['no_hp'], $data['status'], $data['nama']
        );
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function insertBanyak($dataArray) {
        $berhasil = 0;
        foreach ($dataArray as $data) {
            if ($this->insert($data)) $berhasil++;
        }
        return $berhasil;
    }
}
?>