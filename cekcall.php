<?php 
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kandidat</title>
</head>
<body>
<?php
$query = mysqli_query($conn, "select * from vw_kandidat");

// cek error query
if (!$query) {
    die("Query error: " . mysqli_error($conn));
}

// cek apakah ada data
if (mysqli_num_rows($query) > 0) {
    echo "<table border='1'>
            <tr>
                <th>ID</th>
                <th>Nama</th>
            </tr>";

    while ($row = mysqli_fetch_assoc($query)) {
        echo "<tr>
                <td>".$row['id']."</td>
                <td>".$row['nama']."</td>
              </tr>";
    }

    echo "</table>";
} else {
    echo "No data";
}
?>
</body>
</html>