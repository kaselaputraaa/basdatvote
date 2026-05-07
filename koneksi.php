<?php
include 'koneksi.php';
session_start();

$username = $_POST['username'];
$password = $_POST['password'];

// cek ke database
$query = mysqli_query($conn, "SELECT * FROM m_user WHERE username='$username' AND password='$password'");

$data = mysqli_fetch_assoc($query);

if ($data) {
    
    // ✅ TARUH DI SINI (SETELAH LOGIN BERHASIL)
    $_SESSION['id_user'] = $data['id_user'];
    $_SESSION['role'] = $data['role'];

    echo "Login berhasil!";
    
    // arahkan ke halaman voting
    header("Location: voting.html");

} else {
    echo "Username atau password salah!";
}
?>