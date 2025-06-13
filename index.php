    <?php
    // index.php
    require_once './config/database.php';

    // Mengambil data lowongan pekerjaan terbaru
    $query = "SELECT j.*, c.company_name 
            FROM jobs j 
            JOIN companies c ON j.company_id = c.company_id 
            WHERE j.is_active = TRUE 
            ORDER BY j.created_at DESC 
            LIMIT 6";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Menghitung total lowongan yang tersedia
    $query = "SELECT COUNT(*) as total FROM jobs WHERE is_active = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalJobs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Menghitung total perusahaan
    $query = "SELECT COUNT(*) as total FROM companies";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalCompanies = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Menghitung total pengguna yang telah berhasil mendapatkan pekerjaan
    $query = "SELECT COUNT(*) as total FROM users WHERE role_id = 2 AND is_active = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Memformat tanggal ke format Indonesia
    function formatTanggal($tanggal)
    {
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
    ?>

    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Lamarin - Portal Lowongan Kerja Terpercaya</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!------style css-------->
        <link rel="stylesheet" href="css/index.css">

    </head>

    <body>
        <!-- Navbar -->
        <?php include 'components/navbar.php'; ?>

        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="display-4 fw-bold mb-4">Temukan Karir Impian Anda</h1>
                        <p class="lead mb-5">Portal lowongan kerja terpercaya dengan ribuan peluang karir dari perusahaan terkemuka di seluruh Indonesia</p>

                        <!-- Search Bar -->
                        <form action="search.php" method="GET" class="search-form">
                            <div class="row">
                                <div class="d-flex align-items-center rounded-pill col-md-10 mb-3 md-2">
                                    <input type="text" name="keyword" class="form-control form-control-lg rounded-pill" placeholder="Posisi, nama perusahaan, atau keahlian...">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <button type="submit" class="btn btn-light d-flex align-items-center rounded-pill w-100">
                                        <i class="fas fa-search me-1"></i>Cari
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-center">
                            <i class="fas fa-briefcase" style="font-size: 10rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats-section">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="section-title">Mengapa Memilih Lamarin?</h2>
                    <p class="section-subtitle">Platform terpercaya dengan pencapaian yang membanggakan</p>
                </div>

                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="stat-card">
                            <i class="fas fa-briefcase feature-icon"></i>
                            <h3><?php echo number_format($totalJobs); ?></h3>
                            <h5 class="fw-bold">Lowongan Tersedia</h5>
                            <p class="text-muted mb-0">Peluang karir menanti Anda</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="stat-card">
                            <i class="fas fa-building feature-icon"></i>
                            <h3><?php echo number_format($totalCompanies); ?></h3>
                            <h5 class="fw-bold">Perusahaan Terdaftar</h5>
                            <p class="text-muted mb-0">Perusahaan terpercaya</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="stat-card">
                            <i class="fas fa-users feature-icon"></i>
                            <h3><?php echo number_format($totalUsers); ?></h3>
                            <h5 class="fw-bold">Pencari Kerja Sukses</h5>
                            <p class="text-muted mb-0">Telah menemukan pekerjaan</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Latest Jobs Section -->
        <section class="latest-jobs-section">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="section-title">Lowongan Terbaru</h2>
                        <p class="section-subtitle">Peluang karir terbaru dari perusahaan terpercaya</p>
                    </div>
                    <a href="jobs.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-arrow-right me-2"></i>Lihat Semua
                    </a>
                </div>

                <div class="row">
                    <?php if (count($jobs) > 0): ?>
                        <?php foreach ($jobs as $job): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card job-card">
                                    <div class="card-body p-4">
                                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($job['job_title']); ?></h5>
                                        <h6 class="card-subtitle mb-3 text-primary"><?php echo htmlspecialchars($job['company_name']); ?></h6>

                                        <div class="mb-3">
                                            <p class="card-text mb-2">
                                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                                <?php echo htmlspecialchars($job['job_location']); ?>
                                            </p>
                                            <p class="card-text mb-2">
                                                <i class="fas fa-briefcase text-muted me-2"></i>
                                                <?php echo htmlspecialchars($job['job_type']); ?>
                                            </p>
                                            <?php if (!empty($job['salary_range'])): ?>
                                                <p class="card-text mb-2">
                                                    <i class="fas fa-money-bill-wave text-muted me-2"></i>
                                                    <?php echo htmlspecialchars($job['salary_range']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($job['application_deadline'])): ?>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Deadline: <?php echo formatTanggal($job['application_deadline']); ?>
                                                </small>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer bg-white border-top-0 p-4">
                                        <a href="job-detail.php?id=<?php echo $job['job_id']; ?>" class="btn btn-primary w-100">
                                            <i class="fas fa-eye me-2"></i>Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center p-5">
                                <i class="fas fa-info-circle fa-3x mb-3 text-primary"></i>
                                <h5>Belum Ada Lowongan</h5>
                                <p class="mb-0">Belum ada lowongan pekerjaan yang tersedia saat ini. Silakan kembali lagi nanti.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section bg-light">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="section-title">Fitur Unggulan</h2>
                    <p class="section-subtitle">Kemudahan yang kami tawarkan untuk Anda</p>
                </div>

                <div class="row text-center">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="p-4">
                            <i class="fas fa-search feature-icon"></i>
                            <h4 class="fw-bold">Pencarian Mudah</h4>
                            <p class="text-muted">Temukan lowongan yang sesuai dengan keahlian, minat, dan lokasi Anda dengan sistem pencarian canggih.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="p-4">
                            <i class="fas fa-file-alt feature-icon"></i>
                            <h4 class="fw-bold">Profil Sekali Klik</h4>
                            <p class="text-muted">Buat profil sekali dan gunakan untuk melamar berbagai pekerjaan dengan mudah dan cepat.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="p-4">
                            <i class="fas fa-bell feature-icon"></i>
                            <h4 class="fw-bold">Notifikasi Real-time</h4>
                            <p class="text-muted">Dapatkan pemberitahuan tentang lowongan baru yang sesuai dengan preferensi Anda secara langsung.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="p-4">
                            <i class="fas fa-shield-alt feature-icon"></i>
                            <h4 class="fw-bold">Keamanan Terjamin</h4>
                            <p class="text-muted">Data pribadi dan profesional Anda dilindungi dengan sistem keamanan tingkat tinggi.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="p-4">
                            <i class="fas fa-mobile-alt feature-icon"></i>
                            <h4 class="fw-bold">Mobile Friendly</h4>
                            <p class="text-muted">Akses platform kami kapan saja, di mana saja melalui perangkat mobile Anda dengan mudah.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="p-4">
                            <i class="fas fa-headset feature-icon"></i>
                            <h4 class="fw-bold">Dukungan 24/7</h4>
                            <p class="text-muted">Tim customer service kami siap membantu Anda 24 jam sehari, 7 hari seminggu.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h2 class="fw-bold mb-3">Siap Memulai Karir Baru?</h2>
                        <p class="lead mb-0">
                            Daftar sekarang dan mulai perjalanan karir impian Anda bersama Lamarin.
                            Bergabunglah dengan ribuan profesional yang telah mempercayai platform kami.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end text-center mt-4 mt-lg-0">
                        <a href="register.php" class="btn btn-primary btn-lg me-3 fw-bold">
                            <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                        </a>
                        <a href="jobs.php" class="btn btn-outline-light btn-lg fw-bold mt-3">
                            <i class="fas fa-search me-2"></i>Telusuri Lowongan
                        </a>
                    </div>
                </div>
            </div>
        </section>
        <!--kontak -->
        <section class="py-5">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <h2 class="section-title text-primary">Kunjungi Kami</h2>
                        <p class="text-muted mb-4">Kantor kami terbuka untuk kunjungan langsung. Tim kami siap membantu Anda secara profesional di lokasi untuk segala kebutuhan informasi, konsultasi, maupun kerja sama terkait layanan Lamarin.</p>    <div class="mb-3">
                            <p class="mb-1"><strong class="text-primary"><i class="bi bi-geo-alt-fill me-2"></i>Alamat:</strong></p>
                            <p>Jl. Dr. Setiabudi No.193, Gegerkalong, Kec. Sukasari, Kota Bandung, Jawa Barat 40153</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="https://wa.me/6285624604085" target="_blank" class="btn btn-success"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="ratio ratio-16x9 rounded shadow-lg overflow-hidden">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.2009906319286!2d107.5906700747565!3d-6.866501993132092!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e6be3e8a0c49%3A0x730028bf4627def4!2sUniversitas%20Pasundan!5e0!3m2!1sen!2sid!4v1749831158264!5m2!1sen!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        </div>

        <!-- Footer -->
        <?php include 'components/footer.php'; ?>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    </body>

    </html>