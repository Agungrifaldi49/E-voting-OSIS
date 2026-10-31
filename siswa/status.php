<?php
// siswa/status.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CertificatePdfHelper.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';
require_once dirname(__DIR__) . '/app/Models/User.php';

AuthHelper::requireRole(['siswa', 'guru']);
$user = AuthHelper::user();
$userId = (int)$user['id'];
$fullUser = User::findById($userId) ?: $user;

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

$certCode = 'EVOTE-' . strtoupper(substr(hash('sha256', ($activeElection['id'] ?? 1) . '-' . $userId . '-' . ($votedAt ?? '')), 0, 12));

// Handler Unduh PDF & Buka/Cetak Dokumen PDF Resmi
if ($hasVoted && isset($_GET['action']) && in_array($_GET['action'], ['download_pdf', 'print_pdf'], true)) {
    $forceDownload = ($_GET['action'] === 'download_pdf');
    CertificatePdfHelper::outputPdf($fullUser, $activeElection ?? [], (string)$votedAt, $certCode, $forceDownload);
}

$statusBaseUrl = base_url(($user['role'] ?? 'siswa') . '/status.php');
$pageTitle = "Status Partisipasi Pemilihan - E-Voting OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<style>
.print-only-block {
    display: none;
}
@media print {
    @page {
        size: A4 portrait;
        margin: 12mm;
    }
    html, body {
        background: #ffffff !important;
        overflow: visible !important;
    }
    .sidebar,
    .sidebar-backdrop,
    .dashboard-topbar,
    .mobile-bottom-nav,
    .dashboard-footer,
    .no-print {
        display: none !important;
    }
    .dashboard-wrapper,
    .dashboard-main-area,
    .dashboard-content {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }
    .col-lg-8.col-xl-7 {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
    }
    .certificate-card {
        border: 2px solid #0f172a !important;
        box-shadow: none !important;
        border-radius: 12px !important;
        padding: 24px !important;
        margin: 0 !important;
        page-break-inside: avoid;
    }
    .print-only-block {
        display: block !important;
    }
}
</style>

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
        <div class="no-print">
            <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <?php if ($hasVoted): ?>
                    <!-- Action Toolbar Atas (Hanya tampil di layar) -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Bukti &amp; Sertifikat Partisipasi</h5>
                            <small class="text-muted">Dokumen resmi tanda bukti telah menggunakan hak suara.</small>
                        </div>
                        <div class="d-flex flex-wrap gap-2 w-100 w-sm-auto">
                            <a href="<?= $statusBaseUrl ?>?action=download_pdf" class="btn btn-danger btn-sm fw-bold px-3 py-2 shadow-sm d-inline-flex align-items-center justify-content-center gap-1.5 flex-fill flex-sm-grow-0">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                                <span>Unduh PDF</span>
                            </a>
                            <a href="<?= $statusBaseUrl ?>?action=print_pdf" target="_blank" class="btn btn-outline-primary btn-sm fw-bold px-3 py-2 d-inline-flex align-items-center justify-content-center gap-1.5 flex-fill flex-sm-grow-0">
                                <i class="bi bi-printer-fill"></i>
                                <span>Buka / Cetak PDF</span>
                            </a>
                        </div>
                    </div>

                    <!-- BUKTI PARTISIPASI DIGITAL / SERTIFIKAT PEMILIH -->
                    <div class="certificate-card p-3 p-sm-4 p-md-5 mb-4 text-center">
                        <!-- Kop Surat Khusus Saat Print Langsung -->
                        <div class="print-only-block text-center pb-3 mb-3 border-bottom border-dark border-2">
                            <div class="d-flex justify-content-center align-items-center gap-3 mb-1">
                                <img src="<?= app_logo() ?>" alt="Logo" width="56" height="56" class="object-fit-contain">
                                <div class="text-start">
                                    <h5 class="fw-bold text-dark text-uppercase mb-0"><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></h5>
                                    <div class="fw-bold text-primary small text-uppercase">KOMISI PEMILIHAN ORGANISASI SISWA INTRA SEKOLAH (OSIS)</div>
                                    <small class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars(app_setting('school_address', 'Jl. Babakan Peuteuy No.300, Kec. Cicalengka, Kabupaten Bandung, Jawa Barat 40395'), ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4 no-print">
                            <div class="d-flex align-items-center gap-2 text-start min-w-0" style="min-width: 0;">
                                <img src="<?= app_logo() ?>" alt="Logo" width="44" height="44" class="rounded bg-light p-1 object-fit-contain flex-shrink-0">
                                <div class="min-w-0" style="min-width: 0;">
                                    <div class="fw-bold text-dark small mb-0 text-break"><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-xs text-muted">Komisi Pemilihan OSIS Elektronik</div>
                                </div>
                            </div>
                            <div class="certificate-seal flex-shrink-0">
                                <i class="bi bi-patch-check-fill fs-3 mb-1"></i>
                                <span class="fw-bold" style="font-size: 0.62rem; letter-spacing: 0.5px;">SAH &bull; VOTED</span>
                            </div>
                        </div>

                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold text-uppercase mb-2">
                            <i class="bi bi-shield-check me-1"></i> Sertifikat Partisipasi Digital
                        </span>
                        <h4 class="fw-bold text-dark mb-1" style="font-size: clamp(1.15rem, 3.5vw, 1.5rem);">Terima Kasih, Suara Anda Telah Tercatat!</h4>
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
                                    <strong class="text-dark fs-6 d-block text-break"><?= htmlspecialchars($fullUser['nama_lengkap'] ?? $user['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block mb-1">
                                        <i class="bi bi-person-vcard-fill text-info me-1"></i>
                                        <?= $user['role'] === 'guru' ? 'NIP / Identitas:' : 'NISN / Identitas:' ?>
                                    </span>
                                    <strong class="text-dark font-monospace d-block text-break"><?= htmlspecialchars($fullUser['employee_identifier'] ?? $fullUser['username'] ?? $user['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block mb-1">
                                        <i class="bi <?= $user['role'] === 'guru' ? 'bi-book-half' : 'bi-mortarboard-fill' ?> text-warning me-1"></i>
                                        <?= $user['role'] === 'guru' ? 'Mata Pelajaran:' : 'Kelas &amp; Jurusan:' ?>
                                    </span>
                                    <span class="badge bg-white border text-dark py-1.5 px-2.5 shadow-xs text-wrap text-start">
                                        <i class="bi bi-tag-fill text-secondary me-1"></i>
                                        <?= htmlspecialchars($user['role'] === 'guru' ? ($fullUser['mata_pelajaran'] ?? 'Dewan Guru') : trim(($fullUser['kelas'] ?? '') . ' ' . ($fullUser['jurusan'] ?? ''))) ?>
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
                        <div class="p-2 px-3 bg-dark bg-opacity-10 rounded-pill verification-code-pill small text-muted mb-4 font-monospace" style="font-size: 0.78rem;">
                            <i class="bi bi-key-fill text-warning flex-shrink-0"></i>
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

                        <!-- Blok Pengesahan Tanda Tangan (Tampil Saat Print) -->
                        <div class="print-only-block mt-4 pt-3 border-top">
                            <div class="row text-center small">
                                <div class="col-6">
                                    <div class="text-muted">Mengetahui &amp; Mengesahkan,</div>
                                    <div class="fw-bold text-dark">Ketua Panitia Pemilihan OSIS</div>
                                    <div style="height: 55px;"></div>
                                    <div class="fw-bold text-dark text-decoration-underline"><?= htmlspecialchars(app_setting('ketua_panitia_nama', 'Drs. H. Ahmad Fauzi, M.Pd.'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;">NIP. <?= htmlspecialchars(app_setting('ketua_panitia_nip', '198501152010011001'), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted">Diterbitkan pada <?= date('d M Y') ?></div>
                                    <div class="fw-bold text-dark">Kepala Sekolah</div>
                                    <div style="height: 55px;"></div>
                                    <div class="fw-bold text-dark text-decoration-underline"><?= htmlspecialchars(app_setting('kepala_sekolah_nama', 'H. Dadang Sulaeman, S.Pd., M.M.'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;">NIP. <?= htmlspecialchars(app_setting('kepala_sekolah_nip', '197508102003121002'), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons (No Print) -->
                        <div class="d-flex flex-wrap justify-content-center gap-2 no-print">
                            <a href="<?= $statusBaseUrl ?>?action=download_pdf" class="btn btn-danger btn-sm px-3 fw-bold py-2 w-100 w-sm-auto shadow-xs">
                                <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> Unduh Dokumen PDF
                            </a>
                            <a href="<?= $statusBaseUrl ?>?action=print_pdf" target="_blank" class="btn btn-outline-primary btn-sm px-3 fw-semibold py-2 w-100 w-sm-auto">
                                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen PDF
                            </a>
                            <a href="<?= base_url('results.php') ?>" class="btn btn-primary-custom btn-sm px-3 fw-semibold py-2 w-100 w-sm-auto">
                                <i class="bi bi-bar-chart-fill me-1"></i> Perolehan Suara
                            </a>
                            <a href="<?= base_url(($user['role'] ?? 'siswa') . '/dashboard.php') ?>" class="btn btn-light btn-sm px-3 fw-semibold py-2 border w-100 w-sm-auto">
                                <i class="bi bi-arrow-left me-1"></i> Beranda
                            </a>
                        </div>
                    </div>

                <?php else: ?>

                    <!-- BELUM MEMILIH CTA CARD WITH RICH ICONS -->
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-center p-3 p-sm-4 p-md-5">
                        <div class="p-4 rounded-circle mx-auto mb-3 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(217, 119, 6, 0.25) 100%); border: 2px dashed rgba(245, 158, 11, 0.5); color: #d97706;">
                            <i class="bi bi-fingerprint display-5"></i>
                        </div>

                        <span class="badge bg-warning-subtle text-dark border border-warning border-opacity-50 px-3 py-2 rounded-pill fw-bold text-uppercase mb-2 mx-auto d-inline-flex align-items-center gap-1.5 text-wrap">
                            <i class="bi bi-hourglass-split text-warning"></i> Status Hak Suara: Belum Digunakan
                        </span>

                        <h3 class="fw-bold text-dark mb-2" style="font-size: clamp(1.2rem, 4vw, 1.6rem);">Hak Suara Anda Menunggu di Bilik Suara</h3>
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
                                    <strong class="text-dark d-block text-break"><?= htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block mb-1">
                                        <i class="bi bi-person-vcard text-info me-1"></i>
                                        <?= $user['role'] === 'guru' ? 'NIP / Identitas:' : 'NISN / Identitas:' ?>
                                    </span>
                                    <strong class="text-dark font-monospace d-block text-break"><?= htmlspecialchars($user['employee_identifier'] ?? $user['username'], ENT_QUOTES, 'UTF-8') ?></strong>
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
