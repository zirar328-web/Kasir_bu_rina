
<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// Ubah jumlah barang
if (isset($_POST['ubah_jumlah'])) {
    $id = (int) $_POST['id_barang'];
    $jumlah = (int) $_POST['jumlah'];

    $stmt = mysqli_prepare($koneksi, "
        SELECT stok FROM barang
        WHERE id = ? AND status_barang = 'aktif'
    ");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($data && $jumlah > 0 && $jumlah <= (int)$data['stok']) {
        $_SESSION['keranjang'][$id] = $jumlah;
    } elseif ($jumlah <= 0) {
        unset($_SESSION['keranjang'][$id]);
    }

    header("Location: keranjang.php");
    exit;
}

// Hapus barang
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    unset($_SESSION['keranjang'][$id]);

    header("Location: keranjang.php");
    exit;
}

$total = 0;
$daftarBarang = [];

foreach ($_SESSION['keranjang'] as $id => $jumlah) {
    $stmt = mysqli_prepare($koneksi, "
        SELECT * FROM barang
        WHERE id = ? AND status_barang = 'aktif'
    ");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$barang || $barang['stok'] <= 0) {
        unset($_SESSION['keranjang'][$id]);
        continue;
    }

    if ($jumlah > $barang['stok']) {
        $jumlah = (int)$barang['stok'];
        $_SESSION['keranjang'][$id] = $jumlah;
    }

    $subtotal = $barang['harga'] * $jumlah;
    $total += $subtotal;

    $daftarBarang[] = [
        'barang' => $barang,
        'jumlah' => $jumlah,
        'subtotal' => $subtotal
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Keranjang Belanja</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    padding: 25px;
}
.container {
    max-width: 950px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
}
h1 { text-align: center; }
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: center;
}
input[type=number] {
    width: 60px;
    padding: 7px;
}
button, .btn {
    border: none;
    padding: 9px 13px;
    border-radius: 6px;
    color: white;
    background: #16834a;
    cursor: pointer;
    text-decoration: none;
}
.hapus { background: #d93636; }
.total {
    text-align: right;
    font-size: 21px;
    font-weight: bold;
    margin-top: 20px;
}
.kembali {
    display: inline-block;
    margin-top: 20px;
    background: #555;
}
.checkout { background: #16834a; }
</style>
</head>

<body>

<div class="container">

<h1>Keranjang Belanja</h1>

<?php if (empty($daftarBarang)) : ?>

    <p>Keranjang masih kosong.</p>
    <a href="menu.php" class="btn kembali">Kembali ke Menu</a>

<?php else : ?>

<table>
<thead>
<tr>
    <th>Nama Barang</th>
    <th>Harga</th>
    <th>Jumlah</th>
    <th>Subtotal</th>
    <th>Aksi</th>
</tr>
</thead>

<tbody>
<?php foreach ($daftarBarang as $item) :
    $barang = $item['barang'];
?>
<tr>
    <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>

    <td>
        Rp <?php echo number_format($barang['harga'], 0, ',', '.'); ?>
    </td>

    <td>
        <form method="POST">
            <input type="hidden" name="id_barang"
                   value="<?php echo (int)$barang['id']; ?>">
            <input type="number" name="jumlah"
                   value="<?php echo (int)$item['jumlah']; ?>"
                   min="0"
                   max="<?php echo (int)$barang['stok']; ?>">
            <button type="submit" name="ubah_jumlah">Ubah</button>
        </form>
    </td>

    <td>
        Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
    </td>

    <td>
        <a class="btn hapus"
           href="keranjang.php?hapus=<?php echo (int)$barang['id']; ?>"
           onclick="return confirm('Hapus barang ini dari keranjang?')">
           Hapus
        </a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="total">
    Total: Rp <?php echo number_format($total, 0, ',', '.'); ?>
</div>

<a href="menu.php" class="btn kembali">Tambah Barang</a>
<a href="checkout.php" class="btn checkout">Lanjut Checkout</a>

<?php endif; ?>

</div>
</body>
</html>
