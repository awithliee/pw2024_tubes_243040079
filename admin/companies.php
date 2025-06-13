<?php
// admin/companies.php
session_start();

// Cek apakah user sudah login dan merupakan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location:../login.php');
    exit();
}

require '../config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $db->prepare("INSERT INTO companies (company_name, company_address, company_description, website, industry, company_location, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['company_name'],
                        $_POST['company_address'],
                        $_POST['company_description'],
                        $_POST['website'],
                        $_POST['industry'],
                        $_POST['company_location'],
                        $_SESSION['user_id']
                    ]);
                    $success = "Company added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding company: " . $e->getMessage();
                }
                break;

            case 'edit':
                try {
                    $stmt = $db->prepare("UPDATE companies SET company_name=?, company_address=?, company_description=?, website=?, industry=?, company_location=? WHERE company_id=?");
                    $stmt->execute([
                        $_POST['company_name'],
                        $_POST['company_address'],
                        $_POST['company_description'],
                        $_POST['website'],
                        $_POST['industry'],
                        $_POST['company_location'],
                        $_POST['company_id']
                    ]);
                    $success = "Company updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating company: " . $e->getMessage();
                }
                break;

            case 'delete':
                try {
                    $stmt = $db->prepare("DELETE FROM companies WHERE company_id = ?");
                    $stmt->execute([$_POST['company_id']]);
                    $success = "Company deleted successfully!";
                } catch (PDOException $e) {
                    $error = "Error deleting company: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all companies
try {
    $stmt = $db->query("
        SELECT c.*, u.full_name as created_by_name,
        (SELECT COUNT(*) FROM jobs WHERE company_id = c.company_id) as total_jobs
        FROM companies c
        LEFT JOIN users u ON c.created_by = u.user_id
        ORDER BY c.created_at DESC
    ");
    $companies = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching companies: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Perusahaan - Lamarin Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style-admin/adm-comp.css">
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
                            <h2 class="mb-0">Kelola Perusahaan</h2>
                            <p class="text-muted">Kelola Semua Perusahaan</p>
                        </div>
                        <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                            <i class="fas fa-plus me-2"></i>Tambahkan Perusahaan
                        </button>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $success ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Companies Table -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-building me-2 text-primary"></i>Semua Perusahaan
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <th class="table-header">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Perusahaan</th>
                                            <th>Industri</th>
                                            <th>Lokasi</th>
                                            <th>Jumlah Pekerjaan</th>
                                            <th>Di buat oleh</th>
                                            <th>Tanggal Dibuat</th>
                                            <th>Tindakan</th>
                                        </tr>
                                    </th>
                                    <tbody>
                                        <?php foreach ($companies as $company): ?>
                                            <tr>
                                                <td><?= $company['company_id'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($company['company_name']) ?></strong>
                                                    <?php if ($company['website']): ?>
                                                        <br><small><a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" class="text-muted">
                                                                <i class="fas fa-external-link-alt"></i> Website
                                                            </a></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($company['industry']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($company['company_location']) ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?= $company['total_jobs'] ?> Pekerjaan</span>
                                                </td>
                                                <td><?= htmlspecialchars($company['created_by_name']) ?></td>
                                                <td><?= date('d M Y', strtotime($company['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editCompany(<?= htmlspecialchars(json_encode($company)) ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCompany(<?= $company['company_id'] ?>, '<?= htmlspecialchars($company['company_name']) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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

    <!-- tambahkan Modal -->
    <div class="modal fade" id="addCompanyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambahkan perusahaan baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Perusahaan*</label>
                                    <input type="text" class="form-control" name="company_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Industri *</label>
                                    <input type="text" class="form-control" name="industry" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lokasi *</label>
                                    <input type="text" class="form-control" name="company_location" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Website</label>
                                    <input type="url" class="form-control" name="website">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="company_address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="company_description" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-gradient">Tambahkan Perusahaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Company Modal -->
    <div class="modal fade" id="editCompanyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Perusahaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editCompanyForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="company_id" id="edit_company_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Perusahaan *</label>
                                    <input type="text" class="form-control" name="company_name" id="edit_company_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Industri *</label>
                                    <input type="text" class="form-control" name="industry" id="edit_industry" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lokasi *</label>
                                    <input type="text" class="form-control" name="company_location" id="edit_company_location" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Website</label>
                                    <input type="url" class="form-control" name="website" id="edit_website">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="company_address" id="edit_company_address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="company_description" id="edit_company_description" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-gradient">Perbarui Perusahaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Company Modal -->
    <div class="modal fade" id="deleteCompanyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="company_id" id="delete_company_id">
                        <p>Are you sure you want to delete company <strong id="delete_company_name"></strong>?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This action will also delete all jobs associated with this company.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Company</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCompany(company) {
            document.getElementById('edit_company_id').value = company.company_id;
            document.getElementById('edit_company_name').value = company.company_name;
            document.getElementById('edit_industry').value = company.industry || '';
            document.getElementById('edit_company_location').value = company.company_location || '';
            document.getElementById('edit_website').value = company.website || '';
            document.getElementById('edit_company_address').value = company.company_address || '';
            document.getElementById('edit_company_description').value = company.company_description || '';

            new bootstrap.Modal(document.getElementById('editCompanyModal')).show();
        }

        function deleteCompany(id, name) {
            document.getElementById('delete_company_id').value = id;
            document.getElementById('delete_company_name').textContent = name;

            new bootstrap.Modal(document.getElementById('deleteCompanyModal')).show();
        }
    </script>
</body>

</html>