<?php
// migrate_passwords.php — Jalankan SEKALI, lalu HAPUS
// http://localhost/voting2026/migrate_passwords.php

require_once 'config/database.php';

$is_cli       = php_sapi_name() === 'cli';
$is_localhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
if (!$is_cli && !$is_localhost) die('Akses ditolak.');

$result  = $conn->query("SELECT id_user, passwordd FROM m_user");
$updated = 0; $skipped = 0;

while ($row = $result->fetch_assoc()) {
    if (str_starts_with($row['passwordd'], '$2y$')) {
        $skipped++;
        echo "User #{$row['id_user']}: sudah di-hash.\n";
        continue;
    }
    $hashed = password_hash($row['passwordd'], PASSWORD_BCRYPT);
    $stmt   = $conn->prepare("UPDATE m_user SET passwordd=? WHERE id_user=?");
    $stmt->bind_param('si', $hashed, $row['id_user']);
    if ($stmt->execute()) { $updated++; echo "User #{$row['id_user']}: di-hash.\n"; }
    else echo "User #{$row['id_user']}: GAGAL.\n";
}

echo "\nSelesai. $updated di-hash, $skipped dilewati.\nHAPUS FILE INI SEKARANG!\n";
