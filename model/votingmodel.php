<?php
class VotingModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Insert vote via sp_vote (3 param: id_user, id_kandidat, jenis)
    public function insert($data)
    {

        $stmt = $this->conn->prepare(
            "CALL sp_vote(?, ?, ?)"
        );

        if (!$stmt) {

            die(
                "Prepare gagal: " .
                $this->conn->error
            );
        }

        $id_user =
            (int) $data['id_user'];

        $id_kandidat =
            (int) $data['id_kandidat'];

        $jenis =
            strtoupper($data['jenis']);

        $stmt->bind_param(
            "iis",
            $id_user,
            $id_kandidat,
            $jenis
        );

        if (!$stmt->execute()) {

            die(
                "Execute gagal: " .
                $stmt->error
            );
        }

        while (
            $stmt->more_results() &&
            $stmt->next_result()
        ) {
        }

        $stmt->close();

        return true;
    }

    // Cek status vote per jenis untuk satu user
    public function statusVote($id_user)
    {
        $stmt = $this->conn->prepare(
            "SELECT jenis FROM t_vote WHERE id_user = ?"
        );
        $stmt->bind_param("i", $id_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $jenis_list = [];
        while ($row = $result->fetch_assoc()) {
            $jenis_list[] = strtoupper($row['jenis']);
        }
        $stmt->close();
        return [
            'osis' => in_array('OSIS', $jenis_list),
            'mpk' => in_array('MPK', $jenis_list),
        ];
    }

    public function cekVote($id_user)
    {
        $status = $this->statusVote($id_user);
        return $status['osis'] && $status['mpk'];
    }

    // Ambil semua kandidat dari vw_kandidat, filter by jenis
    public function get_kandidat($jenis = null)
    {
        if ($jenis) {
            $jenis = strtoupper($jenis);
            $stmt = $this->conn->prepare("SELECT * FROM vw_kandidat WHERE jenis = ? ORDER BY nomor_urut ASC");
            $stmt->bind_param("s", $jenis);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        }

        // Tanpa filter — ambil semua
        $result = $this->conn->query("SELECT * FROM vw_kandidat ORDER BY jenis, nomor_urut ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>