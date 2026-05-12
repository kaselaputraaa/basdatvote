<?php
// ============================================================
// FILE   : controller/GuruController.php
// FUNGSI : Ambil data guru (hanya admin)
// ============================================================

class GuruController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // GET /index.php?action=guru
    public function getAll(): void
    {
        $stmt   = $this->conn->prepare('SELECT * FROM m_guru ORDER BY nama_guru ASC');
        $stmt->execute();
        $result = $stmt->get_result();
        $data   = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['status' => true, 'data' => $data]);
    }
}
