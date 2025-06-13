<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information
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

// Build query for applications with filters
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

// Get applications with job and company details
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
    SUM(CASE WHEN application_status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN application_status = 'Reviewed' THEN 1 ELSE 0 END) as reviewed,
    SUM(CASE WHEN application_status = 'Interviewed' THEN 1 ELSE 0 END) as interviewed,
    SUM(CASE WHEN application_status = 'Offered' THEN 1 ELSE 0 END) as offered,
    SUM(CASE WHEN application_status = 'Rejected' THEN 1 ELSE 0 END) as rejected
FROM applications 
WHERE user_id = :user_id";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':user_id', $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch($status) {
        case 'Pending': return 'bg-warning text-dark';
        case 'Reviewed': return 'bg-info';
        case 'Interviewed': return 'bg-primary';
        case 'Offered': return 'bg-success';
        case 'Rejected': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// Function to get status icon
function getStatusIcon($status) {
    switch($status) {
        case 'Pending': return 'fas fa-clock';
        case 'Reviewed': return 'fas fa-eye';
        case 'Interviewed': return 'fas fa-users';
        case 'Offered': return 'fas fa-check-circle';
        case 'Rejected': return 'fas fa-times-circle';
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
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 20px;
            border-radius: 10px;
            margin: 5px 15px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateX(5px);
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-card-pending {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #333;
        }

        .stat-card-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stat-card-info {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }

        .application-card {
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }

        .application-card:hover {
            border-left-color: #764ba2;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .job-meta {
            color: #666;
            font-size: 0.9rem;
        }

        .company-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
        }

        .filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-filter {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }

        .btn-filter:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .no-applications {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .timeline-item {
            position: relative;
            padding-left: 30px;
            margin-bottom: 15px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #667eea;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: 11px;
            top: 16px;
            width: 2px;
            height: calc(100% - 8px);
            background: #e9ecef;
        }

        .timeline-item:last-child::after {
            display: none;
        }
    </style>
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
                <!-- Top Navigation -->
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
                    <!-- Statistics Cards -->
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
                            <div class="card stat-card-pending">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <h4><?php echo $stats['pending']; ?></h4>
                                    <small>Pending</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card stat-card-info">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-eye fa-2x mb-2"></i>
                                    <h4><?php echo $stats['reviewed']; ?></h4>
                                    <small>Reviewed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card stat-card-info">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <h4><?php echo $stats['interviewed']; ?></h4>
                                    <small>Interview</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card stat-card-success">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h4><?php echo $stats['offered']; ?></h4>
                                    <small>Offered</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card text-white" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                                    <h4><?php echo $stats['rejected']; ?></h4>
                                    <small>Rejected</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter and Search -->
                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">
                                        <i class="fas fa-filter me-1"></i>Filter Status
                                    </label>
                                    <select name="status" class="form-select btn-filter">
                                        <option value="">Semua Status</option>
                                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Reviewed" <?php echo $status_filter == 'Reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                        <option value="Interviewed" <?php echo $status_filter == 'Interviewed' ? 'selected' : ''; ?>>Interviewed</option>
                                        <option value="Offered" <?php echo $status_filter == 'Offered' ? 'selected' : ''; ?>>Offered</option>
                                        <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-search me-1"></i>Cari Pekerjaan/Perusahaan
                                    </label>
                                    <input type="text" name="search" class="form-control btn-filter" 
                                           placeholder="Masukkan nama pekerjaan atau perusahaan..."
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-light w-100">
                                        <i class="fas fa-search me-1"></i>Cari
                                    </button>
                                </div>
                            </form>
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
                                                    <a href="uploads/resumes/<?php echo $app['resume_file']; ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                                        <i class="fas fa-file-pdf me-1"></i>Lihat Resume
                                                    </a>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Additional actions could go here -->
                                            <div class="mt-3">
                                                <button class="btn btn-outline-secondary btn-sm w-100" onclick="showApplicationDetails(<?php echo $app['application_id']; ?>)">
                                                    <i class="fas fa-info-circle me-1"></i>Detail Lengkap
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Pagination could be added here if needed -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Application Details -->
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
            // This function would load detailed application information
            // For now, just show a placeholder
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

        // Auto-refresh status every 5 minutes
        setInterval(function() {
            // You could add an AJAX call here to refresh application statuses
            console.log('Checking for status updates...');
        }, 300000); // 5 minutes
    </script>
</body>

</html>