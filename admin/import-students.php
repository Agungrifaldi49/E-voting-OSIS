<?php
// admin/import-students.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Controllers/AdminController.php';

AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AdminController::importStudents();
}

$pdo = Database::getConnection();
$totalRegistry = (int)$pdo->query("SELECT COUNT(*) FROM student_registry")->fetchColumn();

$pageTitle = "Import Data Siswa - Panel Admin";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/admin_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1">Import Data Master Siswa (CSV)</h3>
            <p class="text-muted small mb-0">Unggah daftar siswa resmi sekolah untuk memudahkan validasi otomatis pendaftaran siswa baru.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i> Unggah File CSV Siswa</h5>

                    <form action="<?= base_url('admin/import-students.php') ?>" method="POST" enctype="multipart/form-data">
                        <?= CsrfHelper::getTokenField() ?>

                        <div class="mb-3">
                            <label for="csv_file" class="form-label fw-semibold text-dark">Pilih File CSV (.csv)</label>
                            <input type="file" class="form-control form-control-lg fs-6" id="csv_file" name="csv_file" accept=".csv" required>
                            <div class="form-text">Pastikan file berekstensi .csv dengan pemisah koma (,).</div>
                        </div>

                        <div class="alert alert-info small rounded-3 p-3 mb-4">
                            <strong><i class="bi bi-info-circle me-1"></i> Format Kolom Wajib CSV:</strong>
                            <div class="mt-1">
                                <code>nisn,nama_lengkap,kelas,jurusan</code>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-custom px-4 fw-bold">
                            <i class="bi bi-upload me-2"></i> Mulai Proses Import
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
                    <h5 class="fw-bold text-dark mb-2"><i class="bi bi-filetype-csv text-success me-2"></i> Unduh Format Contoh</h5>
                    <p class="text-muted small mb-3">Gunakan template resmi berikut untuk menyusun data siswa sekolah sebelum diimpor:</p>
                    <a href="<?= asset('assets/sample_students.csv') ?>" class="btn btn-outline-success btn-sm w-100" download>
                        <i class="bi bi-download me-1"></i> Unduh Template sample_students.csv
                    </a>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-database-check text-primary me-2"></i> Status Master Siswa</h6>
                    <div class="display-6 fw-bold text-primary mb-1"><?= $totalRegistry ?></div>
                    <small class="text-muted">Total siswa resmi tersimpan di database sekolah (student_registry).</small>
                </div>
            </div>
        </div>
    </main>
    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
