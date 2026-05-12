<?php
// ============================================================
// FILE   : model/VotingModel.php
// FIX    : getKandidat pakai query langsung, tidak CALL stored procedure
//          karena SP mungkin belum ada di DB user
// ============================================================

class VotingModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Ambil id_periode aktif
    private function getPeriodeAktif(): ?int
    {
        $r   = $this->conn->query("SELECT id_periode FROM m_periode WHERE is_active='Y' LIMIT 1");
        $row = $r ? $r->fetch_assoc() : null;
        return $row ? (int) $row['id_periode'] : null;
    }

    // Insert satu suara
    public function insert(array $data): array
    {
        $id_periode = $this->getPeriodeAktif();

        if (!$id_periode) {
            return ['status' => false, 'message' => 'Tidak ada periode voting aktif.'];
        }

        $id_user     = (int) $data['id_user'];
        $id_kandidat = (int) $data['id_kandidat'];
        $jenis       = strtoupper($data['jenis']);

        $stmt = $this->conn->prepare(
            'INSERT INTO t_vote (id_user, id_kandidat, jenis, id_periode) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('iisi', $id_user, $id_kandidat, $jenis, $id_periode);

        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => true, 'message' => 'Vote berhasil.'];
        }

        $stmt->close();

        // errno 1062 = Duplicate entry (sudah pernah vote)
        if ($this->conn->errno === 1062) {
            return ['status' => false, 'message' => 'Anda sudah voting untuk kategori ini.'];
        }

        return ['status' => false, 'message' => 'Terjadi kesalahan. Coba lagi.'];
    }

    // Cek status vote per jenis
    public function statusVote(int $id_user): array
    {
        $id_periode = $this->getPeriodeAktif();
        if (!$id_periode) return ['osis' => false, 'mpk' => false];

        $stmt = $this->conn->prepare(
            "SELECT jenis FROM t_vote WHERE id_user = ? AND id_periode = ?"
        );
        $stmt->bind_param('ii', $id_user, $id_periode);
        $stmt->execute();
        $result = $stmt->get_result();

        $list = [];
        while ($row = $result->fetch_assoc()) {
            $list[] = strtoupper($row['jenis']);
        }
        $stmt->close();

        return [
            'osis' => in_array('OSIS', $list, true),
            'mpk'  => in_array('MPK',  $list, true),
        ];
    }

    // Ambil kandidat via query langsung (tidak bergantung stored procedure)
    public function getKandidat(?string $jenis = null): array
    {
        $sql = "SELECT
                    k.id_kandidat,
                    k.nomor_urut,
                    k.jenis,
                    s1.nama_siswa AS nama_ketua,
                    s2.nama_siswa AS nama_wakil,
                    k.visi,
                    k.misi,
                    k.foto,
                    CONCAT('gambar/', k.foto) AS foto_path,
                    p.nama_periode
                FROM m_kandidat k
                JOIN m_siswa  s1 ON k.id_ketua   = s1.id_siswa
                JOIN m_siswa  s2 ON k.id_wakil   = s2.id_siswa
                JOIN m_periode p  ON k.id_periode = p.id_periode
                WHERE p.is_active = 'Y'
                  AND k.is_active = 'Y'";

        if ($jenis !== null) {
            $sql .= " AND k.jenis = ? ORDER BY k.nomor_urut ASC";
            $stmt = $this->conn->prepare($sql);
            $jenis = strtoupper($jenis);
            $stmt->bind_param('s', $jenis);
        } else {
            $sql .= " ORDER BY k.nomor_urut ASC";
            $stmt = $this->conn->prepare($sql);
        }

        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }
}
