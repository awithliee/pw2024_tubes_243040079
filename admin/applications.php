<?php
session_start();
// Cek apakah user sudah login dan merupakan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location:../login.php');
    exit();
}

require '../config/database.php';

//satatus lamaran 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    try {
        $stmt = $db->prepare("UPDATE applications SET application_status = ?, admin_notes = ? WHERE application_id = ?");
        $stmt->execute([$_POST['status'], $_POST['admin_notes'], $_POST['application_id']]);
        header('Location: applications.php?success=Application status updated successfully');
        exit();
    } catch (PDOException $e) {
        $error = "Error updating application: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$company_filter = isset($_GET['company']) ? $_GET['company'] : '';

// Build WHERE clause based on filters
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "a.application_status = ?";
    $params[] = $status_filter;
}

if ($company_filter) {
    $where_conditions[] = "c.company_id = ?";
    $params[] = $company_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// ambil lamaran dan data relasi nya
try {
    $sql = "
        SELECT a.*, u.full_name, u.email, u.phone_number, 
               j.job_title, j.job_type, j.salary_range,
               c.company_name, c.company_location,
               poster.full_name as posted_by_name
        FROM applications a
        JOIN users u ON a.user_id = u.user_id
        JOIN jobs j ON a.job_id = j.job_id
        JOIN companies c ON j.company_id = c.company_id
        JOIN users poster ON j.posted_by = poster.user_id
        $where_clause
        ORDER BY a.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();

    // filter perusahaan
    $stmt = $db->query("SELECT * FROM companies ORDER BY company_name");
    $companies = $stmt->fetchAll();

    // Get statistics
    $stats = [];
    $stmt = $db->query("SELECT application_status, COUNT(*) as count FROM applications GROUP BY application_status");
    while ($row = $stmt->fetch()) {
        $stats[$row['application_status']] = $row['count'];
    }
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications Management - Lamarin Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style-admin/applications.css">

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
                            <h2 class="mb-0">Kelola Lamaran</h2>
                            <p class="text-muted">Kelola semua</p>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_GET['success']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Status Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card stat-card pending">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?= isset($stats['Tertunda']) ? $stats['Tertunda'] : 0 ?></h4>
                                    <small>Tertunda</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stat-card reviewed">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?= isset($stats['Ditinjau']) ? $stats['Ditinjau'] : 0 ?></h4>
                                    <small>Ditinjau</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stat-card interviewed">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?= isset($stats['Diwawancarai']) ? $stats['Diwawancarai'] : 0 ?></h4>
                                    <small>Diwawancarai</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stat-card offered">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?= isset($stats['Diterima']) ? $stats['Diterima'] : 0 ?></h4>
                                    <small>Diterima</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stat-card rejected">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?= isset($stats['Ditolak']) ? $stats['Ditolak'] : 0 ?></h4>
                                    <small>Ditolak</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?= array_sum($stats) ?></h4>
                                    <small>Total</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Filter by Status</label>
                                        <select name="status" class="form-select">
                                            <option value="">All Status</option>
                                            <option value="Tertunda" <?= $status_filter == 'Tertunda' ? 'selected' : '' ?>>Tertunda</option>
                                            <option value="Ditinjau" <?= $status_filter == 'Ditinjau' ? 'selected' : '' ?>>Ditinjau</option>
                                            <option value="Diwawancarai" <?= $status_filter == 'Diwawancarai' ? 'selected' : '' ?>>Diwawancarai</option>
                                            <option value="Diterima" <?= $status_filter == 'Diterima' ? 'selected' : '' ?>>Diterima</option>
                                            <option value="Ditolak" <?= $status_filter == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Filter by Company</label>
                                        <select name="company" class="form-select">
                                            <option value="">All Companies</option>
                                            <?php foreach ($companies as $company): ?>
                                                <option value="<?= $company['company_id'] ?>" <?= $company_filter == $company['company_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($company['company_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-filter me-1"></i>Filter
                                        </button>
                                        <a href="applications.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-refresh me-1"></i>Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Applications Table -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2 text-primary"></i>Job Applications
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="applicationsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Pelamar</th>
                                            <th>Jabatan</th>
                                            <th>Perusahaan</th>
                                            <th>Status</th>
                                            <th>Tanggal Ditetapkan</th>
                                            <th>Tindakan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td><?= $app['application_id'] ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($app['full_name']) ?></strong><br>
                                                        <small class="text-muted"><?= htmlspecialchars($app['email']) ?></small>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($app['job_title']) ?></td>
                                                <td><?= htmlspecialchars($app['company_name']) ?></td>
                                                <td>
                                                    <?php
                                                    $statusClassMap = [
                                                        'Tertunda' => 'warning',
                                                        'Ditinjau' => 'info',
                                                        'Diwawancarai' => 'primary',
                                                        'Diterima' => 'success',
                                                        'Ditolak' => 'danger'
                                                    ];
                                                    $badgeClass = isset($statusClassMap[$app['application_status']]) ? $statusClassMap[$app['application_status']] : 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $badgeClass ?>">
                                                        <?= $app['application_status'] ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d M Y', strtotime($app['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary btn-action"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewApplicationModal"
                                                        onclick="viewApplication(<?= htmlspecialchars(json_encode($app)) ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning btn-action"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#updateStatusModal"
                                                        onclick="updateStatus(<?= $app['application_id'] ?>, '<?= $app['application_status'] ?>', '<?= htmlspecialchars($app['admin_notes'] ?? '') ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    <?php if ($app['resume_file']): ?>
                                                        <a href="../cv/<?= $app['resume_file'] ?>"
                                                            class="btn btn-sm btn-outline-success btn-action"
                                                            target="_blank">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
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

    <!-- View Application Modal -->
    <div class="modal fade" id="viewApplicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="applicationDetails">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit satus lamaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="application_id" id="updateApplicationId">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="updateStatus" class="form-select" required>
                                <option value="Tertunda">Tertunda</option>
                                <option value="Ditinjau">Ditinjau</option>
                                <option value="Diwawancarai">Diwawancarai</option>
                                <option value="Diterima">Diterima</option>
                                <option value="Ditolak">Ditolak</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="admin_notes" id="updateNotes" class="form-control" rows="4" placeholder="Ketikan sesuatu..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>


    <script>
        $(document).ready(function() {
            $('#applicationsTable').DataTable({
                "order": [
                    [0, "desc"]
                ],
                "pageLength": 25,
                "responsive": true
            });
        });

        function viewApplication(app) {
            const details = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Informasi Lamaran</h6>
                        <p><strong>Nama:</strong> ${app.full_name}</p>
                        <p><strong>Email:</strong> ${app.email}</p>
                        <p><strong>Telepon:</strong> ${app.phone_number || 'Not provided'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Informasi Pekerjaan</h6>
                        <p><strong>Jabatan:</strong> ${app.job_title}</p>
                        <p><strong>Perusahaan:</strong> ${app.company_name}</p>
                        <p><strong>Jenis Pekerjaan:</strong> ${app.job_type}</p>
                        <p><strong>Gaji:</strong> ${app.salary_range || 'Not specified'}</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary">Detail Lamaran</h6>
                        <p><strong>Status:</strong> <span class="badge bg-secondary">${app.application_status}</span></p>
                        <p><strong>Tanggal Diterapkan:</strong> ${new Date(app.created_at).toLocaleDateString()}</p>
                        ${app.cover_letter ? `<p><strong>Kata Pengantar:</strong></p><div class="bg-light p-3 rounded">${app.cover_letter}</div>` : ''}
                        ${app.admin_notes ? `<p><strong>Catatan Admin:</strong></p><div class="bg-warning bg-opacity-10 p-3 rounded">${app.admin_notes}</div>` : ''}
                        ${app.resume_file ? `<p><strong>CV:</strong> <a href="../cv/${app.resume_file}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat CV</a></p>` : ''}
                    </div>
                </div>
            `;
            document.getElementById('applicationDetails').innerHTML = details;
        }

        function updateStatus(applicationId, currentStatus, currentNotes) {
            document.getElementById('updateApplicationId').value = applicationId;
            document.getElementById('updateStatus').value = currentStatus;
            document.getElementById('updateNotes').value = currentNotes;
        }
    </script>
</body>

</html>