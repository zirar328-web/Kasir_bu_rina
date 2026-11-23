
<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

// Siapkan keranjang
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// Proses tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_keranjang'])) {
    $id = (int) $_POST['id_barang'];

    $stmt = mysqli_prepare($koneksi, "
        SELECT id, nama_barang, harga, stok
        FROM barang
        WHERE id = ? AND status_barang = 'aktif'
    ");

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $barang = mysqli_fetch_assoc($hasil);
    mysqli_stmt_close($stmt);

    if ($barang && $barang['stok'] > 0) {
        $jumlahSekarang = $_SESSION['keranjang'][$id] ?? 0;

        if ($jumlahSekarang < $barang['stok']) {
            $_SESSION['keranjang'][$id] = $jumlahSekarang + 1;
            header("Location: menu.php?pesan=berhasil");
            exit;
        }

        header("Location: menu.php?pesan=maksimal");
        exit;
    }

    header("Location: menu.php?pesan=gagal");
    exit;
}

// Ambil barang aktif dan tersedia
$query = mysqli_query($koneksi, "
    SELECT * FROM barang
    WHERE status_barang = 'aktif' AND stok > 0
    ORDER BY nama_barang ASC
");

if (!$query) {
    die("Gagal mengambil data: " . mysqli_error($koneksi));
}

$totalKeranjang = array_sum($_SESSION['keranjang']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RINA POS - Menu Pelanggan</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f8f5f1;
    color: #35271f;
}

.navbar {
    background: #38271f;
    color: white;
    padding: 17px 6%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
}

.logo {
    font-size: 23px;
    font-weight: bold;
    letter-spacing: 1px;
}

.logo span {
    color: #e8b77c;
}

.nav-menu {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.nav-menu a {
    color: white;
    text-decoration: none;
    padding: 10px 13px;
    border-radius: 7px;
    font-size: 14px;
}

.nav-menu a:hover {
    background: #624638;
}

.login {
    background: #d5a16d;
    color: #302118 !important;
    font-weight: bold;
}

.login:hover {
    background: #edbd88 !important;
}

.hero {
    background: linear-gradient(120deg, #4b3327, #8a6044);
    color: white;
    padding: 65px 20px;
    text-align: center;
}

.hero h1 {
    font-size: 35px;
    margin: 0 0 15px;
}

.hero p {
    font-size: 16px;
    color: #f1e2d5;
    line-height: 1.6;
}

.hero a {
    display: inline-block;
    margin-top: 15px;
    background: #e8b77c;
    color: #35271f;
    text-decoration: none;
    padding: 13px 23px;
    border-radius: 8px;
    font-weight: bold;
}

.container {
    max-width: 1150px;
    margin: 35px auto;
    padding: 0 20px;
}

.judul {
    text-align: center;
    margin-bottom: 25px;
}

.judul h2 {
    font-size: 27px;
    margin-bottom: 8px;
}

.judul p {
    color: #806b5c;
}

.atas {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.atas a {
    padding: 11px 16px;
    text-decoration: none;
    border-radius: 7px;
    color: white;
    font-size: 14px;
}

.keranjang {
    background: #287b4b;
}

.cek-pesanan {
    background: #70503c;
}

.pesan {
    padding: 13px;
    background: #dff5e5;
    color: #176b37;
    border-radius: 7px;
    margin-bottom: 20px;
}

.menu {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 22px;
}

.card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 14px #00000012;
    transition: 0.2s;
}

.card:hover {
    transform: translateY(-4px);
}

.card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    display: block;
    background: #eee;
}

.info {
    padding: 16px;
}

.info h3 {
    margin: 0 0 10px;
    font-size: 18px;
}

.kategori {
    color: #806b5c;
    font-size: 14px;
}

.harga {
    color: #287b4b;
    font-weight: bold;
    font-size: 19px;
}

.stok {
    font-size: 13px;
    color: #806b5c;
}

.tombol {
    width: 100%;
    padding: 12px;
    background: #765039;
    color: white;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-size: 14px;
}

.tombol:hover {
    background: #4b3327;
}

.kosong {
    text-align: center;
    background: white;
    padding: 35px;
    border-radius: 10px;
    color: #806b5c;
}

footer {
    background: #38271f;
    color: #eee0d4;
    text-align: center;
    padding: 22px;
    margin-top: 55px;
    font-size: 13px;
}

@media(max-width: 600px) {
    .navbar {
        justify-content: center;
        text-align: center;
    }

    .hero h1 {
        font-size: 27px;
    }

    .atas {
        justify-content: center;
    }
}
</style>
</head>

<body>

<nav class="navbar">
    <div class="logo">RINA <span>POS</span></div>

    <div class="nav-menu">
        <a href="menu.php">Beranda</a>
        <a href="#daftar-menu">Menu</a>
        <a href="cek_pesanan.php">Cek Pesanan</a>
        <a href="keranjang.php">Keranjang (<?= $totalKeranjang ?>)</a>
        <a href="../login.php" class="login">Login Admin</a>
    </div>
</nav>

<section class="hero">
    <h1>Selamat Datang di RINA POS</h1>
    <p>Nikmati berbagai pilihan makanan dan minuman favorit Anda.</p>
    <a href="#daftar-menu">Lihat Menu</a>
</section>

<main class="container" id="daftar-menu">

    <div class="judul">
        <h2>Menu Makanan dan Minuman</h2>
        <p>Pilih menu favorit Anda dan pesan dengan mudah.</p>
    </div>

    <div class="atas">
        <a href="cek_pesanan.php" class="cek-pesanan">
            Cek Status Pesanan
        </a>

        <a href="keranjang.php" class="keranjang">
            Keranjang (<?= $totalKeranjang ?>)
        </a>
    </div>

    <?php if (isset($_GET['pesan'])) : ?>
        <div class="pesan">
            <?php
            if ($_GET['pesan'] === 'berhasil') {
                echo "Barang berhasil ditambahkan ke keranjang!";
            } elseif ($_GET['pesan'] === 'maksimal') {
                echo "Jumlah barang sudah mencapai batas stok!";
            } else {
                echo "Barang tidak tersedia atau stok habis.";
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="menu">

        <?php if (mysqli_num_rows($query) > 0) : ?>

            <?php while ($barang = mysqli_fetch_assoc($query)) : ?>

                <div class="card">

                    <?php if (!empty($barang['gambar'])) : ?>
                        <img
                            src="../uploads/barang/<?= htmlspecialchars(basename($barang['gambar'])) ?>"
                            alt="<?= htmlspecialchars($barang['nama_barang']) ?>"
                        >
                    <?php else : ?>
                        <img
                            src="https://placehold.co/400x250?text=Foto+Tidak+Ada"
                            alt="Foto tidak tersedia"
                        >
                    <?php endif; ?>

                    <div class="info">

                        <h3><?= htmlspecialchars($barang['nama_barang']) ?></h3>

                        <p class="kategori">
                            <?= htmlspecialchars($barang['kategori']) ?>
                        </p>

                        <p class="harga">
                            Rp <?= number_format($barang['harga'], 0, ',', '.') ?>
                        </p>

                        <p class="stok">
                            Stok tersedia: <?= (int)$barang['stok'] ?>
                        </p>

                        <form method="POST">
                            <input
                                type="hidden"
                                name="id_barang"
                                value="<?= (int)$barang['id'] ?>"
                            >

                            <button
                                type="submit"
                                name="tambah_keranjang"
                                class="tombol"
                            >
                                + Tambah ke Keranjang
                            </button>
                        </form>

                    </div>
                </div>

            <?php endwhile; ?>

        <?php else : ?>

            <div class="kosong">
                Belum ada produk yang tersedia saat ini.
            </div>

        <?php endif; ?>

    </div>
</main>

<footer>
    &copy; <?= date('Y') ?> RINA POS - Kasir Bu Rina
</footer>

</body>
</html>
