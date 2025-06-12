<?php
$success = '';
$error = '';

// Process the contact form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Input validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Semua field harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        // Email details
        $to = 'alipasundan24@gmail.com'; // Recipient email address
        $email_subject = "Pesan Kontak dari Lamarin: " . $subject;
        $email_body = "Anda telah menerima pesan baru dari formulir kontak Lamarin.\n\n" .
                      "Nama: " . $name . "\n" .
                      "Email: " . $email . "\n" .
                      "Subjek: " . $subject . "\n" .
                      "Pesan:\n" . $message;
        $headers = "From: webmaster@lamarin.com\r\n"; // Sender email (should be from your domain)
        $headers .= "Reply-To: " . $email . "\r\n"; // Reply-to the user's email

        // Send the email
        if (mail($to, $email_subject, $email_body, $headers)) {
            $success = 'Terima kasih! Pesan Anda telah dikirim. Kami akan segera menghubungi Anda.';
            // Clear form data on successful submission
            $_POST = array();
        } else {
            $error = 'Maaf, terjadi kesalahan saat mengirim pesan Anda. Silakan coba lagi nanti.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak - Lamarin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/contact.css">

</head>

<body>
    <?php include 'components/navbar.php'; ?>

    <section class="hero-section">
        <div class="container">
            <div class="text-center">
                <h1 class="display-4 fw-bold mb-4">Hubungi Kami</h1>
                <p class="lead mb-0">
                    Kami siap membantu Anda. Jangan ragu untuk menghubungi tim kami
                    kapan saja Anda membutuhkan bantuan.
                </p>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card contact-card">
                        <div class="card-body p-5">
                            <h3 class="fw-bold mb-4">Kirim Pesan</h3>

                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo $success; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Nama</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            placeholder="Masukkan nama Anda" required
                                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="Masukkan email Anda" required
                                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subjek</label>
                                    <input type="text" class="form-control" id="subject" name="subject"
                                        placeholder="Masukkan subjek pesan" required
                                        value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                                </div>

                                <div class="mb-4">
                                    <label for="message" class="form-label">Pesan</label>
                                    <textarea class="form-control" id="message" name="message" rows="6"
                                        placeholder="Tulis pesan Anda di sini..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary btn-send">
                                    <i class="fas fa-paper-plane me-2"></i>Kirim Pesan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="contact-info-card p-4">
                        <h4 class="fw-bold mb-4">Informasi Kontak</h4>

                        <div class="mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt contact-info-icon"></i>
                                <div>
                                    <h6 class="mb-1">Alamat</h6>
                                    <p class="mb-0 opacity-75">
                                        Kampus IV : Jl. Dr. Setiabudi No. 193 <br>
                                        Bandung 40154
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-phone contact-info-icon"></i>
                                <div>
                                    <h6 class="mb-1">Telepon</h6>
                                    <p class="mb-0 opacity-75">+62 85 624 604 085</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-envelope contact-info-icon"></i>
                                <div>
                                    <h6 class="mb-1">Email</h6>
                                    <p class="mb-0 opacity-75">info@lamarin.com</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock contact-info-icon"></i>
                                <div>
                                    <h6 class="mb-1">Jam Operasional</h6>
                                    <p class="mb-0 opacity-75">
                                        Senin - Jumat: 09:00 - 18:00<br>
                                        Sabtu: 09:00 - 15:00<br>
                                        Minggu: Tutup
                                    </p>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 opacity-25">

                        <div>
                            <h6 class="mb-3">Follow Us</h6>
                            <div class="d-flex gap-3">
                                <a href="#" class="text-white text-decoration-none">
                                    <i class="fab fa-facebook-f fa-lg"></i>
                                </a>
                                <a href="#" class="text-white text-decoration-none">
                                    <i class="fab fa-twitter fa-lg"></i>
                                </a>
                                <a href="#" class="text-white text-decoration-none">
                                    <i class="fab fa-instagram fa-lg"></i>
                                </a>
                                <a href="#" class="text-white text-decoration-none">
                                    <i class="fab fa-linkedin-in fa-lg"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container"> Bandung 40834<br>

            <div class="text-center mb-5">
                <h2 class="fw-bold">Lokasi Kami</h2>
                <p class="text-muted">Temukan kantor kami di universitas pasundan</p>
            </div>
            <div class="map-container text-center">
                <a href="https://www.google.com/maps/place/Universitas+Pasundan/@-6.866502,107.593245,17z/data=!3m1!4b1!4m6!3m5!1s0x2e68e6be3e8a0c49:0x730028bf4627def4!8m2!3d-6.866502!4d107.593245!16s%2Fg%2F1td10cl0?entry=ttu&g_ep=EgoyMDI1MDUyOC4wIKXMDSoASAFQAw%3D%3D" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-map-marked-alt" style="font-size: 4rem; color: #667eea; margin-bottom: 20px;"></i>
                    <h5 class="text-muted">Interactive Map</h5>
                    <p class="text-muted">
                        Universitas Pasundan<br>
                        <small>* Klik untuk membuka lokasi di Google Maps</small>
                    </p>
                </a>
            </div>

        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Pertanyaan Umum</h2>
                <p class="text-muted">Jawaban untuk pertanyaan yang sering diajukan</p>
            </div>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                                    Apakah gratis untuk mendaftar di Lamarin?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse show"
                                aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Ya, pendaftaran di Lamarin sepenuhnya gratis untuk pencari kerja.
                                    Anda dapat membuat profil, melamar pekerjaan, dan menggunakan semua
                                    fitur utama tanpa biaya apapun.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                    Bagaimana cara melamar pekerjaan?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse"
                                aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Setelah membuat akun dan melengkapi profil, Anda dapat mencari lowongan
                                    yang sesuai, lalu klik tombol "Lamar" pada lowongan yang diminati.
                                    Anda akan diminta untuk mengirim CV dan surat lamaran.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                    Apakah data pribadi saya aman?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse"
                                aria-labelledby="faq3" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Keamanan data adalah prioritas utama kami. Semua informasi pribadi Anda
                                    dilindungi dengan enkripsi tingkat tinggi dan hanya dibagikan dengan
                                    perusahaan yang Anda lamar dengan persetujuan Anda.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                    Berapa lama waktu respons dari perusahaan?
                                </button>
                            </h2>
                            <div id="collapse4" class="accordion-collapse collapse"
                                aria-labelledby="faq4" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Waktu respons bervariasi tergantung kebijakan masing-masing perusahaan,
                                    umumnya antara 1-2 minggu. Anda akan mendapat notifikasi melalui email
                                    dan dashboard ketika ada update status lamaran Anda.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <div class="text-center">
                <h3 class="text-white fw-bold mb-3">Masih Ada Pertanyaan?</h3>
                <p class="text-white mb-4">
                    Tim customer service kami siap membantu Anda 24/7
                </p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="mailto:alipasundan24@gmail.com" class="btn btn-light">
                        <i class="fas fa-envelope me-2"></i>Email Kami
                    </a>
                    <a href="tel:+6285624604085" class="btn btn-outline-light">
                        <i class="fas fa-phone me-2"></i>Telepon Kami
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>

</body>

</html>