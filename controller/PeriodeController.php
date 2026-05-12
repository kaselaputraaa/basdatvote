<?php
// ============================================================
// FILE   : controller/PeriodeController.php
// FUNGSI : Insert periode voting baru (hanya admin)
// ============================================================

class PeriodeController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // POST /index.php?action=insert_periode
    // Body JSON: { "nama_periode": "2027", "tanggal_mulai": "2027-01-01",
    //              "tanggal_selesai": "2027-12-31", "is_active": "N" }
    public function insert(): void
    {
        $body             = json_decode(file_get_contents('php://input'), true);
        $nama_periode     = trim($body['nama_periode']     ?? '');
        $tanggal_mulai    = trim($body['tanggal_mulai']    ?? '');
        $tanggal_selesai  = trim($body['tanggal_selesai']  ?? '');
        $is_active        = trim($body['is_active']        ?? 'N');

        if (!$nama_periode || !$tanggal_mulai || !$tanggal_selesai) {
            echo json_encode(['status' => false, 'message' => 'Data tidak lengkap.']);
            return;
        }

        if (!in_array($is_active, ['Y', 'N'], true)) {
            $is_active = 'N';
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO m_periode (nama_periode, tanggal_mulai, tanggal_selesai, is_active)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $nama_periode, $tanggal_mulai, $tanggal_selesai, $is_active);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'status'  => $ok,
            'message' => $ok ? 'Periode berhasil ditambahkan.' : 'Gagal menambahkan periode.',
        ]);
    }
}
