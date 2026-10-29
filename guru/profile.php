<?php
// guru/profile.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/ValidationHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/LogHelper.php';
require_once dirname(__DIR__) . '/app/Models/User.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';

AuthHelper::requireRole('guru');
$user = AuthHelper::user();
$userId = (int)$user['id'];
$fullProfile = User::findById($userId);

$activeElection = Election::getActive();
$teacherCanVote = !empty($activeElection['teacher_can_vote']);
$hasVoted = false;
if ($activeElection) {
    $hasVoted = Vote::hasUserVoted($activeElection['id'], $userId);
}

// Handle Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    CsrfHelper::validateOrAbort();
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (!password_verify($oldPass, $fullProfile['password_hash'])) {
        set_flash('error', 'Password lama yang Anda masukkan tidak sesuai.');
    } else {
        $pwErr = ValidationHelper::validatePassword($newPass, $confirmPass);
        if ($pwErr) {
            set_flash('error', $pwErr);
        } else {
            User::updatePassword($userId, $newPass);
            LogHelper::log('CHANGE_PASSWORD', "Guru {$user['username']} memperbarui password akunnya.", $userId);
            set_flash('success', 'Password Anda berhasil diperbarui.');
        }
    }
    header("Location: " . base_url('guru/profile.php'));
    exit;
}

$pageTitle = "Profil Dewan Guru - E-Voting OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/guru_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold text-dark mb-1">Pengaturan Profil &amp; Keamanan Akun</h3>
                <p class="text-muted small mb-0">Informasi identitas Dewan Guru dan pembaruan kata sandi akun.</p>
            </div>
            <a href="<?= base_url('guru/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-xs">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>

        <div class="row g-4">
            <!-- Kartu Identitas Dewan Guru -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 end-0 bg-success bg-opacity-10" style="height: 6px;"></div>

                    <div class="position-relative d-inline-block mx-auto mb-3 mt-2">
                        <div class="avatar-circle-lg text-white rounded-circle d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width: 84px; height: 84px; font-size: 2.2rem; background: linear-gradient(135deg, #091e2b 0%, #059669 100%);">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success border border-white p-1" title="Guru Terverifikasi">
                            <i class="bi bi-check-lg fs-6 text-white"></i>
                        </span>
                    </div>

                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($fullProfile['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small mb-2">NIP / ID: <span class="fw-semibold text-dark"><?= htmlspecialchars($fullProfile['employee_identifier'] ?? $fullProfile['username'], ENT_QUOTES, 'UTF-8') ?></span></p>
                    
                    <div class="mb-4">
                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 fw-bold px-3 py-2 rounded-pill">
                            <i class="bi bi-patch-check-fill me-1"></i> Akun Dewan Guru Terverifikasi
                        </span>
                    </div>

                    <div class="p-3 bg-light rounded-4 border text-start small mb-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted"><i class="bi bi-book me-1 text-primary"></i> Mata Pelajaran:</span>
                            <strong class="text-dark"><?= htmlspecialchars($fullProfile['mata_pelajaran'] ?? 'Dewan Guru', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted"><i class="bi bi-shield-check me-1 text-info"></i> Hak Suara:</span>
                            <?php if ($teacherCanVote): ?>
                                <?php if ($hasVoted): ?>
                                    <span class="badge bg-success text-white">Suara Telah Digunakan</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Hak Pilih Tersedia</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary text-white">Pengamat Resmi</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted"><i class="bi bi-envelope me-1 text-secondary"></i> Email:</span>
                            <span class="text-dark text-truncate" style="max-width: 170px;"><?= htmlspecialchars($fullProfile['email'] ?: 'Belum diisi', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted"><i class="bi bi-calendar-check me-1 text-success"></i> Terdaftar Sejak:</span>
                            <span class="text-dark"><?= date('d M Y', strtotime($fullProfile['created_at'])) ?></span>
                        </div>
                    </div>

                    <div class="p-3 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-25 text-start small text-muted">
                        <div class="d-flex gap-2">
                            <i class="bi bi-info-circle-fill text-info flex-shrink-0 mt-0.5"></i>
                            <div style="font-size: 0.8rem; line-height: 1.4;">
                                Akun ini terdaftar resmi dalam basis data Dewan Guru <strong><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></strong>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Ganti Password Akun -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h5 class="fw-bold text-dark mb-2"><i class="bi bi-shield-lock-fill me-2 text-primary"></i> Ganti Password Akun</h5>
                    <p class="text-muted small mb-4">Gunakan password yang aman dan mudah Anda ingat untuk akses bilik suara atau pemantauan pemilu dari perangkat HP Anda.</p>

                    <form action="<?= base_url('guru/profile.php') ?>" method="POST">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label for="old_password" class="form-label small fw-semibold text-dark">Password Saat Ini</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control border-start-0" id="old_password" name="old_password" required placeholder="Masukkan password saat ini">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('old_password', this)" title="Lihat/Sembunyikan Password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label small fw-semibold text-dark">Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key-fill"></i></span>
                                <input type="password" class="form-control border-start-0" id="new_password" name="new_password" minlength="6" required placeholder="Minimal 6 karakter">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('new_password', this)" title="Lihat/Sembunyikan Password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text" style="font-size: 0.78rem;">Minimal 6 karakter. Disarankan mengombinasikan huruf besar, huruf kecil, dan angka.</div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label small fw-semibold text-dark">Ulangi Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-shield-check"></i></span>
                                <input type="password" class="form-control border-start-0" id="confirm_password" name="confirm_password" minlength="6" required placeholder="Ketik ulang password baru">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('confirm_password', this)" title="Lihat/Sembunyikan Password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary-custom px-4 py-2 fw-bold w-100 w-sm-auto shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Perbarui Password
                            </button>
                            <a href="<?= base_url('guru/dashboard.php') ?>" class="btn btn-light border px-4 py-2 fw-semibold w-100 w-sm-auto">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function togglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
        </script>
    </main>
    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
