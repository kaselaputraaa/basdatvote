<?php
// ============================================================
// FILE   : config/session.php
// FUNGSI : Konfigurasi session yang aman
// FIX    : SameSite=None WAJIB dibarengi Secure=1 di browser
//          modern. Untuk dev lokal (HTTP) pakai SameSite=Lax.
// ============================================================

// Cegah JavaScript membaca session cookie (proteksi XSS)
ini_set('session.cookie_httponly', 1);

// Cegah session fixation attack
ini_set('session.use_strict_mode', 1);

// BUG FIX: SameSite=None tanpa Secure tidak valid di Chrome/Firefox.
// Gunakan Lax untuk HTTP lokal. Ganti ke None + Secure=1 jika HTTPS.
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

if ($is_https) {
    ini_set('session.cookie_samesite', 'None');
    ini_set('session.cookie_secure', 1);
} else {
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', 0);
}

// Mulai session satu kali saja
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
