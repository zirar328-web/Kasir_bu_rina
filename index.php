
<?php
session_start();

// Jika belum login, tampilkan beranda pelanggan
if (!isset($_SESSION['id'])) {
    header("Location: beranda.php");
    exit;
}

require_once __DIR__ . '/config/koneksi.php';

$nama = $_SESSION['nama'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'kasir';

// ==============================
// DATA DASHBOARD
// ==============================

// Total barang
$query_barang = mysqli_query($koneksi, "
    SELECT COUNT(*) AS jumlah FROM barang
");
$data_barang = mysqli_fetch_assoc($query_barang);
$total_barang = (int)($data_barang['jumlah'] ?? 0);

// Total transaksi
$query_transaksi = mysqli_query($koneksi, "
    SELECT COUNT(*) AS jumlah FROM transaksi
");
$data_transaksi = mysqli_fetch_assoc($query_transaksi);
$total_transaksi = (int)($data_transaksi['jumlah'] ?? 0);

// Total pendapatan
$query_pendapatan = mysqli_query($koneksi, "
    SELECT COALESCE(SUM(total), 0) AS total FROM transaksi
");
$data_pendapatan = mysqli_fetch_assoc($query_pendapatan);
$total_pendapatan = (float)($data_pendapatan['total'] ?? 0);

// Pesanan menunggu
$query_menunggu = mysqli_query($koneksi, "
    SELECT COUNT(*) AS jumlah
    FROM pesanan_pelanggan
    WHERE status = 'Menunggu'
");
$data_menunggu = mysqli_fetch_assoc($query_menunggu);
$jumlah_menunggu = (int)($data_menunggu['jumlah'] ?? 0);

// ID pesanan terbaru
$query_pesanan_baru = mysqli_query($koneksi, "
    SELECT MAX(id) AS id_terbaru
    FROM pesanan_pelanggan
    WHERE status = 'Menunggu'
");
$data_pesanan_baru = mysqli_fetch_assoc($query_pesanan_baru);
$id_pesanan_terbaru = (int)($data_pesanan_baru['id_terbaru'] ?? 0);

// Barang stok menipis
$query_stok = mysqli_query($koneksi, "
    SELECT COUNT(*) AS jumlah
    FROM barang
    WHERE stok <= 5
    AND status_barang = 'aktif'
");
$data_stok = mysqli_fetch_assoc($query_stok);
$jumlah_stok_menipis = (int)($data_stok['jumlah'] ?? 0);

// Daftar barang stok menipis
$query_daftar_stok = mysqli_query($koneksi, "
    SELECT kode_barang, nama_barang, stok
    FROM barang
    WHERE stok <= 5
    AND status_barang = 'aktif'
    ORDER BY stok ASC, nama_barang ASC
");

// ==============================
// GRAFIK PENJUALAN
// ==============================

// Mencari kolom tanggal yang tersedia
$kolom_tanggal = null;

$hasil_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi");

if ($hasil_kolom) {
    $kolom_tersedia = [];

    while ($kolom = mysqli_fetch_assoc($hasil_kolom)) {
        $kolom_tersedia[] = $kolom['Field'];
    }

    $pilihan_kolom = [
        'tanggal',
        'created_at',
        'waktu',
        'tanggal_transaksi'
    ];

    foreach ($pilihan_kolom as $pilihan) {
        if (in_array($pilihan, $kolom_tersedia, true)) {
            $kolom_tanggal = $pilihan;
            break;
        }
    }
}

$label_grafik = [];
$data_grafik = [];

if ($kolom_tanggal !== null) {
    $query_grafik = mysqli_query($koneksi, "
        SELECT
            DATE(`$kolom_tanggal`) AS tanggal,
            SUM(total) AS pendapatan
        FROM transaksi
        WHERE `$kolom_tanggal` >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
          AND `$kolom_tanggal` < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        GROUP BY DATE(`$kolom_tanggal`)
        ORDER BY tanggal ASC
    ");

    if ($query_grafik) {
        $hasil_grafik = [];

        while ($grafik = mysqli_fetch_assoc($query_grafik)) {
            $hasil_grafik[$grafik['tanggal']] =
                (float)$grafik['pendapatan'];
        }

        // Menampilkan seluruh 7 hari termasuk hari tanpa transaksi
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = date('Y-m-d', strtotime("-$i days"));

            $label_grafik[] = date('d/m', strtotime($tanggal));
            $data_grafik[] = $hasil_grafik[$tanggal] ?? 0;
        }
    }
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard | RINA POS</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Arial, sans-serif;
}

body {
    background: #f7f5f2;
    color: #352b26;
}

.sidebar {
    width: 255px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: linear-gradient(180deg, #543b2b, #38271e);
    color: white;
    padding: 25px 18px;
    overflow-y: auto;
}

.logo {
    text-align: center;
    padding: 15px 0 28px;
    border-bottom: 1px solid #ffffff25;
}

.logo h2 {
    font-size: 28px;
    letter-spacing: 3px;
}

.logo p {
    color: #dfc9b8;
    font-size: 11px;
    margin-top: 7px;
    letter-spacing: 1px;
}

.menu {
    margin-top: 28px;
}

.menu-label {
    color: #c9ae9b;
    font-size: 11px;
    font-weight: bold;
    margin: 20px 12px 12px;
    letter-spacing: 1px;
}

.menu a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 13px 14px;
    margin-bottom: 7px;
    color: #f7eee8;
    text-decoration: none;
    border-radius: 9px;
    font-size: 14px;
    transition: .2s;
}

.menu a:hover,
.menu a.active {
    background: #805a40;
    color: white;
}

.menu .logout {
    background: #a94740;
    margin-top: 30px;
}

.notif {
    background: #e53935;
    color: white;
    min-width: 22px;
    height: 22px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
    padding: 0 6px;
}

.main {
    margin-left: 255px;
    padding: 30px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 28px;
}

.topbar h2 {
    color: #543b2b;
    font-size: 25px;
}

.topbar p {
    color: #96877d;
    font-size: 13px;
    margin-top: 5px;
}

.user {
    background: white;
    padding: 12px 17px;
    border-radius: 10px;
    box-shadow: 0 3px 12px #00000008;
    font-size: 13px;
}

.welcome {
    background: linear-gradient(110deg, #543b2b, #906b50);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 25px;
    box-shadow: 0 5px 18px #543b2b20;
}

.welcome h2 {
    font-size: 24px;
    margin-bottom: 9px;
}

.welcome p {
    color: #f0dfd1;
    font-size: 14px;
    line-height: 1.7;
}

.cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 17px;
}

.card {
    background: white;
    padding: 22px;
    border-radius: 13px;
    box-shadow: 0 3px 14px #00000008;
    border: 1px solid #eee8e2;
    border-left: 5px solid #8b6448;
    min-width: 0;
}

.card p {
    color: #91847b;
    font-size: 13px;
    margin-bottom: 13px;
}

.card h2 {
    color: #543b2b;
    font-size: 24px;
    overflow-wrap: anywhere;
}

.card .card-icon {
    font-size: 23px;
    margin-bottom: 13px;
}

.card.pesanan {
    border-left-color: #d49a35;
}

.card.pesanan h2 {
    color: #b87916;
}

.card.stok {
    border-left-color: #d94b43;
}

.card.stok h2 {
    color: #d94b43;
}

.card a {
    display: inline-block;
    margin-top: 14px;
    color: #79543a;
    text-decoration: none;
    font-size: 12px;
    font-weight: bold;
}

.card a:hover {
    text-decoration: underline;
}

.panel {
    background: white;
    padding: 24px;
    border-radius: 14px;
    margin-top: 25px;
    box-shadow: 0 3px 14px #00000008;
    border: 1px solid #eee8e2;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.panel-header h3 {
    color: #543b2b;
    font-size: 17px;
}

.panel-header p {
    color: #9b8e84;
    font-size: 12px;
    margin-top: 5px;
}

.tabel-wrapper {
    width: 100%;
    overflow-x: auto;
}

.tabel {
    width: 100%;
    border-collapse: collapse;
    min-width: 500px;
}

.tabel th,
.tabel td {
    padding: 14px;
    border-bottom: 1px solid #eee9e4;
    text-align: left;
    font-size: 13px;
}

.tabel th {
    background: #f8f5f2;
    color: #624a39;
}

.tabel td {
    color: #665c55;
}

.stok-rendah {
    color: #d32f2f !important;
    font-weight: bold;
}

.status-habis {
    background: #ffebee;
    color: #c62828;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}

.status-menipis {
    background: #fff4df;
    color: #a66b12;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}

.kosong {
    color: #388e3c;
    background: #eff8ef;
    padding: 15px;
    border-radius: 8px;
    font-size: 13px;
}

.grafik-area {
    position: relative;
    height: 320px;
    width: 100%;
}

.footer {
    text-align: center;
    color: #a99c92;
    font-size: 12px;
    margin-top: 30px;
    padding: 15px;
}

@media(max-width: 1200px) {
    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width: 850px) {
    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
        padding: 20px;
    }
}

@media(max-width: 600px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
        padding: 15px;
    }

    .topbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    .welcome {
        padding: 22px;
    }

    .welcome h2 {
        font-size: 20px;
    }

    .panel {
        padding: 16px;
    }

    .grafik-area {
        height: 260px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="logo">
        <h2>RINA POS</h2>
        <p>SISTEM KASIR PENJUALAN</p>
    </div>

    <div class="menu">

    <div class="menu-label">MENU UTAMA</div>

    <a href="index.php" class="active">
        <span>🏠 Dashboard</span>
    </a>

    <a href="pages/barang.php">
        <span>📦 Data Barang</span>
    </a>

    <a href="pages/transaksi.php">
        <span>🛒 Transaksi</span>
    </a>

    <a href="pages/pesanan.php">
        <span>🛍️ Pesanan Pelanggan</span>

        <?php if ($jumlah_menunggu > 0): ?>
            <span class="notif">
                <?= $jumlah_menunggu ?>
            </span>
        <?php endif; ?>
    </a>

    <div class="menu-label">LAPORAN</div>

    <a href="pages/riwayat.php">
        <span>📋 Riwayat Transaksi</span>
    </a>

    <a href="pages/laporan.php">
        <span>📊 Laporan Penjualan</span>
    </a>

    <div class="menu-label">AKUN</div>

    <a href="beranda.php">
        <span>🏠 Beranda Pengguna</span>
    </a>

    <a href="logout.php" class="logout"
       onclick="return confirm('Yakin ingin keluar?')">
        <span>🚪 Logout</span>
    </a>

</div>
</aside>

<!-- KONTEN UTAMA -->
<main class="main">

    <div class="topbar">
        <div>
            <h2>Dashboard</h2>
            <p>Ringkasan aktivitas penjualan RINA POS</p>
        </div>

        <div class="user">
            👤 <?= htmlspecialchars($nama) ?>
            (<?= htmlspecialchars($role) ?>)
        </div>
    </div>

    <section class="welcome">
        <h2>Selamat Datang di RINA POS!</h2>

        <p>
            Halo, <?= htmlspecialchars($nama) ?>.
            Selamat bekerja! Pantau stok barang, transaksi,
            pesanan pelanggan, dan pendapatan toko melalui
            dashboard ini.
        </p>
    </section>

    <!-- KARTU STATISTIK -->
    <section class="cards">

        <div class="card">
            <div class="card-icon">📦</div>
            <p>Total Data Barang</p>
            <h2><?= $total_barang ?></h2>
            <a href="pages/barang.php">Lihat Data Barang →</a>
        </div>

        <div class="card">
            <div class="card-icon">🛒</div>
            <p>Total Transaksi</p>
            <h2><?= $total_transaksi ?></h2>
            <a href="pages/riwayat.php">Lihat Riwayat →</a>
        </div>

        <div class="card">
            <div class="card-icon">💰</div>
            <p>Total Pendapatan</p>
            <h2><?= rupiah($total_pendapatan) ?></h2>
            <a href="pages/laporan.php">Lihat Laporan →</a>
        </div>

        <div class="card pesanan">
            <div class="card-icon">🛍️</div>
            <p>Pesanan Menunggu</p>
            <h2><?= $jumlah_menunggu ?></h2>
            <a href="pages/pesanan.php">Lihat Pesanan →</a>
        </div>

        <div class="card stok">
            <div class="card-icon">⚠️</div>
            <p>Barang Stok Menipis</p>
            <h2><?= $jumlah_stok_menipis ?></h2>
            <a href="pages/barang.php">Kelola Barang →</a>
        </div>

    </section>

    <!-- GRAFIK PENJUALAN -->
    <section class="panel">

        <div class="panel-header">
            <div>
                <h3>📈 Grafik Penjualan</h3>
                <p>Pendapatan selama 7 hari terakhir</p>
            </div>
        </div>

        <?php if ($kolom_tanggal !== null): ?>

            <div class="grafik-area">
                <canvas id="grafikPenjualan"></canvas>
            </div>

        <?php else: ?>

            <p class="kosong">
                Kolom tanggal transaksi belum ditemukan.
                Periksa struktur tabel transaksi.
            </p>

        <?php endif; ?>

    </section>

    <!-- DAFTAR STOK MENIPIS -->
    <section class="panel">

        <div class="panel-header">
            <div>
                <h3>⚠️ Peringatan Stok Barang</h3>
                <p>Barang aktif dengan stok 5 atau kurang</p>
            </div>
        </div>

        <?php if ($jumlah_stok_menipis > 0): ?>

            <div class="tabel-wrapper">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Stok</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php $no = 1; ?>

                    <?php while ($barang = mysqli_fetch_assoc($query_daftar_stok)): ?>

                        <tr>
                            <td><?= $no++ ?></td>

                            <td>
                                <?= htmlspecialchars($barang['kode_barang']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($barang['nama_barang']) ?>
                            </td>

                            <td class="stok-rendah">
                                <?= (int)$barang['stok'] ?>
                            </td>

                            <td>
                                <?php if ((int)$barang['stok'] === 0): ?>
                                    <span class="status-habis">
                                        Stok Habis
                                    </span>
                                <?php else: ?>
                                    <span class="status-menipis">
                                        Stok Menipis
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <p class="kosong">
                ✓ Semua stok barang masih aman.
            </p>

        <?php endif; ?>

    </section>

    <div class="footer">
        © <?= date('Y') ?> RINA POS - Sistem Kasir Penjualan
    </div>

</main>

<script>

// ==============================
// GRAFIK PENJUALAN
// ==============================

<?php if ($kolom_tanggal !== null): ?>

const labelGrafik = <?= json_encode($label_grafik) ?>;
const dataGrafik = <?= json_encode($data_grafik) ?>;

const canvasGrafik = document.getElementById('grafikPenjualan');

if (canvasGrafik && typeof Chart !== 'undefined') {

    new Chart(canvasGrafik, {
        type: 'line',

        data: {
            labels: labelGrafik,

            datasets: [{
                label: 'Pendapatan',

                data: dataGrafik,

                borderColor: '#80583d',
                backgroundColor: 'rgba(128, 88, 61, 0.12)',

                borderWidth: 3,

                fill: true,

                tension: 0.35,

                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },

                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Pendapatan: Rp ' +
                                Number(context.raw).toLocaleString('id-ID');
                        }
                    }
                }
            },

            scales: {
                y: {
                    beginAtZero: true,

                    ticks: {
                        callback: function(value) {
                            return 'Rp ' +
                                Number(value).toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
}

<?php endif; ?>

// ==============================
// NOTIFIKASI SUARA PESANAN
// ==============================

let idPesananTerakhir = <?= $id_pesanan_terbaru ?>;

let audioContext = null;
let suaraAktif = false;

// Aktifkan suara setelah admin mengeklik dashboard
document.addEventListener('click', function() {

    const AudioContext =
        window.AudioContext || window.webkitAudioContext;

    if (AudioContext && !audioContext) {
        audioContext = new AudioContext();
    }

    if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume();
    }

    suaraAktif = true;

}, { once: true });

// Fungsi bunyi notifikasi
function bunyiNotifikasi() {

    if (!audioContext || !suaraAktif) {
        return;
    }

    const oscillator = audioContext.createOscillator();
    const gain = audioContext.createGain();

    oscillator.connect(gain);
    gain.connect(audioContext.destination);

    oscillator.type = 'sine';

    oscillator.frequency.value = 880;

    gain.gain.setValueAtTime(
        0.3,
        audioContext.currentTime
    );

    gain.gain.exponentialRampToValueAtTime(
        0.01,
        audioContext.currentTime + 0.5
    );

    oscillator.start();

    oscillator.stop(
        audioContext.currentTime + 0.5
    );
}

// Periksa pesanan baru setiap 10 detik
function cekPesananBaru() {

    fetch('cek_notifikasi.php', {
        cache: 'no-store'
    })

    .then(response => {

        if (!response.ok) {
            throw new Error('Gagal memeriksa pesanan');
        }

        return response.json();

    })

    .then(data => {

        const idBaru = Number(data.id_terbaru);

        if (idBaru > idPesananTerakhir) {

            idPesananTerakhir = idBaru;

            bunyiNotifikasi();

            alert('Ada pesanan baru dari pelanggan!');

            location.reload();
        }

    })

    .catch(error => {
        console.log(error);
    });
}

setInterval(cekPesananBaru, 10000);

</script>

</body>
</html>
