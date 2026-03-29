<?php
session_start();
session_destroy(); // Menghapus semua data session
header("Location: landing.php"); // Balik ke halaman utama
?>