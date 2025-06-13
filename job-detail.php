<?php
require_once 'config/database.php';

// id dari url
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($job_id <= 0) {
    header('Location: jobs.php');
    exit();
}

// detail job dari db company
$query = "SELECT j.*, c.company_name, c.company_address, c.company_description, 
                 c.website, c.industry, c.company_location,
                 u.full_name as posted_by_name
          FROM jobs j 
          JOIN companies c ON j.company_id = c.company_id 
          JOIN users u ON j.posted_by = u.user_id
          WHERE j.job_id = :job_id AND j.is_active = 1";

$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
$stmt->execute();
$job = $stmt->fetch();

if (!$job) {
    header('Location: jobs.php');
    exit;
}

// atur tanggal
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

//deadline 
function isDeadlinePassed($deadline)
{
    if (empty($deadline)) return false;
    return strtotime($deadline) < time();
}

// fungsi mengecek deadline
function isDeadlineApproaching($deadline)
{
    if (empty($deadline)) return false;
    $deadlineTime = strtotime($deadline);
    $currentTime = time();
    $daysDiff = ($deadlineTime - $currentTime) / (60 * 60 * 24);
    return $daysDiff <= 7 && $daysDiff > 0;
}

//menampilkan lowongan dari perusahaan yang sama
$relatedQuery = "SELECT j.*, c.company_name 
                 FROM jobs j 
                 JOIN companies c ON j.company_id = c.company_id 
                 WHERE j.company_id = :company_id 
                 AND j.job_id != :job_id 
                 AND j.is_active = 1 
                 ORDER BY j.created_at DESC 
                 LIMIT 3";
$relatedStmt = $db->prepare($relatedQuery);
$relatedStmt->bindParam(':company_id', $job['company_id'], PDO::PARAM_INT);
$relatedStmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
$relatedStmt->execute();
$relatedJobs = $relatedStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['job_title']); ?> - <?php echo htmlspecialchars($job['company_name']); ?> | Lamarin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .job-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }

        .company-initial {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .job-meta {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
        }

        .job-type-badge {
            font-size: 0.875rem;
            padding: 6px 12px;
            border-radius: 20px;
        }

        .salary-highlight {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 15px;
            border-radius: 12px;
            font-weight: bold;
        }

        .apply-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .apply-btn:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .apply-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .deadline-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px 15px;
            border-radius: 8px;
        }

        .deadline-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 8px;
        }

        .section-title {
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .job-requirement {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .company-info {
            background-color: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
        }

        .related-job-card {
            background-color: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .related-job-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .job-stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 10px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            flex: 1;
        }

        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }
    </style>
</head>

<body>
    <!-- Job Header -->
    <section class="job-header">
        <div class="container">
            <!-- nav -->
            <div class=" border-light mt-3 mb-2 pt-3">
                <a class="nav-link" href="jobs.php">
                    <i class="fa-solid fa-circle-arrow-left"></i>
                    Kembali ke daftar lowongan
                </a>
            </div>
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="company-initial me-3">
                            <?php echo substr(htmlspecialchars($job['company_name']), 0, 1); ?>
                        </div>
                        <div>
                            <h1 class="mb-1"><?php echo htmlspecialchars($job['job_title']); ?></h1>
                            <h5 class="mb-0 opacity-75"><?php echo htmlspecialchars($job['company_name']); ?></h5>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-light text-dark job-type-badge">
                            <i class="fas fa-briefcase me-1"></i> <?php echo htmlspecialchars($job['job_type']); ?>
                        </span>
                        <span class="badge bg-light text-dark job-type-badge">
                            <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($job['job_location']); ?>
                        </span>
                        <span class="badge bg-light text-dark job-type-badge">
                            <i class="fas fa-clock me-1"></i> <?php echo formatTanggal($job['created_at']); ?>
                        </span>
                    </div>

                    <div class="job-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo rand(15, 150); ?></span>
                            <span class="stat-label">Pelamar</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo rand(50, 500); ?></span>
                            <span class="stat-label">Dilihat</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo rand(1, 30); ?></span>
                            <span class="stat-label">Hari tersisa</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 text-end">
                    <?php if (!empty($job['salary_range'])): ?>
                        <div class="salary-highlight mb-3">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            <?php echo htmlspecialchars($job['salary_range']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isDeadlinePassed($job['application_deadline'])): ?>
                        <div class="deadline-danger mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Batas lamaran telah berakhir
                        </div>
                        <button class="btn apply-btn" disabled>
                            <i class="fas fa-times me-2"></i> Lamaran Ditutup
                        </button>
                    <?php elseif (isDeadlineApproaching($job['application_deadline'])): ?>
                        <div class="deadline-warning mb-3">
                            <i class="fas fa-clock me-2"></i>
                            Batas lamaran: <?php echo formatTanggal($job['application_deadline']); ?>
                        </div>
                        <!-- SOLUSI ALTERNATIF - DIRECT LINK -->
                        <a href="apply-job.php?job_id=<?php echo $job['job_id']; ?>" class="btn apply-btn">
                            <i class="fas fa-paper-plane me-2"></i> Lamar Sekarang
                        </a>
                    <?php else: ?>
                        <a href="apply-job.php?job_id=<?php echo $job['job_id']; ?>" class="btn apply-btn">
                            <i class="fas fa-paper-plane me-2"></i> Lamar Sekarang
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Job Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Job Description -->
                    <div class="mb-5">
                        <h3 class="section-title">Deskripsi Pekerjaan</h3>
                        <div class="job-requirement">
                            <?php echo nl2br(htmlspecialchars($job['job_description'])); ?>
                        </div>
                    </div>

                    <!-- Job Requirements -->
                    <?php if (!empty($job['requirements'])): ?>
                        <div class="mb-5">
                            <h3 class="section-title">Persyaratan</h3>
                            <div class="job-requirement">
                                <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Benefits -->
                    <?php if (!empty($job['benefits'])): ?>
                        <div class="mb-5">
                            <h3 class="section-title">Tunjangan & Fasilitas</h3>
                            <div class="job-requirement">
                                <?php echo nl2br(htmlspecialchars($job['benefits'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Application Process -->
                    <div class="mb-5">
                        <h3 class="section-title">Cara Melamar</h3>
                        <div class="job-requirement">
                            <p>Untuk melamar pekerjaan ini, silakan klik tombol "Lamar Sekarang" di atas. Pastikan Anda telah:</p>
                            <ul>
                                <li>Melengkapi profil Anda</li>
                                <li>Mengunggah CV terbaru</li>
                                <li>Memastikan semua persyaratan terpenuhi</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Job Info -->
                    <div class="job-meta mb-4">
                        <h5 class="mb-3">Informasi Pekerjaan</h5>
                        <div class="mb-3">
                            <strong>Tipe Pekerjaan:</strong><br>
                            <span class="badge bg-primary"><?php echo htmlspecialchars($job['job_type']); ?></span>
                        </div>
                        <div class="mb-3">
                            <strong>Lokasi:</strong><br>
                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                            <?php echo htmlspecialchars($job['job_location']); ?>
                        </div>
                        <?php if (!empty($job['experience_level'])): ?>
                            <div class="mb-3">
                                <strong>Level Pengalaman:</strong><br>
                                <?php echo htmlspecialchars($job['experience_level']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($job['education_level'])): ?>
                            <div class="mb-3">
                                <strong>Pendidikan:</strong><br>
                                <?php echo htmlspecialchars($job['education_level']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <strong>Dipublikasikan:</strong><br>
                            <?php echo formatTanggal($job['created_at']); ?>
                        </div>
                        <?php if (!empty($job['application_deadline'])): ?>
                            <div class="mb-3">
                                <strong>Batas Lamaran:</strong><br>
                                <span class="<?php echo isDeadlineApproaching($job['application_deadline']) ? 'text-warning' : 'text-dark'; ?>">
                                    <?php echo formatTanggal($job['application_deadline']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Company Info -->
                    <div class="company-info mb-4">
                        <h5 class="mb-3">Tentang Perusahaan</h5>
                        <div class="text-center mb-3">
                            <div class="company-initial mx-auto">
                                <?php echo substr(htmlspecialchars($job['company_name']), 0, 1); ?>
                            </div>
                        </div>
                        <h6 class="text-center mb-3"><?php echo htmlspecialchars($job['company_name']); ?></h6>

                        <?php if (!empty($job['industry'])): ?>
                            <div class="mb-3">
                                <strong>Industri:</strong><br>
                                <?php echo htmlspecialchars($job['industry']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($job['company_location'])): ?>
                            <div class="mb-3">
                                <strong>Lokasi Perusahaan:</strong><br>
                                <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                <?php echo htmlspecialchars($job['company_location']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($job['website'])): ?>
                            <div class="mb-3">
                                <strong>Website:</strong><br>
                                <a href="<?php echo htmlspecialchars($job['website']); ?>" target="_blank" class="text-primary">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    <?php echo htmlspecialchars($job['website']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($job['company_description'])): ?>
                            <div class="mb-3">
                                <strong>Deskripsi:</strong><br>
                                <p class="text-muted small">
                                    <?php echo nl2br(htmlspecialchars(substr($job['company_description'], 0, 200))); ?>
                                    <?php if (strlen($job['company_description']) > 200): ?>...<?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="text-center">
                            <a href="company-profile.php?id=<?php echo $job['company_id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-building me-1"></i> Lihat Profil Perusahaan
                            </a>
                        </div>
                    </div>

                    <!-- Related Jobs -->
                    <?php if (!empty($relatedJobs)): ?>
                        <div class="mb-4">
                            <h5 class="mb-3">Lowongan Lain dari <?php echo htmlspecialchars($job['company_name']); ?></h5>
                            <?php foreach ($relatedJobs as $relatedJob): ?>
                                <div class="related-job-card">
                                    <h6 class="mb-2">
                                        <a href="job-detail.php?id=<?php echo $relatedJob['job_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($relatedJob['job_title']); ?>
                                        </a>
                                    </h6>
                                    <div class="small text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($relatedJob['job_location']); ?>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo formatTanggal($relatedJob['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Share Job -->
                    <div class="job-meta">
                        <h5 class="mb-3">Bagikan Lowongan</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="shareJob('facebook')">
                                <i class="fab fa-facebook-f"></i>
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="shareJob('twitter')">
                                <i class="fab fa-twitter"></i>
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="shareJob('whatsapp')">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="copyJobLink()">
                                <i class="fas fa-link"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function shareJob(platform) {
            const jobTitle = '<?php echo htmlspecialchars($job['job_title']); ?>';
            const companyName = '<?php echo htmlspecialchars($job['company_name']); ?>';
            const currentUrl = window.location.href;
            const shareText = `Lowongan kerja: ${jobTitle} di ${companyName}`;

            let shareUrl = '';

            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(currentUrl)}`;
                    break;
                case 'whatsapp':
                    shareUrl = `https://wa.me/?text=${encodeURIComponent(shareText + ' ' + currentUrl)}`;
                    break;
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        }

        function copyJobLink() {
            const currentUrl = window.location.href;

            if (navigator.clipboard) {
                navigator.clipboard.writeText(currentUrl).then(function() {
                    alert('Link lowongan berhasil disalin!');
                }).catch(function(err) {
                    console.error('Gagal menyalin link: ', err);
                    fallbackCopyTextToClipboard(currentUrl);
                });
            } else {
                fallbackCopyTextToClipboard(currentUrl);
            }
        }

        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    alert('Link lowongan berhasil disalin!');
                } else {
                    alert('Gagal menyalin link. Silakan salin secara manual.');
                }
            } catch (err) {
                alert('Gagal menyalin link. Silakan salin secara manual.');
            }

            document.body.removeChild(textArea);
        }

        // buat fungsi untuk bookmark job
        function bookmarkJob(jobId) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Silakan login terlebih dahulu.');
                return;
            <?php endif; ?>

            fetch('ajax/bookmark-job.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        job_id: jobId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Update bookmark button if needed
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menyimpan bookmark.');
                });
        }
    </script>
</body>

</html>