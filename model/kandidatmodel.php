<?php
class kandidatmodel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function insert($data) {
        $stmt = $this->conn->prepare("CALL sp_vote(?, ?, ?, ?)");

        $stmt->bind_param(
            "iisssss",
            $data['id_user'],        
            $data['id_kandidat'],   
            $data['jenis'],
            $data['periode'],
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