<?php
session_start(); // Mulai session

// Sertakan file koneksi database. Path disesuaikan karena register.php ada di dalam folder 'php/'.
require_once '../service/database.php';

$error_message = '';    // Variabel untuk menyimpan pesan error
$success_message = '';  // Variabel untuk menyimpan pesan sukses

// Jika pengguna sudah login, arahkan ke dashboard.php (tidak perlu register lagi)
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php'); // dashboard.php juga ada di dalam folder php/
    exit();
}

// Tangani submit form registrasi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- Validasi Input ---
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Semua kolom harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password minimal 6 karakter.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Konfirmasi password tidak cocok.";
    } else {
        try {
            // Cek apakah username sudah terdaftar
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $error_message = "Username sudah terdaftar. Silakan gunakan username lain.";
            }

            // Cek apakah email sudah terdaftar (hanya jika tidak ada error username)
            if (empty($error_message)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $error_message = "Email sudah terdaftar. Silakan gunakan email lain.";
                }
            }

            // Jika tidak ada error validasi, masukkan data ke database
            if (empty($error_message)) {
                // Hash password sebelum disimpan ke database (PENTING!)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);

                if ($stmt->execute()) {
                    $success_message = "Registrasi berhasil! Silakan login.";
                    // Arahkan pengguna ke halaman login setelah beberapa detik (atau langsung)
                    header('Location: ../index.php?registered=true'); // Arahkan ke index.php di root
                    exit();
                } else {
                    $error_message = "Gagal melakukan registrasi. Silakan coba lagi.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan database: " . $e->getMessage();
            // Penting: Di lingkungan produksi, jangan tampilkan $e->getMessage() ke pengguna.
            // Gunakan error_log($e->getMessage()) untuk mencatat error di server.
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Diary Kenangan Angkatan Kuliah</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <main class="login-page-main-content">
        <h1 class="site-title">Website Diary</h1>

        <div class="container login-container">
            <h2>Daftar Akun Baru</h2>

            <?php if ($error_message): // Tampilkan pesan error jika ada ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <?php if ($success_message): // Tampilkan pesan sukses jika ada ?>
                <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary">Daftar</button>
            </form>
            <p>Sudah punya akun? <a href="../index.php">Login di sini</a></p>
        </div>
    </main>

    <script src="../js/index.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>