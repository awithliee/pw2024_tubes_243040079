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

// Get users with roles
$stmt = $db->query("
    SELECT u.*, r.role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.role_id 
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// Get roles for drodbwn
$stmt = $db->query("SELECT * FROM roles ORDER BY role_name");
$roles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Lamarin Admin</title>
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
                        <h2 class="mb-0">User Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-plus me-2"></i>Add New User
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
                                            <th>Full Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
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

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="userForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="userAction" value="create">
                        <input type="hidden" name="user_id" id="userId">

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="fullName" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" id="password">
                            <small class="form-text text-muted" id="passwordHelp">Leave blank to keep current password when editing</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone_number" id="phoneNumber">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role_id" id="roleId" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['role_id'] ?>"><?= ucfirst($role['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('userAction').value = 'update';
            document.getElementById('userId').value = user.user_id;
            document.getElementById('fullName').value = user.full_name;
            document.getElementById('email').value = user.email;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('passwordHelp').style.display = 'block';
            document.getElementById('phoneNumber').value = user.phone_number;
            document.getElementById('roleId').value = user.role_id;
            document.getElementById('address').value = user.address;

            new bootstrap.Modal(document.getElementById('userModal')).show();
        }

        // Reset form when modal is closed
        document.getElementById('userModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('userForm').reset();
            document.getElementById('userModalTitle').textContent = 'Add New User';
            document.getElementById('userAction').value = 'create';
            document.getElementById('password').required = true;
            document.getElementById('passwordHelp').style.display = 'none';
        });
    </script>
</body>

</html>