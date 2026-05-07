<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'insert_kandidat':
        require_once 'controller/kandidatcontroller.php';
        (new KandidatController($conn))->insert();
        break;
    case 'insert_periode':
        require_once 'controller/periodecontroller.php';
        (new PeriodeController($conn))->insert();
        break;
    case 'insert_user':
        require_once 'controller/usercontroller.php';
        (new UserController($conn))->insert();
        break;
    case 'insert_voting':
        require_once 'controller/votingcontroller.php';
        (new VotingController($conn))->insert();
        break;
    case 'login':
        require_once 'controller/logincontroller.php';
        (new LoginController($conn))->login();
        break;
    case 'cek_vote':
        require_once 'controller/votingcontroller.php';
        (new VotingController($conn))->cekVote();
        break;
    case 'get_kandidat':
        require_once 'controller/votingcontroller.php';
        (new VotingController($conn))->getKandidat();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Action tidak ditemukan']);
}
?>