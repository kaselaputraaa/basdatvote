<?php
class VotingModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Insert vote via sp_vote (3 param: id_user, id_kandidat, jenis)
    public function insert($data) {
        $stmt = $this->conn->prepare("CALL sp_vote(?, ?, ?)");
        $stmt->bind_param(
            'iiss',
            $data['id_user'],
            $data['id_kandidat'],
            $data['jenis'],
            $data['periode'],     // 'OSIS' atau 'MPK'
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();
        return isset($row['message']) && $row['message'] === 'VOTE BERHASIL';
    }

    // Cek status vote per jenis untuk satu user
    public function statusVote($id_user) {
        $stmt = $this->conn->prepare(
            "SELECT jenis FROM t_vote WHERE id_user = ?"
        );
        $stmt->bind_param("i", $id_user);
        $stmt->execute();
        $result     = $stmt->get_result();
        $jenis_list = [];
        while ($row = $result->fetch_assoc()) {
            $jenis_list[] = strtoupper($row['jenis']);
        }
        $stmt->close();
        return [
            'osis' => in_array('OSIS', $jenis_list),
            'mpk'  => in_array('MPK',  $jenis_list),
        ];
    }

    public function cekVote($id_user) {
        $status = $this->statusVote($id_user);
        return $status['osis'] && $status['mpk'];
    }

    // Ambil semua kandidat dari vw_kandidat, filter by jenis
    public function get_kandidat($jenis = null) {
        if ($jenis) {
            $jenis = strtoupper($jenis);
            $stmt  = $this->conn->prepare("SELECT * FROM vw_kandidat WHERE jenis = ? ORDER BY nomor_urut ASC");
            $stmt->bind_param("s", $jenis);
            $stmt->execute();
            $result = $stmt->get_result();
            $data   = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        }

        // Tanpa filter — ambil semua
        $result = $this->conn->query("SELECT * FROM vw_kandidat ORDER BY jenis, nomor_urut ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
