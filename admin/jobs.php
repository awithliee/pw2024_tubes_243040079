<?php
// admin/jobs.php
session_start();

// Cek apakah user sudah login dan merupakan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location:../login.php');
    exit();
}

require '../config/database.php';

// Handle delete job
if (isset($_GET['delete'])) {
    try {
        $stmt = $db->prepare("UPDATE jobs SET is_active = 0 WHERE job_id = ?");
        $stmt->execute([$_GET['delete']]);
        header('Location: jobs.php?success=Job deactivated successfully');
        exit();
    } catch (PDOException $e) {
        $error = "Error deactivating job: " . $e->getMessage();
    }
}

// Handle activate job
if (isset($_GET['activate'])) {
    try {
        $stmt = $db->prepare("UPDATE jobs SET is_active = 1 WHERE job_id = ?");
        $stmt->execute([$_GET['activate']]);
        header('Location: jobs.php?success=Job activated successfully');
        exit();
    } catch (PDOException $e) {
        $error = "Error activating job: " . $e->getMessage();
    }
}

// Get all jobs with company information
try {
    $stmt = $db->query("
        SELECT j.*, c.company_name, c.company_location, u.full_name as posted_by_name,
               (SELECT COUNT(*) FROM applications WHERE job_id = j.job_id) as application_count
        FROM jobs j
        JOIN companies c ON j.company_id = c.company_id
        JOIN users u ON j.posted_by = u.user_id
        ORDER BY j.created_at DESC
    ");
    $jobs = $stmt->fetchAll();

    // Get companies for add job form
    $stmt = $db->query("SELECT * FROM companies ORDER BY company_name");
    $companies = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Handle add job
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_job'])) {
    try {
        $stmt = $db->prepare("
            INSERT INTO jobs (company_id, job_title, job_description, job_requirements, 
                            job_location, job_type, salary_range, posted_by, application_deadline) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['company_id'],
            $_POST['job_title'],
            $_POST['job_description'],
            $_POST['job_requirements'],
            $_POST['job_location'],
            $_POST['job_type'],
            $_POST['salary_range'],
            $_SESSION['user_id'],
            $_POST['application_deadline']
        ]);
        header('Location: jobs.php?success=Job added successfully');
        exit();
    } catch (PDOException $e) {
        $error = "Error adding job: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs Management - Lamarin Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style-admin/adm-jobs.css">
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
                            <h2 class="mb-0">Kelola Lowongan</h2>
                            <p class="text-muted">Kelola semua Lowongan</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJobModal">
                            <i class="fas fa-plus me-2"></i>Tambah Lowongan
                        </button>
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

                    <!-- Jobs Table -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-briefcase me-2 text-primary"></i>Semua Lowongan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="jobsTable" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Jabatan</th>
                                            <th>Perusahaan</th>
                                            <th>Lokasi</th>
                                            <th>Tipe</th>
                                            <th>Gaji</th>
                                            <th>Lamaran</th>
                                            <th>Status</th>
                                            <th>Batas Waktu</th>
                                            <th>Tindakan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jobs as $job): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($job['job_title']) ?></strong>
                                                    <br><small class="text-muted">Posted by: <?= htmlspecialchars($job['posted_by_name']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($job['company_name']) ?></td>
                                                <td><?= htmlspecialchars($job['job_location']) ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?= htmlspecialchars($job['job_type']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($job['salary_range']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?= $job['application_count'] ?> Lamaran</span>
                                                </td>
                                                <td>
                                                    <?php if ($job['is_active']): ?>
                                                        <span class="job-status bg-success text-white">Aktif</span>
                                                    <?php else: ?>
                                                        <span class="job-status bg-danger text-white">Tidak aktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($job['application_deadline']) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info btn-action" onclick="viewJob(<?= $job['job_id'] ?>)" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($job['is_active']): ?>
                                                        <a href="jobs.php?delete=<?= $job['job_id'] ?>"
                                                            class="btn btn-sm btn-warning btn-action"
                                                            onclick="return confirm('Are you sure you want to deactivate this job?')" title="Deactivate">
                                                            <i class="fas fa-pause"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="jobs.php?activate=<?= $job['job_id'] ?>"
                                                            class="btn btn-sm btn-success btn-action"
                                                            onclick="return confirm('Are you sure you want to activate this job?')" title="Activate">
                                                            <i class="fas fa-play"></i>
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

    <!-- Add Job Modal -->
    <div class="modal fade" id="addJobModal" tabindex="-1" aria-labelledby="addJobModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addJobModalLabel">Tambahkan Lowogan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="job_title" class="form-label">Jabatan</label>
                                    <input type="text" class="form-control" id="job_title" name="job_title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_id" class="form-label">Perusahaan</label>
                                    <select class="form-control" id="company_id" name="company_id" required>
                                        <option value="">Pilih Perusahaan</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?= $company['company_id'] ?>"><?= htmlspecialchars($company['company_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="job_location" class="form-label">Lokasi</label>
                                    <input type="text" class="form-control" id="job_location" name="job_location" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="job_type" class="form-label">Tipe Lowongan</label>
                                    <select class="form-control" id="job_type" name="job_type" required>
                                        <option value="">Pilih Tipe</option>
                                        <option value="Full-time">Full-time</option>
                                        <option value="Part-time">Part-time</option>
                                        <option value="Contract">Contract</option>
                                        <option value="Freelance">Freelance</option>
                                        <option value="Internship">Internship</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="salary_range" class="form-label">Kisaran Gaji</label>
                                    <input type="text" class="form-control" id="salary_range" name="salary_range" placeholder="">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="application_deadline" class="form-label">Batas Waktu Pengajuan</label>
                                    <input type="date" class="form-control" id="application_deadline" name="application_deadline" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="job_description" class="form-label">Deskripsi Lowongan</label>
                            <textarea class="form-control" id="job_description" name="job_description" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="job_requirements" class="form-label">Persyaratn Lowongan</label>
                            <textarea class="form-control" id="job_requirements" name="job_requirements" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_job" class="btn btn-primary">Tambahkan Lowongan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Job Details Modal -->
    <div class="modal fade" id="jobDetailsModal" tabindex="-1" aria-labelledby="jobDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jobDetailsModalLabel">Detail Lowongan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="jobDetailsContent">
                    <!-- Job details will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#jobsTable').DataTable({
                responsive: true,
                order: [
                    [7, 'desc']
                ], // Order by deadline
                columnDefs: [{
                        orderable: false,
                        targets: -1
                    } // Disable ordering on actions column
                ]
            });
        });

        function viewJob(jobId) {
            // Load job details via AJAX
            $.get('ajax/get_job_details.php', {
                id: jobId
            }, function(data) {
                $('#jobDetailsContent').html(data);
                $('#jobDetailsModal').modal('show');
            });
        }
    </script>
</body>

</html>