<?php
include 'koneksi.php';

$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

if (
    $tanggal_mulai == '' ||
    $tanggal_selesai == '' ||
    $tanggal_mulai > $tanggal_selesai
) {
    die("Rentang tanggal tidak valid.");
}

$query = mysqli_prepare($koneksi, "
    SELECT DATE(waktu_transaksi) AS tanggal,
           SUM(total) AS total_harian
    FROM transaksi
    WHERE waktu_transaksi BETWEEN ? AND ?
    GROUP BY DATE(waktu_transaksi)
    ORDER BY tanggal ASC
");

mysqli_stmt_bind_param($query, "ss", $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($query);

$hasil = mysqli_stmt_get_result($query);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_penjualan.xls");
?>

<table border="1">
    <tr>
        <th>No</th>
        <th>Total Pendapatan</th>
        <th>Tanggal Transaksi</th>
    </tr>

    <?php
    $no = 1;

    while ($row = mysqli_fetch_assoc($hasil)) {
    ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td>Rp <?php echo number_format($row['total_harian'], 0, ',', '.'); ?></td>
            <td><?php echo $row['tanggal']; ?></td>
        </tr>
    <?php } ?>
</table>