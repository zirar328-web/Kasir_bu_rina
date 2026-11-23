
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

// Ambil seluruh riwayat transaksi
$query = mysqli_query(
    $koneksi,
    "SELECT transaksi.*, users.nama AS nama_kasir
     FROM transaksi
     LEFT JOIN users ON transaksi.user_id = users.id
     ORDER BY transaksi.id DESC"
);

if (!$query) {
    die("Gagal mengambil data: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Transaksi - RINA POS</title>

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
    gap: 15px;
}

h2 {
    color: #543b2b;
}

.back {
    background: #543b2b;
    color: white;
    text-decoration: none;
    padding: 11px 16px;
    border-radius: 7px;
}

.panel {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #0000000d;
}

.panel h3 {
    color: #543b2b;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
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

.detail {
    display: inline-block;
    padding: 7px 11px;
    background: #79543a;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

.empty {
    padding: 20px;
    text-align: center;
    color: #888;
}

@media(max-width: 750px) {
    .table-wrap {
        overflow-x: auto;
    }

    table {
        min-width: 850px;
    }
}
</style>
</head>

<body>

<div class="container">

    <div class="header">
        <div>
            <h2>RINA POS</h2>
            <p>Riwayat Transaksi Penjualan</p>
        </div>

        <a href="../index.php" class="back">
            ← Dashboard
        </a>
    </div>

    <div class="panel">

        <h3>Daftar Riwayat Transaksi</h3>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Transaksi</th>
                        <th>Tanggal</th>
                        <th>Kasir</th>
                        <th>Total</th>
                        <th>Bayar</th>
                        <th>Kembalian</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($query) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($query)): ?>

                    <tr>
                        <td><?= $no++ ?></td>

                        <td>
                            <?= htmlspecialchars($row['kode_transaksi']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['tanggal']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['nama_kasir'] ?? '-') ?>
                        </td>

                        <td><?= rupiah($row['total']) ?></td>

                        <td><?= rupiah($row['bayar']) ?></td>

                        <td><?= rupiah($row['kembalian']) ?></td>

                        <td>
                            <a class="detail"
                               href="detail_riwayat.php?id=<?= (int)$row['id'] ?>">
                                Detail
                            </a>
                        </td>
                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty">
                            Belum ada transaksi.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </div>

</div>

</body>
</html>
