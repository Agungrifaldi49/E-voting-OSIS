<?php
// public/login.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';

// Redirect if already logged in
if (AuthHelper::check()) {
    $role = AuthHelper::role();
    header("Location: " . base_url($role . '/dashboard.php'));
    exit;
}

// Handle POST login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthController::handleLogin();
}

$pageTitle = "Masuk Sistem - E-Voting OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
require_once BASE_PATH . '/app/Views/layouts/navbar.php';
require_once BASE_PATH . '/app/Views/layouts/alerts.php';
?>

<div class="container py-5 my-auto">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white text-center py-4 border-0">
                    <img src="<?= app_logo() ?>" alt="Logo" width="56" height="56" class="mb-2 rounded bg-white p-1 object-fit-contain">
                    <h4 class="fw-bold text-white mb-0">Masuk ke E-Voting</h4>
                    <p class="text-warning small mb-0"><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form action="<?= base_url('login.php') ?>" method="POST" id="form-login">
                        <?= CsrfHelper::getTokenField() ?>

                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold text-dark">
                                <i class="bi bi-person me-1 text-primary"></i> Username / NISN / NIP
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg fs-6"
                                   id="username"
                                   name="username"
                                   placeholder="Masukkan NISN siswa, NIP guru, atau username admin"
                                   required
                                   autocomplete="username">
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label fw-semibold text-dark mb-0">
                                    <i class="bi bi-key me-1 text-primary"></i> Password
                                </label>
                            </div>
                            <input type="password"
                                   class="form-control form-control-lg fs-6"
                                   id="password"
                                   name="password"
                                   placeholder="Masukkan password Anda"
                                   required
                                   autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn btn-primary-custom btn-lg w-100 fw-bold mb-3" id="btn-submit-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Masuk Sekarang
                        </button>
                    </form>

                    <div class="text-center pt-3 border-top">
                        <p class="text-muted small mb-2">Belum memiliki akun terdaftar?</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="<?= base_url('register.php?type=siswa') ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-mortarboard me-1"></i> Daftar Siswa
                            </a>
                            <a href="<?= base_url('register.php?type=guru') ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-person-workspace me-1"></i> Daftar Guru
                            </a>
                        </div>
                    </div>

                    <!-- Quick Demo Credentials Info for Testing -->
                    <div class="mt-4 p-3 bg-light rounded-3 border small">
                        <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle text-primary me-1"></i> Akun Uji Coba:</div>
                        <ul class="list-unstyled mb-0 text-muted" style="font-size: 0.8rem;">
                            <li>&bull; <strong>Admin:</strong> <code>admin</code> / <code>admin123</code></li>
                            <li>&bull; <strong>Guru:</strong> <code>198501152010011001</code> / <code>guru123</code></li>
                            <li>&bull; <strong>Siswa:</strong> <code>0061234567</code> / <code>siswa123</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/app/Views/layouts/footer.php'; ?>
