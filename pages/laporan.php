
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

$tanggal_awal = $_GET['awal'] ?? '';
$tanggal_akhir = $_GET['akhir'] ?? '';

$filter = '';
if ($tanggal_awal && $tanggal_akhir) {
    $filter = " WHERE DATE(tanggal) BETWEEN '" .
        mysqli_real_escape_string($koneksi, $tanggal_awal) .
        "' AND '" .
        mysqli_real_escape_string($koneksi, $tanggal_akhir) . "'";
}

// Ringkasan transaksi
$ringkasan = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS jumlah_transaksi,
            COALESCE(SUM(total), 0) AS pendapatan
     FROM transaksi $filter"
);

$data_ringkasan = mysqli_fetch_assoc($ringkasan);

// Jumlah barang terjual
$filter_detail = '';
if ($tanggal_awal && $tanggal_akhir) {
    $filter_detail = " WHERE DATE(t.tanggal) BETWEEN '" .
        mysqli_real_escape_string($koneksi, $tanggal_awal) .
        "' AND '" .
        mysqli_real_escape_string($koneksi, $tanggal_akhir) . "'";
}

$barang = mysqli_query(
    $koneksi,
    "SELECT COALESCE(SUM(d.jumlah), 0) AS jumlah_barang
     FROM detail_transaksi d
     JOIN transaksi t ON d.transaksi_id = t.id
     $filter_detail"
);

$data_barang = mysqli_fetch_assoc($barang);

// Daftar transaksi
$daftar = mysqli_query(
    $koneksi,
    "SELECT * FROM transaksi $filter ORDER BY id DESC"
);

if (!$ringkasan || !$barang || !$daftar) {
    die("Gagal mengambil laporan: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Penjualan - RINA POS</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f5f2ee;
    color: #333;
}

.container {
    max-width: 1200px;
    margin: auto;
    padding: 25px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

h2, h3 {
    color: #543b2b;
}

.back {
    background: #543b2b;
    color: white;
    padding: 11px 16px;
    border-radius: 7px;
    text-decoration: none;
}

.panel {
    background: white;
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 22px;
    box-shadow: 0 3px 12px #0000000d;
}

.filter {
    display: flex;
    align-items: end;
    gap: 12px;
    flex-wrap: wrap;
}

.filter div {
    flex: 1;
    min-width: 150px;
}

label {
    display: block;
    margin-bottom: 8px;
}

input {
    padding: 11px;
    border: 1px solid #ddd;
    border-radius: 7px;
    width: 100%;
}

button {
    padding: 11px 17px;
    background: #79543a;
    color: white;
    border: none;
    border-radius: 7px;
    cursor: pointer;
}

.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}

.card {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #0000000d;
}

.card p {
    color: #777;
    margin-bottom: 12px;
}

.card h2 {
    font-size: 23px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 18px;
}

th, td {
    padding: 13px 10px;
    border-bottom: 1px solid #eee;
    text-align: left;
}

th {
    background: #543b2b;
    color: white;
}

@media(max-width: 700px) {
    .cards {
        grid-template-columns: 1fr;
    }
}

@media print {
    body {
        background: white;
    }

    .back, .filter, .no-print {
        display: none;
    }

    .panel, .card {
        box-shadow: none;
    }
}
</style>
</head>

<body>

<div class="container">

    <div class="header">
        <div>
            <h2>RINA POS</h2>
            <p>Laporan Penjualan</p>
        </div>

        <a href="../index.php" class="back">← Dashboard</a>
    </div>

    <div class="panel">
        <h3>Filter Tanggal</h3>

        <form method="GET" class="filter">
            <div>
                <label>Tanggal Awal</label>
                <input type="date" name="awal"
                    value="<?= htmlspecialchars($tanggal_awal) ?>">
            </div>

            <div>
                <label>Tanggal Akhir</label>
                <input type="date" name="akhir"
                    value="<?= htmlspecialchars($tanggal_akhir) ?>">
            </div>

            <button type="submit">Tampilkan</button>
            <a href="laporan.php">Reset</a>
        </form>
    </div>

    <div class="cards">
        <div class="card">
            <p>Total Transaksi</p>
            <h2><?= (int)$data_ringkasan['jumlah_transaksi'] ?></h2>
        </div>

        <div class="card">
            <p>Total Pendapatan</p>
            <h2><?= rupiah($data_ringkasan['pendapatan']) ?></h2>
        </div>

        <div class="card">
            <p>Barang Terjual</p>
            <h2><?= (int)$data_barang['jumlah_barang'] ?> Barang</h2>
        </div>
    </div>

    <div class="panel">
        <h3>Daftar Penjualan</h3>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Transaksi</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Bayar</th>
                        <th>Kembalian</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (mysqli_num_rows($daftar) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($daftar)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['kode_transaksi']) ?></td>
                        <td><?= htmlspecialchars($row['tanggal']) ?></td>
                        <td><?= rupiah($row['total']) ?></td>
                        <td><?= rupiah($row['bayar']) ?></td>
                        <td><?= rupiah($row['kembalian']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center">
                            Belum ada data penjualan.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="no-print" style="text-align:right;margin-top:20px">
            <button onclick="window.print()">Cetak Laporan</button>
        </div>
    </div>

</div>

</body>
</html>
