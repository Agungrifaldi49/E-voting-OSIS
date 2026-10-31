<?php
// app/Views/layouts/navbar.php
require_once dirname(__DIR__, 2) . '/Helpers/AuthHelper.php';
$isLoggedIn = AuthHelper::check();
$user = AuthHelper::user();
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top" id="main-navigation">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url('index.php') ?>">
            <img src="<?= app_logo() ?>" alt="Logo OSIS" width="42" height="42" class="d-inline-block rounded bg-white p-1 object-fit-contain">
            <div>
                <div class="school-title"><?= htmlspecialchars(app_setting('app_title', APP_NAME), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="school-sub"><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </a>

        <button class="navbar-toggler border-0" 
                type="button" 
                id="navbar-hamburger-btn" 
                data-bs-toggle="collapse" 
                data-bs-target="#navbarContent" 
                aria-controls="navbarContent" 
                aria-expanded="false" 
                aria-label="Toggle navigation"
                onclick="toggleMainNavbar(event)">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('index.php#beranda') ?>"><i class="bi bi-house-door me-1"></i> Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('index.php#kandidat') ?>"><i class="bi bi-people me-1"></i> Kandidat</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('index.php#jadwal') ?>"><i class="bi bi-calendar-event me-1"></i> Jadwal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('index.php#panduan') ?>"><i class="bi bi-card-checklist me-1"></i> Panduan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('index.php#hasil') ?>"><i class="bi bi-bar-chart me-1"></i> Hasil Suara</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('index.php#faq') ?>"><i class="bi bi-question-circle me-1"></i> FAQ</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($isLoggedIn): ?>
                    <!-- Desktop User Dropdown (>= 992px) -->
                    <div class="dropdown d-none d-lg-block">
                        <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center gap-2 py-2 px-3" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5 text-warning"></i>
                            <span class="small fw-semibold"><?= htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-primary text-uppercase ms-1"><?= $user['role'] ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userMenuDropdown">
                            <li><h6 class="dropdown-header">Akun: <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></h6></li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= base_url($user['role'] . '/dashboard.php') ?>">
                                    <i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard <?= ucfirst($user['role']) ?>
                                </a>
                            </li>
                            <?php if ($user['role'] === 'siswa' || ($user['role'] === 'guru')): ?>
                            <li>
                                <a class="dropdown-item py-2" href="<?= base_url('voting.php') ?>">
                                    <i class="bi bi-check2-square me-2 text-success"></i> Bilik Suara / Voting
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger" href="<?= base_url('logout.php') ?>">
                                    <i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Mobile User Profile Box (< 992px) - Ultra Modern, Luxury & Professional with Rich Icons -->
                    <div class="mobile-user-profile-box d-lg-none">
                        <div class="user-info-row">
                            <div class="mobile-user-avatar">
                                <?php if (($user['role'] ?? '') === 'admin'): ?>
                                    <i class="bi bi-shield-lock-fill"></i>
                                <?php elseif (($user['role'] ?? '') === 'guru'): ?>
                                    <i class="bi bi-person-workspace"></i>
                                <?php else: ?>
                                    <i class="bi bi-mortarboard-fill"></i>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-bold text-white text-truncate" style="font-size: 1rem; letter-spacing: -0.01em;">
                                    <?= htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                                        <span class="mobile-user-badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50">
                                            <i class="bi bi-shield-shaded"></i> ADMINISTRATOR
                                        </span>
                                    <?php elseif (($user['role'] ?? '') === 'guru'): ?>
                                        <span class="mobile-user-badge bg-info bg-opacity-25 text-info border border-info border-opacity-50">
                                            <i class="bi bi-award-fill"></i> DEWAN GURU
                                        </span>
                                    <?php else: ?>
                                        <span class="mobile-user-badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50">
                                            <i class="bi bi-check-circle-fill"></i> PEMILIH SISWA
                                        </span>
                                    <?php endif; ?>

                                    <span class="text-white-50 small d-inline-flex align-items-center gap-1 font-monospace" style="font-size: 0.78rem;">
                                        <i class="bi bi-person-badge"></i> <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="user-nav-actions pt-2 border-top border-white border-opacity-10">
                            <a href="<?= base_url($user['role'] . '/dashboard.php') ?>" class="btn btn-dashboard-action">
                                <i class="bi bi-speedometer2 fs-5"></i>
                                <span>Masuk ke Dashboard <?= ucfirst($user['role']) ?></span>
                                <i class="bi bi-arrow-right-short fs-4 ms-auto"></i>
                            </a>
                            <?php if ($user['role'] === 'siswa' || ($user['role'] === 'guru')): ?>
                            <a href="<?= base_url('voting.php') ?>" class="btn btn-voting-action">
                                <i class="bi bi-check2-square fs-5"></i>
                                <span>Buka Bilik Suara / Voting</span>
                                <i class="bi bi-box-arrow-in-right fs-5 ms-auto"></i>
                            </a>
                            <?php endif; ?>
                            <a href="<?= base_url('logout.php') ?>" class="btn btn-logout-action">
                                <i class="bi bi-box-arrow-right fs-5"></i>
                                <span>Keluar dari Akun (Logout)</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= base_url('login.php') ?>" class="btn btn-outline-light px-3 py-2 fw-medium" id="btn-nav-login">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                    </a>
                    <a href="<?= base_url('register.php') ?>" class="btn btn-gold-custom px-3 py-2" id="btn-nav-register">
                        <i class="bi bi-person-plus me-1"></i> Registrasi
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Bulletproof Mobile Navbar Interactive Controller -->
<script>
function toggleMainNavbar(e) {
    if (e && e.preventDefault) e.preventDefault();
    if (e && e.stopPropagation) e.stopPropagation();
    var content = document.getElementById('navbarContent');
    var btn = document.getElementById('navbar-hamburger-btn');
    if (!content) return;

    if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
        var bs = bootstrap.Collapse.getOrCreateInstance(content, { toggle: false });
        bs.toggle();
    } else {
        content.classList.toggle('show');
    }

    if (btn) {
        setTimeout(function() {
            var isShown = content.classList.contains('show') || content.classList.contains('collapsing');
            btn.setAttribute('aria-expanded', isShown ? 'true' : 'false');
        }, 50);
    }
}

// Auto close public navbar when clicking nav-links on mobile screens
(function() {
    function setupNavAutoClose() {
        var nav = document.getElementById('navbarContent');
        var btn = document.getElementById('navbar-hamburger-btn');
        if (!nav) return;
        nav.addEventListener('click', function(e) {
            var link = e.target.closest('.nav-link, .btn:not(.dropdown-toggle)');
            if (link && window.innerWidth < 992) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                    var bs = bootstrap.Collapse.getInstance(nav);
                    if (bs) bs.hide();
                } else {
                    nav.classList.remove('show');
                }
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupNavAutoClose);
    } else {
        setupNavAutoClose();
    }
})();
</script>
