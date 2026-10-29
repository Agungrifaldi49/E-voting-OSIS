<?php
// public/index.php
// Halaman Utama Publik E-Voting OSIS SMK Muthia Harapan Cicalengka
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Candidate.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/SettingHelper.php';

$pdo = Database::getConnection();

$activeElection = Election::getActive();
$candidates = [];
$candidateCount = 0;
$results = null;

if ($activeElection) {
    $candidates = Candidate::getByElectionId($activeElection['id'], true);
    $candidateCount = count($candidates);
    if ($activeElection['results_visibility'] !== 'hidden') {
        $results = Vote::getResults($activeElection['id']);
    }
}

// 1. Dynamic DPT Count (Total Pemilih Terdaftar)
$dptCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('siswa', 'guru') AND account_status = 'active'")->fetchColumn();
if ($dptCount === 0) {
    $masterCount = (int)$pdo->query("SELECT (SELECT COUNT(*) FROM student_registry) + (SELECT COUNT(*) FROM teacher_registry)")->fetchColumn();
    $dptCount = $masterCount > 0 ? $masterCount : 1250;
}

// 2. Total Suara Sah Masuk & Turnout
$totalVotes = 0;
if ($activeElection) {
    $totalVotes = (int)$pdo->query("SELECT COUNT(*) FROM votes WHERE election_id = " . (int)$activeElection['id'])->fetchColumn();
}
$turnoutPct = ($dptCount > 0 && $totalVotes > 0) ? round(($totalVotes / $dptCount) * 100, 1) : 0;

// 3. Urgent Ticker Configuration
$urgentEnabled = SettingHelper::get('hero_announcement_enabled', '1') === '1';
$urgentText = SettingHelper::get('hero_announcement_text', '');
if (empty($urgentText)) {
    $latestAnn = $pdo->query("SELECT title, content FROM announcements WHERE is_active = 1 ORDER BY id DESC LIMIT 1")->fetch();
    if ($latestAnn) {
        $urgentText = $latestAnn['title'] . ': ' . $latestAnn['content'];
    }
}

$pageTitle = "Beranda Resmi - Pemilihan Ketua dan Wakil Ketua OSIS " . app_setting('school_name', SCHOOL_NAME);
require_once BASE_PATH . '/app/Views/layouts/header.php';
require_once BASE_PATH . '/app/Views/layouts/navbar.php';
?>

<!-- 0. TOP URGENT ANNOUNCEMENT TICKER (JIKA DIAKTIFKAN DI ADMIN) -->
<?php if ($urgentEnabled && !empty($urgentText)): ?>
<div class="hero-urgent-ticker" id="top-announcement-ticker">
    <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2 overflow-hidden">
            <span class="ticker-badge"><i class="bi bi-megaphone-fill me-1"></i> PENGUMUMAN RESMI</span>
            <span class="text-truncate" style="max-width: 820px;" title="<?= htmlspecialchars($urgentText, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($urgentText, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
        <a href="#jadwal" class="text-warning text-decoration-none small fw-semibold text-nowrap">
            Detail Jadwal <i class="bi bi-arrow-right-short"></i>
        </a>
    </div>
</div>
<?php endif; ?>

<?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

<!-- 1. HERO SECTION (ULTRA-LUXURY WITH GLASSMORPHISM & DYNAMIC SETTINGS) -->
<section class="hero-section hero-luxury-mesh" id="beranda">
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center g-4 g-lg-5">
            <div class="col-lg-7">
                <?php if ($activeElection): ?>
                    <div class="hero-badge mb-3 shadow-sm">
                        <span class="live-pulse me-1"></span>
                        <i class="bi bi-shield-check text-warning"></i>
                        <span>
                            <?= htmlspecialchars(SettingHelper::get('hero_badge_text', 'Pesta Demokrasi Siswa • Asas Luber Jurdil'), ENT_QUOTES, 'UTF-8') ?>
                            &bull; Periode <strong><?= htmlspecialchars($activeElection['tahun'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </span>
                    </div>
                <?php else: ?>
                    <div class="hero-badge mb-3">
                        <i class="bi bi-info-circle text-info"></i>
                        <span><?= htmlspecialchars(SettingHelper::get('hero_badge_text', 'Belum Ada Periode Pemilihan Aktif'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <h1 class="hero-title text-white">
                    <?= htmlspecialchars(SettingHelper::get('hero_title', 'PEMILIHAN KETUA & WAKIL KETUA OSIS'), ENT_QUOTES, 'UTF-8') ?>
                    <span class="d-block text-gradient-gold gold-halo-glow"><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></span>
                </h1>

                <p class="hero-subtitle mt-3 mb-4 text-light text-opacity-75">
                    <?= htmlspecialchars(SettingHelper::get('hero_subtitle', 'Kenali calon pemimpinmu, pelajari visi dan misinya, dan gunakan hak pilihmu untuk menentukan masa depan OSIS ' . SCHOOL_NAME . '.'), ENT_QUOTES, 'UTF-8') ?>
                </p>

                <div class="d-flex flex-column flex-sm-row gap-2 gap-sm-3 align-items-stretch align-items-sm-center">
                    <a href="#kandidat" class="btn btn-gold-custom btn-lg px-4 shadow text-center" id="btn-hero-candidates">
                        <i class="bi bi-people-fill me-2"></i> <?= htmlspecialchars(SettingHelper::get('hero_cta_cand_text', 'Lihat Pasangan Calon'), ENT_QUOTES, 'UTF-8') ?>
                    </a>

                    <?php if (AuthHelper::check()): ?>
                        <a href="<?= base_url('voting.php') ?>" class="btn btn-outline-light btn-lg px-4 text-center" id="btn-hero-vote">
                            <i class="bi bi-check2-square text-warning me-2"></i> <?= htmlspecialchars(SettingHelper::get('hero_cta_vote_text', 'Masuk Bilik Suara'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('login.php') ?>" class="btn btn-outline-light btn-lg px-4 text-center" id="btn-hero-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Login untuk Memilih
                        </a>
                        <a href="<?= base_url('register.php') ?>" class="btn btn-outline-warning btn-lg px-3 text-center d-sm-none">
                            <i class="bi bi-person-plus me-1"></i> Registrasi Pemilih
                        </a>
                        <a href="<?= base_url('register.php') ?>" class="btn btn-link text-warning text-decoration-none fw-semibold d-none d-sm-inline-flex align-items-center">
                            <i class="bi bi-person-plus me-1"></i> Registrasi Pemilih
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Live election metadata pill -->
                <?php if ($activeElection): ?>
                <div class="row g-2 g-sm-3 mt-4 pt-3 border-top border-secondary border-opacity-25 small text-light">
                    <div class="col-4">
                        <div class="text-white-50 text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Mulai</div>
                        <div class="fw-semibold text-white text-truncate" style="font-size: 0.82rem;">
                            <i class="bi bi-calendar3 text-warning me-1"></i>
                            <span class="d-none d-sm-inline"><?= date('d M Y, H:i', strtotime($activeElection['start_at'])) ?> WIB</span>
                            <span class="d-inline d-sm-none"><?= date('d M Y', strtotime($activeElection['start_at'])) ?></span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-white-50 text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Selesai</div>
                        <div class="fw-semibold text-white text-truncate" style="font-size: 0.82rem;">
                            <i class="bi bi-calendar-check text-warning me-1"></i>
                            <span class="d-none d-sm-inline"><?= date('d M Y, H:i', strtotime($activeElection['end_at'])) ?> WIB</span>
                            <span class="d-inline d-sm-none"><?= date('d M Y', strtotime($activeElection['end_at'])) ?></span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-white-50 text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Paslon</div>
                        <div class="fw-semibold text-white text-truncate" style="font-size: 0.82rem;">
                            <i class="bi bi-people text-warning me-1"></i>
                            <?= $candidateCount ?> Paslon
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- COUNTDOWN / STATUS BOX (HIGH TECH GLASS) -->
            <div class="col-lg-5">
                <div class="countdown-box" id="election-countdown"
                     data-start="<?= $activeElection['start_at'] ?? '' ?>"
                     data-end="<?= $activeElection['end_at'] ?? '' ?>"
                     data-server-now="<?= time() * 1000 ?>">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-dark border border-secondary border-opacity-50 text-light py-2 px-3" id="countdown-status-badge">
                            <i class="bi bi-stopwatch text-warning me-1"></i> <?= htmlspecialchars(SettingHelper::get('hero_countdown_title', 'Hitung Mundur Pemilihan'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <small class="text-white-50"><i class="bi bi-clock me-1 text-warning"></i> WIB (UTC+7)</small>
                    </div>

                    <div class="text-white-50 small mb-2" id="countdown-main-label">Menuju Pelaksanaan:</div>

                    <div class="row g-1 g-sm-2 text-center" id="countdown-digits-wrapper">
                        <div class="col-3">
                            <div class="p-3 rounded-3 bg-dark bg-opacity-75 border border-secondary border-opacity-25 shadow-sm">
                                <div class="countdown-digits" id="cd-days">00</div>
                                <div class="countdown-label">Hari</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-3 rounded-3 bg-dark bg-opacity-75 border border-secondary border-opacity-25 shadow-sm">
                                <div class="countdown-digits" id="cd-hours">00</div>
                                <div class="countdown-label">Jam</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-3 rounded-3 bg-dark bg-opacity-75 border border-secondary border-opacity-25 shadow-sm">
                                <div class="countdown-digits" id="cd-minutes">00</div>
                                <div class="countdown-label">Menit</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-3 rounded-3 bg-dark bg-opacity-75 border border-secondary border-opacity-25 shadow-sm">
                                <div class="countdown-digits" id="cd-seconds">00</div>
                                <div class="countdown-label">Detik</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-center">
                        <small class="text-white-50">
                            <i class="bi bi-info-circle text-warning me-1"></i>
                            <?= htmlspecialchars(SettingHelper::get('hero_countdown_desc', 'Pastikan akun Anda telah diverifikasi oleh Panitia Pemilihan sebelum waktu pemilihan berakhir.'), ENT_QUOTES, 'UTF-8') ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. LUXURY 4-CARD VALUE & STATISTICS BAR -->
<section class="py-3 py-md-4 position-relative" style="margin-top: -30px; z-index: 10;">
    <div class="container">
        <div class="row g-2 g-md-3 justify-content-center">
            <!-- Card 1: Total DPT -->
            <div class="col-6 col-lg-3">
                <div class="stats-luxury-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1 mb-2">Daftar Pemilih</span>
                            <h3 class="fw-bold text-dark mb-0"><?= number_format($dptCount, 0, ',', '.') ?></h3>
                            <div class="text-muted small">Total Hak Suara Terverifikasi</div>
                        </div>
                        <div class="stats-icon-wrap bg-primary-subtle text-primary">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Paslon Resmi -->
            <div class="col-6 col-lg-3">
                <div class="stats-luxury-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-warning-subtle text-warning fw-bold px-2 py-1 mb-2">Pasangan Calon</span>
                            <h3 class="fw-bold text-dark mb-0"><?= $candidateCount ?> Paslon</h3>
                            <div class="text-muted small">Siap Berkompetisi Resmi</div>
                        </div>
                        <div class="stats-icon-wrap bg-warning-subtle text-warning">
                            <i class="bi bi-award-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Asas Pemilu -->
            <div class="col-6 col-lg-3">
                <div class="stats-luxury-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-success-subtle text-success fw-bold px-2 py-1 mb-2">Prinsip Pemilu</span>
                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars(SettingHelper::get('stat_card_3_title', 'Asas LUBER JURDIL'), ENT_QUOTES, 'UTF-8') ?></h5>
                            <div class="text-muted small"><?= htmlspecialchars(SettingHelper::get('stat_card_3_desc', 'Langsung, Umum, Bebas, Rahasia, Jujur & Adil'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="stats-icon-wrap bg-success-subtle text-success">
                            <i class="bi bi-shield-shaded"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Keamanan Sistem -->
            <div class="col-6 col-lg-3">
                <div class="stats-luxury-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-info-subtle text-info fw-bold px-2 py-1 mb-2">Keamanan Sistem</span>
                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars(SettingHelper::get('stat_card_4_title', 'Kerahasiaan Terjamin'), ENT_QUOTES, 'UTF-8') ?></h5>
                            <div class="text-muted small"><?= htmlspecialchars(SettingHelper::get('stat_card_4_desc', 'Secret Ballot 100% Enkripsi Suara Terlindungi'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="stats-icon-wrap bg-info-subtle text-info">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. SECTION PASANGAN CALON (KANDIDAT) -->
<section class="py-5" id="kandidat">
    <div class="container py-4">
        <div class="text-center max-w-3xl mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill text-uppercase mb-2">
                <i class="bi bi-stars me-1 text-warning"></i> <?= htmlspecialchars(SettingHelper::get('kandidat_section_badge', 'Kandidat Pemimpin'), ENT_QUOTES, 'UTF-8') ?>
            </span>
            <h2 class="display-6 fw-bold"><?= htmlspecialchars(SettingHelper::get('kandidat_section_title', 'Daftar Pasangan Calon Ketua & Wakil Ketua OSIS'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-muted"><?= htmlspecialchars(SettingHelper::get('kandidat_section_subtitle', 'Pelajari profil, rekam jejak, visi, misi, serta program kerja unggulan sebelum menentukan pilihan terbaik Anda.'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <?php if (empty($candidates)): ?>
            <div class="text-center py-5 bg-white rounded-4 shadow-sm border border-dashed border-2">
                <i class="bi bi-people display-4 text-muted mb-3 d-block"></i>
                <h5 class="fw-bold text-dark"><?= htmlspecialchars(SettingHelper::get('kandidat_empty_msg', 'Belum ada pasangan calon yang dipublikasikan oleh panitia pemilihan untuk periode ini.'), ENT_QUOTES, 'UTF-8') ?></h5>
                <p class="text-muted small">Panitia pemilihan belum mengumumkan daftar pasangan calon resmi untuk periode ini.</p>
            </div>
        <?php else: ?>
            <?php 
            $candColClass = count($candidates) === 1 
                ? 'col-12 col-md-10 col-lg-8' 
                : (count($candidates) === 2 
                    ? 'col-12 col-md-6 col-lg-6' 
                    : (count($candidates) === 3 
                        ? 'col-12 col-md-6 col-lg-4' 
                        : 'col-12 col-md-6 col-lg-6 col-xl-3'));
            ?>
            <div class="row g-4 justify-content-center">
                <?php foreach ($candidates as $cand): 
                    $photoPasangan = !empty($cand['foto_pasangan']) ? asset('uploads/candidates/' . $cand['foto_pasangan']) : null;
                    $photoKetua = !empty($cand['foto_ketua']) ? asset('uploads/candidates/' . $cand['foto_ketua']) : asset('assets/images/default-avatar.svg');
                    $photoWakil = !empty($cand['foto_wakil']) ? asset('uploads/candidates/' . $cand['foto_wakil']) : asset('assets/images/default-avatar.svg');
                ?>
                <div class="<?= $candColClass ?>">
                    <div class="candidate-card h-100" id="candidate-card-<?= $cand['id'] ?>">
                        <div class="candidate-card-header d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-warning text-dark text-uppercase fw-bold px-2 py-1 mb-1">Paslon Resmi</span>
                                <h4 class="text-white mb-0">Pasangan Calon No. <?= sprintf('%02d', $cand['nomor_urut']) ?></h4>
                            </div>
                            <div class="nomor-urut-badge shadow">
                                <?= sprintf('%02d', $cand['nomor_urut']) ?>
                            </div>
                        </div>

                        <!-- Candidate photo area (Harmonized, Top-Aligned & Responsive) -->
                        <div class="candidate-photo-wrapper">
                            <?php if ($photoPasangan): ?>
                                <img src="<?= $photoPasangan ?>" alt="Pasangan No. <?= $cand['nomor_urut'] ?>" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                <span class="candidate-avatar-tag"><i class="bi bi-people-fill me-1 text-warning"></i>Paslon No. <?= sprintf('%02d', $cand['nomor_urut']) ?></span>
                            <?php else: ?>
                                <div class="dual-avatar-box">
                                    <div class="avatar-half position-relative">
                                        <img src="<?= $photoKetua ?>" alt="<?= htmlspecialchars($cand['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                        <span class="candidate-avatar-tag"><i class="bi bi-person-fill text-warning me-1"></i>Calon Ketua</span>
                                    </div>
                                    <div class="avatar-half position-relative">
                                        <img src="<?= $photoWakil ?>" alt="<?= htmlspecialchars($cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                        <span class="candidate-avatar-tag"><i class="bi bi-person text-warning me-1"></i>Calon Wakil</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="candidate-body d-flex flex-column flex-grow-1">
                            <!-- Names & Classes -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="p-2 rounded bg-light border h-100">
                                        <small class="text-muted d-block" style="font-size: 0.72rem;"><i class="bi bi-person me-1 text-primary"></i>Calon Ketua OSIS</small>
                                        <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($cand['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($cand['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($cand['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded bg-light border h-100">
                                        <small class="text-muted d-block" style="font-size: 0.72rem;"><i class="bi bi-person me-1 text-warning"></i>Calon Wakil Ketua OSIS</small>
                                        <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($cand['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Visi Snippet -->
                            <div class="vision-quote flex-grow-1 mb-3">
                                <i class="bi bi-quote fs-5 text-primary me-1"></i>
                                "<?= htmlspecialchars(mb_strimwidth($cand['visi'], 0, 160, '...'), ENT_QUOTES, 'UTF-8') ?>"
                            </div>

                            <div class="d-flex gap-2 mt-auto pt-3 border-top">
                                <button type="button" class="btn btn-outline-primary w-100 btn-detail-trigger shadow-sm"
                                        data-id="<?= $cand['id'] ?>"
                                        data-nomor="<?= sprintf('%02d', $cand['nomor_urut']) ?>"
                                        data-names="<?= htmlspecialchars($cand['nama_ketua'] . ' & ' . $cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-visi="<?= htmlspecialchars($cand['visi'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-misi="<?= htmlspecialchars($cand['misi'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-proker="<?= htmlspecialchars($cand['program_kerja'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-eye me-1"></i> Visi, Misi &amp; Proker
                                </button>
                                <?php if (AuthHelper::check()): ?>
                                    <a href="<?= base_url('voting.php') ?>" class="btn btn-primary-custom text-nowrap px-4 shadow-sm">
                                        <i class="bi bi-check2-circle me-1"></i> Pilih
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 4. SECTION JADWAL & AGENDA PEMILIHAN -->
<section class="py-5 bg-white border-top border-bottom" id="jadwal">
    <div class="container py-4">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="badge bg-secondary-subtle text-secondary fw-bold px-3 py-2 rounded-pill text-uppercase mb-2">
                <i class="bi bi-calendar-check me-1 text-primary"></i> <?= htmlspecialchars(SettingHelper::get('agenda_section_badge', 'Agenda Resmi'), ENT_QUOTES, 'UTF-8') ?>
            </span>
            <h2 class="display-6 fw-bold"><?= htmlspecialchars(SettingHelper::get('agenda_section_title', 'Jadwal & Tahapan Pemilihan OSIS'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-muted"><?= htmlspecialchars(SettingHelper::get('agenda_section_subtitle', 'Ikuti seluruh tahapan pesta demokrasi sekolah sesuai dengan jadwal yang telah ditetapkan panitia.'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Tahap 1 -->
            <div class="col-md-4">
                <div class="agenda-luxury-card text-center">
                    <div class="p-3 rounded-circle bg-primary text-white d-inline-flex mb-3 shadow-sm">
                        <i class="bi bi-person-check fs-3"></i>
                    </div>
                    <div class="mb-2">
                        <span class="agenda-step-badge bg-primary-subtle text-primary"><?= htmlspecialchars(SettingHelper::get('agenda_step1_badge', 'Tahap 1'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <h5 class="fw-bold text-dark"><?= htmlspecialchars(SettingHelper::get('agenda_step1_title', 'Registrasi & Verifikasi Pemilih'), ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small"><?= htmlspecialchars(SettingHelper::get('agenda_step1_desc', 'Siswa dan Dewan Guru mendaftarkan akun di portal E-Voting dan diverifikasi langsung oleh admin panitia pemilihan sekolah.'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <!-- Tahap 2 -->
            <div class="col-md-4">
                <div class="agenda-luxury-card text-center border-warning">
                    <div class="p-3 rounded-circle bg-warning text-dark d-inline-flex mb-3 shadow-sm">
                        <i class="bi bi-check2-square fs-3"></i>
                    </div>
                    <div class="mb-2">
                        <span class="agenda-step-badge bg-warning-subtle text-dark"><?= htmlspecialchars(SettingHelper::get('agenda_step2_badge', 'Tahap 2'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <h5 class="fw-bold text-dark"><?= htmlspecialchars(SettingHelper::get('agenda_step2_title', 'Pemungutan Suara (E-Voting)'), ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small"><?= htmlspecialchars(SettingHelper::get('agenda_step2_desc', 'Pemilih yang terdaftar dalam DPT menggunakan hak pilihnya di bilik suara elektronik secara rahasia, aman, dan tanpa paksaan.'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($activeElection): ?>
                        <div class="small fw-semibold text-primary pt-2 border-top">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= date('d M Y', strtotime($activeElection['start_at'])) ?> &ndash; <?= date('d M Y', strtotime($activeElection['end_at'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tahap 3 -->
            <div class="col-md-4">
                <div class="agenda-luxury-card text-center">
                    <div class="p-3 rounded-circle bg-dark text-white d-inline-flex mb-3 shadow-sm">
                        <i class="bi bi-trophy fs-3"></i>
                    </div>
                    <div class="mb-2">
                        <span class="agenda-step-badge bg-secondary-subtle text-secondary"><?= htmlspecialchars(SettingHelper::get('agenda_step3_badge', 'Tahap 3'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <h5 class="fw-bold text-dark"><?= htmlspecialchars(SettingHelper::get('agenda_step3_title', 'Rekapitulasi & Penetapan Paslon'), ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small"><?= htmlspecialchars(SettingHelper::get('agenda_step3_desc', 'Penetapan dan pengumuman resmi pasangan calon terpilih Ketua dan Wakil Ketua OSIS berdasarkan perolehan suara terbanyak yang sah.'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. SECTION TATA CARA MEMILIH (4 LANGKAH VISUAL GUIDE) -->
<section class="py-5 bg-light" id="panduan">
    <div class="container py-4">
        <div class="text-center max-w-3xl mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill text-uppercase mb-2">
                <i class="bi bi-compass me-1 text-warning"></i> <?= htmlspecialchars(SettingHelper::get('guide_section_badge', 'Panduan Pemilih'), ENT_QUOTES, 'UTF-8') ?>
            </span>
            <h2 class="display-6 fw-bold"><?= htmlspecialchars(SettingHelper::get('guide_section_title', 'Tata Cara Memberikan Hak Suara'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-muted"><?= htmlspecialchars(SettingHelper::get('guide_section_subtitle', '4 langkah praktis menyalurkan hak suara Anda secara cepat, mudah, dan terjamin kerahasiaannya.'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="row g-2 g-md-4">
            <!-- Step 1 -->
            <div class="col-6 col-lg-3">
                <div class="guide-step-card">
                    <span class="guide-step-number">01</span>
                    <div class="guide-icon-pill">
                        <i class="bi bi-box-arrow-in-right"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars(SettingHelper::get('guide_step1_title', '1. Masuk & Verifikasi Akun'), ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small mb-0"><?= htmlspecialchars(SettingHelper::get('guide_step1_desc', 'Login menggunakan NISN/NIP dan kata sandi yang telah diverifikasi dan disetujui panitia pemilihan.'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="col-6 col-lg-3">
                <div class="guide-step-card">
                    <span class="guide-step-number">02</span>
                    <div class="guide-icon-pill">
                        <i class="bi bi-card-text"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars(SettingHelper::get('guide_step2_title', '2. Pelajari Visi & Misi'), ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small mb-0"><?= htmlspecialchars(SettingHelper::get('guide_step2_desc', 'Cermati program kerja unggulan, gagasan, dan integritas masing-masing pasangan calon.'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="col-6 col-lg-3">
                <div class="guide-step-card">
                    <span class="guide-step-number">03</span>
                    <div class="guide-icon-pill">
                        <i class="bi bi-check2-square"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars(SettingHelper::get('guide_step3_title', '3. Coblos di Bilik Suara'), ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small mb-0"><?= htmlspecialchars(SettingHelper::get('guide_step3_desc', 'Masuk bilik suara digital dan tekan tombol coblos pada nomor urut paslon yang menjadi pilihan hati nurani Anda.'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="col-6 col-lg-3">
                <div class="guide-step-card">
                    <span class="guide-step-number">04</span>
                    <div class="guide-icon-pill">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars(SettingHelper::get('guide_step4_title', '4. Konfirmasi & Selesai'), ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small mb-0"><?= htmlspecialchars(SettingHelper::get('guide_step4_desc', 'Konfirmasi suara sah Anda. Sistem mencatat suara secara terenkripsi anonim (Secret Ballot).'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 6. SECTION HASIL VOTING (REALTIME / SEALED BILIK SUARA) -->
<section class="py-5 bg-white border-top" id="hasil">
    <div class="container py-4">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill text-uppercase mb-2">
                <i class="bi bi-graph-up-arrow me-1"></i> <?= htmlspecialchars(SettingHelper::get('hasil_section_badge', 'Transparansi Pemilu'), ENT_QUOTES, 'UTF-8') ?>
            </span>
            <h2 class="display-6 fw-bold"><?= htmlspecialchars(SettingHelper::get('hasil_section_title', 'Perolehan Suara & Grafik Pemilihan'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-muted"><?= htmlspecialchars(SettingHelper::get('hasil_section_subtitle', 'Pantau perkembangan perolehan suara sah secara transparan dan akurat langsung dari sistem e-voting.'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <?php if (!$activeElection || $activeElection['results_visibility'] === 'hidden'): ?>
            <!-- Mode 1: Hasil Disembunyikan (Sealed Ballot Luxury Box) -->
            <div class="sealed-ballot-card text-center" id="results-hidden-message">
                <div class="sealed-lock-icon">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <h3 class="fw-bold text-white mb-2"><?= htmlspecialchars(SettingHelper::get('hasil_hidden_title', 'Hasil Pemilihan Sementara Masih Dirahasiakan'), ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-light text-opacity-75 max-w-2xl mx-auto mb-4 lead fs-6">
                    <?= htmlspecialchars(SettingHelper::get('hasil_hidden_desc', 'Sesuai dengan ketetapan Panitia Pemilihan OSIS SMK Muthia Harapan Cicalengka, perolehan suara sementara dirahasiakan selama proses pemungutan suara berlangsung untuk menjaga netralitas dan kerahasiaan pemilihan.'), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div>
                    <span class="badge bg-warning text-dark py-2 px-3 fw-bold shadow-sm">
                        <i class="bi bi-shield-lock me-1"></i> <?= htmlspecialchars(SettingHelper::get('hasil_hidden_note', 'Pengumuman resmi akan dipublikasikan setelah seluruh proses pemungutan suara ditutup.'), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>
        <?php else: ?>
            <!-- Mode 2 & 3: Realtime atau Final Chart & Photo Leaderboard Cards -->
            <div class="results-card p-4 p-lg-5" id="results-live-container">
                <!-- Header Rekapitulasi Realtime -->
                <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-4 border-bottom gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="live-pulse"></span>
                            <h3 class="mb-0 fw-bold text-dark">Rekapitulasi Suara Pemilihan Realtime</h3>
                            <span class="badge bg-primary text-uppercase ms-1 px-3 py-1"><?= $activeElection['results_visibility'] === 'final' ? 'Hasil Final Resmi' : 'Quick Count Live' ?></span>
                        </div>
                        <p class="text-muted small mb-0">Data perolehan suara terhubung langsung secara terenkripsi dengan bilik suara elektronik.</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-md-end">
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Status Pembaruan:</small>
                            <span class="fw-semibold text-dark small" id="results-last-updated">Memuat data...</span>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm" id="btn-manual-refresh" onclick="if(typeof triggerManualRefresh === 'function') triggerManualRefresh();" title="Segarkan Data Realtime">
                            <i class="bi bi-arrow-clockwise me-1" id="refresh-icon"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- 1. LIVE CANDIDATE PHOTO CARDS GRID (FOTO PASLON RESMI & PEROLEHAN SUARA) -->
                <div class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-people-fill text-primary me-2"></i>Perolehan Suara Pasangan Calon
                        </h5>
                        <span class="text-muted small">Pembaruan otomatis tanpa memuat ulang halaman</span>
                    </div>

                    <?php 
                    $liveColClass = count($candidates) <= 2 
                        ? 'col-12 col-md-6 col-lg-6' 
                        : (count($candidates) === 3 
                            ? 'col-12 col-md-6 col-lg-4' 
                            : 'col-12 col-md-6 col-lg-6 col-xl-3');
                    ?>
                    <div class="row g-4 justify-content-center" id="results-candidates-grid">
                        <?php foreach ($candidates as $c): 
                            $photoPasangan = !empty($c['foto_pasangan']) ? asset('uploads/candidates/' . $c['foto_pasangan']) : null;
                            $photoKetua = !empty($c['foto_ketua']) ? asset('uploads/candidates/' . $c['foto_ketua']) : asset('assets/images/default-avatar.svg');
                            $photoWakil = !empty($c['foto_wakil']) ? asset('uploads/candidates/' . $c['foto_wakil']) : asset('assets/images/default-avatar.svg');
                            $candVotes = $c['total_suara'] ?? 0;
                            $candPct = $c['persentase'] ?? 0;
                        ?>
                        <div class="<?= $liveColClass ?>">
                            <div class="live-result-candidate-card h-100" id="live-cand-card-<?= $c['id'] ?>">
                                
                                <!-- Card Top Header -->
                                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="nomor-urut-badge shadow-sm" style="width: 38px; height: 38px; font-size: 1.15rem;">
                                            <?= sprintf('%02d', $c['nomor_urut']) ?>
                                        </div>
                                        <div>
                                            <span class="badge bg-dark text-white fw-bold">Paslon No. <?= sprintf('%02d', $c['nomor_urut']) ?></span>
                                        </div>
                                    </div>
                                    <div>
                                        <!-- Dynamic leader badge -->
                                        <span class="badge bg-warning text-dark fw-bold px-2 py-1 shadow-sm d-none" id="cand-lead-badge-<?= $c['id'] ?>">
                                            <i class="bi bi-trophy-fill me-1"></i> Unggul
                                        </span>
                                    </div>
                                </div>

                                <!-- Candidate Photo Box (Harmonized, Top-Aligned & Responsive) -->
                                <div class="live-result-photo-box">
                                    <?php if ($photoPasangan): ?>
                                        <img src="<?= $photoPasangan ?>" alt="Paslon No. <?= $c['nomor_urut'] ?>" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                        <span class="avatar-side-tag"><i class="bi bi-people-fill me-1 text-warning"></i>Paslon No. <?= sprintf('%02d', $c['nomor_urut']) ?></span>
                                    <?php else: ?>
                                        <div class="live-result-dual-avatar">
                                            <div class="avatar-side">
                                                <img src="<?= $photoKetua ?>" alt="<?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                                <span class="avatar-side-tag"><i class="bi bi-person-fill text-warning me-1"></i>Ketua</span>
                                            </div>
                                            <div class="avatar-side">
                                                <img src="<?= $photoWakil ?>" alt="<?= htmlspecialchars($c['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                                <span class="avatar-side-tag"><i class="bi bi-person text-warning me-1"></i>Wakil</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Candidate Identity & Names -->
                                <div class="p-3 border-bottom bg-white" style="min-height: 94px;">
                                    <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-person-fill text-primary me-1"></i> <?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="small text-muted text-truncate mb-1" title="<?= htmlspecialchars($c['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-person text-secondary me-1"></i> <?= htmlspecialchars($c['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;"><?= htmlspecialchars($c['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;"><?= htmlspecialchars($c['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>

                                <!-- Vote Count & Percentage Body -->
                                <div class="p-3 bg-light bg-opacity-50 mt-auto">
                                    <div class="d-flex justify-content-between align-items-end mb-2">
                                        <div>
                                            <span class="text-muted d-block" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Perolehan Suara:</span>
                                            <div class="live-tally-number text-primary" id="cand-votes-num-<?= $c['id'] ?>"><?= number_format($candVotes, 0, ',', '.') ?></div>
                                            <span class="text-muted small" id="cand-votes-<?= $c['id'] ?>"><?= number_format($candVotes, 0, ',', '.') ?> Suara</span>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary text-white fs-6 fw-bold px-3 py-2 shadow-sm rounded-pill" id="cand-pct-badge-<?= $c['id'] ?>">
                                                <span id="cand-pct-<?= $c['id'] ?>"><?= $candPct ?>%</span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Animated Progress Bar -->
                                    <div class="progress shadow-sm" style="height: 12px; border-radius: 8px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                             role="progressbar"
                                             id="cand-bar-<?= $c['id'] ?>"
                                             style="width: <?= $candPct ?>%;"
                                             aria-valuenow="<?= $candPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. CHART VISUALIZER & SUMMARY METRICS -->
                <div class="row g-4 align-items-center pt-3 border-top">
                    <div class="col-lg-7">
                        <div class="p-3 bg-light rounded-4 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark small"><i class="bi bi-bar-chart-line text-primary me-1"></i> Grafik Komparasi Suara</span>
                                <span class="badge bg-white border text-secondary small">Chart.js Realtime</span>
                            </div>
                            <div style="height: 290px; position: relative;">
                                <canvas id="liveResultsChart"
                                        data-election-id="<?= $activeElection['id'] ?>"
                                        data-api-url="<?= base_url('api/results.php?election_id=' . $activeElection['id']) ?>"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <!-- Summary KPI Box -->
                        <div class="p-4 rounded-4 bg-light border shadow-sm mb-3">
                            <div class="row g-3 text-center">
                                <div class="col-6">
                                    <div class="p-2 rounded bg-white border">
                                        <span class="text-muted text-xs d-block text-uppercase fw-semibold">Suara Sah Masuk</span>
                                        <h3 class="fw-bold text-primary mb-0" id="total-votes-count"><?= number_format($totalVotes, 0, ',', '.') ?></h3>
                                        <span class="text-muted" style="font-size: 0.7rem;">Surat Suara Digital</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded bg-white border">
                                        <span class="text-muted text-xs d-block text-uppercase fw-semibold">Partisipasi DPT</span>
                                        <h3 class="fw-bold text-success mb-0" id="turnout-pct-badge"><?= $turnoutPct ?>%</h3>
                                        <span class="text-muted" style="font-size: 0.7rem;">Dari <?= number_format($dptCount, 0, ',', '.') ?> DPT</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Integrity & Secret Ballot Guarantee Info -->
                        <div class="p-3 rounded-3 bg-white border shadow-sm">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-shield-fill-check fs-4 text-success mt-1"></i>
                                <div>
                                    <div class="fw-bold text-dark small">Integritas Hasil Pemilihan Terjamin</div>
                                    <p class="text-muted small mb-0" style="font-size: 0.78rem;">
                                        Setiap suara yang masuk langsung diverifikasi secara kriptografis melalui sistem Secret Ballot tanpa menyimpan identitas akun pemilih.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 7. SECTION FAQ & HELPDESK PUSAT BANTUAN -->
<section class="py-5 bg-light border-top" id="faq">
    <div class="container py-4">
        <div class="text-center max-w-3xl mx-auto mb-5">
            <span class="badge bg-warning-subtle text-warning fw-bold px-3 py-2 rounded-pill text-uppercase mb-2">
                <i class="bi bi-question-circle me-1"></i> <?= htmlspecialchars(SettingHelper::get('faq_section_badge', 'Pusat Informasi & Bantuan'), ENT_QUOTES, 'UTF-8') ?>
            </span>
            <h2 class="display-6 fw-bold"><?= htmlspecialchars(SettingHelper::get('faq_section_title', 'Pertanyaan yang Sering Diajukan (FAQ)'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-muted"><?= htmlspecialchars(SettingHelper::get('faq_section_subtitle', 'Temukan jawaban atas pertanyaan seputar akun, tata tertib, dan teknis pemilihan ketua OSIS.'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="row g-4 align-items-start">
            <!-- Accordion FAQ -->
            <div class="col-lg-7">
                <div class="accordion faq-accordion" id="faqAccordion">
                    <!-- FAQ Item 1 -->
                    <div class="accordion-item shadow-sm">
                        <h2 class="accordion-header" id="faqHeading1">
                            <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                                <i class="bi bi-patch-question text-primary me-2"></i>
                                <?= htmlspecialchars(SettingHelper::get('faq1_q', 'Siapa saja yang berhak memberikan suara dalam pemilihan ini?'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </h2>
                        <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?= htmlspecialchars(SettingHelper::get('faq1_a', 'Seluruh siswa aktif dan dewan guru SMK Muthia Harapan Cicalengka yang telah terdaftar dalam Daftar Pemilih Tetap (DPT) dan akunnya telah diverifikasi oleh panitia pemilihan.'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 2 -->
                    <div class="accordion-item shadow-sm">
                        <h2 class="accordion-header" id="faqHeading2">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                                <i class="bi bi-shield-lock text-success me-2"></i>
                                <?= htmlspecialchars(SettingHelper::get('faq2_q', 'Apakah kerahasiaan pilihan saya benar-benar terjamin?'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </h2>
                        <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?= htmlspecialchars(SettingHelper::get('faq2_a', 'Ya, 100% terjamin. Sistem E-Voting menerapkan prinsip Secret Ballot kriptografis di mana catatan siapa yang Anda pilih diputus total dari identitas akun Anda.'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 3 -->
                    <div class="accordion-item shadow-sm">
                        <h2 class="accordion-header" id="faqHeading3">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                                <i class="bi bi-key text-warning me-2"></i>
                                <?= htmlspecialchars(SettingHelper::get('faq3_q', 'Bagaimana jika saya lupa kata sandi atau akun belum diverifikasi?'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </h2>
                        <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?= htmlspecialchars(SettingHelper::get('faq3_a', 'Segera hubungi tim helpdesk panitia pemilihan melalui WhatsApp atau kunjungi posko panitia di sekolah sebelum batas waktu pemilihan berakhir.'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Helpdesk Contact Card -->
            <div class="col-lg-5">
                <div class="helpdesk-luxury-card">
                    <span class="badge bg-warning text-dark text-uppercase fw-bold px-3 py-1 rounded-pill mb-3">
                        <i class="bi bi-headset me-1"></i> Bantuan Langsung
                    </span>
                    <h4 class="fw-bold mb-2"><?= htmlspecialchars(SettingHelper::get('helpdesk_title', 'Pusat Bantuan Panitia Pemilihan'), ENT_QUOTES, 'UTF-8') ?></h4>
                    <p class="text-white-50 small mb-4">
                        <?= htmlspecialchars(SettingHelper::get('helpdesk_desc', 'Mengalami kendala saat login atau membutuhkan panduan lebih lanjut? Tim panitia siap membantu Anda.'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 rounded-3 bg-white bg-opacity-10 text-warning fs-5">
                                <i class="bi bi-whatsapp"></i>
                            </div>
                            <div>
                                <small class="text-white-50 d-block">WhatsApp Panitia</small>
                                <span class="fw-bold fs-6"><?= htmlspecialchars(SettingHelper::get('helpdesk_phone', '0812-3456-7890'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 rounded-3 bg-white bg-opacity-10 text-primary fs-5">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div>
                                <small class="text-white-50 d-block">Email Resmi Sekolah</small>
                                <span class="fw-bold fs-6"><?= htmlspecialchars(SettingHelper::get('school_email', 'info@smkmuthiaharapan.sch.id'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 rounded-3 bg-white bg-opacity-10 text-success fs-5">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div>
                                <small class="text-white-50 d-block">Jam Layanan</small>
                                <span class="fw-bold fs-6"><?= htmlspecialchars(SettingHelper::get('helpdesk_hours', 'Senin - Jumat: 07.30 - 15.30 WIB'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', SettingHelper::get('helpdesk_phone', '081234567890')) ?>" target="_blank" class="btn btn-gold-custom w-100 fw-bold shadow">
                        <i class="bi bi-whatsapp me-2"></i> Hubungi Helpdesk via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 8. MODAL DETAIL VISI MISI KANDIDAT DENGAN SMART POINT FORMATTING -->
<div class="modal fade" id="candidateDetailModal" tabindex="-1" aria-labelledby="modal-cand-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-xl rounded-4 overflow-hidden">
            <div class="modal-header modal-header-premium p-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-warning text-dark rounded-circle"><i class="bi bi-award-fill"></i></div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="modal-cand-title">Detail Pasangan Calon</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-scrollable-body p-4 bg-light">
                <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
                    <h6 class="fw-bold text-primary text-uppercase tracking-wide mb-2"><i class="bi bi-eye-fill me-2"></i>Visi Pasangan Calon</h6>
                    <div class="p-3 rounded-3 bg-light border text-dark" id="modal-cand-visi"></div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
                    <h6 class="fw-bold text-primary text-uppercase tracking-wide mb-2"><i class="bi bi-list-check me-2"></i>Misi Pasangan Calon</h6>
                    <div class="p-3 rounded-3 bg-light border text-dark" id="modal-cand-misi"></div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <h6 class="fw-bold text-primary text-uppercase tracking-wide mb-2"><i class="bi bi-briefcase-fill me-2"></i>Program Kerja Unggulan</h6>
                    <div class="p-3 rounded-3 bg-light border text-dark" id="modal-cand-proker"></div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Tutup</button>
                <?php if (AuthHelper::check()): ?>
                    <a href="<?= base_url('voting.php') ?>" class="btn btn-primary-custom px-4 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Masuk Bilik Suara
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/app/Views/layouts/footer.php'; ?>
