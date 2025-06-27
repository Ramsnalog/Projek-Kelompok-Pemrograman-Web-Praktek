<?php
session_start();

require_once 'service/database.php';

$error_message = '';

if (isset($_SESSION['user_id'])) {
    header('Location: php/dashboard.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = $_POST['username_or_email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $error_message = "Username/Email dan password harus diisi.";
    } else {
        try {
            if (filter_var($username_or_email, FILTER_VALIDATE_EMAIL)) {
                $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = :identifier");
            } else {
                $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :identifier");
            }
            $stmt->bindParam(':identifier', $username_or_email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: php/dashboard.php'); 
                exit();
            } else {
                $error_message = "Username/Email atau password salah.";
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Diary Kenangan Angkatan Kuliah</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <main class="login-page-main-content">
        <h1 class="site-title">Website Diary</h1>

        <div class="container login-container">
            <h2>Login ke Diary Kenangan</h2>

            <?php if ($error_message): ?>
                <p class="error-message" style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <div class="form-group">
                    <label for="username_or_email">Username atau Email:</label>
                    <input type="text" id="username_or_email" name="username_or_email" value="<?php echo htmlspecialchars($username_or_email ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            <p>Belum punya akun? <a href="php/register.php">Daftar di sini</a></p>
        </div>
    </main>

    <script src="js/index.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>