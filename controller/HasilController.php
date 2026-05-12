<?php
// ============================================================
// FILE   : controller/HasilController.php
// FIX    : Ganti CALL sp_hasil_voting() dengan query langsung
//          karena SP mungkin belum ada di DB
// ============================================================

class HasilController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // GET /index.php?action=hasil_vote&jenis=OSIS
    public function get(): void
    {
        $jenis = isset($_GET['jenis']) ? strtoupper(trim($_GET['jenis'])) : null;

        $sql = "SELECT
                    k.id_kandidat,
                    k.nomor_urut,
                    k.jenis,
                    s1.nama_siswa AS nama_ketua,
                    s2.nama_siswa AS nama_wakil,
                    p.nama_periode,
                    COUNT(v.id_vote) AS jumlah_suara
                FROM m_kandidat k
                JOIN m_siswa  s1 ON k.id_ketua   = s1.id_siswa
                JOIN m_siswa  s2 ON k.id_wakil   = s2.id_siswa
                JOIN m_periode p  ON k.id_periode = p.id_periode
                LEFT JOIN t_vote v ON k.id_kandidat = v.id_kandidat
                WHERE p.is_active = 'Y'";

        if ($jenis !== null) {
            $sql .= " AND k.jenis = ?";
        }

        $sql .= " GROUP BY k.id_kandidat, k.nomor_urut, k.jenis,
                    s1.nama_siswa, s2.nama_siswa, p.nama_periode
                  ORDER BY jumlah_suara DESC";

        $stmt = $this->conn->prepare($sql);

        if ($jenis !== null) {
            $stmt->bind_param('s', $jenis);
        }

        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['status' => true, 'data' => $data]);
    }
}
