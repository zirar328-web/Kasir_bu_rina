
<?php
require_once __DIR__ . '/../config/koneksi.php';

$error = '';
$daftarPesanan = [];
$no_hp = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_hp = trim($_POST['no_hp'] ?? '');

    if ($no_hp === '') {
        $error = "Nomor HP wajib diisi.";
    } else {
        $stmt = mysqli_prepare($koneksi, "
            SELECT *
            FROM pesanan_pelanggan
            WHERE no_hp = ?
            ORDER BY tanggal_pesanan DESC
        ");

        mysqli_stmt_bind_param($stmt, "s", $no_hp);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($hasil)) {
            $daftarPesanan[] = $row;
        }

        mysqli_stmt_close($stmt);

        if (count($daftarPesanan) === 0) {
            $error = "Pesanan tidak ditemukan. Periksa kembali nomor HP kamu.";
        }
    }
}

function warnaStatus($status) {
    switch ($status) {
        case 'Menunggu':
            return '#d68910';
        case 'Diproses':
            return '#2874a6';
        case 'Selesai':
            return '#16834a';
        case 'Dibatalkan':
            return '#c0392b';
        default:
            return '#555';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Cek Status Pesanan - RINA POS</title>

<style>
* {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    margin: 0;
    padding: 25px;
    background: #f5f2ee;
    color: #333;
}

.container {
    max-width: 900px;
    margin: 30px auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #0000000d;
}

h1 {
    text-align: center;
    color: #543b2b;
}

h3 {
    color: #543b2b;
}

.keterangan {
    text-align: center;
    color: #666;
    margin-bottom: 25px;
}

label {
    display: block;
    margin: 15px 0 7px;
    font-weight: bold;
}

input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

button {
    width: 100%;
    padding: 12px;
    margin-top: 20px;
    border: none;
    border-radius: 6px;
    background: #543b2b;
    color: white;
    font-size: 15px;
    cursor: pointer;
}

button:hover {
    background: #72513c;
}

.error {
    background: #ffe3e3;
    color: #a40000;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.kartu-pesanan {
    margin-top: 25px;
    padding: 20px;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
}

.info {
    line-height: 1.8;
}

.status {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 20px;
    color: white;
    font-weight: bold;
    font-size: 13px;
}

.tabel {
    overflow-x: auto;
    margin-top: 15px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 500px;
}

th, td {
    padding: 11px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

th {
    background: #543b2b;
    color: white;
}

.total {
    text-align: right;
    font-weight: bold;
    color: #16834a;
    margin-top: 15px;
}

.tombol-cetak {
    display: inline-block;
    padding: 11px 16px;
    margin-top: 10px;
    background: #543b2b;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}

.tombol-cetak:hover {
    background: #72513c;
}

.kembali {
    display: inline-block;
    margin-top: 25px;
    color: #543b2b;
    text-decoration: none;
    font-weight: bold;
}

@media(max-width: 600px) {
    body {
        padding: 12px;
    }

    .container {
        padding: 18px;
        margin: 10px auto;
    }

    .kartu-pesanan {
        padding: 13px;
    }
}
</style>
</head>

<body>

<div class="container">

    <h1>Cek Status Pesanan</h1>

    <p class="keterangan">
        Masukkan nomor HP yang digunakan saat melakukan pemesanan.
    </p>

    <?php if ($error !== '') : ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Nomor HP</label>

        <input
            type="text"
            name="no_hp"
            placeholder="Contoh: 081234567890"
            value="<?php echo htmlspecialchars($no_hp); ?>"
            required
        >

        <button type="submit">Cek Pesanan</button>

    </form>

    <?php if (count($daftarPesanan) > 0) : ?>

        <h3>Daftar Pesanan Kamu</h3>

        <?php foreach ($daftarPesanan as $data) : ?>

            <div class="kartu-pesanan">

                <div class="info">

                    <p>
                        Kode Pesanan:
                        <strong>
                            <?php echo htmlspecialchars($data['kode_pesanan']); ?>
                        </strong>
                    </p>

                    <p>
                        Tanggal:
                        <?php echo htmlspecialchars($data['tanggal_pesanan']); ?>
                    </p>

                    <p>Status Pesanan:</p>

                    <span
                        class="status"
                        style="background: <?php echo warnaStatus($data['status']); ?>"
                    >
                        <?php echo htmlspecialchars($data['status']); ?>
                    </span>

                </div>

                <h3>Detail Barang</h3>

                <div class="tabel">

                    <table>
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php
                        $idPesanan = (int)$data['id'];

                        $stmtDetail = mysqli_prepare($koneksi, "
                            SELECT *
                            FROM detail_pesanan_pelanggan
                            WHERE pesanan_id = ?
                        ");

                        mysqli_stmt_bind_param(
                            $stmtDetail,
                            "i",
                            $idPesanan
                        );

                        mysqli_stmt_execute($stmtDetail);

                        $detail = mysqli_stmt_get_result($stmtDetail);

                        while ($barang = mysqli_fetch_assoc($detail)) :
                        ?>

                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($barang['nama_barang']); ?>
                                </td>

                                <td>
                                    Rp <?php echo number_format($barang['harga'], 0, ',', '.'); ?>
                                </td>

                                <td>
                                    <?php echo (int)$barang['jumlah']; ?>
                                </td>

                                <td>
                                    Rp <?php echo number_format($barang['subtotal'], 0, ',', '.'); ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                        </tbody>
                    </table>

                    <?php mysqli_stmt_close($stmtDetail); ?>

                </div>

                <p class="total">
                    Total Pesanan:
                    Rp <?php echo number_format($data['total'], 0, ',', '.'); ?>
                </p>

                <a
                    href="cetak_pesanan.php?id=<?php echo (int)$data['id']; ?>&no_hp=<?php echo urlencode($no_hp); ?>"
                    class="tombol-cetak"
                >
                    Cetak Bukti Pesanan
                </a>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    <a href="menu.php" class="kembali">
        ← Kembali ke Menu
    </a>

</div>

</body>
</html>
