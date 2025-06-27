<?php
session_start();
require_once '../service/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$memories = [];

try {
    $stmt = $pdo->prepare("SELECT id, title, content, image_path, memory_date, created_at FROM memories WHERE user_id = :user_id ORDER BY memory_date DESC, created_at DESC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Terjadi kesalahan saat mengambil kenangan: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Diary Kenangan Angkatan Kuliah</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .btn-download {
            background-color: #0d6efd;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px; /* Tambah jarak bawah dari tombol edit/hapus */
        }
        .btn-download:hover {
            background-color: #0b5ed7;
        }
    </style>
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
                    <a href="logout.php">Logout</a>
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
            <h2>Kenangan Saya</h2>
            <a href="add_memory.php" class="btn btn-primary btn-add-memory">Tambah Kenangan Baru</a>

            <?php if (empty($memories)): ?>
                <p class="no-memories-message">Anda belum memiliki kenangan. Mari buat yang pertama!</p>
            <?php else: ?>
                <div class="memories-grid">
                    <?php foreach ($memories as $memory): ?>
                        <div class="memory-card">
                            <h3><?php echo htmlspecialchars($memory['title']); ?></h3>
                            <p class="memory-date">Tanggal: <?php echo date('d M Y', strtotime($memory['memory_date'])); ?></p>
                            <p class="memory-content"><?php echo nl2br(htmlspecialchars($memory['content'])); ?></p>
                            <?php if ($memory['image_path']): ?>
                                <img src="../<?php echo htmlspecialchars($memory['image_path']); ?>" alt="Gambar Kenangan" class="memory-image">
                                <a href="../<?php echo htmlspecialchars($memory['image_path']); ?>" download class="btn-download">Simpan</a>
                            <?php endif; ?>
                            <div class="memory-actions">
                                <a href="edit_memory.php?id=<?php echo $memory['id']; ?>" class="btn btn-secondary btn-edit">Edit</a>
                                <a href="delete_memory.php?id=<?php echo $memory['id']; ?>" class="btn btn-danger btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus kenangan ini?');">Hapus</a>
                            </div>
                            <p class="created-at">Dibuat: <?php echo date('d M Y H:i', strtotime($memory['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
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
