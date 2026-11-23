
<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);

    echo json_encode([
        'error' => 'Tidak diizinkan'
    ]);

    exit;
}

require_once __DIR__ . '/config/koneksi.php';

$query = mysqli_query(
    $koneksi,
    "SELECT MAX(id) AS id_terbaru
     FROM pesanan_pelanggan
     WHERE status = 'Menunggu'"
);

if (!$query) {
    http_response_code(500);

    echo json_encode([
        'error' => 'Gagal mengambil data pesanan'
    ]);

    exit;
}

$data = mysqli_fetch_assoc($query);

echo json_encode([
    'id_terbaru' => (int)($data['id_terbaru'] ?? 0)
]);
