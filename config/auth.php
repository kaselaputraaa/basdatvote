<?php
// ============================================================
// FILE   : config/auth.php
// FUNGSI : Fungsi-fungsi guard untuk autentikasi & otorisasi
// ============================================================

/**
 * Cek apakah user sudah login.
 */
function is_logged_in(): bool
{
    return isset($_SESSION['id_user']) && $_SESSION['id_user'] > 0;
}

/**
 * Paksa user harus sudah login.
 * Kalau belum → kirim JSON 401 dan stop eksekusi.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode([
            'status'  => false,
            'message' => 'Anda harus login terlebih dahulu.',
        ]);
        exit;
    }
}

/**
 * Paksa user harus punya role tertentu.
 * Otomatis memanggil require_login() juga.
 *
 * Contoh: require_role('admin')
 */
function require_role(string $role): void
{
    require_login();

    if (($_SESSION['roles'] ?? '') !== $role) {
        http_response_code(403);
        echo json_encode([
            'status'  => false,
            'message' => 'Akses ditolak. Role Anda tidak diizinkan.',
        ]);
        exit;
    }
}

/**
 * Ambil id_user dari SESSION (bukan dari request client).
 * Ini mencegah user vote atas nama user lain.
 */
function current_user_id(): ?int
{
    return isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : null;
}

/**
 * Ambil role user yang sedang login dari SESSION.
 */
function current_user_role(): ?string
{
    return $_SESSION['roles'] ?? null;
}
