<?php
require_once __DIR__ . '/../model/usermodel.php';
class UserController {
    private $model;
    public function __construct($conn) { $this->model = new usermodel($conn); }

    public function insert() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) { echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']); return; }

        if (isset($data[0])) {
            $berhasil = $this->model->insertBanyak($data);
            echo json_encode(['status' => 'success', 'message' => "$berhasil user berhasil ditambahkan"]);
        } else {
            echo $this->model->insert($data)
                ? json_encode(['status' => 'success', 'message' => 'User berhasil ditambahkan'])
                : json_encode(['status' => 'error', 'message' => 'Gagal menambahkan user']);
        }
    }
}
?>