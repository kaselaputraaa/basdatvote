<?php
// ============================================================
// FILE   : controller/GuruController.php
// FUNGSI : Ambil data guru (hanya admin)
// UPDATE : Tambah method update() dan delete()
// ============================================================

class GuruController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll(): void
    {
        $stmt = $this->conn->prepare('SELECT * FROM m_guru ORDER BY nama_guru ASC');
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['status' => true, 'data' => $data]);
    }

    public function insert(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $kode_guru = trim($body['kode_guru'] ?? '');
        $nama_guru = trim($body['nama_guru'] ?? '');
        $jenis_kelamin = trim($body['jenis_kelamin'] ?? '');

        if (!$kode_guru || !$nama_guru || !in_array($jenis_kelamin, ['L', 'P'], true)) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Data tidak lengkap atau tidak valid.']);
            return;
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO m_guru (kode_guru, nama_guru, jenis_kelamin) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('sss', $kode_guru, $nama_guru, $jenis_kelamin);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'status' => $ok,
            'message' => $ok ? 'Guru berhasil ditambahkan.' : 'Gagal menambahkan guru.',
        ]);
    }

    public function update(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($body['id'] ?? 0);
        $kode_guru = trim($body['kode_guru'] ?? '');
        $nama_guru = trim($body['nama_guru'] ?? '');
        $jenis_kelamin = trim($body['jenis_kelamin'] ?? '');

        if (!$id || !$kode_guru || !$nama_guru || !in_array($jenis_kelamin, ['L', 'P'], true)) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Data tidak lengkap atau tidak valid.']);
            return;
        }

        $stmt = $this->conn->prepare(
            'UPDATE m_guru SET kode_guru = ?, nama_guru = ?, jenis_kelamin = ? WHERE id_guru = ?'
        );
        $stmt->bind_param('sssi', $kode_guru, $nama_guru, $jenis_kelamin, $id);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'status' => $ok,
            'message' => $ok ? 'Data guru berhasil diperbarui.' : 'Gagal memperbarui guru.',
        ]);
    }

    public function delete(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($body['id'] ?? 0);

        if (!$id) {
            echo json_encode(['status' => false, 'message' => 'ID tidak valid.']);
            return;
        }

        $stmt = $this->conn->prepare('DELETE FROM m_guru WHERE id_guru = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'status' => $ok,
            'message' => $ok ? 'Guru berhasil dihapus.' : 'Gagal menghapus guru.',
        ]);
    }
}