<?php

$koneksi = mysqli_connect(
    "localhost",
    "root",
    "",
    "db_kasir_burina",
    3307
);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}