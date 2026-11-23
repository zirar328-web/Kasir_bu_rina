
<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';

// Ambil jumlah barang tersedia
$query = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM barang
    WHERE status_barang = 'aktif' AND stok > 0
");
$data = mysqli_fetch_assoc($query);
$total_menu = $data['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kantin Bu Rina</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #fffaf4;
    color: #392719;
}

nav {
    background: #56351f;
    color: white;
    padding: 20px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.logo {
    font-size: 24px;
    font-weight: bold;
}

nav a {
    color: white;
    text-decoration: none;
    margin-left: 20px;
    font-size: 14px;
}

nav a:hover {
    color: #ffc982;
}

.hero {
    padding: 90px 8%;
    background: linear-gradient(120deg, #754b2d, #b77b49);
    color: white;
    text-align: center;
}

.hero h1 {
    font-size: 45px;
    margin-bottom: 18px;
}

.hero p {
    font-size: 17px;
    line-height: 1.7;
    margin-bottom: 30px;
}

.btn {
    display: inline-block;
    padding: 13px 25px;
    border-radius: 8px;
    background: #fff0d9;
    color: #56351f;
    text-decoration: none;
    font-weight: bold;
}

.btn:hover {
    background: white;
}

.container {
    padding: 55px 8%;
}

.judul {
    text-align: center;
    margin-bottom: 35px;
}

.judul h2 {
    font-size: 30px;
    margin-bottom: 10px;
}

.fitur {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 22px;
}

.kartu {
    background: white;
    padding: 28px 20px;
    border-radius: 13px;
    text-align: center;
    box-shadow: 0 4px 15px #00000012;
}

.kartu .ikon {
    font-size: 38px;
    margin-bottom: 15px;
}

.kartu h3 {
    margin-bottom: 12px;
}

.kartu p {
    color: #777;
    line-height: 1.6;
    font-size: 14px;
    margin-bottom: 18px;
}

.kartu a {
    color: #9a6034;
    font-weight: bold;
    text-decoration: none;
}

.info {
    background: #f3e6d7;
    border-radius: 12px;
    text-align: center;
    padding: 30px;
    margin-top: 35px;
}

footer {
    background: #56351f;
    color: white;
    text-align: center;
    padding: 23px;
    margin-top: 30px;
}

@media(max-width:600px) {
    nav {
        flex-direction: column;
    }

    nav a {
        margin: 5px;
    }

    .hero h1 {
        font-size: 32px;
    }
}
</style>
</head>

<body>

<nav>
    <div class="logo">🍽️ KANTIN BU RINA</div>

    <div>
        <a href="beranda.php">Beranda</a>
        <a href="pelanggan/menu.php">Menu</a>
        <a href="pelanggan/cek_pesanan.php">Cek Pesanan</a>
        <a href="pelanggan/keranjang.php">Keranjang</a>
        <a href="login.php">Login Admin</a>
    </div>
</nav>

<section class="hero">
    <h1>Selamat Datang di Kantin Bu Rina!</h1>

    <p>
        Temukan makanan dan minuman favoritmu.<br>
        Pesan dengan mudah, praktis, dan nyaman.
    </p>

    <a href="pelanggan/menu.php" class="btn">
        Lihat Menu Sekarang
    </a>
</section>

<section class="container">

    <div class="judul">
        <h2>Layanan Kantin Bu Rina</h2>
        <p>Berbagai fitur untuk memudahkan pelanggan melakukan pemesanan.</p>
    </div>

    <div class="fitur">

        <div class="kartu">
            <div class="ikon">🍛</div>
            <h3>Daftar Menu</h3>
            <p>Lihat berbagai makanan dan minuman yang tersedia.</p>
            <a href="pelanggan/menu.php">Lihat Menu →</a>
        </div>

        <div class="kartu">
            <div class="ikon">🛒</div>
            <h3>Keranjang</h3>
            <p>Lihat dan atur produk yang ingin kamu pesan.</p>
            <a href="pelanggan/keranjang.php">Buka Keranjang →</a>
        </div>

        <div class="kartu">
            <div class="ikon">🧾</div>
            <h3>Checkout</h3>
            <p>Lanjutkan pesanan dan isi data pemesananmu.</p>
            <a href="pelanggan/checkout.php">Pesan Sekarang →</a>
        </div>

        <div class="kartu">
            <div class="ikon">📦</div>
            <h3>Cek Pesanan</h3>
            <p>Pantau status pesanan menggunakan nomor HP.</p>
            <a href="pelanggan/cek_pesanan.php">Cek Pesanan →</a>
        </div>

    </div>

    <div class="info">
        <h2>🍽️ Pilihan Menu Tersedia</h2>
        <p style="margin:12px 0;">
            Saat ini terdapat <b><?= (int)$total_menu ?></b> menu yang tersedia.
        </p>
        <a href="pelanggan/menu.php" class="btn"
           style="background:#704727;color:white;">
            Mulai Pesan
        </a>
    </div>

</section>

<footer>
    <p>© <?= date('Y') ?> Kantin Bu Rina. Semua Hak Dilindungi.</p>
</footer>

</body>
</html>
