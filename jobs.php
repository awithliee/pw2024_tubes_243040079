<?php
// jobs.php
require_once 'config/database.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Simple search keyword
$searchKeyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// buat query
$whereConditions = ["j.is_active = 1"];
$params = [];

if (!empty($searchKeyword)) {
    $whereConditions[] = "(j.job_title LIKE :keyword OR c.company_name LIKE :keyword)";
    $params[':keyword'] = '%' . $searchKeyword . '%';
}

$whereClause = implode(' AND ', $whereConditions);

// total hitung page
$countQuery = "SELECT COUNT(*) as total 
               FROM jobs j 
               JOIN companies c ON j.company_id = c.company_id 
               WHERE $whereClause";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalJobs = $countStmt->fetch()['total'];
$totalPages = ceil($totalJobs / $limit);

// job dengan informasi pperusahaan
$query = "SELECT j.*, c.company_name, c.company_location, c.industry, c.website
          FROM jobs j 
          JOIN companies c ON j.company_id = c.company_id 
          WHERE $whereClause 
          ORDER BY j.created_at DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll();

//perusahaan
$companyCountQuery = "SELECT COUNT(DISTINCT c.company_id) as total FROM companies c JOIN jobs j ON c.company_id = j.company_id WHERE j.is_active = 1";
$companyCountStmt = $db->prepare($companyCountQuery);
$companyCountStmt->execute();
$totalActiveCompanies = $companyCountStmt->fetch()['total'];

// Menghitung total pengguna yang telah berhasil mendapatkan pekerjaan
$query = "SELECT COUNT(*) as total FROM users WHERE role_id = 2 AND is_active = TRUE";
$stmt = $db->prepare($query);
$stmt->execute();
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Format waktu
function formatTanggal($tanggal)
{
    if (empty($tanggal)) return '';
    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// cek jika mendekati deadline
function isDeadlineApproaching($deadline)
{
    if (empty($deadline)) return false;
    $deadlineTime = strtotime($deadline);
    $currentTime = time();
    $daysDiff = ($deadlineTime - $currentTime) / (60 * 60 * 24);
    return $daysDiff <= 7 && $daysDiff > 0;
}

// cek deadline
function isDeadlinePassed($deadline)
{
    if (empty($deadline)) return false;
    return strtotime($deadline) < time();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Lowongan - Lamarin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/jobs.css">
</head>

<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Hero-Section -->
    <section class="hero-section">
        <div class="container">
            <div class="text-center">
                <h1 class="display-5 fw-bold mb-3">Temukan Lowongan Kerja Impian</h1>
                <p class="lead mb-4">Jelajahi <?php echo number_format($totalJobs); ?> lowongan pekerjaan dari perusahaan terpercaya</p>
            </div>
        </div>
    </section>

    <!-- Search-Section  -->
    <section class="filter-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="keyword" class="form-control" placeholder="Cari posisi atau nama perusahaan..."
                            value="<?php echo htmlspecialchars($searchKeyword); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Cari
                        </button>
                        <?php if (!empty($searchKeyword)): ?>
                            <a href="jobs.php" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-arrow-rotate-left"></i> </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Jobs Section -->
    <section class="py-5">
        <div class="container">
            <div class="mb-4">
                <h2>
                    <?php if (!empty($searchKeyword)): ?>
                        Hasil Pencarian "<?php echo htmlspecialchars($searchKeyword); ?>"
                    <?php else: ?>
                        Semua Lowongan
                    <?php endif; ?>
                    <span class="text-muted fs-6">(<?php echo number_format($totalJobs); ?> lowongan)</span>
                </h2>
            </div>

            <?php if (count($jobs) > 0): ?>
                <div class="row">
                    <?php foreach ($jobs as $job): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card job-card">
                                <div class="card-body d-flex flex-column">
                                    <!-- Header -->
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="company-logo me-3 d-flex align-items-center justify-content-center">
                                            <i class="fas fa-building text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-1 fw-bold">
                                                <?php echo htmlspecialchars($job['job_title']); ?>
                                            </h5>
                                            <h6 class="text-primary mb-0">
                                                <?php echo htmlspecialchars($job['company_name']); ?>
                                            </h6>
                                            <?php if (!empty($job['industry'])): ?>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($job['industry']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($job['job_type'])): ?>
                                            <span class="badge job-type-badge 
                                                <?php
                                                switch ($job['job_type']) {
                                                    case 'Full-time':
                                                        echo 'bg-success';
                                                        break;
                                                    case 'Part-time':
                                                        echo 'bg-warning';
                                                        break;
                                                    case 'Contract':
                                                        echo 'bg-info';
                                                        break;
                                                    case 'Freelance':
                                                        echo 'bg-secondary';
                                                        break;
                                                    case 'Internship':
                                                        echo 'bg-primary';
                                                        break;
                                                    default:
                                                        echo 'bg-secondary';
                                                }
                                                ?>">
                                                <?php echo htmlspecialchars($job['job_type']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Job Info -->
                                    <div class="mb-3 flex-grow-1">
                                        <?php if (!empty($job['job_location'])): ?>
                                            <div class="mb-2">
                                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                                <span class="small"><?php echo htmlspecialchars($job['job_location']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($job['salary_range'])): ?>
                                            <div class="mb-2">
                                                <span class="salary-badge">
                                                    <i class="fas fa-money-bill-wave me-1"></i>
                                                    <?php echo htmlspecialchars($job['salary_range']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($job['job_description'])): ?>
                                            <p class="card-text text-muted small mt-2">
                                                <?php echo substr(strip_tags($job['job_description']), 0, 100) . '...'; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Deadline Info -->
                                    <?php if (!empty($job['application_deadline'])): ?>
                                        <div class="mb-3">
                                            <small class="
                                                <?php
                                                if (isDeadlinePassed($job['application_deadline'])) {
                                                    echo 'deadline-passed';
                                                } elseif (isDeadlineApproaching($job['application_deadline'])) {
                                                    echo 'deadline-approaching';
                                                } else {
                                                    echo 'text-muted';
                                                }
                                                ?>">
                                                <i class="fas fa-clock me-1"></i>
                                                Deadline: <?php echo formatTanggal($job['application_deadline']); ?>
                                                <?php if (isDeadlineApproaching($job['application_deadline']) && !isDeadlinePassed($job['application_deadline'])): ?>
                                                    <span class="badge bg-warning text-dark ms-1">Segera Berakhir!</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>

                                    <!-- waktu post -->
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            Diposting: <?php echo formatTanggal(date('Y-m-d', strtotime($job['created_at']))); ?>
                                        </small>
                                    </div>
                                </div>

                                <!-- kartu footer-->
                                <div class="card-footer bg-white border-top-0 pt-0">
                                    <?php if (!isDeadlinePassed($job['application_deadline'])): ?>
                                        <a href="job-detail.php?id=<?php echo $job['job_id']; ?>"
                                            class="btn btn-apply w-100">
                                            <i class="fas fa-eye me-2"></i>Lihat Detail & Lamar
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100" disabled>
                                            <i class="fas fa-times me-2"></i>Lowongan Ditutup
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Job pagination" class="mt-5">
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
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h3>Tidak Ada Lowongan Ditemukan</h3>
                    <p class="text-muted mb-4">
                        <?php if (!empty($searchKeyword)): ?>
                            Tidak ada lowongan dengan kata kunci "<?php echo htmlspecialchars($searchKeyword); ?>"
                        <?php else: ?>
                            Belum ada lowongan yang tersedia saat ini.
                        <?php endif; ?>
                    </p>
                    <div>
                        <?php if (!empty($searchKeyword)): ?>
                            <a href="jobs.php" class="btn btn-primary me-2">
                                <i class="fas fa-refresh me-2"></i>Lihat Semua Lowongan
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-home me-2"></i>Kembali ke Beranda
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- statistik -->
    <section class="bg-light py-4">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-3">
                    <div class="p-3">
                        <i class="fas fa-briefcase fa-2x text-primary mb-2"></i>
                        <h4 class="fw-bold"><?php echo number_format($totalJobs); ?></h4>
                        <p class="text-muted mb-0">Lowongan Tersedia</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3">
                        <i class="fas fa-building fa-2x text-primary mb-2"></i>
                        <h4 class="fw-bold"><?php echo number_format($totalActiveCompanies); ?></h4>
                        <p class="text-muted mb-0">Perusahaan Aktif</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h4 class="fw-bold"><?php echo number_format($totalUsers); ?></h4>
                        <p class="text-muted mb-0">Pelamar Berhasil</p>
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