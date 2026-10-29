<?php
// app/Views/layouts/guru_sidebar.php
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<aside class="sidebar" id="guru-sidebar">
    <div class="sidebar-brand d-flex align-items-center justify-content-between">
        <a href="<?= base_url('guru/dashboard.php') ?>" class="d-flex align-items-center gap-2 text-decoration-none">
            <img src="<?= app_logo() ?>" alt="Logo" width="38" height="38" class="rounded object-fit-contain bg-white p-1">
            <div>
                <div class="sidebar-brand-title"><?= htmlspecialchars(app_setting('app_title', APP_NAME), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="sidebar-brand-sub"><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </a>
        <button type="button" class="btn btn-sm btn-outline-secondary text-white d-lg-none border-0" onclick="window.closeSidebarMobile(event)" aria-label="Tutup Menu">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <div class="sidebar-heading d-flex align-items-center justify-content-between">
        <span>Menu Pendidik</span>
        <span class="badge bg-info-subtle text-info border border-info border-opacity-25" style="font-size: 0.65rem;">GURU</span>
    </div>
    <ul class="nav flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'dashboard.php') ? 'active' : '' ?>" href="<?= base_url('guru/dashboard.php') ?>">
                <i class="bi bi-speedometer2"></i> Dashboard Guru
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'candidates.php') ? 'active' : '' ?>" href="<?= base_url('guru/candidates.php') ?>">
                <i class="bi bi-people-fill"></i> Pasangan Calon &amp; Visi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-warning fw-bold <?= ($currentScript === 'voting.php') ? 'active' : '' ?>" href="<?= base_url('voting.php') ?>">
                <i class="bi bi-check2-square text-warning"></i> Bilik Suara / Voting
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'status.php') ? 'active' : '' ?>" href="<?= base_url('guru/status.php') ?>">
                <i class="bi bi-patch-check"></i> Status Hak Suara
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'info.php') ? 'active' : '' ?>" href="<?= base_url('guru/info.php') ?>">
                <i class="bi bi-info-circle"></i> Informasi Pemilihan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'results.php') ? 'active' : '' ?>" href="<?= base_url('results.php') ?>">
                <i class="bi bi-bar-chart-fill"></i> Hasil Pemilihan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'profile.php') ? 'active' : '' ?>" href="<?= base_url('guru/profile.php') ?>">
                <i class="bi bi-person-gear"></i> Pengaturan Akun
            </a>
        </li>
    </ul>

    <div class="mt-auto p-3 border-top border-secondary border-opacity-25">
        <a href="<?= base_url('logout.php') ?>" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i> Keluar
        </a>
    </div>
</aside>
