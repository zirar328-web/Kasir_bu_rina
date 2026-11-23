
<?php
require_once __DIR__ . '/../config/koneksi.php';

$error = '';
$data = null;
$daftarBarang = [];

$id = (int)($_GET['id'] ?? 0);
$no_hp = trim($_GET['no_hp'] ?? '');

if ($id <= 0 || $no_hp === '') {
    $error = "Data pesanan tidak lengkap.";
} else {
    $stmt = mysqli_prepare($koneksi, "
        SELECT *
        FROM pesanan_pelanggan
        WHERE id = ? AND no_hp = ?
    ");

    mysqli_stmt_bind_param($stmt, "is", $id, $no_hp);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);

    if (!$data) {
        $error = "Pesanan tidak ditemukan.";
    } else {
        $stmtDetail = mysqli_prepare($koneksi, "
            SELECT *
            FROM detail_pesanan_pelanggan
            WHERE pesanan_id = ?
        ");

        mysqli_stmt_bind_param($stmtDetail, "i", $id);
        mysqli_stmt_execute($stmtDetail);

        $hasilDetail = mysqli_stmt_get_result($stmtDetail);

        while ($barang = mysqli_fetch_assoc($hasilDetail)) {
            $daftarBarang[] = $barang;
        }

        mysqli_stmt_close($stmtDetail);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Bukti Pesanan - RINA POS</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f2f2f2;
    padding: 20px;
    color: #333;
}

.struk {
    max-width: 650px;
    margin: 20px auto;
    background: white;
    padding: 30px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

h2, .tengah {
    text-align: center;
}

h2 {
    color: #543b2b;
}

hr {
    border: none;
    border-top: 1px dashed #555;
    margin: 20px 0;
}

.info p {
    margin: 10px 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

th {
    background: #f1e9e2;
}

.total {
    text-align: right;
    font-size: 18px;
    font-weight: bold;
    margin-top: 20px;
}

.tombol {
    text-align: center;
    margin: 20px auto;
}

button, .kembali {
    display: inline-block;
    padding: 11px 18px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
}

button {
    background: #543b2b;
    color: white;
}

.kembali {
    background: #ddd;
    color: #333;
}

.error {
    max-width: 650px;
    margin: 30px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    color: #a40000;
}

@media print {
    body {
        background: white;
        padding: 0;
    }

    .struk {
        border: none;
        margin: 0 auto;
    }

    .tombol {
        display: none;
    }
}
</style>
</head>

<body>

<?php if ($data) : ?>

<div class="struk">

    <h2>RINA POS</h2>
    <p class="tengah">BUKTI PEMESANAN PELANGGAN</p>

    <hr>

    <div class="info">

        <p>
            <strong>Kode Pesanan:</strong>
            <?php echo htmlspecialchars($data['kode_pesanan']); ?>
        </p>

        <p>
            <strong>Nama:</strong>
            <?php echo htmlspecialchars($data['nama_pelanggan']); ?>
        </p>

        <p>
            <strong>Nomor HP:</strong>
            <?php echo htmlspecialchars($data['no_hp']); ?>
        </p>

        <p>
            <strong>Alamat:</strong>
            <?php echo htmlspecialchars($data['alamat']); ?>
        </p>

        <p>
            <strong>Tanggal:</strong>
            <?php echo htmlspecialchars($data['tanggal_pesanan']); ?>
        </p>

        <p>
            <strong>Status:</strong>
            <?php echo htmlspecialchars($data['status']); ?>
        </p>

    </div>

    <hr>

    <h3>Detail Barang</h3>

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

        <?php foreach ($daftarBarang as $barang) : ?>

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

        <?php endforeach; ?>

        </tbody>
    </table>

    <p class="total">
        Total Pesanan:
        Rp <?php echo number_format($data['total'], 0, ',', '.'); ?>
    </p>

    <hr>

    <p class="tengah">
        Terima kasih telah melakukan pemesanan!
    </p>

</div>

<div class="tombol">
    <button onclick="window.print()">Cetak / Simpan PDF</button>

    <a
        class="kembali"
        href="cek_pesanan.php"
    >
        Kembali
    </a>
</div>

<?php else : ?>

<div class="error">
    <h3>Pesanan Tidak Ditemukan</h3>
    <p><?php echo htmlspecialchars($error); ?></p>
    <a class="kembali" href="cek_pesanan.php">Kembali ke Cek Pesanan</a>
</div>

<?php endif; ?>

</body>
</html>
