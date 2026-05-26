<?php
// ============================================================
// FILE   : controller/SiswaController.php
// FUNGSI : CRUD data siswa (hanya admin)
// ============================================================

class SiswaController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // GET /index.php?action=siswa
    public function getAll(): void
    {
        $stmt   = $this->conn->prepare('SELECT * FROM m_siswa ORDER BY nama_siswa ASC');
        $stmt->execute();
        $result = $stmt->get_result();
        $data   = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['status' => true, 'data' => $data]);
    }

    // POST /index.php?action=tambah_siswa
    // Body JSON: { "nipd": "...", "nama_siswa": "...", "jenis_kelamin": "L" }
    public function insert(): void
    {
        $body          = json_decode(file_get_contents('php://input'), true);
        $nipd          = trim($body['nipd']          ?? '');
        $nama_siswa    = trim($body['nama_siswa']    ?? '');
        $jenis_kelamin = trim($body['jenis_kelamin'] ?? '');

        if (!$nipd || !$nama_siswa || !in_array($jenis_kelamin, ['L', 'P'], true)) {
            echo json_encode(['status' => false, 'message' => 'Data tidak lengkap atau tidak valid.']);
            return;
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO m_siswa (nipd, nama_siswa, jenis_kelamin) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('sss', $nipd, $nama_siswa, $jenis_kelamin);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'status'  => $ok,
            'message' => $ok ? 'Siswa berhasil ditambahkan.' : 'Gagal menambahkan siswa.',
        ]);
    }

    // POST /index.php?action=hapus_siswa
    // Body JSON: { "id": 5 }
    public function delete(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $id   = (int) ($body['id'] ?? 0);

        if (!$id) {
            echo json_encode(['status' => false, 'message' => 'ID tidak valid.']);
            return;
        }

        $stmt = $this->conn->prepare('DELETE FROM m_siswa WHERE id_siswa = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'status'  => $ok,
            'message' => $ok ? 'Siswa berhasil dihapus.' : 'Gagal menghapus siswa.',
        ]);
    }
  public function update(): void
{
    $body          = json_decode(file_get_contents('php://input'), true);
    $id            = (int) ($body['id']            ?? 0);
    $nipd          = trim($body['nipd']            ?? '');
    $nama_siswa    = trim($body['nama_siswa']       ?? '');
    $jenis_kelamin = trim($body['jenis_kelamin']    ?? '');

    if (!$id || !$nipd || !$nama_siswa || !in_array($jenis_kelamin, ['L', 'P'], true)) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Data tidak lengkap atau tidak valid.']);
        return;
    }

    $stmt = $this->conn->prepare(
        'UPDATE m_siswa SET nipd = ?, nama_siswa = ?, jenis_kelamin = ? WHERE id_siswa = ?'
    );
    $stmt->bind_param('sssi', $nipd, $nama_siswa, $jenis_kelamin, $id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode([
        'status'  => $ok,
        'message' => $ok ? 'Data siswa berhasil diperbarui.' : 'Gagal memperbarui siswa.',
    ]);
}
}
