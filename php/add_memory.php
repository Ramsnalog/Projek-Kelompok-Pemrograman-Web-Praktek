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

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // Untuk tampilan di header/logout

$error_message = '';   // Variabel untuk menyimpan pesan error
$success_message = ''; // Variabel untuk menyimpan pesan sukses

// Inisialisasi nilai form agar tidak hilang jika ada error
$title = '';
$content = '';
$memory_date = date('Y-m-d'); // Default tanggal hari ini

// Tangani submit form penambahan kenangan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $memory_date = $_POST['memory_date'] ?? '';
    $image_path = null; // Default path gambar adalah null

    // --- Validasi Input Form ---
    if (empty($title) || empty($content) || empty($memory_date)) {
        $error_message = "Judul, isi kenangan, dan tanggal harus diisi.";
    } else {
        // --- Penanganan Upload Gambar (jika ada file diunggah) ---
        // $_FILES['image']['error'] == UPLOAD_ERR_OK berarti file berhasil diunggah tanpa error
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['image']['tmp_name']; // Lokasi sementara file di server
            $file_name = $_FILES['image']['name'];       // Nama asli file
            $file_size = $_FILES['image']['size'];       // Ukuran file
            $file_type = $_FILES['image']['type'];       // Tipe MIME file
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION)); // Ekstensi file

            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif'); // Ekstensi file yang diizinkan
            $max_file_size = 5 * 1024 * 1024; // Maksimal ukuran file 5MB

            if (!in_array($file_ext, $allowed_extensions)) {
                $error_message = "Jenis file gambar tidak diizinkan. Hanya JPG, JPEG, PNG, GIF yang diperbolehkan.";
            } elseif ($file_size > $max_file_size) {
                $error_message = "Ukuran file gambar terlalu besar. Maksimal 5MB.";
            } else {
                // Buat nama file unik untuk menghindari tabrakan nama
                $new_file_name = uniqid('memory_') . '.' . $file_ext;
                // Folder tujuan upload, relatif dari file ini (add_memory.php ada di php/, jadi naik '../' ke root, lalu masuk 'uploads/')
                $upload_dir = '../uploads/';
                $destination = $upload_dir . $new_file_name;

                // Pindahkan file dari lokasi sementara ke folder tujuan
                if (move_uploaded_file($file_tmp_name, $destination)) {
                    // Simpan path relatif ke root website untuk disimpan di database
                    $image_path = 'uploads/' . $new_file_name;
                } else {
                    $error_message = "Gagal mengunggah gambar. Silakan coba lagi.";
                }
            }
        }

        // --- Simpan Kenangan ke Database (jika tidak ada error validasi atau upload) ---
        if (empty($error_message)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO memories (user_id, title, content, memory_date, image_path) VALUES (:user_id, :title, :content, :memory_date, :image_path)");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':content', $content);
                $stmt->bindParam(':memory_date', $memory_date);
                $stmt->bindParam(':image_path', $image_path); // Akan null jika tidak ada gambar diunggah

                if ($stmt->execute()) {
                    $success_message = "Kenangan berhasil ditambahkan!";
                    // Redirect ke dashboard setelah sukses untuk melihat kenangan yang baru ditambahkan
                    header('Location: dashboard.php?status=added');
                    exit();
                } else {
                    $error_message = "Gagal menambahkan kenangan. Silakan coba lagi.";
                }
            } catch (PDOException $e) {
                $error_message = "Terjadi kesalahan database: " . $e->getMessage();
                // Di lingkungan produksi, log error ini dan jangan tampilkan detailnya ke pengguna.
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kenangan Baru - Diary Kenangan</title>
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
                <h2>Tambah Kenangan Baru</h2>

                <?php if ($error_message): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
                <?php endif; ?>

                <form action="add_memory.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Judul Kenangan:</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="memory_date">Tanggal Kenangan:</label>
                        <input type="date" id="memory_date" name="memory_date" value="<?php echo htmlspecialchars($memory_date); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="content">Isi Kenangan:</label>
                        <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($content); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="image">Gambar Kenangan (opsional, maks 5MB):</label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif">
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Kenangan</button>
                </form>
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