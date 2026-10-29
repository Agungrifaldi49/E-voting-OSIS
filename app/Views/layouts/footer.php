<?php
// app/Views/layouts/footer.php
// Footer Resmi E-Voting OSIS SMK Muthia Harapan Cicalengka
// Desain Mewah, Kontras Tinggi & Terhubung dengan Pengaturan Sistem

$schoolName = app_setting('school_name', SCHOOL_NAME);
$schoolNpsn = app_setting('school_npsn', '69900000');
$appTitle = app_setting('app_title', APP_NAME);
$appTagline = app_setting('app_tagline', 'Sistem Pemilihan Elektronik Ketua dan Wakil Ketua OSIS yang mengedepankan asas Langsung, Umum, Bebas, Rahasia, Jujur, dan Adil (LUBER JURDIL).');
$schoolAddress = app_setting('school_address', 'Jl. Babakan Peuteuy No.300, Babakan Peuteuy, Kec. Cicalengka, Kabupaten Bandung, Jawa Barat 40395');
$schoolPhone = app_setting('school_phone', '(022) 7949000');
$schoolEmail = app_setting('school_email', 'info@smkmuthiaharapan.sch.id');
$socialInstagram = app_setting('social_instagram', '@smkmuthiaharapan');
$socialYoutube = app_setting('social_youtube', 'SMK Muthia Harapan Official');
$websiteUrl = app_setting('website_url', 'https://smkmuthiaharapan.sch.id');
$helpdeskPhone = app_setting('helpdesk_phone', '0812-3456-7890');
$helpdeskHours = app_setting('helpdesk_hours', 'Senin - Jumat: 07.30 - 15.30 WIB');
?>
<footer class="site-footer" id="site-footer">
    <div class="container">
        <div class="row g-4 mb-5">
            <!-- 1. IDENTITAS & BRANDING SEKOLAH -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= app_logo() ?>" alt="Logo Sekolah" width="46" height="46" class="rounded-3 bg-white p-1 object-fit-contain shadow-sm">
                    <div>
                        <h5 class="text-white fw-bold mb-0"><?= htmlspecialchars($appTitle, ENT_QUOTES, 'UTF-8') ?></h5>
                        <div class="text-warning small fw-semibold"><?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>

                <p class="footer-text small mb-3">
                    <?= htmlspecialchars($appTagline, ENT_QUOTES, 'UTF-8') ?>
                </p>

                <div class="footer-text small mb-3">
                    <i class="bi bi-geo-alt-fill text-warning me-2"></i>
                    <span><?= htmlspecialchars($schoolAddress, ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <!-- Social Media Links -->
                <div class="d-flex align-items-center gap-2 pt-1">
                    <?php if (!empty($socialInstagram)): ?>
                        <a href="https://instagram.com/<?= ltrim($socialInstagram, '@') ?>" target="_blank" class="footer-social-btn" title="Instagram Resmi" aria-label="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($socialYoutube)): ?>
                        <a href="https://youtube.com" target="_blank" class="footer-social-btn" title="YouTube Channel" aria-label="YouTube">
                            <i class="bi bi-youtube"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($websiteUrl)): ?>
                        <a href="<?= htmlspecialchars($websiteUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="footer-social-btn" title="Website Sekolah" aria-label="Website">
                            <i class="bi bi-globe"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($helpdeskPhone)): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $helpdeskPhone) ?>" target="_blank" class="footer-social-btn" title="WhatsApp Helpdesk" aria-label="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. NAVIGASI CEPAT HALAMAN BERANDA -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="text-white text-uppercase tracking-wider fw-bold mb-3">
                    <i class="bi bi-compass text-warning me-1"></i> Navigasi
                </h6>
                <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                    <li><a href="<?= base_url('index.php#beranda') ?>"><i class="bi bi-chevron-right me-1 text-warning small"></i> Beranda</a></li>
                    <li><a href="<?= base_url('index.php#kandidat') ?>"><i class="bi bi-chevron-right me-1 text-warning small"></i> Paslon OSIS</a></li>
                    <li><a href="<?= base_url('index.php#jadwal') ?>"><i class="bi bi-chevron-right me-1 text-warning small"></i> Jadwal Pemilu</a></li>
                    <li><a href="<?= base_url('index.php#panduan') ?>"><i class="bi bi-chevron-right me-1 text-warning small"></i> Cara Memilih</a></li>
                    <li><a href="<?= base_url('index.php#hasil') ?>"><i class="bi bi-chevron-right me-1 text-warning small"></i> Hasil Suara</a></li>
                    <li><a href="<?= base_url('index.php#faq') ?>"><i class="bi bi-chevron-right me-1 text-warning small"></i> FAQ &amp; Bantuan</a></li>
                </ul>
            </div>

            <!-- 3. KONTAK & PUSAT BANTUAN -->
            <div class="col-lg-3 col-md-6 col-6">
                <h6 class="text-white text-uppercase tracking-wider fw-bold mb-3">
                    <i class="bi bi-headset text-warning me-1"></i> Helpdesk
                </h6>
                <div class="d-flex flex-column gap-2 small">
                    <div>
                        <div class="text-white-50 text-xs text-uppercase">WhatsApp Panitia:</div>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $helpdeskPhone) ?>" target="_blank" class="fw-semibold text-warning text-decoration-none">
                            <i class="bi bi-whatsapp me-1"></i> <?= htmlspecialchars($helpdeskPhone, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <div>
                        <div class="text-white-50 text-xs text-uppercase">Email Resmi:</div>
                        <a href="mailto:<?= htmlspecialchars($schoolEmail, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                            <i class="bi bi-envelope me-1 text-warning"></i> <?= htmlspecialchars($schoolEmail, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <div>
                        <div class="text-white-50 text-xs text-uppercase">Jam Operasional:</div>
                        <div class="footer-text">
                            <i class="bi bi-clock me-1 text-warning"></i> <?= htmlspecialchars($helpdeskHours, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. KEAMANAN & INTEGRITAS PEMILU -->
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white text-uppercase tracking-wider fw-bold mb-3">
                    <i class="bi bi-shield-check text-warning me-1"></i> Integritas
                </h6>
                <div class="footer-security-card">
                    <div class="d-flex align-items-center gap-2 mb-2 text-white fw-bold small">
                        <i class="bi bi-lock-fill text-success fs-6"></i>
                        <span>Secret Ballot 100%</span>
                    </div>
                    <p class="footer-text text-xs mb-2">
                        Setiap suara pemilih dienkripsi dan diputus dari ID akun pemilih. Tidak ada pihak yang dapat mengetahui pilihan suara Anda.
                    </p>
                    <div class="pt-2 border-top border-secondary border-opacity-25 text-xs text-warning fw-semibold">
                        <i class="bi bi-check-circle-fill me-1 text-success"></i> Proteksi Anti-Double Voting Aktif
                    </div>
                </div>
            </div>
        </div>

        <!-- BOTTOM COPYRIGHT BAR -->
        <div class="footer-bottom-bar d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small">
            <div class="text-light text-opacity-75">
                &copy; <?= date('Y') ?> <strong class="text-white"><?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></strong> (NPSN: <?= htmlspecialchars($schoolNpsn, ENT_QUOTES, 'UTF-8') ?>). Seluruh Hak Cipta Dilindungi.
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-dark border border-secondary border-opacity-50 text-light py-1 px-2">
                    <i class="bi bi-shield-lock text-warning me-1"></i> Asas LUBER JURDIL
                </span>
                <span class="text-white-50">
                    E-Voting OSIS v<?= APP_VERSION ?>
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 JS Bundle (Ensured loaded from head with safety check) -->
<script>
if (typeof bootstrap === 'undefined') {
    var bsScript = document.createElement('script');
    bsScript.src = "<?= asset('assets/js/bootstrap.bundle.min.js') ?>";
    bsScript.onerror = function() {
        var cdnScript = document.createElement('script');
        cdnScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
        document.head.appendChild(cdnScript);
    };
    document.head.appendChild(bsScript);
}
</script>

<!-- Chart.js 4.4 CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Main Interactive JS with cache busting -->
<script src="<?= asset('assets/js/main.js') ?>?v=<?= file_exists(PUBLIC_PATH . '/assets/js/main.js') ? filemtime(PUBLIC_PATH . '/assets/js/main.js') : '1.0.1' ?>"></script>
</body>
</html>
