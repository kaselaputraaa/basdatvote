<?php
require_once __DIR__ . '/../model/periodemodel.php';
class PeriodeController {
    private $model;
    public function __construct($conn) { $this->model = new periodemodel($conn); }

    public function insert() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) { echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']); return; }
        echo $this->model->insert($data)
            ? json_encode(['status' => 'success', 'message' => 'Periode berhasil ditambahkan'])
            : json_encode(['status' => 'error', 'message' => 'Gagal menambahkan periode']);
    }
}
?>