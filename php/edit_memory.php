<?php
session_start(); // Mulai session

// Sertakan file koneksi database
require_once '../service/database.php';

// --- Proteksi Halaman: Periksa apakah pengguna sudah login ---
// Jika tidak ada 'user_id' di session, arahkan kembali mereka ke halaman login (index.php) di root.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php'); // Arahkan ke halaman login
    exit();
}

$user_id = $_SESSION['user_id'];   // Ambil ID pengguna yang sedang login
$username = $_SESSION['username']; // Untuk tampilan di header/logout

$error_message = '';   // Variabel untuk menyimpan pesan error
$success_message = ''; // Variabel untuk menyimpan pesan sukses

$memory_data = [];     // Array untuk menyimpan data kenangan yang akan diedit

// --- Logika untuk MENAMPILKAN FORM dengan data yang sudah ada (saat pertama kali halaman diakses melalui GET) ---
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Pastikan ID kenangan diberikan di URL
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header('Location: dashboard.php?status=no_id_provided'); // Redirect jika tidak ada ID
        exit();
    }

    $memory_id = $_GET['id'];

    try {
        // Ambil data kenangan berdasarkan ID DAN PASTIKAN ITU MILIK PENGGUNA YANG SEDANG LOGIN (KEAMANAN PENTING!)
        $stmt = $pdo->prepare("SELECT id, title, content, image_path, memory_date FROM memories WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $memory_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $memory_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Jika kenangan tidak ditemukan atau bukan milik pengguna, redirect
        if (!$memory_data) {
            header('Location: dashboard.php?status=not_found_or_unauthorized');
            exit();
        }
    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan database saat mengambil kenangan: " . $e->getMessage();
        // error_log($e->getMessage()); // Catat error untuk debugging
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Logika untuk MEMPROSES UPDATE FORM (saat form disubmit) ---
    $memory_id = $_POST['memory_id'] ?? ''; // Ambil ID dari hidden field
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $memory_date = $_POST['memory_date'] ?? '';
    $current_image_path = $_POST['current_image_path'] ?? null; // Path gambar yang sudah ada (dari hidden field)
    $image_path = $current_image_path; // Secara default, pertahankan gambar lama

    // Re-populate memory_data agar form tetap terisi jika ada error saat POST
    $memory_data = [
        'id' => $memory_id,
        'title' => $title,
        'content' => $content,
        'memory_date' => $memory_date,
        'image_path' => $current_image_path
    ];

    // --- Validasi Input Form ---
    if (empty($memory_id) || empty($title) || empty($content) || empty($memory_date)) {
        $error_message = "Semua kolom penting harus diisi.";
    } else {
        // Verifikasi ulang kepemilikan memory_id sebelum melakukan update (KEAMANAN PENTING!)
        $stmt_check_owner = $pdo->prepare("SELECT id, image_path FROM memories WHERE id = :id AND user_id = :user_id");
        $stmt_check_owner->bindParam(':id', $memory_id);
        $stmt_check_owner->bindParam(':user_id', $user_id);
        $stmt_check_owner->execute();
        $existing_memory = $stmt_check_owner->fetch(PDO::FETCH_ASSOC);

        if (!$existing_memory) {
            $error_message = "Kenangan tidak ditemukan atau Anda tidak memiliki izin untuk mengeditnya.";
        } else {
            // --- Penanganan Upload Gambar Baru (jika ada file baru diunggah) ---
            if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $file_tmp_name = $_FILES['image']['tmp_name'];
                $file_name = $_FILES['image']['name'];
                $file_size = $_FILES['image']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
                $max_file_size = 5 * 1024 * 1024; // 5MB

                if (!in_array($file_ext, $allowed_extensions)) {
                    $error_message = "Jenis file gambar baru tidak diizinkan. Hanya JPG, JPEG, PNG, GIF yang diperbolehkan.";
                } elseif ($file_size > $max_file_size) {
                    $error_message = "Ukuran file gambar baru terlalu besar. Maksimal 5MB.";
                } else {
                    $new_file_name = uniqid('memory_') . '.' . $file_ext;
                    $upload_dir = '../uploads/'; // Folder uploads di root DAIRY/
                    $destination = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp_name, $destination)) {
                        $image_path = 'uploads/' . $new_file_name; // Path baru untuk DB

                        // Hapus gambar lama jika ada dan gambar baru berhasil diunggah
                        if ($existing_memory['image_path'] && file_exists('../' . $existing_memory['image_path'])) {
                            unlink('../' . $existing_memory['image_path']);
                        }
                    } else {
                        $error_message = "Gagal mengunggah gambar baru. Silakan coba lagi.";
                    }
                }
            }
            // Jika tidak ada gambar baru diunggah dan tidak ada error lainnya, $image_path akan tetap $current_image_path.
            // Jika ingin menghapus gambar tanpa mengunggah baru, diperlukan logika atau tombol terpisah.
            
            // --- Lanjutkan Update ke Database (jika tidak ada error) ---
            if (empty($error_message)) {
                try {
                    $stmt = $pdo->prepare("UPDATE memories SET title = :title, content = :content, memory_date = :memory_date, image_path = :image_path, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :user_id");
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':content', $content);
                    $stmt->bindParam(':memory_date', $memory_date);
                    $stmt->bindParam(':image_path', $image_path); // Bisa null, bisa path baru, bisa path lama
                    $stmt->bindParam(':id', $memory_id);
                    $stmt->bindParam(':user_id', $user_id);

                    if ($stmt->execute()) {
                        $success_message = "Kenangan berhasil diperbarui!";
                        // Redirect ke dashboard setelah sukses
                        header('Location: dashboard.php?status=updated');
                        exit();
                    } else {
                        $error_message = "Gagal memperbarui kenangan. Silakan coba lagi.";
                    }
                } catch (PDOException $e) {
                    $error_message = "Terjadi kesalahan database: " . $e->getMessage();
                }
            }
        }
    }
} else {
    // Jika diakses tanpa ID (GET request) dan bukan dari submit POST,
    // maka $memory_data akan kosong atau tidak valid, dan form tidak akan terisi.
    // Error message akan ditampilkan oleh bagian HTML di bawah.
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kenangan - Diary Kenangan</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="container">
                <a href="dashboard.php" class="logo">Diary Kenangan</a>
                <ul class="nav-links">
                    <li><a href="dashboard.php">Kenangan Saya</a></li>
                    <li><a href="add_memory.php">Tambah Kenangan</a></li>
                    <li class="nav-logout-btn">
                        <a href="logout.php">
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <main class="main-content">
        <div class="container">
            <aside class="sidebar">
                <div class="sidebar-header">
                    <h3>Menu Diary</h3>
                </div>
                <ul class="sidebar-nav">
                    <li><a href="dashboard.php">Lihat Semua Kenangan</a></li>
                    <li><a href="add_memory.php">Buat Kenangan Baru</a></li>
                </ul>
            </aside>

            <section class="content-area">
                <h2>Edit Kenangan</h2>

                <?php if ($error_message): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
                <?php endif; ?>

                <?php if (empty($memory_data) && $_SERVER["REQUEST_METHOD"] == "GET"): ?>
                    <p class="error-message">Kenangan tidak ditemukan atau ID tidak valid. Silakan pilih kenangan dari <a href="dashboard.php">Dashboard</a>.</p>
                <?php else: ?>
                <form action="edit_memory.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="memory_id" value="<?php echo htmlspecialchars($memory_data['id'] ?? ''); ?>">
                    <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($memory_data['image_path'] ?? ''); ?>">

                    <div class="form-group">
                        <label for="title">Judul Kenangan:</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($memory_data['title'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="memory_date">Tanggal Kenangan:</label>
                        <input type="date" id="memory_date" name="memory_date" value="<?php echo htmlspecialchars($memory_data['memory_date'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="content">Isi Kenangan:</label>
                        <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($memory_data['content'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Gambar Kenangan Saat Ini:</label>
                        <?php if ($memory_data['image_path']): // Tampilkan gambar saat ini jika ada ?>
                            <img src="../<?php echo htmlspecialchars($memory_data['image_path']); ?>" alt="Gambar Saat Ini" class="current-memory-image">
                            <p style="font-size:0.9em; color:var(--secondary-color); margin-top:5px;">Upload gambar baru untuk mengganti gambar ini.</p>
                        <?php else: ?>
                            <p style="font-size:0.9em; color:var(--secondary-color); margin-top:5px;">Tidak ada gambar saat ini.</p>
                        <?php endif; ?>
                        <label for="image" style="margin-top: 15px;">Unggah Gambar Baru (opsional, maks 5MB):</label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif">
                    </div>
                    <button type="submit" class="btn btn-primary">Perbarui Kenangan</button>
                    <a href="dashboard.php" class="btn btn-secondary" style="margin-left: 10px;">Batal</a>
                </form>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <footer>
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> Diary Kenangan Angkatan Kuliah. Dibuat oleh Attar, Rama, dan Taib.</p>
                <div class="social-icons">
                    <a href="https://wa.me/6283121562697" target="_blank" aria-label="WhatsApp">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/1200px-WhatsApp.svg.png" alt="WhatsApp" class="social-icon">
                    </a>
                    <a href="https://instagram.com/arsyattar_" target="_blank" aria-label="Instagram">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Instagram_icon.png/2048px-Instagram_icon.png" alt="Instagram" class="social-icon">
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script src="../js/index.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>