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
    public function getAll(): void
{
    $result = $this->conn->query(
        'SELECT id_periode, nama_periode, tanggal_mulai, tanggal_selesai, is_active
         FROM m_periode
         ORDER BY tanggal_mulai DESC'
    );

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['status' => true, 'data' => $data]);
}
public function update(): void
{
    $body            = json_decode(file_get_contents('php://input'), true);
    $id              = $body['id']              ?? 0;
    $nama_periode    = trim($body['nama_periode']    ?? '');
    $tanggal_mulai   = trim($body['tanggal_mulai']   ?? '');
    $tanggal_selesai = trim($body['tanggal_selesai'] ?? '');
    $is_active       = trim($body['is_active']       ?? 'N');

    $stmt = $this->conn->prepare(
        'UPDATE m_periode SET nama_periode=?, tanggal_mulai=?, tanggal_selesai=?, is_active=?
         WHERE id_periode=?'
    );
    $stmt->bind_param('ssssi', $nama_periode, $tanggal_mulai, $tanggal_selesai, $is_active, $id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode([
        'status'  => $ok,
        'message' => $ok ? 'Periode berhasil diupdate.' : 'Gagal update periode.',
    ]);
}

public function delete(): void
{
    $body = json_decode(file_get_contents('php://input'), true);
    $id   = $body['id'] ?? 0;

    $stmt = $this->conn->prepare('DELETE FROM m_periode WHERE id_periode=?');
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode([
        'status'  => $ok,
        'message' => $ok ? 'Periode berhasil dihapus.' : 'Gagal hapus periode.',
    ]);
}
}
