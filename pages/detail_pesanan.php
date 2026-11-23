
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID pesanan tidak valid.");
}

$stmt = mysqli_prepare($koneksi, "
    SELECT * FROM pesanan_pelanggan WHERE id = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$pesanan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pesanan) {
    die("Pesanan tidak ditemukan.");
}

$stmt = mysqli_prepare($koneksi, "
    SELECT * FROM detail_pesanan_pelanggan
    WHERE pesanan_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$detail = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Pesanan</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    padding: 25px;
    color: #333;
}
.container {
    max-width: 950px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
}
h1 { text-align: center; }
.info {
    background: #f7f7f7;
    padding: 18px;
    border-radius: 8px;
    line-height: 1.9;
    margin: 20px 0;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 13px;
    border-bottom: 1px solid #ddd;
    text-align: center;
}
th {
    background: #16834a;
    color: white;
}
.total {
    text-align: right;
    font-size: 22px;
    font-weight: bold;
    margin-top: 20px;
}
.btn {
    display: inline-block;
    margin-top: 20px;
    padding: 11px 17px;
    background: #555;
    color: white;
    text-decoration: none;
    border-radius: 7px;
}
</style>
</head>

<body>

<div class="container">

<h1>Detail Pesanan Pelanggan</h1>

<div class="info">
    <strong>Kode Pesanan:</strong>
    <?php echo htmlspecialchars($pesanan['kode_pesanan']); ?><br>

    <strong>Nama Pelanggan:</strong>
    <?php echo htmlspecialchars($pesanan['nama_pelanggan']); ?><br>

    <strong>Nomor HP:</strong>
    <?php echo htmlspecialchars($pesanan['no_hp']); ?><br>

    <strong>Alamat:</strong>
    <?php echo nl2br(htmlspecialchars($pesanan['alamat'])); ?><br>

    <strong>Tanggal:</strong>
    <?php echo htmlspecialchars($pesanan['tanggal_pesanan']); ?><br>

    <strong>Status:</strong>
    <?php echo htmlspecialchars($pesanan['status']); ?>
</div>

<h3>Barang yang Dipesan</h3>

<table>
<thead>
<tr>
    <th>No.</th>
    <th>Nama Barang</th>
    <th>Harga</th>
    <th>Jumlah</th>
    <th>Subtotal</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($detail) > 0) : ?>

<?php $no = 1; ?>

<?php while ($barang = mysqli_fetch_assoc($detail)) : ?>
<tr>
    <td><?php echo $no++; ?></td>
    <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
    <td>Rp <?php echo number_format($barang['harga'], 0, ',', '.'); ?></td>
    <td><?php echo (int)$barang['jumlah']; ?></td>
    <td>Rp <?php echo number_format($barang['subtotal'], 0, ',', '.'); ?></td>
</tr>
<?php endwhile; ?>

<?php else : ?>

<tr>
    <td colspan="5">Tidak ada detail barang.</td>
</tr>

<?php endif; ?>

</tbody>
</table>

<div class="total">
    Total: Rp <?php echo number_format($pesanan['total'], 0, ',', '.'); ?>
</div>

<a href="pesanan.php" class="btn">Kembali ke Pesanan</a>

</div>
</body>
</html>
