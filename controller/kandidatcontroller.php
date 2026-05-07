<?php
require_once __DIR__ . '/../model/kandidatmodel.php';

class KandidatController {
    private $model;

    public function __construct($conn) {
        $this->model = new kandidatmodel($conn);
    }

    public function insert() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            echo json_encode(['status' => 'error', 'message' => 'JSON lu rusak']);
            return;
        }

        if (isset($data[0])) {
            $berhasil = $this->model->insertBanyak($data);
            echo json_encode([
                'status' => 'success',
                'message' => "$berhasil kandidat berhasil ditambahkan"
            ]);
        } 
        else {
            $result = $this->model->insert($data);
            echo json_encode([
                'status' => $result ? 'success' : 'error',
                'message' => $result ? 'kandidat masuk' : 'gagal'
            ]);
        }
    }
}
?>