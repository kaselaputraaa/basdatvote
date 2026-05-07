<?php
class PeriodeModel {
    private $conn;
    public function __construct($conn) { $this->conn = $conn; }

    public function insert($data) {
        $stmt = $this->conn->prepare("CALL sp_insert_periode(?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss',
            $data['tahun'], $data['tanggal_mulai'],
            $data['tanggal_selesai'], $data['is_active'], $data['tipe_voting']
        );
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
?>