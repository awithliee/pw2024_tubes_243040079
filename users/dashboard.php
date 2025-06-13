<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header('Location: login.php');
    exit();
}
// Get user information
$query = "SELECT u.*, r.role_name FROM users u 
          JOIN roles r ON u.role_id = r.role_id 
          WHERE u.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get statistics for dashboard (removed job-related stats)
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM applications WHERE user_id = :user_id) as my_applications,
    (SELECT COUNT(*) FROM applications WHERE user_id = :user_id AND application_status = 'Pending') as pending_applications,
    (SELECT COUNT(*) FROM companies) as total_companies";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':user_id', $_SESSION['user_id']);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lamarin Job Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/style-user/dash.css">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-4 text-center">
                        <h4 class="text-white mb-0">
                            <i class="fas fa-briefcase me-2"></i>Lamarin
                        </h4>
                        <small class="text-white-50">Job Portal</small>
                    </div>

                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="my-applications.php">
                            <i class="fas fa-file-alt me-2"></i>Lamaran Saya
                        </a>
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-2"></i>Profile
                        </a>
                        <a class="nav-link" href="../index.php">
                            <i class="fa-solid fa-house me-2"></i>Beranda
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <!-- Top Navigation -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 rounded-bottom">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">Dashboard</span>
                        <div class="d-flex align-items-center">
                            <span class="me-3">Selamat datang, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></span>
                            <img src="<?php echo $user['profile_photo'] ? 'uploads/profiles/' . $user['profile_photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=667eea&color=fff'; ?>"
                                alt="Profile" class="rounded-circle" width="40" height="40">
                        </div>
                    </div>
                </nav>

                <div class="container-fluid px-4">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-alt fa-2x mb-3"></i>
                                    <h3><?php echo $stats['my_applications']; ?></h3>
                                    <p class="mb-0">Lamaran Saya</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock fa-2x mb-3"></i>
                                    <h3><?php echo $stats['pending_applications']; ?></h3>
                                    <p class="mb-0">Menunggu Review</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-building fa-2x mb-3"></i>
                                    <h3><?php echo $stats['total_companies']; ?></h3>
                                    <p class="mb-0">Total Perusahaan</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Profile Summary -->
                        <div class="col-md-6 mb-4">
                            <div class="profile-section">
                                <h5 class="mb-3"><i class="fas fa-user me-2"></i>Profil Saya</h5>
                                <div class="text-center mb-3">
                                    <img src="<?php echo $user['profile_photo'] ? 'uploads/profiles/' . $user['profile_photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=667eea&color=fff'; ?>"
                                        alt="Profile" class="rounded-circle mb-3" width="80" height="80">
                                    <h6><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                    <small class="opacity-75"><?php echo htmlspecialchars($user['email']); ?></small>
                                </div>
                                <div class="d-grid">
                                    <a href="profile.php" class="btn btn-light btn-sm">
                                        <i class="fas fa-edit me-1"></i>Edit Profil
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Information Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card info-card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-info-circle me-2"></i>Informasi
                                    </h5>
                                    <p class="card-text">
                                        Selamat datang di dashboard Lamarin Job Portal! Dari sini Anda dapat:
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check me-2"></i>Mengelola lamaran pekerjaan Anda</li>
                                        <li><i class="fas fa-check me-2"></i>Memperbarui profil pribadi</li>
                                        <li><i class="fas fa-check me-2"></i>Melihat status aplikasi</li>
                                    </ul>
                                    <div class="d-grid mt-3">
                                        <a href="my-applications.php" class="btn btn-light btn-sm">
                                            <i class="fas fa-arrow-right me-1"></i>Lihat Lamaran Saya
                                        </a>
                                    </div>
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