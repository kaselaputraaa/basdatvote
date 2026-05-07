<?php
include 'koneksi.php';
session_start();

// ambil dari login nanti ya
$id_siswa = $_SESSION['id_siswa'];
$id_kandidat = $_POST['id_kandidat'];

// 🔍 CEK SUDAH VOTING BELUM
$cek = mysqli_query($conn, "SELECT * FROM t_vote WHERE id_siswa='$id_siswa'");

if (mysqli_num_rows($cek) > 0) {
    echo "Kamu sudah voting!";
} else {
    // ✅ SIMPAN DATA
    $query = "INSERT INTO t_vote (id_siswa, id_kandidat) 
              VALUES ('$id_siswa', '$id_kandidat')";

    if (mysqli_query($conn, $query)) {
        echo "Voting berhasil!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>