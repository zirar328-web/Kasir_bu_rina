
<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['keranjang']) || empty($_SESSION['keranjang'])) {
    header("Location: menu.php");
    exit;
}

$pesan = '';
$error = '';
$daftarBarang = [];
$total = 0;

// Ambil barang dari keranjang dan cek stok terbaru
foreach ($_SESSION['keranjang'] as $id => $jumlah) {
    $id = (int)$id;
    $jumlah = (int)$jumlah;

    $stmt = mysqli_prepare($koneksi, "
        SELECT id, nama_barang, harga, stok
        FROM barang
        WHERE id = ? AND status_barang = 'aktif'
    ");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$barang || $jumlah <= 0 || $jumlah > (int)$barang['stok']) {
        $error = "Stok salah satu barang sudah berubah. Silakan periksa kembali keranjang.";
        break;
    }

    $subtotal = $barang['harga'] * $jumlah;
    $total += $subtotal;

    $daftarBarang[] = [
        'barang' => $barang,
        'jumlah' => $jumlah,
        'subtotal' => $subtotal
    ];
}

// Proses simpan pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_pesanan'])) {
    $nama = trim($_POST['nama_pelanggan'] ?? '');
    $noHp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($nama === '' || $noHp === '' || $alamat === '') {
        $error = "Semua data pelanggan wajib diisi.";
    } elseif (strlen($nama) > 100 || strlen($noHp) > 20) {
        $error = "Nama atau nomor HP terlalu panjang.";
    } elseif (!empty($error)) {
        // Pertahankan pesan pemeriksaan stok
    } else {
        mysqli_begin_transaction($koneksi);

        try {
            // Kunci dan periksa stok setiap barang lagi
            $totalFinal = 0;
            $barangFinal = [];

            foreach ($_SESSION['keranjang'] as $id => $jumlah) {
                $id = (int)$id;
                $jumlah = (int)$jumlah;

                $stmt = mysqli_prepare($koneksi, "
                    SELECT id, nama_barang, harga, stok
                    FROM barang
                    WHERE id = ? AND status_barang = 'aktif'
                    FOR UPDATE
                ");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                if (!$barang || $jumlah <= 0 || $jumlah > (int)$barang['stok']) {
                    throw new Exception("Stok barang tidak mencukupi. Silakan periksa keranjang.");
                }

                $subtotal = $barang['harga'] * $jumlah;
                $totalFinal += $subtotal;

                $barangFinal[] = [
                    'barang' => $barang,
                    'jumlah' => $jumlah,
                    'subtotal' => $subtotal
                ];
            }

            $kode = 'ORD-' . date('YmdHis') . '-' . random_int(100, 999);

            $stmt = mysqli_prepare($koneksi, "
                INSERT INTO pesanan_pelanggan
                (kode_pesanan, nama_pelanggan, no_hp, alamat, total)
                VALUES (?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param(
                $stmt,
                "ssssd",
                $kode,
                $nama,
                $noHp,
                $alamat,
                $totalFinal
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Gagal menyimpan pesanan.");
            }

            $pesananId = mysqli_insert_id($koneksi);

            foreach ($barangFinal as $item) {
                $barang = $item['barang'];
                $jumlah = $item['jumlah'];
                $subtotal = $item['subtotal'];

                $stmt = mysqli_prepare($koneksi, "
                    INSERT INTO detail_pesanan_pelanggan
                    (pesanan_id, barang_id, nama_barang, harga, jumlah, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                mysqli_stmt_bind_param(
                    $stmt,
                    "iisdid",
                    $pesananId,
                    $barang['id'],
                    $barang['nama_barang'],
                    $barang['harga'],
                    $jumlah,
                    $subtotal
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Gagal menyimpan detail pesanan.");
                }

                // Kurangi stok barang
                $stmt = mysqli_prepare($koneksi, "
                    UPDATE barang
                    SET stok = stok - ?
                    WHERE id = ? AND stok >= ?
                ");
                mysqli_stmt_bind_param(
                    $stmt,
                    "iii",
                    $jumlah,
                    $barang['id'],
                    $jumlah
                );

                if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) !== 1) {
                    throw new Exception("Stok gagal diperbarui.");
                }
            }

            mysqli_commit($koneksi);

            $_SESSION['keranjang'] = [];

            header("Location: checkout.php?sukses=" . urlencode($kode));
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - Bu Rina</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    padding: 25px;
}
.container {
    max-width: 750px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
}
h1 { text-align: center; }
label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
}
input, textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 12px;
    margin-top: 7px;
    border: 1px solid #ccc;
    border-radius: 7px;
}
textarea { height: 100px; }
button, .btn {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 18px;
    background: #16834a;
    color: white;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    text-decoration: none;
}
.kembali { background: #555; }
.total {
    font-size: 21px;
    font-weight: bold;
    text-align: right;
    margin-top: 20px;
}
.error {
    background: #ffe3e3;
    color: #a40000;
    padding: 12px;
    border-radius: 7px;
}
.sukses {
    background: #dff5e5;
    color: #176b37;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}
</style>
</head>

<body>

<div class="container">

<?php if (isset($_GET['sukses'])) : ?>

    <div class="sukses">
        <h1>Pesanan Berhasil!</h1>
        <p>Terima kasih sudah memesan.</p>
        <p>Kode Pesanan:</p>
        <h2><?php echo htmlspecialchars($_GET['sukses']); ?></h2>
        <p>Status: Menunggu</p>
        <a href="menu.php" class="btn">Kembali ke Menu</a>
    </div>

<?php else : ?>

    <h1>Checkout Pesanan</h1>

    <?php if ($error !== '') : ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <h3>Ringkasan Pesanan</h3>

    <?php foreach ($daftarBarang as $item) : ?>
        <p>
            <?php echo htmlspecialchars($item['barang']['nama_barang']); ?>
            × <?php echo (int)$item['jumlah']; ?>
            = Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
        </p>
    <?php endforeach; ?>

    <div class="total">
        Total: Rp <?php echo number_format($total, 0, ',', '.'); ?>
    </div>

    <hr>

    <form method="POST">
        <label>Nama Pelanggan</label>
        <input type="text" name="nama_pelanggan"
               maxlength="100" required
               value="<?php echo htmlspecialchars($_POST['nama_pelanggan'] ?? ''); ?>">

        <label>Nomor HP</label>
        <input type="tel" name="no_hp"
               maxlength="20" required
               value="<?php echo htmlspecialchars($_POST['no_hp'] ?? ''); ?>">

        <label>Alamat</label>
        <textarea name="alamat" required><?php
            echo htmlspecialchars($_POST['alamat'] ?? '');
        ?></textarea>

        <button type="submit" name="buat_pesanan">
            Buat Pesanan
        </button>

        <a href="keranjang.php" class="btn kembali">
            Kembali
        </a>
    </form>

<?php endif; ?>

</div>

</body>
</html>
