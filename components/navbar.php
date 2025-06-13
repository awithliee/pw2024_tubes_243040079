<?php
// components/navbar.php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <strong>Lamarin</strong>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="jobs.php">Lowongan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="companies.php">Perusahaan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">Tentang Kami</a>
                </li>
            </ul>
            <div class="d-flex">
                <?php if ($isLoggedIn): ?>
                    <?php if ($isAdmin): ?>
                        <a href="admin/index.php" class="btn btn-warning me-2">Admin Panel</a>
                    <?php else: ?>
                        <a href="users/dashboard.php" class="btn btn-outline-light me-2">Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-danger">Keluar</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light me-2">Masuk</a>
                    <a href="register.php" class="btn btn-primary">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>