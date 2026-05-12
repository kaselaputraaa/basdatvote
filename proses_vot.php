<?php
// ============================================================
// FILE   : proses_vot.php
// FIX    : - Ganti $_SESSION['id_siswa'] → $_SESSION['id_user']
//            (sesuai struktur session LoginController)
//          - Ganti raw query → prepared statement (cegah SQL Injection)
//          - Tambah kolom jenis & id_periode (sesuai struktur t_vote baru)
//          - Tambah session_start() lewat config/session.php
// ============================================================

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Ambil id_user dari SESSION (bukan dari POST/GET)
$id_user = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;

if (!$id_user) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Anda harus login terlebih dahulu.']);
    exit;
}

$id_kandidat = (int)($_POST['id_kandidat'] ?? 0);
$jenis       = strtoupper(trim($_POST['jenis'] ?? ''));

if (!$id_kandidat || !in_array($jenis, ['OSIS', 'MPK'], true)) {
    echo json_encode(['status' => false, 'message' => 'Data tidak valid.']);
    exit;
}

// Ambil periode aktif
$r          = $conn->query("SELECT id_periode FROM m_periode WHERE is_active='Y' LIMIT 1");
$periode    = $r ? $r->fetch_assoc() : null;
$id_periode = $periode ? (int)$periode['id_periode'] : 0;

if (!$id_periode) {
    echo json_encode(['status' => false, 'message' => 'Tidak ada periode voting aktif.']);
    exit;
}

// BUG FIX: Cek sudah voting menggunakan prepared statement
$cek = $conn->prepare(
    "SELECT id_vote FROM t_vote WHERE id_user = ? AND jenis = ? AND id_periode = ?"
);
$cek->bind_param('isi', $id_user, $jenis, $id_periode);
$cek->execute();
$cek->store_result();

if ($cek->num_rows > 0) {
    $cek->close();
    echo json_encode(['status' => false, 'message' => 'Kamu sudah voting untuk kategori ini!']);
    exit;
}
$cek->close();

// BUG FIX: Insert menggunakan prepared statement
$stmt = $conn->prepare(
    "INSERT INTO t_vote (id_user, id_kandidat, jenis, id_periode) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param('iisi', $id_user, $id_kandidat, $jenis, $id_periode);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['status' => true, 'message' => 'Voting berhasil!']);
} else {
    $stmt->close();
    echo json_encode(['status' => false, 'message' => 'Terjadi kesalahan. Coba lagi.']);
}
