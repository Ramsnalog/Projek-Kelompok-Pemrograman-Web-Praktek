<?php
// Konfigurasi database
$host     = "localhost";     // Biasanya localhost
$username = "root";          // Username default XAMPP
$password = "";              // Kosongkan jika belum diset
$database = "dairy_db";      // Nama database yang kamu buat

// Membuat koneksi
$koneksi = new mysqli($host, $username, $password, $database);

// Cek koneksi berhasil atau tidak
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
} else {
    // echo "Koneksi berhasil";
}
?>
