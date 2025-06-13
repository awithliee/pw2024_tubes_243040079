<?php
// company-detail.php
require_once 'config/database.php';

// Get company ID from URL
$company_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($company_id <= 0) {
    header('Location: companies.php');
    exit();
}

// Get company details
$companyQuery = "SELECT * FROM companies WHERE company_id = :company_id";
$companyStmt = $db->prepare($companyQuery);
$companyStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
$companyStmt->execute();
$company = $companyStmt->fetch();

if (!$company) {
    header('Location: companies.php');
    exit();
}

// Get basic job count for this company
$jobCountQuery = "SELECT COUNT(*) as total_jobs FROM jobs WHERE company_id = :company_id AND is_active = TRUE";
$jobCountStmt = $db->prepare($jobCountQuery);
$jobCountStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
$jobCountStmt->execute();
$jobCount = $jobCountStmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($company['company_name']); ?> - Detail Perusahaan - Lamarin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .company-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }

        .company-icon {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 3rem;
            color: white;
            border-radius: 50%;
        }

        .info-card {
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            transition: transform 0.3s ease;
        }

        .company-description {
            line-height: 1.8;
            text-align: justify;
        }

        .contact-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
        }

        .section-title {
            position: relative;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .job-count-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <!-- Company Header -->
    <section class="company-header">
        <!-- nav -->
        <div class=" border-light mt-3 ms-5 pt-3">
            <a class="nav-link" href="companies.php">
                <i class="fa-solid fa-circle-arrow-left"></i>
                Kembali ke daftar Perusahaan
            </a>
        </div>
        <div class="row align-items-center">
            <div class="col-md-2 text-center mb-3 mb-md-0">
                <div class="company-icon mx-auto d-flex align-items-center justify-content-center">
                    <i class="fas fa-building fa-3x text-white"></i>
                </div>
            </div>
            <div class="col-md-10">
                <h1 class="display-5 fw-bold mb-2"><?php echo htmlspecialchars($company['company_name']); ?></h1>
                <?php if (!empty($company['industry'])): ?>
                    <p class="lead mb-2">
                        <i class="fas fa-industry me-2"></i>
                        <?php echo htmlspecialchars($company['industry']); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($company['company_location'])): ?>
                    <p class="mb-3">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?php echo htmlspecialchars($company['company_location']); ?>
                    </p>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <?php if (!empty($company['website'])): ?>
                        <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" class="btn btn-light btn-sm">
                            <i class="fas fa-globe me-1"></i>Website
                        </a>
                    <?php endif; ?>
                    <?php if ($jobCount['total_jobs'] > 0): ?>
                        <span class="job-count-badge">
                            <i class="fas fa-briefcase me-1"></i><?php echo $jobCount['total_jobs']; ?> Lowongan Aktif
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
    </section>

    <!-- Company Details -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8 mb-4">
                    <!-- Company Description -->
                    <div class="info-card card">
                        <div class="card-body">
                            <h3 class="section-title">Tentang Perusahaan</h3>
                            <?php if (!empty($company['company_description'])): ?>
                                <div class="company-description">
                                    <?php echo nl2br(htmlspecialchars($company['company_description'])); ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Deskripsi perusahaan belum tersedia.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Contact Information -->
                    <div class="contact-info mb-4">
                        <h4 class="fw-bold mb-3">
                            <i class="fas fa-info-circle me-2"></i>Informasi Kontak
                        </h4>

                        <?php if (!empty($company['company_address'])): ?>
                            <div class="mb-3">
                                <h6 class="fw-bold text-muted">Alamat</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($company['company_address'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($company['company_location'])): ?>
                            <div class="mb-3">
                                <h6 class="fw-bold text-muted">Lokasi</h6>
                                <p class="mb-0">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                    <?php echo htmlspecialchars($company['company_location']); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($company['website'])): ?>
                            <div class="mb-3">
                                <h6 class="fw-bold text-muted">Website</h6>
                                <p class="mb-0">
                                    <i class="fas fa-globe text-primary me-1"></i>
                                    <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" class="text-decoration-none">
                                        <?php echo htmlspecialchars($company['website']); ?>
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($company['industry'])): ?>
                            <div class="mb-3">
                                <h6 class="fw-bold text-muted">Industri</h6>
                                <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($company['industry']); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <h6 class="fw-bold text-muted">Bergabung Sejak</h6>
                            <p class="mb-0">
                                <i class="fas fa-calendar text-success me-1"></i>
                                <?php echo date('F Y', strtotime($company['created_at'])); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Tertarik dengan Perusahaan Ini?</h5>
                            <p class="card-text text-muted">Lihat semua lowongan yang tersedia dan mulai karir impian Anda!</p>

                            <?php if ($jobCount['total_jobs'] > 0): ?>
                                <a href="jobs.php?company=<?php echo $company['company_id']; ?>" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-briefcase me-1"></i>Lihat Lowongan (<?php echo $jobCount['total_jobs']; ?>)
                                </a>
                            <?php else: ?>
                                <div class="alert alert-info mb-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Belum ada lowongan aktif saat ini
                                </div>
                            <?php endif; ?>

                            <a href="companies.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-building me-1"></i>Lihat Perusahaan Lain
                            </a>
                        </div>
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