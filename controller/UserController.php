<?php
// ============================================================
// FILE   : controller/UserController.php
// FUNGSI : Insert user baru (hanya admin)
// ============================================================

require_once __DIR__ . '/../model/UserModel.php';

class UserController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new UserModel($conn);
    }

    // POST /index.php?action=insert_user
    // Body JSON: satu objek atau array of objek
    // Objek: { "nipd": "...", "passwordd": "...", "roles": "siswa",
    //           "id_siswa": 1, "id_guru": null }
    public function insert(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body) {
            echo json_encode(['status' => false, 'message' => 'Data tidak valid.']);
            return;
        }

        if (isset($body[0])) {
            $berhasil = $this->model->insertBanyak($body);
            echo json_encode([
                'status'  => true,
                'message' => "{$berhasil} user berhasil ditambahkan.",
            ]);
        } else {
            $ok = $this->model->insert($body);
            echo json_encode([
                'status'  => $ok,
                'message' => $ok ? 'User berhasil ditambahkan.' : 'Gagal menambahkan user.',
            ]);
        }
    }
}
