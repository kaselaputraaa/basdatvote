<?php
// ============================================================
// FILE   : koneksi.php
// FUNGSI : Wrapper koneksi lama — arahkan ke config/database.php
// FIX    : File ini dulu berisi login langsung dengan SQL Injection.
//          Sekarang hanya menyediakan $conn agar file lama yang
//          masih include koneksi.php tidak error.
//          Login sesungguhnya ada di controller/LoginController.php
// ============================================================

require_once __DIR__ . '/config/database.php';
// $conn sudah tersedia dari database.php
