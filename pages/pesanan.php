
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';

$error = '';

$statusValid = ['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'];

// PROSES UBAH STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_status'])) {
    $id = (int)($_POST['id_pesanan'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($id > 0 && in_array($status, $statusValid, true)) {
        $stmt = mysqli_prepare($koneksi, "
            UPDATE pesanan_pelanggan
            SET status = ?
            WHERE id = ?
        ");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $status, $id);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: pesanan.php?pesan=berhasil");
                exit;
            } else {
                $error = "Gagal mengubah status pesanan.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $error = "Gagal menyiapkan perubahan status.";
        }
    } else {
        $error = "Data tidak valid.";
    }
}

// PENCARIAN DAN FILTER
$cari = trim($_GET['cari'] ?? '');
$filter = $_GET['status'] ?? '';

if (!in_array($filter, $statusValid, true)) {
    $filter = '';
}

$sql = "
    SELECT *
    FROM pesanan_pelanggan
    WHERE 1=1
";

if ($cari !== '') {
    $sql .= "
        AND (
            kode_pesanan LIKE ?
            OR nama_pelanggan LIKE ?
        )
    ";
}

if ($filter !== '') {
    $sql .= " AND status = ? ";
}

$sql .= " ORDER BY tanggal_pesanan DESC";

$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    die("Gagal menyiapkan query: " . mysqli_error($koneksi));
}

if ($cari !== '' && $filter !== '') {
    $kata = "%" . $cari . "%";
    mysqli_stmt_bind_param($stmt, "sss", $kata, $kata, $filter);
} elseif ($cari !== '') {
    $kata = "%" . $cari . "%";
    mysqli_stmt_bind_param($stmt, "ss", $kata, $kata);
} elseif ($filter !== '') {
    mysqli_stmt_bind_param($stmt, "s", $filter);
}

mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);

if (!$query) {
    die("Gagal mengambil pesanan.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pesanan Pelanggan - RINA POS</title>

<style>
* {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f5f2ee;
    margin: 0;
    padding: 25px;
    color: #333;
}

.container {
    max-width: 1250px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #0000000d;
}

h1 {
    text-align: center;
    color: #543b2b;
    margin-bottom: 25px;
}

.atas {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.kembali {
    display: inline-block;
    background: #543b2b;
    color: white;
    padding: 10px 15px;
    border-radius: 6px;
    text-decoration: none;
}

.form-filter {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.form-filter input,
.form-filter select {
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.form-filter input {
    width: 260px;
}

button {
    padding: 9px 13px;
    background: #16834a;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

button:hover {
    background: #106b3c;
}

.form-filter button {
    padding: 11px 18px;
}

.reset {
    display: inline-block;
    padding: 11px 13px;
    background: #777;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}

select {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.tabel {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

th {
    background: #543b2b;
    color: white;
}

.detail {
    color: #16834a;
    font-weight: bold;
    text-decoration: none;
}

.detail:hover {
    text-decoration: underline;
}

.pesan {
    background: #dff5e5;
    color: #176b37;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 7px;
}

.error {
    background: #ffe3e3;
    color: #a40000;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 7px;
}

.status {
    font-weight: bold;
}

.hasil {
    margin-bottom: 15px;
    color: #666;
}

@media(max-width: 600px) {
    body {
        padding: 12px;
    }

    .container {
        padding: 15px;
    }

    .form-filter {
        width: 100%;
    }

    .form-filter input {
        width: 100%;
    }
}
</style>
</head>

<body>

<div class="container">

    <h1>Data Pesanan Pelanggan</h1>

    <div class="atas">

        <a href="../index.php" class="kembali">
            ← Kembali ke Dashboard
        </a>

        <form method="GET" class="form-filter">

            <input
                type="text"
                name="cari"
                placeholder="Cari kode atau nama pelanggan..."
                value="<?php echo htmlspecialchars($cari); ?>"
            >

            <select name="status">
                <option value="">Semua Status</option>

                <?php foreach ($statusValid as $s) : ?>
                    <option
                        value="<?php echo htmlspecialchars($s); ?>"
                        <?php echo $filter === $s ? 'selected' : ''; ?>
                    >
                        <?php echo htmlspecialchars($s); ?>
                    </option>
                <?php endforeach; ?>

            </select>

            <button type="submit">Terapkan</button>

            <a href="pesanan.php" class="reset">Reset</a>

        </form>

    </div>

    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'berhasil') : ?>
        <div class="pesan">
            Status pesanan berhasil diperbarui!
        </div>
    <?php endif; ?>

    <?php if ($error !== '') : ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="hasil">
        Menampilkan
        <strong><?php echo mysqli_num_rows($query); ?></strong>
        pesanan.
    </div>

    <div class="tabel">

        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Pesanan</th>
                    <th>Nama Pelanggan</th>
                    <th>No. HP</th>
                    <th>Alamat</th>
                    <th>Total</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Ubah Status</th>
                    <th>Detail</th>
                </tr>
            </thead>

            <tbody>

            <?php if (mysqli_num_rows($query) > 0) : ?>

                <?php $no = 1; ?>

                <?php while ($pesanan = mysqli_fetch_assoc($query)) : ?>

                <tr>
                    <td><?php echo $no++; ?></td>

                    <td>
                        <?php echo htmlspecialchars($pesanan['kode_pesanan']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($pesanan['nama_pelanggan']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($pesanan['no_hp']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($pesanan['alamat']); ?>
                    </td>

                    <td>
                        Rp <?php echo number_format($pesanan['total'], 0, ',', '.'); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($pesanan['tanggal_pesanan']); ?>
                    </td>

                    <td class="status">
                        <?php echo htmlspecialchars($pesanan['status']); ?>
                    </td>

                    <td>
                        <form method="POST" onsubmit="return konfirmasiStatus(this);">

                            <input
                                type="hidden"
                                name="id_pesanan"
                                value="<?php echo (int)$pesanan['id']; ?>"
                            >

                            <select name="status">
                                <?php foreach ($statusValid as $s) : ?>
                                    <option
                                        value="<?php echo htmlspecialchars($s); ?>"
                                        <?php echo $pesanan['status'] === $s ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($s); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" name="ubah_status">
                                Simpan
                            </button>
                        </form>
                    </td>

                    <td>
                        <a
                            class="detail"
                            href="detail_pesanan.php?id=<?php echo (int)$pesanan['id']; ?>"
                        >
                            Lihat Detail
                        </a>
                    </td>
                </tr>

                <?php endwhile; ?>

            <?php else : ?>

                <tr>
                    <td colspan="10" style="text-align:center;">
                        Pesanan tidak ditemukan.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>

    </div>

</div>

<script>
function konfirmasiStatus(form) {
    const status = form.querySelector('select[name="status"]').value;

    return confirm(
        'Apakah kamu yakin ingin mengubah status pesanan menjadi ' 
        + status + '?'
    );
}
</script>

</body>
</html>
