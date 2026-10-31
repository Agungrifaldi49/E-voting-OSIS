<?php
// guru/dashboard.php
// Dashboard Utama Dewan Guru - E-Voting OSIS SMK Muthia Harapan Cicalengka
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Candidate.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';
require_once dirname(__DIR__) . '/app/Models/User.php';

AuthHelper::requireRole('guru');
$user = AuthHelper::user();
$userId = (int)$user['id'];
$fullTeacher = User::findById($userId);

$activeElection = Election::getActive();
$hasVoted = false;
$isEligible = false;
$teacherCanVote = false;
$candidates = [];

if ($activeElection) {
    $teacherCanVote = !empty($activeElection['teacher_can_vote']);
    $hasVoted = Vote::hasUserVoted($activeElection['id'], $userId);
    $isEligible = Vote::isUserEligible($activeElection['id'], $userId);
    $candidates = Candidate::getByElectionId($activeElection['id'], true);
}

// Announcements
$pdo = Database::getConnection();
$announcements = $pdo->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY id DESC LIMIT 3")->fetchAll();

$pageTitle = "Dashboard Dewan Guru - E-Voting OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/guru_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <!-- Digital Teacher Voter Card (Kartu Pemilih Dewan Guru Digital) -->
        <div class="teacher-card-luxury mb-4">
            <div class="card-watermark"><i class="bi bi-mortarboard-fill"></i></div>
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 gap-sm-3 position-relative" style="z-index: 2;">
                <div class="d-flex align-items-center gap-2 gap-sm-3 min-w-0" style="min-width: 0;">
                    <div class="card-chip flex-shrink-0">
                        <i class="bi bi-sim"></i>
                    </div>
                    <div class="min-w-0" style="min-width: 0;">
                        <div class="text-uppercase text-warning small fw-bold" style="font-size: 0.7rem; letter-spacing: 0.6px;">
                            <i class="bi bi-shield-check me-1"></i> E-KARTU DEWAN GURU RESMI &bull; PEMILIHAN OSIS
                        </div>
                        <h4 class="fw-bold mb-0 text-white mt-1 text-break" style="font-size: clamp(1.1rem, 3.5vw, 1.4rem);"><?= htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></h4>
                    </div>
                </div>
                <div class="w-100 w-sm-auto">
                    <?php if ($teacherCanVote): ?>
                        <?php if ($hasVoted): ?>
                            <a href="<?= base_url('guru/status.php') ?>" class="badge voter-status-pill bg-success bg-opacity-90 text-white border border-white border-opacity-25 px-3 py-2 rounded-pill fw-bold text-decoration-none shadow-sm d-inline-flex align-items-center justify-content-center w-100 w-sm-auto" title="Klik untuk melihat bukti partisipasi &amp; sertifikat dewan guru">
                                <i class="bi bi-patch-check-fill me-1 text-warning flex-shrink-0"></i>
                                <span>SUARA TELAH DIGUNAKAN</span>
                                <i class="bi bi-arrow-right-short ms-1 flex-shrink-0"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?= base_url('voting.php') ?>" class="badge voter-status-pill bg-warning text-dark border border-white border-opacity-50 px-3 py-2 rounded-pill fw-bold text-decoration-none shadow-sm d-inline-flex align-items-center justify-content-center w-100 w-sm-auto" title="Klik untuk masuk ke bilik suara">
                                <i class="bi bi-check2-square me-1 flex-shrink-0"></i>
                                <span>HAK SUARA TERSEDIA &bull; PILIH SEKARANG</span>
                                <i class="bi bi-arrow-right-short ms-1 flex-shrink-0"></i>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge voter-status-pill bg-light bg-opacity-25 text-white border border-white border-opacity-25 px-3 py-2 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center justify-content-center w-100 w-sm-auto">
                            <i class="bi bi-eye-fill me-1 text-info flex-shrink-0"></i>
                            <span>PENGAMAT RESMI &bull; KHUSUS SISWA</span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-3 mt-sm-4 pt-2 position-relative" style="z-index: 2;">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-8">
                        <div class="d-flex flex-wrap gap-x-2 gap-1 text-white-50 small mb-1">
                            <span>NIP / ID: <strong class="text-white"><?= htmlspecialchars($fullTeacher['employee_identifier'] ?? $user['username'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                            <span class="d-none d-sm-inline">&bull;</span>
                            <span>Mapel: <strong class="text-white"><?= htmlspecialchars($fullTeacher['mata_pelajaran'] ?? 'Dewan Guru', ENT_QUOTES, 'UTF-8') ?></strong></span>
                        </div>
                        <div class="small text-white-50">
                            <?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4 text-sm-end mt-2 mt-sm-0">
                        <?php if ($teacherCanVote && !$hasVoted): ?>
                            <a href="<?= base_url('voting.php') ?>" class="btn btn-warning btn-sm text-dark fw-bold px-3 py-2 rounded-pill shadow-sm w-100 w-sm-auto d-inline-flex align-items-center justify-content-center">
                                <i class="bi bi-check2-square me-1"></i> Masuk Bilik Suara
                            </a>
                        <?php elseif ($teacherCanVote && $hasVoted): ?>
                            <a href="<?= base_url('guru/status.php') ?>" class="btn btn-outline-light btn-sm fw-semibold px-3 py-2 rounded-pill w-100 w-sm-auto d-inline-flex align-items-center justify-content-center">
                                <i class="bi bi-patch-check me-1"></i> Bukti Suara
                            </a>
                        <?php else: ?>
                            <a href="<?= base_url('guru/candidates.php') ?>" class="btn btn-outline-light btn-sm fw-semibold px-3 py-2 rounded-pill w-100 w-sm-auto d-inline-flex align-items-center justify-content-center">
                                <i class="bi bi-people me-1"></i> Paslon OSIS
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Grid (Aksi Cepat Mobile & Desktop) -->
        <div class="student-quick-grid mb-4">
            <?php if ($teacherCanVote): ?>
                <a href="<?= base_url($hasVoted ? 'guru/status.php' : 'voting.php') ?>" class="quick-action-box">
                    <div class="quick-action-icon icon-vote">
                        <i class="bi <?= $hasVoted ? 'bi-patch-check-fill' : 'bi-check2-square' ?>"></i>
                    </div>
                    <span class="quick-action-title"><?= $hasVoted ? 'Status Suara' : 'Bilik Suara' ?></span>
                </a>
            <?php else: ?>
                <a href="<?= base_url('guru/candidates.php') ?>" class="quick-action-box">
                    <div class="quick-action-icon icon-paslon">
                        <i class="bi bi-eye-fill"></i>
                    </div>
                    <span class="quick-action-title">Pengamat</span>
                </a>
            <?php endif; ?>
            <a href="<?= base_url('guru/candidates.php') ?>" class="quick-action-box">
                <div class="quick-action-icon icon-paslon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <span class="quick-action-title">Paslon OSIS</span>
            </a>
            <a href="<?= base_url('guru/status.php') ?>" class="quick-action-box">
                <div class="quick-action-icon icon-status">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <span class="quick-action-title">Sertifikat</span>
            </a>
            <a href="<?= base_url('guru/info.php') ?>" class="quick-action-box">
                <div class="quick-action-icon icon-info">
                    <i class="bi bi-info-circle-fill"></i>
                </div>
                <span class="quick-action-title">Tata Cara</span>
            </a>
        </div>

        <!-- Active Election Status Card -->
        <?php if ($activeElection): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-dark text-white p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-event text-warning"></i>
                    <span><?= htmlspecialchars($activeElection['nama_periode'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <span class="badge bg-primary text-uppercase"><?= $activeElection['computed_status'] ?></span>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <small class="text-muted d-block" style="font-size: 0.78rem;">Jadwal Dimulai:</small>
                        <strong class="text-dark small d-block"><?= date('d M Y, H:i', strtotime($activeElection['start_at'])) ?> WIB</strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <small class="text-muted d-block" style="font-size: 0.78rem;">Batas Akhir:</small>
                        <strong class="text-dark small d-block"><?= date('d M Y, H:i', strtotime($activeElection['end_at'])) ?> WIB</strong>
                    </div>
                    <div class="col-12 col-md-4">
                        <small class="text-muted d-block" style="font-size: 0.78rem;">Kebijakan Hak Suara Guru:</small>
                        <?php if ($teacherCanVote): ?>
                            <span class="badge bg-success-subtle text-success fw-bold px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i> Guru Diizinkan Memilih</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary fw-bold px-2 py-1"><i class="bi bi-dash-circle me-1"></i> Khusus Pemilih Siswa</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Candidate Cards Overview -->
        <div class="mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-people me-2 text-primary"></i> Pasangan Calon Pemimpin OSIS</h5>
                <a href="<?= base_url('guru/candidates.php') ?>" class="text-decoration-none small fw-semibold">Lihat Visi &amp; Misi Lengkap <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="row g-3 g-md-4">
                <?php foreach ($candidates as $cand): 
                    $photoPasangan = !empty($cand['foto_pasangan']) ? asset('uploads/candidates/' . $cand['foto_pasangan']) : null;
                    $photoKetua = !empty($cand['foto_ketua']) ? asset('uploads/candidates/' . $cand['foto_ketua']) : asset('assets/images/default-avatar.svg');
                    $photoWakil = !empty($cand['foto_wakil']) ? asset('uploads/candidates/' . $cand['foto_wakil']) : asset('assets/images/default-avatar.svg');
                ?>
                <div class="col-md-6">
                    <div class="candidate-card bg-white rounded-4 border shadow-sm overflow-hidden h-100 d-flex flex-column">
                        <div class="candidate-card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white mb-0 fw-bold">Paslon No. <?= sprintf('%02d', $cand['nomor_urut']) ?></h6>
                            </div>
                            <div class="nomor-urut-badge bg-warning text-dark fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 1.05rem;">
                                <?= sprintf('%02d', $cand['nomor_urut']) ?>
                            </div>
                        </div>

                        <!-- Candidate Photos Container -->
                        <div class="candidate-photo-wrapper position-relative" style="height: 210px; overflow: hidden; background: #0f172a;">
                            <?php if ($photoPasangan): ?>
                                <img src="<?= $photoPasangan ?>" alt="Paslon No. <?= $cand['nomor_urut'] ?>" class="w-100 h-100 object-fit-cover object-position-top">
                            <?php else: ?>
                                <div class="row g-0 h-100">
                                    <div class="col-6 h-100 border-end border-dark">
                                        <img src="<?= $photoKetua ?>" alt="<?= htmlspecialchars($cand['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>" class="w-100 h-100 object-fit-cover">
                                    </div>
                                    <div class="col-6 h-100">
                                        <img src="<?= $photoWakil ?>" alt="<?= htmlspecialchars($cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>" class="w-100 h-100 object-fit-cover">
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="position-absolute bottom-0 start-0 end-0 p-2 text-white" style="background: linear-gradient(to top, rgba(0,0,0,0.85), transparent);">
                                <small class="fw-semibold d-block text-truncate" style="font-size: 0.78rem;">
                                    <?= htmlspecialchars($cand['nama_ketua'], ENT_QUOTES, 'UTF-8') ?> &amp; <?= htmlspecialchars($cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </div>
                        </div>

                        <div class="p-3 d-flex flex-column flex-grow-1 justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary-subtle text-primary fw-semibold" style="font-size: 0.75rem;">
                                        <?= htmlspecialchars($cand['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($cand['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <p class="small text-muted mb-3 fst-italic" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;">
                                    "<?= htmlspecialchars($cand['visi'], ENT_QUOTES, 'UTF-8') ?>"
                                </p>
                            </div>

                            <div class="d-flex flex-wrap flex-sm-nowrap gap-2">
                                <a href="<?= base_url('guru/candidates.php') ?>" class="btn btn-outline-primary btn-sm w-100 py-2">
                                    <i class="bi bi-eye me-1"></i> Visi Misi Lengkap
                                </a>
                                <?php if ($teacherCanVote && !$hasVoted): ?>
                                    <a href="<?= base_url('voting.php') ?>" class="btn btn-warning btn-sm text-dark fw-bold px-3 py-2 flex-shrink-0 w-100 w-sm-auto">
                                        <i class="bi bi-check2-square"></i> Pilih
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Official Announcements Section -->
        <?php if (!empty($announcements)): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 p-md-4">
            <h5 class="fw-bold text-dark mb-3">
                <i class="bi bi-megaphone-fill text-warning me-2"></i> Pengumuman &amp; Agenda Panitia Pemilihan
            </h5>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($announcements as $ann): ?>
                <div class="p-3 bg-light rounded-3 border">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-1 mb-1">
                        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                        <small class="text-muted" style="font-size: 0.75rem;"><?= date('d M Y, H:i', strtotime($ann['created_at'])) ?></small>
                    </div>
                    <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($ann['content'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        </main>
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
