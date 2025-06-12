<?php
// companies.php
require_once 'config/database.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Simple search functionality - hanya keyword
$searchKeyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Build query
$whereClause = '';
$params = [];

if (!empty($searchKeyword)) {
    $whereClause = "WHERE company_name LIKE :keyword";
    $params[':keyword'] = '%' . $searchKeyword . '%';
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM companies $whereClause";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalCompanies = $countStmt->fetch()['total'];
$totalPages = ceil($totalCompanies / $limit);

// Get companies with job count
$query = "SELECT c.*, COUNT(j.job_id) as job_count 
          FROM companies c 
          LEFT JOIN jobs j ON c.company_id = j.company_id AND j.is_active = TRUE 
          $whereClause 
          GROUP BY c.company_id 
          ORDER BY c.company_name ASC 
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$companies = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Perusahaan - Lamarin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/companies.css">
</head>

<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="text-center">
                <h1 class="display-5 fw-bold mb-3">Perusahaan Terpercaya</h1>
                <p class="lead mb-4">Temukan perusahaan impian Anda dari <?php echo number_format($totalCompanies); ?> perusahaan terdaftar</p>
            </div>
        </div>
    </section>

    <!-- Search Section - Simplified -->
    <section class="filter-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="keyword" class="form-control" placeholder="Cari nama perusahaan..."
                            value="<?php echo htmlspecialchars($searchKeyword); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Cari
                        </button>
                        <?php if (!empty($searchKeyword)): ?>
                            <a href="companies.php" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-arrow-rotate-left"></i> </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Companies Section -->
    <section class="py-5">
        <div class="container">
            <div class="mb-4">
                <h2>
                    <?php if (!empty($searchKeyword)): ?>
                        Hasil Pencarian "<?php echo htmlspecialchars($searchKeyword); ?>" (<?php echo number_format($totalCompanies); ?> perusahaan)
                    <?php else: ?>
                        Semua Perusahaan (<?php echo number_format($totalCompanies); ?> perusahaan)
                    <?php endif; ?>
                </h2>
            </div>

            <?php if (count($companies) > 0): ?>
                <div class="row">
                    <?php foreach ($companies as $company): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card company-card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <?php if (!empty($company['company_logo'])): ?>
                                            <img src="uploads/logos/<?php echo htmlspecialchars($company['company_logo']); ?>"
                                                alt="<?php echo htmlspecialchars($company['company_name']); ?>"
                                                class="company-logo">
                                        <?php else: ?>
                                            <div class="company-logo mx-auto bg-primary d-flex align-items-center justify-content-center text-white">
                                                <i class="fas fa-building fa-2x"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($company['company_name']); ?></h5>

                                    <?php if (!empty($company['industry'])): ?>
                                        <span class="badge bg-primary mb-3"><?php echo htmlspecialchars($company['industry']); ?></span>
                                    <?php endif; ?>

                                    <?php if (!empty($company['company_location'])): ?>
                                        <p class="card-text mb-2">
                                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                            <?php echo htmlspecialchars($company['company_location']); ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($company['company_description'])): ?>
                                        <p class="card-text text-muted small mb-3">
                                            <?php echo substr(strip_tags($company['company_description']), 0, 100) . '...'; ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="company-stats p-3 mb-3">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <div class="fw-bold fs-4"><?php echo $company['job_count']; ?></div>
                                                <div class="small">Lowongan Aktif</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="fw-bold fs-4">
                                                    <?php echo !empty($company['company_size']) ? $company['company_size'] : 'N/A'; ?>
                                                </div>
                                                <div class="small">Ukuran Perusahaan</div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($company['website'])): ?>
                                        <p class="card-text mb-2">
                                            <i class="fas fa-globe text-muted me-1"></i>
                                            <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" class="text-decoration-none">
                                                Website
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-white border-top-0">
                                    <div class="row">
                                        <div class="col-6">
                                            <a href="company-detail.php?id=<?php echo $company['company_id']; ?>" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-info-circle me-1"></i>Detail
                                            </a>
                                        </div>
                                        <div class="col-6">
                                            <a href="jobs.php?company=<?php echo $company['company_id']; ?>" class="btn btn-primary w-100">
                                                <i class="fas fa-briefcase me-1"></i>Lowongan
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Company pagination" class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <h3>Tidak Ada Perusahaan Ditemukan</h3>
                    <?php if (!empty($searchKeyword)): ?>
                        <p class="text-muted">Tidak ada perusahaan dengan nama "<?php echo htmlspecialchars($searchKeyword); ?>"</p>
                    <?php else: ?>
                        <p class="text-muted">Belum ada perusahaan yang terdaftar.</p>
                    <?php endif; ?>
                    <a href="companies.php" class="btn btn-primary">
                        <i class="fas fa-refresh me-2"></i>Lihat Semua Perusahaan
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="p-4">
                        <i class="fas fa-building fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold"><?php echo number_format($totalCompanies); ?></h3>
                        <p class="text-muted">Perusahaan Terdaftar</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="p-4">
                        <i class="fas fa-briefcase fa-3x text-primary mb-3"></i>
                        <?php
                        $jobCountQuery = "SELECT COUNT(*) as total FROM jobs WHERE is_active = TRUE";
                        $jobCountStmt = $db->prepare($jobCountQuery);
                        $jobCountStmt->execute();
                        $totalActiveJobs = $jobCountStmt->fetch()['total'];
                        ?>
                        <h3 class="fw-bold"><?php echo number_format($totalActiveJobs); ?></h3>
                        <p class="text-muted">Lowongan Aktif</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="p-4">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold">10,000+</h3>
                        <p class="text-muted">Pelamar Berhasil</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>