<?php
// Konfigurasi Database
$host     = "localhost";
$username = "root";
$password = ""; // Kosongkan jika menggunakan XAMPP default
$database = "db_pinfo";

// Membuat koneksi
$koneksi = mysqli_connect($host, $username, $password, $database);

// Memeriksa koneksi
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>