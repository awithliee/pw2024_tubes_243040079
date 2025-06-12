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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Lamarin</h1>
            <p>Silakan login untuk melanjutkan</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div style="text-align: center; margin-top: 20px; color: #666;">
            <small>Default admin: admin@lamarin.com</small>
        </div>
    </div>
</body>

</html>