<?php
require_once __DIR__ . '/../model/votingmodel.php';

class VotingController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new VotingModel($conn);
    }

    public function insert() {

    $raw = file_get_contents(
        'php://input'
    );

    $data = json_decode(
        $raw,
        true
    );

    if (
        !$data ||
        empty($data['id_user']) ||
        empty($data['id_kandidat']) ||
        empty($data['jenis'])
    ) {

        echo json_encode([
            'status' => 'error',
            'message' => 'Data tidak valid'
        ]);

        return;
    }

    $data['jenis'] =
        strtoupper($data['jenis']);

    $ok = $this->model->insert($data);

    echo json_encode([

        'status' =>
            $ok
            ? 'success'
            : 'error',

        'message' =>
            $ok
            ? 'Voting berhasil'
            : 'Voting gagal'

    ]);
}

    // ✅ cekVote — huruf V kapital agar cocok dengan index.php
    public function cekVote()
    {
        $id_user = $_GET['id_user'] ?? null;
        if (!$id_user) {
            echo json_encode(['sudah' => false, 'osis' => false, 'mpk' => false]);
            return;
        }
        $status = $this->model->statusVote($id_user);
        echo json_encode([
            'sudah' => $status['osis'] && $status['mpk'],
            'osis' => $status['osis'],
            'mpk' => $status['mpk'],
        ]);
    }

    // ✅ getKandidat — bisa filter by jenis (?jenis=OSIS atau ?jenis=MPK)
    public function getKandidat()
    {
        $jenis = $_GET['jenis'] ?? null;
        $data = $this->model->get_kandidat($jenis);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }
}
?>