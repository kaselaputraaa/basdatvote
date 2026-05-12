<?php
// ============================================================
// FILE   : controller/VotingController.php
// FUNGSI : Handle insert vote, cek status vote, ambil kandidat
// ============================================================

require_once __DIR__ . '/../model/VotingModel.php';

class VotingController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new VotingModel($conn);
    }

    // ----------------------------------------------------------
    // POST /index.php?action=insert_voting
    // Body JSON: { "id_kandidat": 1, "jenis": "OSIS" }
    // id_user diambil dari SESSION (tidak bisa dimanipulasi)
    // ----------------------------------------------------------
    public function insert(): void
    {
        $id_user = current_user_id();

        if (!$id_user) {
            http_response_code(401);
            echo json_encode(['status' => false, 'message' => 'Sesi tidak valid.']);
            return;
        }

        $body        = json_decode(file_get_contents('php://input'), true);
        $id_kandidat = (int) ($body['id_kandidat'] ?? 0);
        $jenis       = strtoupper(trim($body['jenis'] ?? ''));

        if (!$id_kandidat || !in_array($jenis, ['OSIS', 'MPK'], true)) {
            echo json_encode(['status' => false, 'message' => 'Data tidak valid.']);
            return;
        }

        $result = $this->model->insert([
            'id_user'     => $id_user,
            'id_kandidat' => $id_kandidat,
            'jenis'       => $jenis,
        ]);

        echo json_encode($result);
    }

    // ----------------------------------------------------------
    // GET /index.php?action=cek_vote
    // Kembalikan status sudah/belum vote per jenis
    // ----------------------------------------------------------
    public function cekVote(): void
    {
        $id_user = current_user_id();

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

    // ----------------------------------------------------------
    // GET /index.php?action=get_kandidat&jenis=OSIS
    // jenis bisa OSIS, MPK, atau kosong (ambil semua)
    // ----------------------------------------------------------
    public function getKandidat(): void
    {
        $jenis = $_GET['jenis'] ?? null;
        $data  = $this->model->getKandidat($jenis);

        echo json_encode(['status' => true, 'data' => $data]);
    }
}
