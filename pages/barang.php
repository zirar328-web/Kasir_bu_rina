
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';

$pesan = '';
$jenis_pesan = 'success';
$edit = null;

$folderUpload = __DIR__ . '/../uploads/barang/';

// FUNGSI UPLOAD GAMBAR
function uploadGambar($namaInput, $folderUpload) {
    if (!isset($_FILES[$namaInput]) ||
        $_FILES[$namaInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$namaInput]['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if ($_FILES[$namaInput]['size'] > 2 * 1024 * 1024) {
        return false;
    }

    $info = getimagesize($_FILES[$namaInput]['tmp_name']);

    if ($info === false) {
        return false;
    }

    $ekstensi = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($ekstensi[$info['mime']])) {
        return false;
    }

    if (!is_dir($folderUpload)) {
        mkdir($folderUpload, 0755, true);
    }

    $namaFile = uniqid('barang_', true) . '.' . $ekstensi[$info['mime']];

    if (!move_uploaded_file(
        $_FILES[$namaInput]['tmp_name'],
        $folderUpload . $namaFile
    )) {
        return false;
    }

    return $namaFile;
}

// TAMBAH BARANG
if (isset($_POST['tambah'])) {
    $kode = trim($_POST['kode_barang']);
    $nama = trim($_POST['nama_barang']);
    $kategori = trim($_POST['kategori']);
    $harga = (float) $_POST['harga'];
    $stok = (int) $_POST['stok'];

    $gambar = uploadGambar('gambar', $folderUpload);

    if ($gambar === false) {
        $pesan = 'Foto gagal diunggah. Gunakan JPG, PNG, atau WEBP maksimal 2 MB.';
        $jenis_pesan = 'error';
    } else {
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO barang
            (kode_barang, nama_barang, kategori, harga, stok, gambar)
            VALUES (?, ?, ?, ?, ?, ?)");

        mysqli_stmt_bind_param(
            $stmt, "sssdis",
            $kode, $nama, $kategori, $harga, $stok, $gambar
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: barang.php?status=tambah");
            exit;
        } else {
            if ($gambar !== null && file_exists($folderUpload . $gambar)) {
                unlink($folderUpload . $gambar);
            }

            $pesan = 'Gagal menambahkan barang. Pastikan kode belum digunakan.';
            $jenis_pesan = 'error';
        }
    }
}

// AMBIL DATA EDIT
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = mysqli_prepare($koneksi,
        "SELECT * FROM barang WHERE id = ?");

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $edit = mysqli_fetch_assoc($hasil);
}

// UPDATE BARANG
if (isset($_POST['update'])) {
    $id = (int) $_POST['id'];
    $kode = trim($_POST['kode_barang']);
    $nama = trim($_POST['nama_barang']);
    $kategori = trim($_POST['kategori']);
    $harga = (float) $_POST['harga'];
    $stok = (int) $_POST['stok'];

    $gambarBaru = uploadGambar('gambar', $folderUpload);

    if ($gambarBaru === false) {
        $pesan = 'Foto gagal diunggah. Gunakan JPG, PNG, atau WEBP maksimal 2 MB.';
        $jenis_pesan = 'error';
    } else {
        if ($gambarBaru !== null) {
            $stmt = mysqli_prepare($koneksi,
                "UPDATE barang SET
                kode_barang = ?,
                nama_barang = ?,
                kategori = ?,
                harga = ?,
                stok = ?,
                gambar = ?
                WHERE id = ?");

            mysqli_stmt_bind_param(
                $stmt, "sssdisi",
                $kode, $nama, $kategori, $harga, $stok, $gambarBaru, $id
            );
        } else {
            $stmt = mysqli_prepare($koneksi,
                "UPDATE barang SET
                kode_barang = ?,
                nama_barang = ?,
                kategori = ?,
                harga = ?,
                stok = ?
                WHERE id = ?");

            mysqli_stmt_bind_param(
                $stmt, "sssdii",
                $kode, $nama, $kategori, $harga, $stok, $id
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            header("Location: barang.php?status=update");
            exit;
        } else {
            if ($gambarBaru !== null && file_exists($folderUpload . $gambarBaru)) {
                unlink($folderUpload . $gambarBaru);
            }

            $pesan = 'Gagal memperbarui barang.';
            $jenis_pesan = 'error';
        }
    }
}

// AKTIFKAN / NONAKTIFKAN BARANG
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];

    $stmt = mysqli_prepare($koneksi,
        "UPDATE barang SET status_barang =
        IF(status_barang = 'aktif', 'nonaktif', 'aktif')
        WHERE id = ?");

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    header("Location: barang.php?status=toggle");
    exit;
}

// PENCARIAN
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $kata = "%$cari%";

    $stmt = mysqli_prepare($koneksi,
        "SELECT * FROM barang
        WHERE kode_barang LIKE ?
        OR nama_barang LIKE ?
        ORDER BY id DESC");

    mysqli_stmt_bind_param($stmt, "ss", $kata, $kata);
} else {
    $stmt = mysqli_prepare($koneksi,
        "SELECT * FROM barang ORDER BY id DESC");
}

mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);

// PESAN STATUS
if (isset($_GET['status'])) {
    $status = $_GET['status'];

    if ($status === 'tambah') {
        $pesan = 'Barang berhasil ditambahkan!';
    } elseif ($status === 'update') {
        $pesan = 'Barang berhasil diperbarui!';
    } elseif ($status === 'toggle') {
        $pesan = 'Status barang berhasil diperbarui!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Barang - RINA POS</title>

<style>
* {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    margin: 0;
    background: #f5f2ee;
    color: #333;
}

.container {
    max-width: 1150px;
    margin: 35px auto;
    padding: 20px;
}

h2 {
    color: #543b2b;
}

a {
    text-decoration: none;
}

.back, .btn, button {
    display: inline-block;
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    color: white;
    background: #79543a;
}

.back {
    background: #543b2b;
    margin-bottom: 20px;
}

.panel {
    background: white;
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 22px;
    box-shadow: 0 3px 12px #0000000d;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

input {
    width: 100%;
    padding: 11px;
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-top: 7px;
}

.search {
    display: flex;
    gap: 10px;
}

.search input {
    margin: 0;
}

.cancel {
    background: #777;
}

.edit {
    background: #3979ad;
}

.delete {
    background: #b94b45;
}

.activate {
    background: #39834d;
}

.message {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.success {
    background: #e3f5e7;
    color: #246b36;
}

.error {
    background: #fce8e6;
    color: #a12622;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    min-width: 850px;
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #eee;
    text-align: left;
}

th {
    background: #f0e7df;
    color: #543b2b;
}

.foto {
    width: 65px;
    height: 55px;
    object-fit: cover;
    border-radius: 7px;
}

.status-aktif {
    color: #238342;
    font-weight: bold;
}

.status-nonaktif {
    color: #b94b45;
    font-weight: bold;
}

@media(max-width:600px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .search {
        flex-wrap: wrap;
    }
}
</style>
</head>

<body>

<div class="container">

<a href="../index.php" class="back">← Dashboard</a>

<h2>Manajemen Data Barang</h2>
<p>Kelola barang, stok, dan foto menu toko Bu Rina.</p>

<?php if ($pesan): ?>
<div class="message <?= $jenis_pesan ?>">
<?= htmlspecialchars($pesan) ?>
</div>
<?php endif; ?>

<div class="panel">

<h3><?= $edit ? 'Edit Barang' : 'Tambah Barang' ?></h3>

<form method="POST" enctype="multipart/form-data">

<?php if ($edit): ?>
<input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
<?php endif; ?>

<div class="form-grid">

<div>
<label>Kode Barang</label>
<input type="text" name="kode_barang" required
value="<?= htmlspecialchars($edit['kode_barang'] ?? '') ?>">
</div>

<div>
<label>Nama Barang</label>
<input type="text" name="nama_barang" required
value="<?= htmlspecialchars($edit['nama_barang'] ?? '') ?>">
</div>

<div>
<label>Kategori</label>
<input type="text" name="kategori" required
value="<?= htmlspecialchars($edit['kategori'] ?? '') ?>">
</div>

<div>
<label>Harga</label>
<input type="number" name="harga" min="0" step="0.01" required
value="<?= htmlspecialchars((string)($edit['harga'] ?? '')) ?>">
</div>

<div>
<label>Stok</label>
<input type="number" name="stok" min="0" required
value="<?= htmlspecialchars((string)($edit['stok'] ?? '0')) ?>">
</div>

<div>
<label>Foto Barang (JPG, PNG, WEBP maksimal 2 MB)</label>
<input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp">
</div>

</div>

<?php if ($edit && !empty($edit['gambar'])): ?>
<p>Foto saat ini:</p>
<img class="foto"
src="../uploads/barang/<?= htmlspecialchars($edit['gambar']) ?>"
alt="Foto barang">
<?php endif; ?>

<br><br>

<button type="submit" name="<?= $edit ? 'update' : 'tambah' ?>">
<?= $edit ? 'Simpan Perubahan' : 'Tambah Barang' ?>
</button>

<?php if ($edit): ?>
<a href="barang.php" class="btn cancel">Batal</a>
<?php endif; ?>

</form>
</div>

<div class="panel">

<h3>Daftar Barang</h3>

<form method="GET" class="search">
<input type="text" name="cari"
placeholder="Cari kode atau nama barang..."
value="<?= htmlspecialchars($cari) ?>">
<button type="submit">Cari</button>
<a href="barang.php" class="btn cancel">Reset</a>
</form>

<div class="table-wrap">

<table>
<thead>
<tr>
<th>No</th>
<th>Foto</th>
<th>Kode</th>
<th>Nama Barang</th>
<th>Kategori</th>
<th>Harga</th>
<th>Stok</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php $no = 1; ?>

<?php if (mysqli_num_rows($data) > 0): ?>

<?php while ($barang = mysqli_fetch_assoc($data)): ?>

<tr>

<td><?= $no++ ?></td>

<td>
<?php if (!empty($barang['gambar'])): ?>
<img class="foto"
src="../uploads/barang/<?= htmlspecialchars($barang['gambar']) ?>"
alt="Foto barang">
<?php else: ?>
Belum ada
<?php endif; ?>
</td>

<td><?= htmlspecialchars($barang['kode_barang']) ?></td>
<td><?= htmlspecialchars($barang['nama_barang']) ?></td>
<td><?= htmlspecialchars($barang['kategori']) ?></td>

<td>Rp <?= number_format($barang['harga'], 0, ',', '.') ?></td>

<td><?= (int)$barang['stok'] ?></td>

<td>
<span class="<?= $barang['status_barang'] === 'aktif' ? 'status-aktif' : 'status-nonaktif' ?>">
<?= htmlspecialchars(ucfirst($barang['status_barang'])) ?>
</span>
</td>

<td>
<a class="btn edit"
href="barang.php?edit=<?= (int)$barang['id'] ?>">
Edit
</a>

<a class="btn <?= $barang['status_barang'] === 'aktif' ? 'delete' : 'activate' ?>"
href="barang.php?toggle=<?= (int)$barang['id'] ?>"
onclick="return confirm('Yakin ingin mengubah status barang ini?')">
<?= $barang['status_barang'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
</a>
</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="9" style="text-align:center">
Belum ada data barang.
</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>
</div>
</div>

</body>
</html>
