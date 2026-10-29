<?php
// siswa/profile.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/ValidationHelper.php';
require_once dirname(__DIR__) . '/app/Models/User.php';

AuthHelper::requireRole('siswa');
$user = AuthHelper::user();
$userId = (int)$user['id'];
$fullProfile = User::findById($userId);

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
            LogHelper::log('CHANGE_PASSWORD', "Siswa {$user['username']} memperbarui password akunnya.", $userId);
            set_flash('success', 'Password Anda berhasil diperbarui.');
        }
    }
    header("Location: " . base_url('siswa/profile.php'));
    exit;
}

$pageTitle = "Profil Saya - E-Voting OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/siswa_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1">Pengaturan Akun Siswa</h3>
            <p class="text-muted small mb-0">Kelola informasi identitas pemilih dan keamanan akun Anda.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 text-center p-4">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <div class="avatar-circle-lg bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; font-size: 2.2rem;">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success border border-white p-1">
                            <i class="bi bi-check fs-6 text-white"></i>
                        </span>
                    </div>

                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($fullProfile['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small mb-2">NISN: <span class="fw-semibold text-dark"><?= htmlspecialchars($fullProfile['username'], ENT_QUOTES, 'UTF-8') ?></span></p>
                    
                    <div class="mb-3">
                        <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill">
                            <i class="bi bi-patch-check-fill me-1"></i> Akun Pemilih Terverifikasi
                        </span>
                    </div>

                    <div class="p-3 bg-light rounded-4 border text-start small">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Kelas:</span>
                            <strong class="text-dark"><?= htmlspecialchars($fullProfile['kelas'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Jurusan:</span>
                            <strong class="text-dark"><?= htmlspecialchars($fullProfile['jurusan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Email:</span>
                            <span class="text-dark text-truncate" style="max-width: 170px;"><?= htmlspecialchars($fullProfile['email'] ?: 'Belum diisi', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Terdaftar:</span>
                            <span class="text-dark"><?= date('d M Y', strtotime($fullProfile['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold text-dark mb-2"><i class="bi bi-shield-lock-fill me-2 text-primary"></i> Ganti Password Akun</h5>
                    <p class="text-muted small mb-4">Gunakan password yang aman dan mudah Anda ingat untuk login ke bilik suara.</p>

                    <form action="<?= base_url('siswa/profile.php') ?>" method="POST">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label for="old_password" class="form-label small fw-semibold text-dark">Password Saat Ini</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="old_password" name="old_password" required placeholder="Masukkan password saat ini">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('old_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label small fw-semibold text-dark">Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required placeholder="Minimal 6 karakter">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('new_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text" style="font-size: 0.78rem;">Kombinasikan huruf dan angka agar akun tidak mudah ditebak.</div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label small fw-semibold text-dark">Ulangi Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-check-lg"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required placeholder="Ketik ulang password baru">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('confirm_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-custom px-4 py-2 fw-bold w-100 w-sm-auto shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Perbarui Password
                        </button>
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
