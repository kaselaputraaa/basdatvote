<?php
require_once __DIR__ . '/../model/votingmodel.php';

class VotingController {
    private $model;

    public function __construct($conn) {
        $this->model = new VotingModel($conn);
    }

    public function insert() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['id_user']) || empty($data['id_kandidat']) || empty($data['jenis'])) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
            return;
        }

        $data['jenis'] = strtoupper($data['jenis']); // pastikan OSIS / MPK

        $ok = $this->model->insert($data);
        echo $ok
            ? json_encode(['status' => 'success', 'message' => 'Voting berhasil'])
            : json_encode(['status' => 'error',   'message' => 'Gagal voting. Mungkin sudah memilih untuk jenis ini.']);
    }

    // ✅ cekVote — huruf V kapital agar cocok dengan index.php
    public function cekVote() {
        $id_user = $_GET['id_user'] ?? null;
        if (!$id_user) {
            echo json_encode(['sudah' => false, 'osis' => false, 'mpk' => false]);
            return;
        }
        $status = $this->model->statusVote($id_user);
        echo json_encode([
            'sudah' => $status['osis'] && $status['mpk'],
            'osis'  => $status['osis'],
            'mpk'   => $status['mpk'],
        ]);
    }

    // ✅ getKandidat — bisa filter by jenis (?jenis=OSIS atau ?jenis=MPK)
    public function getKandidat() {
        $jenis = $_GET['jenis'] ?? null;
        $data  = $this->model->get_kandidat($jenis);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }
}
?>
