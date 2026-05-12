<?php
// FILE: model/periodemodel.php
// FIX: sp_insert_periode tidak ada di DB, pakai INSERT langsung

class PeriodeModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function insert($data): bool
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO m_periode (nama_periode, tanggal_mulai, tanggal_selesai, is_active)
             VALUES (?, ?, ?, ?)'
        );
        $is_active = $data['is_active'] ?? 'N';
        $stmt->bind_param('ssss',
            $data['nama_periode'],
            $data['tanggal_mulai'],
            $data['tanggal_selesai'],
            $is_active
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
