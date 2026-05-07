<?php
include 'koneksi.php';

$id_kandidat = $_GET['id'];

// contoh sementara (belum pakai login)
$id_siswa = 1;

mysqli_query($conn, "
INSERT INTO t_vote (id_siswa, id_kandidat)
VALUES ('$id_siswa', '$id_kandidat')
");

header("Location: index.php");
?>