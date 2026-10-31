<?php
// app/Views/layouts/dashboard_topbar.php
// Dedicated Topbar untuk Panel Dashboard (Admin, Guru, Siswa)
require_once dirname(__DIR__, 2) . '/Helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/Models/Election.php';
require_once dirname(__DIR__, 2) . '/Models/Vote.php';

$user = AuthHelper::user();
$activeElection = Election::getActive();
$userHasVoted = false;
if ($activeElection && !empty($user['id'])) {
    $userHasVoted = Vote::hasUserVoted($activeElection['id'], (int)$user['id']);
}

$portalLabel = match($user['role'] ?? '') {
    'admin' => 'Administrator Panel',
    'guru'  => 'Portal Dewan Guru',
    'siswa' => 'Portal Siswa Pemilih',
    default => 'Portal E-Voting'
};
$roleBadgeClass = match($user['role'] ?? '') {
    'admin' => 'bg-danger-subtle text-danger border border-danger border-opacity-25',
    'guru'  => 'bg-info-subtle text-info border border-info border-opacity-25',
    'siswa' => 'bg-primary-subtle text-primary border border-primary border-opacity-25',
    default => 'bg-secondary'
};
?>
<header class="dashboard-topbar bg-white border-bottom sticky-top py-2 px-2 px-md-3 px-lg-4" id="mainDashboardTopbar">
    <div class="d-flex align-items-center justify-content-between gap-2 flex-nowrap">
        <!-- Left: Mobile Toggle & Clickable Brand / Portal Title -->
        <div class="d-flex align-items-center gap-2 min-w-0" style="min-width: 0;">
            <button class="btn btn-outline-secondary btn-sm d-lg-none mobile-nav-toggler flex-shrink-0" type="button" id="dashboardSidebarToggle" aria-label="Buka Menu Navigasi" onclick="toggleSidebarMobile(event)">
                <i class="bi bi-list fs-5"></i>
            </button>

            <a href="<?= base_url(($user['role'] ?? 'siswa') . '/dashboard.php') ?>" class="d-flex align-items-center gap-2 text-decoration-none min-w-0" style="min-width: 0;" title="Ke Dashboard Utama">
                <img src="<?= app_logo() ?>" alt="Logo" width="32" height="32" class="rounded object-fit-contain bg-white border p-0.5 flex-shrink-0">
                <div class="d-flex flex-column min-w-0" style="min-width: 0;">
                    <span class="badge <?= $roleBadgeClass ?> text-uppercase fw-bold text-start text-truncate" style="font-size: 0.68rem; letter-spacing: 0.04em; max-width: 100%;">
                        <?= $portalLabel ?>
                    </span>
                    <span class="small text-muted d-none d-sm-inline text-truncate" style="font-size: 0.68rem; line-height: 1.1; max-width: 240px;">
                        <?= htmlspecialchars(app_setting('app_title', APP_NAME), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </a>

            <?php if ($activeElection): ?>
                <span class="d-none d-xl-inline-flex align-items-center gap-1 badge bg-light text-secondary border small ms-1 flex-shrink-0">
                    <span class="live-pulse me-1" style="width: 7px; height: 7px;"></span>
                    <?= htmlspecialchars($activeElection['tahun'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= strtoupper($activeElection['computed_status']) ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- Right: Actions & User Dropdown -->
        <div class="d-flex align-items-center gap-1 gap-sm-2 flex-shrink-0">
            <!-- View Public Site Button -->
            <a href="<?= base_url('index.php') ?>" class="btn btn-outline-secondary btn-sm d-none d-md-inline-flex align-items-center gap-1" title="Lihat Halaman Utama Publik" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i>
                <span class="small">Halaman Publik</span>
            </a>

            <?php if (($user['role'] ?? '') === 'siswa' || ($user['role'] ?? '') === 'guru'): ?>
                <?php if ($userHasVoted): ?>
                    <a href="<?= base_url(($user['role'] ?? 'siswa') . '/status.php') ?>" class="btn btn-outline-success btn-sm fw-bold px-2 px-md-3 d-inline-flex align-items-center gap-1" title="Lihat Bukti &amp; Sertifikat Pemilihan">
                        <i class="bi bi-patch-check-fill text-success"></i>
                        <span class="small d-none d-sm-inline">Sudah Memilih</span>
                        <span class="small d-sm-none" style="font-size: 0.72rem;">Status</span>
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('voting.php') ?>" class="btn btn-gold-custom btn-sm fw-bold px-2 px-md-3 d-inline-flex align-items-center gap-1 shadow-sm" title="Masuk ke Bilik Suara Elektronik">
                        <i class="bi bi-check2-square"></i>
                        <span class="small d-none d-sm-inline">Bilik Suara</span>
                        <span class="small d-sm-none" style="font-size: 0.72rem;">Voting</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- User Menu Dropdown -->
            <div class="dropdown position-relative" id="userMenuDropdownContainer">
                <button class="btn btn-light border btn-sm d-flex align-items-center gap-2 py-1 px-2 px-md-3 rounded-pill shadow-xs" type="button" id="dashboardUserDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Menu Akun &amp; Profil">
                    <?php if (!empty($user['avatar']) && file_exists(PUBLIC_PATH . '/' . ltrim($user['avatar'], '/'))): ?>
                        <img src="<?= asset(ltrim($user['avatar'], '/')) ?>" alt="Avatar" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-person-fill fs-6"></i>
                        </div>
                    <?php endif; ?>
                    <div class="text-start d-none d-md-block">
                        <div class="fw-bold text-dark small leading-none" style="max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= htmlspecialchars($user['nama_lengkap'] ?? $user['username'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="text-muted" style="font-size: 0.7rem; line-height: 1;">
                            <?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <i class="bi bi-chevron-down text-muted small ms-1" style="font-size: 0.7rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2 py-2" id="dashboardUserDropdownMenu" aria-labelledby="dashboardUserDropdown" style="min-width: 230px; z-index: 1060;">
                    <li class="px-3 py-2 border-bottom mb-1 bg-light">
                        <div class="fw-bold text-dark small"><?= htmlspecialchars($user['nama_lengkap'] ?? $user['username'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted small" style="font-size: 0.75rem;"><?= ($user['role'] ?? '') === 'guru' ? 'NIP/ID' : 'NISN/ID' ?>: <strong><?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <span class="badge <?= $roleBadgeClass ?> text-uppercase mt-1" style="font-size: 0.65rem;"><?= $user['role'] ?></span>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small" href="<?= base_url(($user['role'] ?? 'siswa') . '/dashboard.php') ?>">
                            <i class="bi bi-grid me-2 text-primary"></i> Beranda Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small" href="<?= base_url(($user['role'] ?? 'siswa') . '/profile.php') ?>">
                            <i class="bi bi-person-gear me-2 text-primary"></i> Pengaturan Akun &amp; Password
                        </a>
                    </li>
                    <?php if (in_array(($user['role'] ?? ''), ['siswa', 'guru'], true)): ?>
                    <li>
                        <a class="dropdown-item py-2 small" href="<?= base_url(($user['role'] ?? 'siswa') . '/status.php') ?>">
                            <i class="bi bi-patch-check me-2 text-success"></i> Status &amp; Bukti Suara
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a class="dropdown-item py-2 small" href="<?= base_url('index.php') ?>" target="_blank">
                            <i class="bi bi-box-arrow-up-right me-2 text-secondary"></i> Ke Halaman Utama Publik
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item py-2 small text-danger fw-semibold" href="<?= base_url('logout.php') ?>">
                            <i class="bi bi-box-arrow-right me-2 text-danger"></i> Keluar (Logout)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<!-- Mobile Dashboard Sidebar Controller (Self-contained in topbar) -->
<script>
window.toggleSidebarMobile = function(e) {
    if (e && e.preventDefault) e.preventDefault();
    if (e && e.stopPropagation) e.stopPropagation();
    var sidebar = document.querySelector('.sidebar') || document.getElementById('guru-sidebar') || document.getElementById('siswa-sidebar') || document.getElementById('admin-sidebar');
    var overlay = document.getElementById('sidebarBackdrop');
    if (sidebar) {
        sidebar.classList.toggle('show-mobile');
    }
    if (overlay) {
        overlay.classList.toggle('d-none');
    }
};

window.closeSidebarMobile = function(e) {
    if (e && e.preventDefault) e.preventDefault();
    if (e && e.stopPropagation) e.stopPropagation();
    var sidebar = document.querySelector('.sidebar') || document.getElementById('guru-sidebar') || document.getElementById('siswa-sidebar') || document.getElementById('admin-sidebar');
    var overlay = document.getElementById('sidebarBackdrop');
    if (sidebar) {
        sidebar.classList.remove('show-mobile');
    }
    if (overlay) {
        overlay.classList.add('d-none');
    }
};

(function() {
    function bindOverlay() {
        var overlay = document.getElementById('sidebarBackdrop');
        if (overlay) {
            overlay.removeEventListener('click', window.closeSidebarMobile);
            overlay.addEventListener('click', window.closeSidebarMobile);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindOverlay);
    } else {
        bindOverlay();
    }
})();
</script>
