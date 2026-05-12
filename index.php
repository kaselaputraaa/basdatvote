<?php
// ============================================================
// FILE   : index.php
// FUNGSI : Router utama + Middleware guard akses
// FIX    : CORS header dikirim paling awal, support kedua origin
//          (127.0.0.1:5500 dari Live Server & localhost langsung)
// ============================================================

// ── CORS harus keluar SEBELUM output apapun ──────────────────
$allowed_origins = [
    'http://127.0.0.1:5500',
    'http://localhost',
    'http://localhost:5500',
    'http://127.0.0.1',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    // Fallback development — hapus baris ini di production
    header('Access-Control-Allow-Origin: http://127.0.0.1:5500');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight CORS — harus exit SEBELUM session_start()
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Load config ──────────────────────────────────────────────
require_once __DIR__ . '/config/session.php';   // session_start() aman
require_once __DIR__ . '/config/database.php';  // $conn
require_once __DIR__ . '/config/auth.php';       // fungsi guard

// ── Ambil action ─────────────────────────────────────────────
$action = trim($_GET['action'] ?? '');

// ── Tabel aturan akses ───────────────────────────────────────
$rules = [
    // Auth
    'login'           => 'public',
    'logout'          => 'public',
    'check_session'   => 'public',

    // Voting (user yang sudah login)
    'get_kandidat'    => 'any',
    'sp_get_kandidat' => 'any',   // alias untuk kompatibilitas
    'insert_voting'   => 'any',
    'cek_vote'        => 'any',

    // Admin only
    'siswa'           => 'admin',
    'guru'            => 'admin',
    'tambah_siswa'    => 'admin',
    'hapus_siswa'     => 'admin',
    'hasil_vote'      => 'admin',
    'insert_kandidat' => 'admin',
    'insert_periode'  => 'admin',
    'insert_user'     => 'admin',
];

// ── Cek action ada ───────────────────────────────────────────
if (!array_key_exists($action, $rules)) {
    http_response_code(404);
    echo json_encode(['status' => false, 'message' => "Action '{$action}' tidak ditemukan."]);
    exit;
}

// ── Terapkan middleware ──────────────────────────────────────
switch ($rules[$action]) {
    case 'any':
        require_login();
        break;
    case 'admin':
        require_role('admin');
        break;
    // 'public': tidak ada pengecekan
}

// ── Route ke controller ──────────────────────────────────────
switch ($action) {

    case 'login':
        require_once __DIR__ . '/controller/LoginController.php';
        (new LoginController($conn))->login();
        break;

    case 'logout':
        require_once __DIR__ . '/controller/LoginController.php';
        (new LoginController($conn))->logout();
        break;

    case 'check_session':
        require_once __DIR__ . '/controller/LoginController.php';
        (new LoginController($conn))->checkSession();
        break;

    case 'get_kandidat':
    case 'sp_get_kandidat':   // alias
        require_once __DIR__ . '/controller/VotingController.php';
        (new VotingController($conn))->getKandidat();
        break;

    case 'insert_voting':
        require_once __DIR__ . '/controller/VotingController.php';
        (new VotingController($conn))->insert();
        break;

    case 'cek_vote':
        require_once __DIR__ . '/controller/VotingController.php';
        (new VotingController($conn))->cekVote();
        break;

    case 'siswa':
        require_once __DIR__ . '/controller/SiswaController.php';
        (new SiswaController($conn))->getAll();
        break;

    case 'guru':
        require_once __DIR__ . '/controller/GuruController.php';
        (new GuruController($conn))->getAll();
        break;

    case 'tambah_siswa':
        require_once __DIR__ . '/controller/SiswaController.php';
        (new SiswaController($conn))->insert();
        break;

    case 'hapus_siswa':
        require_once __DIR__ . '/controller/SiswaController.php';
        (new SiswaController($conn))->delete();
        break;

    case 'hasil_vote':
        require_once __DIR__ . '/controller/HasilController.php';
        (new HasilController($conn))->get();
        break;

    case 'insert_kandidat':
        require_once __DIR__ . '/controller/KandidatController.php';
        (new KandidatController($conn))->insert();
        break;

    case 'insert_periode':
        require_once __DIR__ . '/controller/PeriodeController.php';
        (new PeriodeController($conn))->insert();
        break;

    case 'insert_user':
        require_once __DIR__ . '/controller/UserController.php';
        (new UserController($conn))->insert();
        break;
}
