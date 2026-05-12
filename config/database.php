<?php
// ============================================================
// FILE   : config/database.php
// FUNGSI : Koneksi ke database MySQL menggunakan MySQLi OOP
// ============================================================

$host = 'localhost';
$user = 'root';
$pass = '';        // XAMPP default: kosong
$db   = 'osis';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'status'  => false,
        'message' => 'Koneksi database gagal.',
        
    ]));
}

// Charset utf8mb4: mendukung semua karakter Unicode termasuk emoji
$conn->set_charset('utf8mb4');
