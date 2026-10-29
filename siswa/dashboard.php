<?php
// siswa/dashboard.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Candidate.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';

AuthHelper::requireRole('siswa');
$user = AuthHelper::user();
$userId = (int)$user['id'];

$activeElection = Election::getActive();
$hasVoted = false;
$isEligible = false;
$candidates = [];

if ($activeElection) {
    $hasVoted = Vote::hasUserVoted($activeElection['id'], $userId);
    $isEligible = Vote::isUserEligible($activeElection['id'], $userId);
    $candidates = Candidate::getByElectionId($activeElection['id'], true);
}

// Announcements
$pdo = Database::getConnection();
$announcements = $pdo->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY id DESC LIMIT 3")->fetchAll();

$pageTitle = "Dashboard Siswa - E-Voting OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/siswa_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <!-- Digital Student Voter Card (Kartu Pemilih Siswa Digital) -->
        <div class="student-card-luxury mb-4">
            <div class="card-watermark"><i class="bi bi-mortarboard-fill"></i></div>
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 position-relative" style="z-index: 2;">
                <div class="d-flex align-items-center gap-3">
                    <div class="card-chip">
                        <i class="bi bi-sim"></i>
                    </div>
                    <div>
                        <div class="text-uppercase text-warning small fw-bold" style="font-size: 0.72rem; letter-spacing: 0.8px;">
                            <i class="bi bi-shield-check me-1"></i> E-KARTU PEMILIH RESMI OSIS
                        </div>
                        <h4 class="fw-bold mb-0 text-white mt-1"><?= htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></h4>
                    </div>
                </div>
                <div>
                    <?php if ($hasVoted): ?>
                        <a href="<?= base_url('siswa/status.php') ?>" class="badge bg-success bg-opacity-90 text-white border border-white border-opacity-25 px-3 py-2 rounded-pill fw-bold text-decoration-none shadow-sm d-inline-flex align-items-center" title="Klik untuk melihat bukti partisipasi &amp; sertifikat pemilih">
                            <i class="bi bi-patch-check-fill me-1 text-warning"></i> SUARA TELAH DIGUNAKAN <i class="bi bi-arrow-right-short ms-1"></i>
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('voting.php') ?>" class="badge bg-warning text-dark border border-white border-opacity-50 px-3 py-2 rounded-pill fw-bold text-decoration-none shadow-sm d-inline-flex align-items-center" title="Klik untuk masuk ke bilik suara">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> BELUM MEMILIH &bull; PILIH SEKARANG <i class="bi bi-arrow-right-short ms-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 pt-2 position-relative" style="z-index: 2;">
                <div class="row g-2 align-items-end">
                    <div class="col-8 col-sm-9">
                        <div class="d-flex flex-wrap gap-2 text-white-50 small mb-1">
                            <span>NISN: <strong class="text-white"><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                            <span class="d-none d-sm-inline">&bull;</span>
                            <span>Kelas: <strong class="text-white"><?= htmlspecialchars($user['kelas'] . ' ' . $user['jurusan'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                        </div>
                        <div class="small text-white-50">
                            <?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <div class="col-4 col-sm-3 text-end">
                        <?php if (!$hasVoted): ?>
                            <a href="<?= base_url('voting.php') ?>" class="btn btn-warning btn-sm text-dark fw-bold px-3 py-2 rounded-pill shadow-sm">
                                <i class="bi bi-check2-square me-1"></i> Masuk Bilik
                            </a>
                        <?php else: ?>
                            <a href="<?= base_url('siswa/status.php') ?>" class="btn btn-outline-light btn-sm fw-semibold px-3 py-2 rounded-pill">
                                <i class="bi bi-patch-check me-1"></i> Bukti Suara
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Grid (Aksi Cepat Mobile & Desktop) -->
        <div class="student-quick-grid mb-4">
            <a href="<?= base_url($hasVoted ? 'siswa/status.php' : 'voting.php') ?>" class="quick-action-box">
                <div class="quick-action-icon icon-vote">
                    <i class="bi <?= $hasVoted ? 'bi-patch-check-fill' : 'bi-check2-square' ?>"></i>
                </div>
                <span class="quick-action-title"><?= $hasVoted ? 'Status Suara' : 'Bilik Suara' ?></span>
            </a>
            <a href="<?= base_url('siswa/candidates.php') ?>" class="quick-action-box">
                <div class="quick-action-icon icon-paslon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <span class="quick-action-title">Paslon OSIS</span>
            </a>
            <a href="<?= base_url('siswa/status.php') ?>" class="quick-action-box">
                <div class="quick-action-icon icon-status">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <span class="quick-action-title">Sertifikat</span>
            </a>
            <a href="<?= base_url('siswa/info.php') ?>" class="quick-action-box">
                <div class="quick-action-icon icon-info">
                    <i class="bi bi-info-circle-fill"></i>
                </div>
                <span class="quick-action-title">Tata Cara</span>
            </a>
        </div>

        <!-- Active Election Status Card -->
        <?php if ($activeElection): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
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
                        <small class="text-muted d-block" style="font-size: 0.78rem;">Status Hak Pilih DPT:</small>
                        <?php if ($isEligible): ?>
                            <span class="badge bg-success-subtle text-success fw-bold px-2 py-1"><i class="bi bi-check-circle me-1"></i> Terdaftar di DPT</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger fw-bold px-2 py-1"><i class="bi bi-x-circle me-1"></i> Tidak Terdaftar di DPT</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Candidate Cards Overview -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-people me-2 text-primary"></i> Pasangan Calon Ketua & Wakil Ketua OSIS</h5>
                <a href="<?= base_url('siswa/candidates.php') ?>" class="text-decoration-none small fw-semibold">Lihat Semua Detail <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="row g-4">
                <?php foreach ($candidates as $cand): 
                    $photoPasangan = !empty($cand['foto_pasangan']) ? asset('uploads/candidates/' . $cand['foto_pasangan']) : null;
                    $photoKetua = !empty($cand['foto_ketua']) ? asset('uploads/candidates/' . $cand['foto_ketua']) : asset('assets/images/default-avatar.svg');
                    $photoWakil = !empty($cand['foto_wakil']) ? asset('uploads/candidates/' . $cand['foto_wakil']) : asset('assets/images/default-avatar.svg');
                ?>
                <div class="col-md-6">
                    <div class="candidate-card">
                        <div class="candidate-card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-white mb-0">Paslon No. <?= sprintf('%02d', $cand['nomor_urut']) ?></h5>
                            </div>
                            <div class="nomor-urut-badge" style="width: 42px; height: 42px; font-size: 1.2rem;">
                                <?= sprintf('%02d', $cand['nomor_urut']) ?>
                            </div>
                        </div>

                        <div class="candidate-photo-wrapper" style="height: 180px;">
                            <?php if ($photoPasangan): ?>
                                <img src="<?= $photoPasangan ?>" alt="Paslon" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                            <?php else: ?>
                                <div class="dual-avatar-box">
                                    <div class="avatar-half position-relative">
                                        <img src="<?= $photoKetua ?>" alt="Ketua" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                    </div>
                                    <div class="avatar-half position-relative">
                                        <img src="<?= $photoWakil ?>" alt="Wakil" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="candidate-body">
                            <div class="fw-bold text-dark mb-1">
                                <?= htmlspecialchars($cand['nama_ketua'], ENT_QUOTES, 'UTF-8') ?> &amp; <?= htmlspecialchars($cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <small class="text-muted d-block mb-2"><?= htmlspecialchars($cand['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($cand['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?></small>
                            <div class="vision-quote small flex-grow-1">
                                "<?= htmlspecialchars(mb_strimwidth($cand['visi'], 0, 110, '...'), ENT_QUOTES, 'UTF-8') ?>"
                            </div>

                            <div class="mt-3 pt-3 border-top d-flex gap-2">
                                <a href="<?= base_url('siswa/candidates.php') ?>" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="bi bi-eye me-1"></i> Visi &amp; Misi Lengkap
                                </a>
                                <?php if (!$hasVoted && $isEligible): ?>
                                    <a href="<?= base_url('voting.php') ?>" class="btn btn-sm btn-primary-custom text-nowrap">
                                        <i class="bi bi-check2-circle me-1"></i> Pilih
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- School Announcements -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-megaphone me-2 text-warning"></i> Pengumuman Panitia Pemilihan</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($announcements)): ?>
                    <p class="text-muted mb-0 small">Belum ada pengumuman baru dari panitia.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($announcements as $ann): ?>
                        <div class="list-group-item px-0 py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                                <small class="text-muted"><?= date('d M Y', strtotime($ann['created_at'])) ?></small>
                            </div>
                            <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($ann['content'], ENT_QUOTES, 'UTF-8')) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
