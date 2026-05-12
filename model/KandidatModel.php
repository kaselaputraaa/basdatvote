<?php
// ============================================================
// FILE   : model/KandidatModel.php
// FUNGSI : Insert kandidat ke m_kandidat
// FIX    : affected_rows dicek SEBELUM stmt->close()
// ============================================================

class KandidatModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function insert(array $data): bool
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO m_kandidat
               (id_ketua, id_wakil, jenis, visi, misi, nomor_urut, foto, id_periode, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'iisssisis',
            $data['id_ketua'],
            $data['id_wakil'],
            $data['jenis'],
            $data['visi'],
            $data['misi'],
            $data['nomor_urut'],
            $data['foto'],
            $data['id_periode'],
            $data['is_active']
        );
        $ok            = $stmt->execute();
        $affected_rows = $stmt->affected_rows; // BUG FIX: ambil SEBELUM close()
        $stmt->close();
        return $ok && $affected_rows > 0;
    }

    public function insertBanyak(array $dataArray): int
    {
        $berhasil = 0;
        foreach ($dataArray as $data) {
            if ($this->insert($data)) {
                $berhasil++;
            }
        }
        return $berhasil;
    }
}
