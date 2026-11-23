
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$pesan = '';
$tipe_pesan = 'error';

// TAMBAH BARANG KE KERANJANG
if (isset($_POST['tambah'])) {
    $id = (int) $_POST['barang_id'];
    $jumlah = max(1, (int) $_POST['jumlah']);

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id, stok FROM barang WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$barang) {
        $pesan = "Barang tidak ditemukan!";
    } elseif ($barang['stok'] < $_SESSION['keranjang'][$id] + $jumlah) {
        $pesan = "Jumlah melebihi stok yang tersedia!";
    } else {
        $_SESSION['keranjang'][$id] =
            ($_SESSION['keranjang'][$id] ?? 0) + $jumlah;

        header("Location: transaksi.php");
        exit;
    }
}

// HAPUS BARANG DARI KERANJANG
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    unset($_SESSION['keranjang'][$id]);

    header("Location: transaksi.php");
    exit;
}

// UPDATE JUMLAH KERANJANG
if (isset($_POST['update_keranjang'])) {
    foreach ($_POST['jumlah'] as $id => $jumlah) {
        $id = (int) $id;
        $jumlah = (int) $jumlah;

        if ($jumlah <= 0) {
            unset($_SESSION['keranjang'][$id]);
        } else {
            $_SESSION['keranjang'][$id] = $jumlah;
        }
    }

    header("Location: transaksi.php");
    exit;
}

// PROSES PEMBAYARAN
if (isset($_POST['checkout'])) {
    $bayar = (float) ($_POST['bayar'] ?? 0);

    if (empty($_SESSION['keranjang'])) {
        $pesan = "Keranjang masih kosong!";
    } elseif ($bayar < 0) {
        $pesan = "Pembayaran tidak valid!";
    } else {
        try {
            mysqli_begin_transaction($koneksi);

            $daftar = [];
            $total = 0;

            // Periksa stok dan harga terbaru
            foreach ($_SESSION['keranjang'] as $id => $jumlah) {
                $id = (int) $id;
                $jumlah = (int) $jumlah;

                $stmt = mysqli_prepare(
                    $koneksi,
                    "SELECT id, nama_barang, harga, stok
                     FROM barang WHERE id = ? FOR UPDATE"
                );

                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);

                $item = mysqli_fetch_assoc(
                    mysqli_stmt_get_result($stmt)
                );

                if (!$item || $jumlah <= 0 || $item['stok'] < $jumlah) {
                    throw new Exception(
                        "Stok barang tidak mencukupi. Periksa kembali keranjang."
                    );
                }

                $subtotal = $item['harga'] * $jumlah;
                $total += $subtotal;

                $item['jumlah'] = $jumlah;
                $item['subtotal'] = $subtotal;

                $daftar[] = $item;
            }

            if ($bayar < $total) {
                throw new Exception("Uang pembayaran masih kurang!");
            }

            $kembalian = $bayar - $total;

            $kode = 'TRX' . date('YmdHis') .
                    strtoupper(bin2hex(random_bytes(3)));

            $user_id = (int) $_SESSION['id'];

            // Simpan transaksi utama
            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO transaksi
                (kode_transaksi, total, bayar, kembalian, user_id)
                VALUES (?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sdddi",
                $kode,
                $total,
                $bayar,
                $kembalian,
                $user_id
            );

            mysqli_stmt_execute($stmt);

            $transaksi_id = mysqli_insert_id($koneksi);

            // Simpan detail dan kurangi stok
            foreach ($daftar as $item) {
                $barang_id = (int) $item['id'];
                $nama = $item['nama_barang'];
                $harga = (float) $item['harga'];
                $jumlah = (int) $item['jumlah'];
                $subtotal = (float) $item['subtotal'];

                $stmt = mysqli_prepare(
                    $koneksi,
                    "INSERT INTO detail_transaksi
                    (transaksi_id, barang_id, nama_barang,
                     harga, jumlah, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "iisdid",
                    $transaksi_id,
                    $barang_id,
                    $nama,
                    $harga,
                    $jumlah,
                    $subtotal
                );

                mysqli_stmt_execute($stmt);

                $stmt = mysqli_prepare(
                    $koneksi,
                    "UPDATE barang SET stok = stok - ?
                     WHERE id = ? AND stok >= ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "iii",
                    $jumlah,
                    $barang_id,
                    $jumlah
                );

                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) !== 1) {
                    throw new Exception("Gagal memperbarui stok barang.");
                }
            }

            mysqli_commit($koneksi);

            $_SESSION['struk'] = [
    'id' => $transaksi_id
];

$_SESSION['keranjang'] = [];
            $_SESSION['transaksi_sukses'] = [
                'kode' => $kode,
                'total' => $total,
                'bayar' => $bayar,
                'kembalian' => $kembalian
            ];

            header("Location: struk.php");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $pesan = $e->getMessage();
        }
    }
}

// PENCARIAN BARANG
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $kata = "%$cari%";

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM barang
         WHERE nama_barang LIKE ? OR kode_barang LIKE ?
         ORDER BY nama_barang"
    );

    mysqli_stmt_bind_param($stmt, "ss", $kata, $kata);
} else {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM barang ORDER BY nama_barang"
    );
}

mysqli_stmt_execute($stmt);
$barang_list = mysqli_stmt_get_result($stmt);

// AMBIL DATA KERANJANG
$keranjang = [];
$total_keranjang = 0;

foreach ($_SESSION['keranjang'] as $id => $jumlah) {
    $id = (int) $id;

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM barang WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($item) {
        $item['jumlah'] = (int) $jumlah;
        $item['subtotal'] = $item['harga'] * $jumlah;

        $total_keranjang += $item['subtotal'];
        $keranjang[] = $item;
    }
}

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

$transaksi_sukses = $_SESSION['transaksi_sukses'] ?? null;
unset($_SESSION['transaksi_sukses']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Transaksi - RINA POS</title>

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
    max-width: 1400px;
    margin: auto;
    padding: 25px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.header h2 {
    color: #543b2b;
}

.back {
    background: #543b2b;
    color: white;
    padding: 11px 16px;
    text-decoration: none;
    border-radius: 7px;
}

.layout {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 22px;
}

.panel {
    background: white;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 3px 12px #0000000d;
}

.panel h3 {
    color: #543b2b;
    margin-bottom: 18px;
}

.search {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

input {
    padding: 11px;
    border: 1px solid #ddd;
    border-radius: 7px;
    width: 100%;
}

button {
    border: none;
    cursor: pointer;
    border-radius: 7px;
    padding: 10px 15px;
    color: white;
    background: #79543a;
}

.search button {
    min-width: 75px;
}

.product-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.product {
    border: 1px solid #eee;
    padding: 17px;
    border-radius: 10px;
}

.product h4 {
    margin-bottom: 8px;
    color: #543b2b;
}

.product p {
    font-size: 13px;
    margin-bottom: 7px;
    color: #777;
}

.product strong {
    display: block;
    margin: 12px 0;
    color: #79543a;
}

.product form {
    display: flex;
    gap: 8px;
}

.product input {
    width: 65px;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.cart-table th,
.cart-table td {
    padding: 11px 5px;
    border-bottom: 1px solid #eee;
    text-align: left;
}

.cart-table th {
    color: #79543a;
}

.qty {
    width: 60px;
}

.remove {
    background: #b94b45;
    padding: 7px 9px;
    text-decoration: none;
    color: white;
    border-radius: 5px;
}

.update {
    margin-top: 12px;
    background: #777;
}

.summary {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid #eee;
}

.summary h2 {
    color: #543b2b;
    margin-bottom: 15px;
}

.summary label {
    display: block;
    margin: 12px 0 7px;
}

.pay {
    width: 100%;
    margin-top: 15px;
    padding: 14px;
    background: #543b2b;
    font-weight: bold;
}

.message {
    padding: 13px;
    margin-bottom: 18px;
    background: #ffe9e7;
    color: #a33;
    border-radius: 7px;
}

.success {
    background: #e4f5e8;
    color: #246b36;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 10px;
}

@media(max-width: 850px) {
    .layout {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 500px) {
    .product-list {
        grid-template-columns: 1fr;
    }

    .container {
        padding: 12px;
    }
}
</style>
</head>

<body>

<div class="container">

<div class="header">
    <div>
        <h2>RINA POS</h2>
        <p>Transaksi Penjualan</p>
    </div>

    <a href="../index.php" class="back">← Dashboard</a>
</div>

<?php if ($transaksi_sukses): ?>
<div class="success">
    <h3>Transaksi Berhasil!</h3>
    <p>Kode: <?= htmlspecialchars($transaksi_sukses['kode']) ?></p>
    <p>Total: <?= rupiah($transaksi_sukses['total']) ?></p>
    <p>Bayar: <?= rupiah($transaksi_sukses['bayar']) ?></p>
    <p>Kembalian: <?= rupiah($transaksi_sukses['kembalian']) ?></p>
</div>
<?php endif; ?>

<?php if ($pesan): ?>
<div class="message">
    <?= htmlspecialchars($pesan) ?>
</div>
<?php endif; ?>

<div class="layout">

<!-- DAFTAR BARANG -->
<section class="panel">

<h3>Daftar Barang</h3>

<form method="GET" class="search">
    <input type="text" name="cari"
        placeholder="Cari nama atau kode barang..."
        value="<?= htmlspecialchars($cari) ?>">
    <button type="submit">Cari</button>
</form>

<div class="product-list">

<?php if (mysqli_num_rows($barang_list) > 0): ?>

<?php while ($b = mysqli_fetch_assoc($barang_list)): ?>

<div class="product">

    <h4><?= htmlspecialchars($b['nama_barang']) ?></h4>

    <p>Kode: <?= htmlspecialchars($b['kode_barang']) ?></p>

    <p>Kategori: <?= htmlspecialchars($b['kategori']) ?></p>

    <p>Stok: <?= $b['stok'] ?></p>

    <strong><?= rupiah($b['harga']) ?></strong>

    <form method="POST">
        <input type="hidden" name="barang_id"
            value="<?= $b['id'] ?>">

        <input type="number" name="jumlah"
            min="1" max="<?= $b['stok'] ?>"
            value="1" required>

        <button type="submit" name="tambah"
            <?= $b['stok'] <= 0 ? 'disabled' : '' ?>>
            Tambah
        </button>
    </form>

</div>

<?php endwhile; ?>

<?php else: ?>

<p>Barang tidak ditemukan.</p>

<?php endif; ?>

</div>
</section>

<!-- KERANJANG -->
<section class="panel">

<h3>Keranjang Belanja</h3>

<?php if (!empty($keranjang)): ?>

<form method="POST">

<div style="overflow-x:auto">

<table class="cart-table">

<thead>
<tr>
    <th>Barang</th>
    <th>Harga</th>
    <th>Jumlah</th>
    <th>Subtotal</th>
    <th>Aksi</th>
</tr>
</thead>

<tbody>

<?php foreach ($keranjang as $item): ?>

<tr>
    <td><?= htmlspecialchars($item['nama_barang']) ?></td>

    <td><?= rupiah($item['harga']) ?></td>

    <td>
        <input class="qty" type="number"
            name="jumlah[<?= $item['id'] ?>]"
            min="0"
            value="<?= $item['jumlah'] ?>">
    </td>

    <td><?= rupiah($item['subtotal']) ?></td>

    <td>
        <a class="remove"
            href="transaksi.php?hapus=<?= $item['id'] ?>"
            onclick="return confirm('Hapus barang dari keranjang?')">
            X
        </a>
    </td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

<button type="submit" name="update_keranjang"
    class="update">
    Perbarui Keranjang
</button>

</form>

<div class="summary">

<h2>Total: <?= rupiah($total_keranjang) ?></h2>

<form method="POST">

<label>Uang Pembayaran (Rp)</label>

<input type="number" name="bayar"
    min="<?= $total_keranjang ?>"
    step="0.01"
    placeholder="Masukkan uang pembayaran"
    required>

<button type="submit" name="checkout"
    class="pay"
    onclick="return confirm('Proses transaksi sekarang?')">
    PROSES PEMBAYARAN
</button>

</form>

</div>

<?php else: ?>

<p>Keranjang masih kosong.</p>
<p style="margin-top:10px;color:#888">
Pilih barang dari daftar di sebelah kiri.
</p>

<?php endif; ?>

</section>

</div>
</div>

</body>
</html>
