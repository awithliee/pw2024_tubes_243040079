<?php
session_start();
require_once '../config/database.php';

// login sebagai user biasa
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// user
$query = "SELECT u.*, r.role_name FROM users u 
          JOIN roles r ON u.role_id = r.role_id 
          WHERE u.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// wuery lamaran dgn filter
$where_conditions = ["a.user_id = :user_id"];
$params = [':user_id' => $user_id];

if (!empty($status_filter)) {
    $where_conditions[] = "a.application_status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(j.job_title LIKE :search OR c.company_name LIKE :search2)";
    $params[':search'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
}

$where_clause = implode(' AND ', $where_conditions);

// lamaran dan detail lowongan
$applications_query = "SELECT 
    a.application_id,
    a.job_id,
    a.cover_letter,
    a.resume_file,
    a.application_status,
    a.admin_notes,
    a.created_at,
    a.updated_at,
    j.job_title,
    j.job_location,
    j.job_type,
    j.salary_range,
    j.application_deadline,
    c.company_name,
    c.company_location,
    c.industry
FROM applications a
JOIN jobs j ON a.job_id = j.job_id
JOIN companies c ON j.company_id = c.company_id
WHERE {$where_clause}
ORDER BY a.created_at DESC";

$stmt = $db->prepare($applications_query);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_applications,
    SUM(CASE WHEN application_status = 'Tertunda' THEN 1 ELSE 0 END) as Tertunda,
    SUM(CASE WHEN application_status = 'Ditinjau' THEN 1 ELSE 0 END) as Ditinjau,
    SUM(CASE WHEN application_status = 'Diwawancarai' THEN 1 ELSE 0 END) as Diwawancarai,
    SUM(CASE WHEN application_status = 'Diterima' THEN 1 ELSE 0 END) as Diterima,
    SUM(CASE WHEN application_status = 'Ditolak' THEN 1 ELSE 0 END) as Ditolak
FROM applications 
WHERE user_id = :user_id";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':user_id', $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// untuk mendapatkan status lencana
function getStatusBadgeClass($status) {
    switch($status) {
        case 'Tertunda': return 'bg-warning text-dark';
        case 'Ditinjau': return 'bg-info';
        case 'Diwawancarai': return 'bg-primary';
        case 'Diterima': return 'bg-success';
        case 'Ditolak': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// untuk mendaapatkan status ikon
function getStatusIcon($status) {
    switch($status) {
        case 'Tertunda': return 'fas fa-clock';
        case 'Ditinjau': return 'fas fa-eye';
        case 'Diwawancarai': return 'fas fa-users';
        case 'Diterima': return 'fas fa-check-circle';
        case 'Ditolak': return 'fas fa-times-circle';
        default: return 'fas fa-question-circle';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lamaran Saya - Lamarin Job Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/style-user/my-app.css">
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="my-applications.php">
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
                <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 rounded-bottom">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">Lamaran Saya</span>
                        <div class="d-flex align-items-center">
                            <span class="me-3">Selamat datang, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></span>
                            <img src="<?php echo $user['profile_photo'] ? 'uploads/profiles/' . $user['profile_photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=667eea&color=fff'; ?>"
                                alt="Profile" class="rounded-circle" width="40" height="40">
                        </div>
                    </div>
                </nav>

                <div class="container-fluid px-4">
                    <div class="row mb-4">
                        <div class="col-md-2 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                                    <h4><?php echo $stats['total_applications']; ?></h4>
                                    <small>Total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card stat-card-Tertunda">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <h4><?php echo $stats['Tertunda']; ?></h4>
                                    <small>Tertunda</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card stat-card-info">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-eye fa-2x mb-2"></i>
                                    <h4><?php echo $stats['Ditinjau']; ?></h4>
                                    <small>Ditinjau</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card stat-card-info">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <h4><?php echo $stats['Diwawancarai']; ?></h4>
                                    <small>Interview</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card stat-card-success">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h4><?php echo $stats['Diterima']; ?></h4>
                                    <small>Diterima</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card text-white" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                                    <h4><?php echo $stats['Ditolak']; ?></h4>
                                    <small>Ditolak</small>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Applications List -->
                    <?php if (empty($applications)): ?>
                        <div class="card">
                            <div class="card-body no-applications">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <h4>Belum Ada Lamaran</h4>
                                <p class="text-muted">Anda belum melamar pekerjaan apapun. Mulai cari pekerjaan impian Anda!</p>
                                <a href="../index.php" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Cari Pekerjaan
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <div class="card application-card mb-4">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="card-title mb-1">
                                                    <i class="fas fa-briefcase me-2 text-primary"></i>
                                                    <?php echo htmlspecialchars($app['job_title']); ?>
                                                </h5>
                                                <span class="badge status-badge <?php echo getStatusBadgeClass($app['application_status']); ?>">
                                                    <i class="<?php echo getStatusIcon($app['application_status']); ?> me-1"></i>
                                                    <?php echo $app['application_status']; ?>
                                                </span>
                                            </div>

                                            <div class="company-info mb-3">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong><i class="fas fa-building me-1"></i><?php echo htmlspecialchars($app['company_name']); ?></strong><br>
                                                        <small class="job-meta">
                                                            <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($app['company_location']); ?>
                                                        </small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small class="job-meta">
                                                            <i class="fas fa-industry me-1"></i><?php echo htmlspecialchars($app['industry']); ?><br>
                                                            <i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($app['job_type']); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="job-meta mb-3">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <i class="fas fa-money-bill-wave me-1"></i>
                                                        <strong>Gaji:</strong> <?php echo htmlspecialchars($app['salary_range'] ?: 'Nego'); ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <i class="fas fa-calendar-alt me-1"></i>
                                                        <strong>Deadline:</strong> <?php echo $app['application_deadline'] ? date('d M Y', strtotime($app['application_deadline'])) : 'Tidak ditentukan'; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if (!empty($app['cover_letter'])): ?>
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-envelope me-1"></i>
                                                        <strong>Cover Letter:</strong>
                                                    </small>
                                                    <p class="mb-0" style="font-size: 0.9rem;">
                                                        <?php echo nl2br(htmlspecialchars(substr($app['cover_letter'], 0, 200))); ?>
                                                        <?php if (strlen($app['cover_letter']) > 200): ?>
                                                            <span class="text-muted">...</span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($app['admin_notes'])): ?>
                                                <div class="alert alert-info alert-sm">
                                                    <i class="fas fa-sticky-note me-1"></i>
                                                    <strong>Catatan Admin:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars($app['admin_notes'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="timeline-item">
                                                <small class="text-muted">Dilamar</small><br>
                                                <strong><?php echo date('d M Y, H:i', strtotime($app['created_at'])); ?></strong>
                                            </div>
                                            
                                            <?php if ($app['created_at'] != $app['updated_at']): ?>
                                                <div class="timeline-item">
                                                    <small class="text-muted">Update Terakhir</small><br>
                                                    <strong><?php echo date('d M Y, H:i', strtotime($app['updated_at'])); ?></strong>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($app['resume_file']): ?>
                                                <div class="mt-3">
                                                    <a href="../cv/<?php echo $app['resume_file']; ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                                        <i class="fas fa-file-pdf me-1"></i>Lihat Resume
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal lamaran Details -->
    <div class="modal fade" id="applicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Lamaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function showApplicationDetails(applicationId) {
            // tampillkan place holder
            document.getElementById('modalContent').innerHTML = `
                <div class="text-center p-4">
                    <i class="fas fa-info-circle fa-3x text-primary mb-3"></i>
                    <h5>Detail Lamaran #${applicationId}</h5>
                    <p class="text-muted">Fitur ini akan menampilkan informasi detail lengkap tentang lamaran Anda.</p>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
            modal.show();
        }
    </script>
</body>

</html>