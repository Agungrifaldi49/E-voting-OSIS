<?php
// app/Views/layouts/admin_sidebar.php
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<aside class="sidebar" id="admin-sidebar">
    <div class="sidebar-brand d-flex align-items-center justify-content-between">
        <a href="<?= base_url('admin/dashboard.php') ?>" class="d-flex align-items-center gap-2 text-decoration-none">
            <img src="<?= app_logo() ?>" alt="Logo" width="38" height="38" class="rounded object-fit-contain bg-white p-1">
            <div>
                <div class="sidebar-brand-title"><?= htmlspecialchars(app_setting('app_title', APP_NAME), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="sidebar-brand-sub"><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </a>
        <button type="button" class="btn btn-sm btn-outline-secondary text-white d-lg-none border-0" id="closeSidebarMobile" onclick="document.querySelector('.sidebar').classList.remove('show-mobile'); document.getElementById('sidebarBackdrop').classList.add('d-none');">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <div class="sidebar-heading">Menu Utama</div>
    <ul class="nav flex-column mb-2">
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'dashboard.php') ? 'active' : '' ?>" href="<?= base_url('admin/dashboard.php') ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'verification.php') ? 'active' : '' ?>" href="<?= base_url('admin/verification.php') ?>">
                <i class="bi bi-person-check"></i> Verifikasi Registrasi
            </a>
        </li>
    </ul>

    <div class="sidebar-heading">Data Master Pemilih</div>
    <ul class="nav flex-column mb-2">
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'students.php') ? 'active' : '' ?>" href="<?= base_url('admin/students.php') ?>">
                <i class="bi bi-mortarboard"></i> Data Siswa
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'teachers.php') ? 'active' : '' ?>" href="<?= base_url('admin/teachers.php') ?>">
                <i class="bi bi-person-workspace"></i> Data Guru
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'import-students.php') ? 'active' : '' ?>" href="<?= base_url('admin/import-students.php') ?>">
                <i class="bi bi-file-earmark-arrow-up"></i> Import Data Siswa
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'import-teachers.php') ? 'active' : '' ?>" href="<?= base_url('admin/import-teachers.php') ?>">
                <i class="bi bi-file-earmark-person"></i> Import Data Guru
            </a>
        </li>
    </ul>

    <div class="sidebar-heading">Manajemen Pemilihan</div>
    <ul class="nav flex-column mb-2">
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'elections.php') ? 'active' : '' ?>" href="<?= base_url('admin/elections.php') ?>">
                <i class="bi bi-calendar-check"></i> Periode Pemilihan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'candidates.php') ? 'active' : '' ?>" href="<?= base_url('admin/candidates.php') ?>">
                <i class="bi bi-person-badge"></i> Data Pasangan Calon
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'voters.php') ? 'active' : '' ?>" href="<?= base_url('admin/voters.php') ?>">
                <i class="bi bi-card-checklist"></i> Pengaturan DPT
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'results.php') ? 'active' : '' ?>" href="<?= base_url('admin/results.php') ?>">
                <i class="bi bi-bar-chart-line"></i> Monitoring &amp; Publikasi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'reports.php') ? 'active' : '' ?>" href="<?= base_url('admin/reports.php') ?>">
                <i class="bi bi-file-earmark-bar-graph"></i> Laporan Pemilihan
            </a>
        </li>
    </ul>

    <div class="sidebar-heading">Sistem &amp; Keamanan</div>
    <ul class="nav flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'logs.php') ? 'active' : '' ?>" href="<?= base_url('admin/logs.php') ?>">
                <i class="bi bi-journal-text"></i> Riwayat Aktivitas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'settings.php') ? 'active' : '' ?>" href="<?= base_url('admin/settings.php') ?>">
                <i class="bi bi-gear"></i> Pengaturan Website
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentScript === 'profile.php') ? 'active' : '' ?>" href="<?= base_url('admin/profile.php') ?>">
                <i class="bi bi-person-lock"></i> Profil Admin
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger fw-semibold" href="<?= base_url('admin/settings.php#tab-maintenance') ?>">
                <i class="bi bi-shield-exclamation text-danger"></i> Pembersihan &amp; Go-Live
            </a>
        </li>
    </ul>

    <div class="mt-auto p-3 border-top border-secondary border-opacity-25">
        <a href="<?= base_url('logout.php') ?>" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i> Keluar
        </a>
    </div>
</aside>
