<?php
include 'koneksi.php';

$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

$labels = [];
$data_total = [];
$rekap = [];
$total_pelanggan = 0;
$total_pendapatan = 0;

if ($tanggal_mulai != '' && $tanggal_selesai != '') {

    if ($tanggal_mulai > $tanggal_selesai) {
        die("Tanggal mulai tidak boleh melebihi tanggal selesai.");
    }

    // Mengambil rekap penjualan harian
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

    while ($row = mysqli_fetch_assoc($hasil)) {
        $labels[] = $row['tanggal'];
        $data_total[] = $row['total_harian'];
        $rekap[] = $row;
    }

    // Menghitung pelanggan unik dan total pendapatan
    $query_ringkasan = mysqli_prepare($koneksi, "
        SELECT COUNT(DISTINCT pelanggan_id) AS jumlah_pelanggan,
               SUM(total) AS total_pendapatan
        FROM transaksi
        WHERE waktu_transaksi BETWEEN ? AND ?
    ");

    mysqli_stmt_bind_param(
        $query_ringkasan,
        "ss",
        $tanggal_mulai,
        $tanggal_selesai
    );

    mysqli_stmt_execute($query_ringkasan);

    $hasil_ringkasan = mysqli_stmt_get_result($query_ringkasan);
    $ringkasan = mysqli_fetch_assoc($hasil_ringkasan);

    $total_pelanggan = $ringkasan['jumlah_pelanggan'];
    $total_pendapatan = $ringkasan['total_pendapatan'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        h2 {
            color: #29485c;
            margin-bottom: 18px;
        }

        .panel {
            background: white;
            padding: 22px;
            border-radius: 8px;
            margin-bottom: 22px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .filter {
            display: flex;
            align-items: end;
            flex-wrap: wrap;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        input[type="date"] {
            padding: 9px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button, .excel {
            padding: 10px 16px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        button {
            background: #287b9b;
        }

        .excel {
            background: #217346;
        }

        .cetak {
            background: #64748b;
        }

        button:hover, .excel:hover {
            opacity: 0.85;
        }

        .aksi {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }

        .periode {
            color: #666;
            margin-top: -8px;
        }

        .grafik {
            width: 100%;
            max-width: 850px;
            margin: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border-bottom: 1px solid #e5e7eb;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #edf4f7;
            color: #29485c;
        }

        .ringkasan {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        .card {
            flex: 1;
            min-width: 220px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #287b9b;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .card p {
            color: #666;
            margin: 0 0 12px;
        }

        .angka {
            font-size: 23px;
            font-weight: bold;
            color: #29485c;
        }

        .kosong {
            text-align: center;
            color: #777;
        }

        @media print {
            body {
                background: white;
                padding: 10px;
            }

            .no-print {
                display: none !important;
            }

            .panel, .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="panel no-print">
        <h2>Filter Laporan Penjualan</h2>

        <form method="GET" class="filter">
            <div class="form-group">
                <label>Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai"
                    value="<?php echo htmlspecialchars($tanggal_mulai); ?>"
                    required>
            </div>

            <div class="form-group">
                <label>Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai"
                    value="<?php echo htmlspecialchars($tanggal_selesai); ?>"
                    required>
            </div>

            <button type="submit">Tampilkan</button>
        </form>

        <?php if ($tanggal_mulai != '' && $tanggal_selesai != '') { ?>
            <div class="aksi">
                <button type="button" class="cetak"
                    onclick="window.print()">Cetak</button>

                <a class="excel"
                    href="export_excel.php?tanggal_mulai=<?php echo urlencode($tanggal_mulai); ?>&tanggal_selesai=<?php echo urlencode($tanggal_selesai); ?>">
                    Export Excel
                </a>
            </div>
        <?php } ?>
    </div>

    <?php if ($tanggal_mulai != '' && $tanggal_selesai != '') { ?>

        <div class="panel">
            <h2>Laporan Penjualan</h2>

            <p class="periode">
                Periode:
                <?php echo htmlspecialchars($tanggal_mulai); ?>
                sampai
                <?php echo htmlspecialchars($tanggal_selesai); ?>
            </p>
        </div>

        <div class="panel">
            <h2>Grafik Penjualan Harian</h2>

            <?php if (count($labels) > 0) { ?>
                <div class="grafik">
                    <canvas id="grafikPenjualan"></canvas>
                </div>

                <script>
                    new Chart(document.getElementById('grafikPenjualan'), {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode($labels); ?>,
                            datasets: [{
                                label: 'Total Pendapatan',
                                data: <?php echo json_encode($data_total); ?>,
                                backgroundColor: '#287b9b',
                                borderRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                </script>
            <?php } else { ?>
                <p class="kosong">Tidak ada data penjualan pada periode tersebut.</p>
            <?php } ?>
        </div>

        <div class="panel">
            <h2>Rekapitulasi Penjualan</h2>

            <table>
                <tr>
                    <th>No</th>
                    <th>Total Pendapatan</th>
                    <th>Tanggal Transaksi</th>
                </tr>

                <?php if (count($rekap) > 0) { ?>
                    <?php $no = 1; ?>

                    <?php foreach ($rekap as $row) { ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                Rp <?php echo number_format(
                                    $row['total_harian'], 0, ',', '.'
                                ); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['tanggal']); ?></td>
                        </tr>
                    <?php } ?>

                <?php } else { ?>
                    <tr>
                        <td colspan="3" class="kosong">
                            Tidak ada data penjualan.
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>

        <h2>Ringkasan Penjualan</h2>

        <div class="ringkasan">
            <div class="card">
                <p>Jumlah Pelanggan Unik</p>
                <div class="angka">
                    <?php echo $total_pelanggan; ?> Orang
                </div>
            </div>

            <div class="card">
                <p>Total Pendapatan Keseluruhan</p>
                <div class="angka">
                    Rp <?php echo number_format(
                        $total_pendapatan, 0, ',', '.'
                    ); ?>
                </div>
            </div>
        </div>

    <?php } ?>

</div>

</body>
</html>