<?php
require_once 'session-check.php';
require_once './config/database.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    if ($userRole === 'admin') {
        header('Location: ../admin/index.php');
        exit;
    } elseif ($userRole === 'user') {
        header('Location: ../user/dashboard.php');
        exit;
    } else {
        // Jika peran tidak dikenali atau tidak ada, mungkin arahkan ke dashboard default atau halaman error
        header('Location: default_dashboard.php');
        exit;
    }
}

$error = '';

if ($_POST) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        try {
            $stmt = $db->prepare("SELECT user_id, role_id, full_name, email, password FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login berhasil
                createSession($user['user_id'], $user['role_id'], $user['full_name'], $user['email']);

                // Redirect berdasarkan role setelah login berhasil
                if ($user['role_id'] == 1) { // Admin
                    header('Location: index.php');
                } else { // User biasa
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Email atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lamarin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2 class="mb-0">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Masuk ke Lamarin
                </h2>
                <p class="mb-0 mt-2 opacity-75">Silakan login untuk melanjutkan</p>
            </div>

            <div class="login-body">
                <!-- Success Message -->
                <?php if (isset($_GET['registered']) && $_GET['registered'] == 'true'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        Pendaftaran berhasil! Silakan login dengan akun Anda.
                    </div>
                <?php endif; ?>

                <!-- Error Alert -->
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-2"></i>Email
                        </label>
                        <input type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            placeholder="Masukkan email Anda"
                            required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-2"></i>Password
                        </label>
                        <input type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Masukkan password Anda"
                            required>
                    </div>

                    <button type="submit" class="btn btn-login text-white w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>

                <!-- Register Link -->
                <div class="text-center">
                    <p class="mb-0">Belum punya akun?
                        <a href="register.php" class="text-decoration-none">Daftar di sini</a>
                    </p>
                    <a href="index.php" class="text-muted text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>

</html>