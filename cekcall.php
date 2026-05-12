<?php
// ============================================================
// FILE   : cekcall.php
// FIX    : - Tambah auth check (hanya admin)
//          - Ganti kolom id/nama → kolom asli dari vw_kandidat
//            (id_kandidat, nomor_urut, nama_ketua, nama_wakil, jenis)
//          - Escape output pakai htmlspecialchars() (cegah XSS)
// ============================================================

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

// BUG FIX: halaman ini hanya boleh diakses admin
require_role('admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kandidat</title>
</head>
<body>
<?php
// BUG FIX: kolom yang dipakai sesuai vw_kandidat yang ada di DB
$query = mysqli_query($conn, "SELECT * FROM vw_kandidat");

if (!$query) {
    die("Query error: " . htmlspecialchars(mysqli_error($conn)));
}

if (mysqli_num_rows($query) > 0) {
    echo "<table border='1'>
            <tr>
                <th>ID Kandidat</th>
                <th>No. Urut</th>
                <th>Nama Ketua</th>
                <th>Nama Wakil</th>
                <th>Jenis</th>
            </tr>";

    while ($row = mysqli_fetch_assoc($query)) {
        // BUG FIX: escape semua output untuk mencegah XSS
        echo "<tr>
                <td>" . htmlspecialchars($row['id_kandidat'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['nomor_urut']  ?? '') . "</td>
                <td>" . htmlspecialchars($row['nama_ketua']  ?? '') . "</td>
                <td>" . htmlspecialchars($row['nama_wakil']  ?? '') . "</td>
                <td>" . htmlspecialchars($row['jenis']       ?? '') . "</td>
              </tr>";
    }

    echo "</table>";
} else {
    echo "Tidak ada data kandidat.";
}
?>
</body>
</html>
