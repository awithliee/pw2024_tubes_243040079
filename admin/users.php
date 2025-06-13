<?php
// admin/users.php
session_start();
// Cek apakah user sudah login dan merupakan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}
require '../config/database.php';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $stmt = $db->prepare("INSERT INTO users (role_id, full_name, email, password, phone_number, address) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['role_id'],
                        $_POST['full_name'],
                        $_POST['email'],
                        password_hash($_POST['password'], PASSWORD_DEFAULT),
                        $_POST['phone_number'],
                        $_POST['address']
                    ]);
                    $success = "User created successfully!";
                    break;

                case 'update':
                    if (!empty($_POST['password'])) {
                        $stmt = $db->prepare("UPDATE users SET role_id=?, full_name=?, email=?, password=?, phone_number=?, address=? WHERE user_id=?");
                        $stmt->execute([
                            $_POST['role_id'],
                            $_POST['full_name'],
                            $_POST['email'],
                            password_hash($_POST['password'], PASSWORD_DEFAULT),
                            $_POST['phone_number'],
                            $_POST['address'],
                            $_POST['user_id']
                        ]);
                    } else {
                        $stmt = $db->prepare("UPDATE users SET role_id=?, full_name=?, email=?, phone_number=?, address=? WHERE user_id=?");
                        $stmt->execute([
                            $_POST['role_id'],
                            $_POST['full_name'],
                            $_POST['email'],
                            $_POST['phone_number'],
                            $_POST['address'],
                            $_POST['user_id']
                        ]);
                    }
                    $success = "User updated successfully!";
                    break;

                case 'delete':
                    $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    $success = "User deleted successfully!";
                    break;

                case 'toggle_status':
                    $stmt = $db->prepare("UPDATE users SET is_active = !is_active WHERE user_id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    $success = "User status updated successfully!";
                    break;
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// user details
$stmt = $db->query("
    SELECT u.*, r.role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.role_id 
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// dapatkan role
$stmt = $db->query("SELECT * FROM roles ORDER BY role_name");
$roles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Lamarin Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style-admin/adm-user.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../components/nav.php'; ?>
            <!-- Main Content -->
            <div class="col-md-10 px-0">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">Kelola Pengguna</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-plus me-2"></i>Tambahkan Pengguna
                        </button>
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $success ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Lengkap</th>
                                            <th>Email</th>
                                            <th>Telepon</th>
                                            <th>Peran</th>
                                            <th>Status</th>
                                            <th>dibuat</th>
                                            <th>tindakan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?= $user['user_id'] ?></td>
                                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= htmlspecialchars($user['phone_number']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $user['role_name'] == 'admin' ? 'danger' : 'primary' ?>">
                                                        <?= ucfirst($user['role_name']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($user['created_at']) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-<?= $user['is_active'] ? 'warning' : 'success' ?>" onclick="return confirm('Are you sure?')">
                                                                <i class="fas fa-<?= $user['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                                            </button>
                                                        </form>
                                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Modal</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    </head>

    <body>
        <!-- User Modal -->
        <div class="modal fade" id="userModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalTitle">Tambahkan Pengguna</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" id="userForm">
                        <div class="modal-body">
                            <input type="hidden" name="action" id="userAction" value="create">
                            <input type="hidden" name="user_id" id="userId">

                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama_lengkap" id="namaLengkap" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kata Sandi</label>
                                <input type="password" class="form-control" name="kata_sandi" id="kataSandi">
                                <small class="form-text text-muted" id="passwordHelp" style="display: none;">*Biarkan kosong untuk menyimpan kata sandi saat ini</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" class="form-control" name="telepon" id="telepon">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Peran</label>
                                <select class="form-select" name="role_id" id="roleId" required>
                                    <option value="">Pilih Peran</option>
                                    <option value="1">Admin</option>
                                    <option value="2">User</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" name="address" id="address" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
        <script>
            function editUser(user) {
                // Update modal title and form action
                document.getElementById('userModalTitle').textContent = 'Edit Peengguna';
                document.getElementById('userAction').value = 'update';
                document.getElementById('userId').value = user.user_id;

                // Populate form fields with consistent ID names
                document.getElementById('namaLengkap').value = user.full_name;
                document.getElementById('email').value = user.email;
                document.getElementById('kataSandi').value = '';
                document.getElementById('kataSandi').required = false;
                document.getElementById('passwordHelp').style.display = 'block';
                document.getElementById('telepon').value = user.phone_number;
                document.getElementById('roleId').value = user.role_id;
                document.getElementById('address').value = user.address;

                // Show modal
                new bootstrap.Modal(document.getElementById('userModal')).show();
            }

            // Reset form when modal is closed
            document.getElementById('userModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('userForm').reset();
                document.getElementById('userModalTitle').textContent = 'Tambahkan Pengguna';
                document.getElementById('userAction').value = 'create';
                document.getElementById('kataSandi').required = true;
                document.getElementById('passwordHelp').style.display = 'none';
            });

            // Handle form submission (optional - for testing)
            document.getElementById('userForm').addEventListener('submit', function(e) {
                e.preventDefault();
                alert('Form submitted! (This is just for demo)');
            });
        </script>
    </body>

    </html>
</body>

</html>