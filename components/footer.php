<?php
// components/footer.php
?>
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5>Tentang Lamarin</h5>
                <p>Lamarin adalah platform lowongan kerja terkemuka yang menghubungkan pencari kerja dengan perusahaan terbaik di Indonesia.</p>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Tautan Penting</h5>
                <ul class="list-unstyled">
                    <li><a href="about.php" class="text-white">Tentang Kami</a></li>
                    <li><a href="privacy.php" class="text-white">Kebijakan Privasi</a></li>
                    <li><a href="terms.php" class="text-white">Syarat & Ketentuan</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Hubungi Kami</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-map-marker-alt me-2"></i>Kampus IV : Jl. Dr. Setiabudi No. 193 Bandung 40154</li>
                    <li><i class="fas fa-phone me-2"></i> (085) 624-604-085</li>
                    <li><i class="fas fa-envelope me-2"></i> lamarin@gmail.com</li>
                </ul>
                <div class="mt-3">
                    <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <hr class="bg-light">
        <div class="text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Lamarin. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</footer>

<style>
    .footer {
        background-color:
            #343a40;
        color: white;
        padding: 40px 0 20px 0;
        margin-top: 50px;
    }

    .hero-section {
        background: linear-gradient(135deg,
                #667eea 0%,
                #764ba2 100%);
        color: white;
        padding: 100px 0;
    }

    .stat-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        text-align: center;
        margin-bottom: 30px;
    }

    .feature-icon {
        font-size: 3rem;
        color:
            #667eea;
        margin-bottom: 20px;
    }

    .job-card {
        border: none;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
        margin-bottom: 30px;
    }

    .job-card:hover {
        transform: translateY(-5px);
    }
</style>