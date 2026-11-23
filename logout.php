
<?php
session_start();

// Hapus seluruh data session
$_SESSION = [];

// Hapus session
session_destroy();

// Kembali ke halaman login
header("Location: login.php");
exit;
