<?php
// ============================================================
// FILE   : vot.php
// FIX    : - Hapus hardcode id_siswa = 1
//          - Ambil id_user dari SESSION
//          - Ganti raw query → prepared statement (cegah SQL Injection)
//          - Tambah auth check + kolom jenis & id_periode
// ============================================================

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// BUG FIX: dulu hardcode $id_siswa = 1 — sekarang ambil dari SESSION
$id_user = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;

if (!$id_user) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Anda harus login terlebih dahulu.']);
    exit;
}

$id_kandidat = (int)($_GET['id'] ?? 0);
$jenis       = strtoupper(trim($_GET['jenis'] ?? ''));

if (!$id_kandidat || !in_array($jenis, ['OSIS', 'MPK'], true)) {
    echo json_encode(['status' => false, 'message' => 'Parameter tidak valid.']);
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

// BUG FIX: Insert dengan prepared statement, bukan raw string
$stmt = $conn->prepare(
    "INSERT INTO t_vote (id_user, id_kandidat, jenis, id_periode) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param('iisi', $id_user, $id_kandidat, $jenis, $id_periode);

if ($stmt->execute()) {
    $stmt->close();
    // Redirect ke halaman utama setelah vote
    header("Location: index.html");
} else {
    $stmt->close();
    // errno 1062 = duplicate entry (sudah pernah vote)
    if ($conn->errno === 1062) {
        echo json_encode(['status' => false, 'message' => 'Anda sudah voting untuk kategori ini.']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Gagal menyimpan vote.']);
    }
}
