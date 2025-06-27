<?php
session_start(); // Mulai session

// Sertakan file koneksi database
require_once '../service/database.php';

// Proteksi Halaman: Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id']; // Ambil ID pengguna yang sedang login

// Periksa apakah ID kenangan diberikan melalui URL (GET request)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $memory_id = $_GET['id'];

    try {
        // Pertama, ambil jalur gambar jika ada, untuk dihapus dari server
        $stmt_select_image = $pdo->prepare("SELECT image_path FROM memories WHERE id = :memory_id AND user_id = :user_id");
        $stmt_select_image->bindParam(':memory_id', $memory_id);
        $stmt_select_image->bindParam(':user_id', $user_id);
        $stmt_select_image->execute();
        $memory = $stmt_select_image->fetch(PDO::FETCH_ASSOC);

        if ($memory) {
            $image_path_to_delete = $memory['image_path'];

            // Hapus kenangan dari database
            $stmt_delete = $pdo->prepare("DELETE FROM memories WHERE id = :memory_id AND user_id = :user_id");
            $stmt_delete->bindParam(':memory_id', $memory_id);
            $stmt_delete->bindParam(':user_id', $user_id); // Penting: Pastikan hanya pengguna yang memiliki kenangan yang bisa menghapus

            if ($stmt_delete->execute()) {
                // Jika penghapusan dari DB berhasil, coba hapus file gambar dari server
                if ($image_path_to_delete && file_exists('../' . $image_path_to_delete)) {
                    unlink('../' . $image_path_to_delete); // Hapus file
                }
                header('Location: dashboard.php?status=deleted'); // Redirect ke dashboard dengan pesan sukses
                exit();
            } else {
                // Jika gagal menghapus dari database
                header('Location: dashboard.php?status=delete_failed');
                exit();
            }
        } else {
            // Jika kenangan tidak ditemukan atau bukan milik pengguna
            header('Location: dashboard.php?status=not_found_or_unauthorized');
            exit();
        }
    } catch (PDOException $e) {
        // Tangani error database
        error_log("Database error during memory deletion: " . $e->getMessage()); // Log error untuk debugging
        header('Location: dashboard.php?status=db_error');
        exit();
    }
} else {
    // Jika tidak ada ID kenangan yang diberikan
    header('Location: dashboard.php?status=no_id');
    exit();
}
?>