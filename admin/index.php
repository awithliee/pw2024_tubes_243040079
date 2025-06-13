<?php
// admin/index.php
session_start();

require '../config/database.php';

// Cek apakah user sudah login dan merupakan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: login.php');
    exit();
}

// Get dashboard statistics
try {
    // Count users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role_id = 2");
    $totalUsers = $stmt->fetch()['total'];

    // Count companies
    $stmt = $db->query("SELECT COUNT(*) as total FROM companies");
    $totalCompanies = $stmt->fetch()['total'];

    // Count jobs
    $stmt = $db->query("SELECT COUNT(*) as total FROM jobs WHERE is_active = 1");
    $totalJobs = $stmt->fetch()['total'];

    // Count applications
    $stmt = $db->query("SELECT COUNT(*) as total FROM applications");
    $totalApplications = $stmt->fetch()['total'];

    // Recent applications
    $stmt = $db->query("
        SELECT a.*, u.full_name, j.job_title, c.company_name 
        FROM applications a
        JOIN users u ON a.user_id = u.user_id
        JOIN jobs j ON a.job_id = j.job_id
        JOIN companies c ON j.company_id = c.company_id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $recentApplications = $stmt->fetchAll();

    // Recent users
    $stmt = $db->query("
        SELECT * FROM users 
        WHERE role_id = 2 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentUsers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lamarin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style-admin/admin.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../components/nav.php'; ?>
            <!-- Main Content -->
            <div class="col-md-10 px-0">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-0">Dashboard</h2>
                            <p class="text-muted">Selamat Datang, <?= htmlspecialchars($_SESSION['full_name']) ?>!</p>
                        </div>
                        <div class="text-muted">
                            <i class="fas fa-calendar me-2"></i><?= date('l, d F Y') ?>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card blue">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-0"><?= $totalUsers ?></h3>
                                            <p class="mb-0">Total Pengguna</p>
                                        </div>
                                        <i class="fas fa-users fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card green">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-0"><?= $totalCompanies ?></h3>
                                            <p class="mb-0">Perusahaan</p>
                                        </div>
                                        <i class="fas fa-building fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card orange">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-0"><?= $totalJobs ?></h3>
                                            <p class="mb-0">Lowongan</p>
                                        </div>
                                        <i class="fas fa-briefcase fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-0"><?= $totalApplications ?></h3>
                                            <p class="mb-0">Lamaran</p>
                                        </div>
                                        <i class="fas fa-file-alt fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Data -->
                    <div class="row">
                        <!-- Recent Applications -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-file-alt me-2 text-primary"></i>Lamran Terbaru
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <th class="table-light">
                                                <tr>
                                                    <th>Pelamar</th>
                                                    <th>Jabatan</th>
                                                    <th>Perusahaan</th>
                                                    <th>Status</th>
                                                    <th>Tanggal</th>
                                                </tr>
                                            </th>
                                            <tbody>
                                                <?php foreach ($recentApplications as $app): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($app['full_name']) ?></td>
                                                        <td><?= htmlspecialchars($app['job_title']) ?></td>
                                                        <td><?= htmlspecialchars($app['company_name']) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $app['application_status'] == 'Pending' ? 'warning' : ($app['application_status'] == 'Offered' ? 'success' : 'info') ?>">
                                                                <?= $app['application_status'] ?>
                                                            </span>
                                                        </td>
                                                        <td><?= htmlspecialchars($app['created_at']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Users -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-plus me-2 text-success"></i>Pengguna Terbaru
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($recentUsers as $user): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0"><?= htmlspecialchars($user['full_name']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                            </div>
                                            <small class="text-muted"><?= htmlspecialchars($user['created_at']) ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>