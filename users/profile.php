<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    
    // Validate required fields
    if (empty($full_name) || empty($email)) {
        $error = 'Nama lengkap dan email harus diisi!';
    } else {
        try {
            // Check if email already exists for other users
            $check_email = "SELECT user_id FROM users WHERE email = :email AND user_id != :user_id";
            $stmt_check = $db->prepare($check_email);
            $stmt_check->bindParam(':email', $email);
            $stmt_check->bindParam(':user_id', $user_id);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                $error = 'Email sudah digunakan oleh pengguna lain!';
            } else {
                $profile_photo = null;
                
                // Handle profile photo upload
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                    $allowed_photo_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['profile_photo']['type'];
                    $file_size = $_FILES['profile_photo']['size'];
                    
                    if (!in_array($file_type, $allowed_photo_types)) {
                        $error = 'Format foto harus JPG, JPEG, PNG, atau GIF!';
                    } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
                        $error = 'Ukuran foto maksimal 5MB!';
                    } else {
                        $upload_dir = 'uploads/profiles/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                        $profile_photo = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $profile_photo;
                        
                        if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                            $error = 'Gagal mengupload foto profil!';
                        }
                    }
                }
                
                if (empty($error)) {
                    // Update user data
                    $update_query = "UPDATE users SET 
                        full_name = :full_name, 
                        email = :email, 
                        phone_number = :phone_number, 
                        address = :address";
                    
                    $params = [
                        ':full_name' => $full_name,
                        ':email' => $email,
                        ':phone_number' => $phone_number,
                        ':address' => $address,
                        ':user_id' => $user_id
                    ];
                    
                    if ($profile_photo) {
                        $update_query .= ", profile_photo = :profile_photo";
                        $params[':profile_photo'] = $profile_photo;
                    }
                    
                    $update_query .= " WHERE user_id = :user_id";
                    
                    $stmt = $db->prepare($update_query);
                    
                    if ($stmt->execute($params)) {
                        $message = 'Profil berhasil diperbarui!';
                        // Update session data
                        $_SESSION['full_name'] = $full_name;
                    } else {
                        $error = 'Gagal memperbarui profil!';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan database: ' . $e->getMessage();
        }
    }
}

// Get current user data
$query = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Lamarin Job Portal</title>
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

        .profile-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b5b95 100%);
            transform: translateY(-2px);
        }

        .profile-photo-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .upload-area:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }

        .file-info {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .current-file {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
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
                        <a class="nav-link" href="my-applications.php">
                            <i class="fas fa-file-alt me-2"></i>Lamaran Saya
                        </a>
                        <a class="nav-link active" href="profile.php">
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
                        <span class="navbar-brand mb-0 h1">Profile</span>
                        <div class="d-flex align-items-center">
                            <span class="me-3">Selamat datang, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></span>
                            <img src="<?php echo $user['profile_photo'] ? 'uploads/profiles/' . $user['profile_photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=667eea&color=fff'; ?>"
                                alt="Profile" class="rounded-circle" width="40" height="40">
                        </div>
                    </div>
                </nav>

                <div class="container-fluid px-4">
                    <!-- Alert Messages -->
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Profile Photo Section -->
                        <div class="col-md-4 mb-4">
                            <div class="card profile-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title mb-3">
                                        <i class="fas fa-camera me-2"></i>Foto Profil
                                    </h5>
                                    <img src="<?php echo $user['profile_photo'] ? 'uploads/profiles/' . $user['profile_photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=667eea&color=fff'; ?>"
                                        alt="Profile" class="rounded-circle profile-photo-preview mb-3">
                                    <p class="mb-0">
                                        <small><?php echo htmlspecialchars($user['full_name']); ?></small><br>
                                        <small class="opacity-75"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Form -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">
                                        <i class="fas fa-user-edit me-2"></i>Edit Profil
                                    </h5>

                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="full_name" class="form-label">
                                                    <i class="fas fa-user me-1"></i>Nama Lengkap *
                                                </label>
                                                <input type="text" class="form-control" id="full_name" name="full_name"
                                                    value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">
                                                    <i class="fas fa-envelope me-1"></i>Email *
                                                </label>
                                                <input type="email" class="form-control" id="email" name="email"
                                                    value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="phone_number" class="form-label">
                                                    <i class="fas fa-phone me-1"></i>Nomor Telepon
                                                </label>
                                                <input type="text" class="form-control" id="phone_number" name="phone_number"
                                                    value="<?php echo htmlspecialchars($user['phone_number']); ?>">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="address" class="form-label">
                                                    <i class="fas fa-map-marker-alt me-1"></i>Alamat
                                                </label>
                                                <input type="text" class="form-control" id="address" name="address"
                                                    value="<?php echo htmlspecialchars($user['address']); ?>">
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <!-- Profile Photo Upload -->
                                        <div class="mb-4">
                                            <label class="form-label">
                                                <i class="fas fa-camera me-1"></i>Upload Foto Profil
                                            </label>
                                            
                                            <?php if ($user['profile_photo']): ?>
                                                <div class="current-file">
                                                    <i class="fas fa-image text-success me-2"></i>
                                                    <strong>File saat ini:</strong> <?php echo $user['profile_photo']; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="upload-area">
                                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                                                <div class="file-info">
                                                    Format: JPG, JPEG, PNG, GIF (Maksimal 5MB)
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <a href="dashboard.php" class="btn btn-secondary me-md-2">
                                                <i class="fas fa-arrow-left me-1"></i>Kembali
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview uploaded image
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-photo-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // File size validation
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.size > 5 * 1024 * 1024) {
                alert('Ukuran foto maksimal 5MB!');
                this.value = '';
            }
        });
    </script>
</body>

</html>