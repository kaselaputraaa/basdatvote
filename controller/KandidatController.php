<?php
// ============================================================
// FILE   : controller/KandidatController.php
// FUNGSI : Insert kandidat baru (hanya admin)
// ============================================================

require_once __DIR__ . '/../model/KandidatModel.php';

class KandidatController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new KandidatModel($conn);
    }

    // POST /index.php?action=insert_kandidat
    // Body JSON: satu objek atau array of objek
    public function insert(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body) {
            echo json_encode(['status' => false, 'message' => 'Data tidak valid.']);
            return;
        }

        // Insert banyak sekaligus jika input berupa array
        if (isset($body[0])) {
            $berhasil = $this->model->insertBanyak($body);
            echo json_encode([
                'status'  => true,
                'message' => "{$berhasil} kandidat berhasil ditambahkan.",
            ]);
        } else {
            $ok = $this->model->insert($body);
            echo json_encode([
                'status'  => $ok,
                'message' => $ok ? 'Kandidat berhasil ditambahkan.' : 'Gagal menambahkan kandidat.',
            ]);
        }
    }
}
