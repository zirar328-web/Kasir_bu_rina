
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

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID transaksi tidak valid.");
}

// Ambil data transaksi
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT transaksi.*, users.nama AS nama_kasir
     FROM transaksi
     LEFT JOIN users ON transaksi.user_id = users.id
     WHERE transaksi.id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$transaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$transaksi) {
    die("Transaksi tidak ditemukan.");
}

// Ambil rincian barang
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM detail_transaksi
     WHERE transaksi_id = ?
     ORDER BY id ASC"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$detail = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Transaksi - RINA POS</title>

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
    padding: 25px;
}

.container {
    max-width: 900px;
    margin: auto;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    gap: 12px;
}

h2, h3 {
    color: #543b2b;
}

.panel {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #0000000d;
    margin-bottom: 20px;
}

.info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 18px;
}

.info p {
    margin-bottom: 10px;
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

.summary {
    max-width: 350px;
    margin: 20px 0 0 auto;
}

.summary p {
    display: flex;
    justify-content: space-between;
    padding: 9px 0;
}

.total {
    font-weight: bold;
    color: #543b2b;
    border-top: 1px solid #ddd;
}

.back, .print {
    display: inline-block;
    padding: 11px 16px;
    border-radius: 7px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    color: white;
    background: #543b2b;
}

.print {
    background: #79543a;
}

@media(max-width: 600px) {
    .info {
        grid-template-columns: 1fr;
    }

    .table-wrap {
        overflow-x: auto;
    }

    table {
        min-width: 550px;
    }
}

@media print {
    body {
        background: white;
        padding: 0;
    }

    .back, .print {
        display: none;
    }

    .panel {
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
            <p>Detail Transaksi</p>
        </div>

        <a href="riwayat.php" class="back">← Kembali</a>
    </div>

    <div class="panel">

        <h3>Informasi Transaksi</h3>

        <div class="info">
            <div>
                <p><b>Kode Transaksi:</b></p>
                <p><?= htmlspecialchars($transaksi['kode_transaksi']) ?></p>

                <p><b>Tanggal:</b></p>
                <p><?= htmlspecialchars($transaksi['tanggal']) ?></p>
            </div>

            <div>
                <p><b>Nama Kasir:</b></p>
                <p><?= htmlspecialchars($transaksi['nama_kasir'] ?? '-') ?></p>
            </div>
        </div>

    </div>

    <div class="panel">

        <h3>Rincian Barang</h3>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $no = 1; ?>

                    <?php while ($item = mysqli_fetch_assoc($detail)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                        <td><?= rupiah($item['harga']) ?></td>
                        <td><?= (int)$item['jumlah'] ?></td>
                        <td><?= rupiah($item['subtotal']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="summary">
            <p class="total">
                <span>Total</span>
                <span><?= rupiah($transaksi['total']) ?></span>
            </p>

            <p>
                <span>Bayar</span>
                <span><?= rupiah($transaksi['bayar']) ?></span>
            </p>

            <p>
                <span>Kembalian</span>
                <span><?= rupiah($transaksi['kembalian']) ?></span>
            </p>
        </div>

        <div style="margin-top:25px; text-align:right">
            <button class="print" onclick="window.print()">
                Cetak Detail
            </button>
        </div>

    </div>

</div>

</body>
</html>
