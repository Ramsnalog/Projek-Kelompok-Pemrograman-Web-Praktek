<?php
session_start();
require_once '../service/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id'])) {
    die("ID kenangan tidak ditemukan.");
}

$memory_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT title, content, memory_date FROM memories WHERE id = :id AND user_id = :user_id");
$stmt->execute([
    ':id' => $memory_id,
    ':user_id' => $_SESSION['user_id']
]);

$memory = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$memory) {
    die("Kenangan tidak ditemukan.");
}

// Persiapkan teks
$title = "Judul: " . $memory['title'];
$date = "Tanggal: " . $memory['memory_date'];
$content = "Kenangan:\n" . $memory['content'];

// Bungkus konten jadi baris pendek
$wrapped = wordwrap($content, 90, "\n");
$lines = explode("\n", "$title\n$date\n\n$wrapped");

// Tentukan ukuran gambar berdasarkan jumlah baris
$line_height = 20;
$padding = 40;
$width = 900;
$height = count($lines) * $line_height + $padding * 2;

// Buat gambar
$image = imagecreatetruecolor($width, $height);
$bg = imagecolorallocate($image, 255, 255, 255); // putih
$black = imagecolorallocate($image, 0, 0, 0); // hitam
imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Tulis teks
$y = $padding;
foreach ($lines as $line) {
    imagestring($image, 4, 30, $y, $line, $black);
    $y += $line_height;
}

// Header agar otomatis download JPG
header('Content-Type: image/jpeg');
header('Content-Disposition: attachment; filename="kenangan_' . date('Ymd_His') . '.jpg"');
imagejpeg($image);
imagedestroy($image);
exit;
?>
