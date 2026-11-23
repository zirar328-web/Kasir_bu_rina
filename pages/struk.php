
<?php
session_start();

require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['struk'])) {
    die("Data struk tidak ditemukan. Silakan lakukan transaksi terlebih dahulu.");
}

$id_transaksi = (int) $_SESSION['struk']['id'];

$query = mysqli_prepare(
    $koneksi,
    "SELECT * FROM transaksi WHERE id = ?"
);
mysqli_stmt_bind_param($query, "i", $id_transaksi);
mysqli_stmt_execute($query);

$hasil = mysqli_stmt_get_result($query);
$transaksi = mysqli_fetch_assoc($hasil);

if (!$transaksi) {
    die("Transaksi tidak ditemukan.");
}

$detail = mysqli_prepare(
    $koneksi,
    "SELECT * FROM detail_transaksi WHERE transaksi_id = ?"
);
mysqli_stmt_bind_param($detail, "i", $id_transaksi);
mysqli_stmt_execute($detail);

$data_detail = mysqli_stmt_get_result($detail);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk RINA POS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            padding: 20px;
        }

        .struk {
            width: 320px;
            margin: auto;
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
        }

        h2, p {
            text-align: center;
            margin: 5px 0;
        }

        hr {
            border-top: 1px dashed #555;
        }

        table {
            width: 100%;
            font-size: 13px;
            border-collapse: collapse;
        }

        td {
            padding: 5px 0;
        }

        .kanan {
            text-align: right;
        }

        .tombol {
            text-align: center;
            margin-top: 20px;
        }

        button {
            padding: 10px 18px;
            cursor: pointer;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .struk {
                border: none;
            }

            .tombol {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="struk">
    <h2>RINA POS</h2>
    <p>BUKTI PEMBAYARAN</p>

    <hr>

    <p style="text-align:left">
        Kode: <?= htmlspecialchars($transaksi['kode_transaksi']) ?><br>
        Tanggal: <?= htmlspecialchars($transaksi['tanggal']) ?>
    </p>

    <hr>

    <table>
        <?php while ($barang = mysqli_fetch_assoc($data_detail)): ?>
        <tr>
            <td colspan="2">
                <?= htmlspecialchars($barang['nama_barang']) ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= (int)$barang['jumlah'] ?> x
                Rp <?= number_format((float)$barang['harga'], 0, ',', '.') ?>
            </td>
            <td class="kanan">
                Rp <?= number_format((float)$barang['subtotal'], 0, ',', '.') ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <hr>

    <table>
        <tr>
            <td><b>Total</b></td>
            <td class="kanan">
                <b>Rp <?= number_format((float)$transaksi['total'], 0, ',', '.') ?></b>
            </td>
        </tr>
        <tr>
            <td>Bayar</td>
            <td class="kanan">
                Rp <?= number_format((float)$transaksi['bayar'], 0, ',', '.') ?>
            </td>
        </tr>
        <tr>
            <td>Kembalian</td>
            <td class="kanan">
                Rp <?= number_format((float)$transaksi['kembalian'], 0, ',', '.') ?>
            </td>
        </tr>
    </table>

    <hr>
    <p>Terima kasih telah berbelanja!</p>
</div>

<div class="tombol">
    <button onclick="window.print()">Cetak Struk</button>
    <button onclick="window.location.href='transaksi.php'">Kembali</button>
</div>

</body>
</html>
