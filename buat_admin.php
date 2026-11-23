
<?php
require_once 'config/koneksi.php';

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $username === '' || $password === '') {
        $pesan = "Semua kolom wajib diisi!";
    } elseif (strlen($password) < 8) {
        $pesan = "Password minimal 8 karakter!";
    } else {
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id FROM users WHERE username = ?"
        );

        mysqli_stmt_bind_param($cek, "s", $username);
        mysqli_stmt_execute($cek);
        $hasil = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasil) > 0) {
            $pesan = "Username sudah digunakan!";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO users (nama, username, password, role)
                 VALUES (?, ?, ?, 'admin')"
            );

            mysqli_stmt_bind_param(
                $stmt, "sss", $nama, $username, $hash
            );

            if (mysqli_stmt_execute($stmt)) {
                $pesan = "Akun admin berhasil dibuat!";
            } else {
                $pesan = "Gagal membuat akun admin.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Buat Admin RINA POS</title>
</head>
<body>
<h2>Buat Akun Admin RINA POS</h2>

<p><?= htmlspecialchars($pesan) ?></p>

<form method="POST">
    <label>Nama</label><br>
    <input type="text" name="nama" required><br><br>

    <label>Username</label><br>
    <input type="text" name="username" required><br><br>

    <label>Password (minimal 8 karakter)</label><br>
    <input type="password" name="password" required minlength="8"><br><br>

    <button type="submit">Buat Akun Admin</button>
</form>
</body>
</html>
