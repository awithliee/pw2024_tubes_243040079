<?php
session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=apply-job.php');
    exit();
}

$message = '';
$messageType = '';

// Proses form submission (POST request)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
    $user_id = $_SESSION['user_id'];
    $cover_letter = trim($_POST['cover_letter']);
    
    // Validasi input
    if ($job_id <= 0) {
        $message = 'ID pekerjaan tidak valid.';
        $messageType = 'danger';
    } else {
        // Cek apakah user sudah pernah melamar untuk job ini
        $checkQuery = "SELECT application_id FROM applications WHERE job_id = :job_id AND user_id = :user_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':job_id', $job_id);
        $checkStmt->bindParam(':user_id', $user_id);
        $checkStmt->execute();
        
        if ($checkStmt->fetch()) {
            $message = 'Anda sudah pernah melamar untuk pekerjaan ini.';
            $messageType = 'warning';
        } else {
            // Handle file upload
            $cv_filename = null;
            if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'users/uploads/resumes/';
                
                // Buat folder jika belum ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_tmp = $_FILES['resume_file']['tmp_name'];
                $file_name = $_FILES['resume_file']['name'];
                $file_size = $_FILES['resume_file']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Validasi file
                $allowed_ext = ['pdf', 'doc', 'docx'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file_ext, $allowed_ext)) {
                    $message = 'Format file tidak diizinkan. Gunakan PDF, DOC, atau DOCX.';
                    $messageType = 'danger';
                } elseif ($file_size > $max_size) {
                    $message = 'Ukuran file terlalu besar. Maksimal 5MB.';
                    $messageType = 'danger';
                } else {
                    // Generate nama file unik
                    $cv_filename = 'cv_' . $user_id . '_' . $job_id . '_' . time() . '.' . $file_ext;
                    $upload_path = $upload_dir . $cv_filename;
                    
                    if (!move_uploaded_file($file_tmp, $upload_path)) {
                        $message = 'Gagal mengupload file CV.';
                        $messageType = 'danger';
                        $cv_filename = null;
                    }
                }
            }
            
            // Insert lamaran ke database jika tidak ada error
            if (empty($message)) {
                try {
                    $insertQuery = "INSERT INTO applications (job_id, user_id, cover_letter, resume_file, application_status, created_at) 
                                   VALUES (:job_id, :user_id, :cover_letter, :resume_file, 'Pending', NOW())";
                    $insertStmt = $db->prepare($insertQuery);
                    $insertStmt->bindParam(':job_id', $job_id);
                    $insertStmt->bindParam(':user_id', $user_id);
                    $insertStmt->bindParam(':cover_letter', $cover_letter);
                    $insertStmt->bindParam(':resume_file', $cv_filename);
                    
                    if ($insertStmt->execute()) {
                        $message = 'Lamaran Anda berhasil dikirim!';
                        $messageType = 'success';
                        
                        // Redirect setelah 2 detik
                        header("refresh:2;url=job-detail.php?id=" . $job_id);
                    } else {
                        $message = 'Terjadi kesalahan saat mengirim lamaran.';
                        $messageType = 'danger';
                    }
                } catch (PDOException $e) {
                    $message = 'Error database: ' . $e->getMessage();
                    $messageType = 'danger';
                }
            }
        }
    }
}

// Ambil data job untuk form (GET request)
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if ($job_id <= 0) {
    header('Location: jobs.php');
    exit();
}

// Query untuk mengambil detail job
$jobQuery = "SELECT j.*, c.company_name 
             FROM jobs j 
             JOIN companies c ON j.company_id = c.company_id 
             WHERE j.job_id = :job_id AND j.is_active = 1";
$jobStmt = $db->prepare($jobQuery);
$jobStmt->bindParam(':job_id', $job_id);
$jobStmt->execute();
$job = $jobStmt->fetch();

if (!$job) {
    header('Location: jobs.php');
    exit();
}

// Ambil data user
$userQuery = "SELECT * FROM users WHERE user_id = :user_id";
$userStmt = $db->prepare($userQuery);
$userStmt->bindParam(':user_id', $_SESSION['user_id']);
$userStmt->execute();
$user = $userStmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lamar Pekerjaan - <?php echo htmlspecialchars($job['job_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .apply-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
        }
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        .job-info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .btn-apply {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-apply:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>

<body>
    <!-- Header -->
    <section class="apply-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="mb-3">
                        <a href="job-detail.php?id=<?php echo $job['job_id']; ?>" class="text-white text-decoration-none">
                            <i class="fas fa-arrow-left me-2"></i> Kembali ke Detail Pekerjaan
                        </a>
                    </div>
                    <h1 class="mb-2">Lamar Pekerjaan</h1>
                    <p class="mb-0 opacity-75">Lengkapi form di bawah untuk melamar pekerjaan ini</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Form Content -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="form-container">
                        <!-- Alert Messages -->
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Job Info -->
                        <div class="job-info-card">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($job['job_title']); ?></h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($job['company_name']); ?>
                                        <i class="fas fa-map-marker-alt me-2 ms-3"></i><?php echo htmlspecialchars($job['job_location']); ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Application Form -->
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="resume_file" class="form-label">Upload CV <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="resume_file" name="resume_file" accept=".pdf,.doc,.docx" required>
                                <div class="form-text">Format yang diizinkan: PDF, DOC, DOCX. Maksimal 5MB.</div>
                            </div>

                            <div class="mb-4">
                                <label for="cover_letter" class="form-label">Surat Lamaran / Cover Letter</label>
                                <textarea class="form-control" id="cover_letter" name="cover_letter" rows="8" 
                                          placeholder="Tuliskan alasan Anda melamar pekerjaan ini, pengalaman yang relevan, dan mengapa Anda cocok untuk posisi ini..."></textarea>
                                <div class="form-text">Opsional, tetapi sangat disarankan untuk meningkatkan peluang Anda.</div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="job-detail.php?id=<?php echo $job['job_id']; ?>" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-apply">
                                    <i class="fas fa-paper-plane me-2"></i>Kirim Lamaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview file name when selected
        document.getElementById('resume_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : '';
            if (fileName) {
                console.log('File selected:', fileName);
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const cvFile = document.getElementById('resume_file').files[0];
            
            if (!cvFile) {
                e.preventDefault();
                alert('Silakan pilih file CV terlebih dahulu.');
                return false;
            }

            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!allowedTypes.includes(cvFile.type)) {
                e.preventDefault();
                alert('Format file tidak valid. Gunakan PDF, DOC, atau DOCX.');
                return false;
            }

            const maxSize = 5 * 1024 * 1024; // 5MB
            if (cvFile.size > maxSize) {
                e.preventDefault();
                alert('Ukuran file terlalu besar. Maksimal 5MB.');
                return false;
            }

            // Show loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengirim...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>