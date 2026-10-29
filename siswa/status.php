<?php
// siswa/status.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';

AuthHelper::requireRole(['siswa', 'guru']);
$user = AuthHelper::user();
$userId = (int)$user['id'];

$activeElection = Election::getActive();
$hasVoted = false;
$votedAt = null;

if ($activeElection) {
    $hasVoted = Vote::hasUserVoted($activeElection['id'], $userId);
    if ($hasVoted) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT voted_at FROM voting_participation WHERE election_id = :eid AND user_id = :uid LIMIT 1");
        $stmt->execute([':eid' => $activeElection['id'], ':uid' => $userId]);
        $row = $stmt->fetch();
        if ($row) {
            $votedAt = $row['voted_at'];
        }
    }
}

$pageTitle = "Status Partisipasi Pemilihan - E-Voting OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php 
    if ($user['role'] === 'guru') {
        require_once BASE_PATH . '/app/Views/layouts/guru_sidebar.php';
    } else {
        require_once BASE_PATH . '/app/Views/layouts/siswa_sidebar.php';
    }
    ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <?php if ($hasVoted): 
                    // Generate a verifiable anonymous certificate code
                    $certCode = 'EVOTE-' . strtoupper(substr(hash('sha256', ($activeElection['id'] ?? 1) . '-' . $userId . '-' . ($votedAt ?? '')), 0, 12));
                ?>
                    <!-- BUKTI PARTISIPASI DIGITAL / SERTIFIKAT PEMILIH -->
                    <div class="certificate-card p-4 p-md-5 mb-4 text-center">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                            <div class="d-flex align-items-center gap-2 text-start">
                                <img src="<?= app_logo() ?>" alt="Logo" width="44" height="44" class="rounded bg-light p-1 object-fit-contain">
                                <div>
                                    <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-xs text-muted">Komisi Pemilihan OSIS Elektronik</div>
                                </div>
                            </div>
                            <div class="certificate-seal">
                                <i class="bi bi-patch-check-fill fs-3 mb-1"></i>
                                <span class="fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">SAH • VOTED</span>
                            </div>
                        </div>

                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold text-uppercase mb-2">
                            <i class="bi bi-shield-check me-1"></i> Sertifikat Partisipasi Digital
                        </span>
                        <h4 class="fw-bold text-dark mb-1">Terima Kasih, Suara Anda Telah Tercatat!</h4>
                        <p class="text-muted small mb-4">
                            Hak suara Anda telah berhasil disimpan di dalam bilik suara elektronik pada <strong><?= htmlspecialchars($activeElection['nama_periode'] ?? 'Pemilihan Ketua OSIS', ENT_QUOTES, 'UTF-8') ?></strong>.
                        </p>

                        <!-- Voter Identity Box with Rich Icons -->
                        <div class="p-3 p-md-4 bg-light rounded-4 border text-start mb-4">
                            <div class="row g-3 small">
                                <div class="col-sm-6">
                                    <span class="text-muted d-block mb-1">
                                        <i class="bi bi-person-fill text-primary me-1"></i>
                                        <?= $user['role'] === 'guru' ? 'Nama Pengajar:' : 'Nama Pemilih:' ?>
                                    </span>
                                    <strong class="text-dark fs-6 d-block"><?= htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block mb-1">
                                        <i class="bi bi-person-vcard-fill text-info me-1"></i>
                                        <?= $user['role'] === 'guru' ? 'NIP / Identitas:' : 'NISN / Identitas:' ?>
                                    </span>
                                    <strong class="text-dark font-monospace d-block"><?= htmlspecialchars($user['employee_identifier'] ?? $user['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block mb-1">
                                        <i class="bi <?= $user['role'] === 'guru' ? 'bi-book-half' : 'bi-mortarboard-fill' ?> text-warning me-1"></i>
                                        <?= $user['role'] === 'guru' ? 'Mata Pelajaran:' : 'Kelas &amp; Jurusan:' ?>
                                    </span>
                                    <span class="badge bg-white border text-dark py-1.5 px-2.5 shadow-xs">
                                        <i class="bi bi-tag-fill text-secondary me-1"></i>
                                        <?= htmlspecialchars($user['role'] === 'guru' ? ($user['mata_pelajaran'] ?? 'Dewan Guru') : (($user['kelas'] ?? '') . ' ' . ($user['jurusan'] ?? ''))) ?>
                                    </span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block mb-1">
                                        <i class="bi bi-clock-history text-success me-1"></i> Waktu Pencoblosan:
                                    </span>
                                    <strong class="text-success d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-check2-circle"></i>
                                        <?= $votedAt ? date('d M Y, H:i:s', strtotime($votedAt)) . ' WIB' : '-' ?>
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <!-- Certificate Security Hash -->
                        <div class="p-2 px-3 bg-dark bg-opacity-10 rounded-pill d-inline-flex align-items-center gap-2 small text-muted mb-4 font-monospace" style="font-size: 0.78rem;">
                            <i class="bi bi-key-fill text-warning"></i>
                            <span>KODE VERIFIKASI: <strong><?= $certCode ?></strong></span>
                        </div>

                        <!-- Secret Ballot Notice -->
                        <div class="alert alert-secondary small text-start border-0 rounded-4 p-3 mb-4">
                            <div class="fw-bold text-dark mb-1 d-flex align-items-center gap-1">
                                <i class="bi bi-lock-fill text-primary"></i> Asas Kerahasiaan Penuh (Secret Ballot)
                            </div>
                            <div class="text-muted" style="font-size: 0.8rem;">
                                Pasangan calon yang Anda coblos tersimpan secara terenkripsi dan <strong>tanpa kaitan ke akun Anda</strong>. Pilihan Anda dijamin 100% rahasia, bebas, jujur, dan adil.
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="<?= base_url('results.php') ?>" class="btn btn-primary-custom btn-sm px-4 fw-semibold py-2">
                                <i class="bi bi-bar-chart-fill me-1"></i> Perolehan Suara
                            </a>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold py-2" onclick="window.print()">
                                <i class="bi bi-printer me-1"></i> Cetak / Simpan
                            </button>
                            <a href="<?= base_url(($user['role'] ?? 'siswa') . '/dashboard.php') ?>" class="btn btn-light btn-sm px-3 fw-semibold py-2 border">
                                <i class="bi bi-arrow-left me-1"></i> Beranda
                            </a>
                        </div>
                    </div>

                <?php else: ?>

                    <!-- BELUM MEMILIH CTA CARD WITH RICH ICONS -->
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-center p-4 p-md-5">
                        <div class="p-4 rounded-circle mx-auto mb-3 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 88px; height: 88px; background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(217, 119, 6, 0.25) 100%); border: 2px dashed rgba(245, 158, 11, 0.5); color: #d97706;">
                            <i class="bi bi-fingerprint display-5"></i>
                        </div>

                        <span class="badge bg-warning-subtle text-dark border border-warning border-opacity-50 px-3 py-2 rounded-pill fw-bold text-uppercase mb-2 mx-auto d-inline-flex align-items-center gap-1.5">
                            <i class="bi bi-hourglass-split text-warning"></i> Status Hak Suara: Belum Digunakan
                        </span>

                        <h3 class="fw-bold text-dark mb-2">Hak Suara Anda Menunggu di Bilik Suara</h3>
                        <p class="text-muted small mx-auto mb-4" style="max-width: 520px;">
                            Satu suara Anda sangat berharga untuk menentukan kepemimpinan OSIS masa bakti berikutnya. Masuk ke bilik suara elektronik sekarang untuk memberikan hak pilih secara mandiri, jujur, dan rahasia.
                        </p>

                        <!-- Identity & Status Badges Box -->
                        <div class="p-3 bg-light rounded-4 border text-start mb-4 mx-auto" style="max-width: 540px;">
                            <div class="row g-3 small">
                                <div class="col-sm-6">
                                    <span class="text-muted d-block mb-1">
                                        <i class="bi bi-person-fill text-primary me-1"></i>
                                        <?= $user['role'] === 'guru' ? 'Nama Pengajar:' : 'Nama Pemilih:' ?>
                                    </span>
                                    <strong class="text-dark d-block text-truncate"><?= htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block mb-1">
                                        <i class="bi bi-person-vcard text-info me-1"></i>
                                        <?= $user['role'] === 'guru' ? 'NIP / Identitas:' : 'NISN / Identitas:' ?>
                                    </span>
                                    <strong class="text-dark font-monospace d-block"><?= htmlspecialchars($user['employee_identifier'] ?? $user['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block mb-1">
                                        <i class="bi bi-ticket-perforated-fill text-warning me-1"></i> Status Hak Pilih:
                                    </span>
                                    <span class="badge bg-warning text-dark fw-bold px-2 py-1">
                                        <i class="bi bi-check2-circle me-1"></i> 1 Hak Suara Sah Tersedia
                                    </span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block mb-1">
                                        <i class="bi bi-shield-lock-fill text-success me-1"></i> Jaminan Asas:
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 fw-bold px-2 py-1">
                                        <i class="bi bi-shield-check me-1"></i> 100% Rahasia (LUBER)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 col-md-8 mx-auto mb-4">
                            <a href="<?= base_url('voting.php') ?>" class="btn btn-gold-custom btn-lg fw-bold shadow-sm py-3 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-check2-square fs-4"></i>
                                <span>Buka Bilik Suara Sekarang</span>
                            </a>
                            <a href="<?= base_url(($user['role'] ?? 'siswa') . '/candidates.php') ?>" class="btn btn-outline-primary btn-sm py-2 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-people-fill"></i>
                                <span>Pelajari Visi Misi Paslon Dahulu</span>
                            </a>
                            <a href="<?= base_url(($user['role'] ?? 'siswa') . '/dashboard.php') ?>" class="btn btn-light btn-sm py-2 border d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-house-door"></i>
                                <span>Kembali ke Beranda Dashboard</span>
                            </a>
                        </div>

                        <div class="border-top pt-3 small text-muted d-flex align-items-center justify-content-center gap-3 flex-wrap">
                            <span><i class="bi bi-shield-check text-success me-1"></i> Asas LUBER JURDIL</span>
                            <span><i class="bi bi-eye-slash-fill text-primary me-1"></i> Pilihan Dijamin Rahasia</span>
                            <span><i class="bi bi-phone-fill text-secondary me-1"></i> Akses HP &amp; Laptop</span>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
