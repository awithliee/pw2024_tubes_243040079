<?php
// register.php
require_once 'config/database.php';

$error = '';
$success = '';

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);

    // Validasi input
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field wajib harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        try {
            // Cek apakah email sudah terdaftar
            $check_query = "SELECT user_id FROM users WHERE email = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$email]);

            if ($check_stmt->fetch()) {
                $error = 'Email sudah terdaftar! Silakan gunakan email lain.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user baru (role_id = 2 untuk user biasa)
                $insert_query = "INSERT INTO users (role_id, full_name, email, password, phone_number, address)
                                 VALUES (2, ?, ?, ?, ?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->execute([$full_name, $email, $hashed_password, $phone_number, $address]);
                // Setelah registrasi berhasil, redirect ke halaman login
                header("Location: login.php?registered=true");
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Lamarin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/register.css">

</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h2 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Daftar ke Lamarin
                </h2>
                <p class="mb-0 mt-2 opacity-75">Buat akun untuk memulai karir Anda</p>
            </div>

            <div class="register-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php
                // Pesan sukses tidak lagi dibutuhkan di sini karena kita melakukan redirect
                // if ($success):
                //     <div class="alert alert-success">
                //         <i class="fas fa-check-circle me-2"></i>
                //         echo $success;
                //     </div>
                // endif;
                ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">
                            <i class="fas fa-user me-2"></i>Nama Lengkap
                        </label>
                        <input type="text" class="form-control" id="full_name" name="full_name"
                               placeholder="Masukkan nama lengkap Anda" required
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-2"></i>Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="Masukkan email Anda" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Minimal 6 karakter" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Konfirmasi Password
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   placeholder="Ulangi password" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone_number" class="form-label">
                            <i class="fas fa-phone me-2"></i>Nomor Telepon
                        </label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number"
                               placeholder="Masukkan nomor telepon Anda"
                               value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
                    </div>

                    <div class="mb-4">
                        <label for="address" class="form-label">
                            <i class="fas fa-map-marker-alt me-2"></i>Alamat
                        </label>
                        <textarea class="form-control" id="address" name="address" rows="3"
                                  placeholder="Masukkan alamat lengkap Anda"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-register w-100 mb-3">
                        <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                    </button>
                </form>

                <div class="text-center">
                    <p class="mb-0">Sudah punya akun?
                        <a href="login.php" class="text-decoration-none">Masuk di sini</a>
                    </p>
                    <a href="index.php" class="text-muted text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>