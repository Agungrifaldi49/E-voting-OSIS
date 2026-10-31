<?php
// admin/majors.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Models/AcademicMaster.php';

AuthHelper::requireRole('admin');
AcademicMaster::ensureTables();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfHelper::validateOrAbort();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $res = AcademicMaster::createMajor($_POST);
        if ($res['success']) {
            set_flash('success', 'Program Keahlian / Jurusan baru berhasil ditambahkan.');
        } else {
            set_flash('error', $res['error'] ?? 'Gagal menambahkan jurusan.');
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['major_id'] ?? 0);
        $res = AcademicMaster::updateMajor($id, $_POST);
        if ($res['success']) {
            set_flash('success', 'Data jurusan berhasil diperbarui.');
        } else {
            set_flash('error', $res['error'] ?? 'Gagal memperbarui jurusan.');
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['major_id'] ?? 0);
        $res = AcademicMaster::toggleMajorStatus($id);
        if ($res['success']) {
            $statusText = $res['new_status'] ? 'diaktifkan' : 'dinonaktifkan';
            set_flash('success', "Jurusan <strong>" . htmlspecialchars($res['major']['nama_jurusan'], ENT_QUOTES, 'UTF-8') . "</strong> berhasil {$statusText}.");
        } else {
            set_flash('error', $res['error'] ?? 'Gagal mengubah status jurusan.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['major_id'] ?? 0);
        $res = AcademicMaster::deleteMajor($id);
        if ($res['success']) {
            set_flash('success', "Jurusan <strong>" . htmlspecialchars($res['major']['nama_jurusan'], ENT_QUOTES, 'UTF-8') . "</strong> berhasil dihapus.");
        } else {
            set_flash('error', $res['error'] ?? 'Gagal menghapus jurusan.');
        }
    }

    header("Location: " . base_url('admin/majors.php'));
    exit;
}

$search = trim($_GET['q'] ?? '');
$majors = AcademicMaster::getAllMajors($search, false);

$totalMajors = count($majors);
$activeMajors = 0;
$totalClassesLinked = 0;
foreach ($majors as $m) {
    if ($m['is_active']) {
        $activeMajors++;
    }
    $totalClassesLinked += (int)$m['total_classes'];
}

$pageTitle = "Manajemen Jurusan - Panel Admin";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/admin_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold text-dark mb-1">Manajemen Jurusan (Program Keahlian)</h3>
                <p class="text-muted small mb-0">Kelola daftar kompetensi keahlian / jurusan sekolah untuk pembentukan rombel kelas dan data siswa.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/classes.php') ?>" class="btn btn-outline-primary btn-sm fw-semibold">
                    <i class="bi bi-door-open me-1"></i> Kelola Kelas (Rombel)
                </a>
                <button type="button" class="btn btn-primary-custom btn-sm fw-bold" onclick="openCreateMajorModal()">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Jurusan
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Total Jurusan</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?= $totalMajors ?> Jurusan</h3>
                            <small class="text-muted">Program keahlian terdaftar</small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Jurusan Aktif</span>
                            <h3 class="fw-bold text-success mb-0 mt-1"><?= $activeMajors ?> Aktif</h3>
                            <small class="text-muted">Tampil di formulir &amp; kelas</small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Total Rombel Kelas</span>
                            <h3 class="fw-bold text-info mb-0 mt-1"><?= $totalClassesLinked ?> Kelas</h3>
                            <small class="text-primary"><a href="<?= base_url('admin/classes.php') ?>" class="text-decoration-none">Lihat manajemen kelas &rarr;</a></small>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-building"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <form action="<?= base_url('admin/majors.php') ?>" method="GET" class="row g-2">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari kode singkatan (misal: RPL, TKJ) atau nama jurusan...">
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-custom px-4">Cari</button>
                    <?php if ($search): ?>
                        <a href="<?= base_url('admin/majors.php') ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Majors Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">No</th>
                            <th width="130">Kode Jurusan</th>
                            <th>Nama Program Keahlian / Jurusan</th>
                            <th>Keterangan</th>
                            <th class="text-center">Jumlah Kelas</th>
                            <th class="text-center">Jumlah Siswa</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($majors)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-diagram-3 fs-2 d-block mb-2"></i>
                                    Belum ada data jurusan yang ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($majors as $idx => $m): ?>
                                <tr>
                                    <td class="text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold px-3 py-2 fs-6">
                                            <?= htmlspecialchars($m['kode_jurusan'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($m['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($m['deskripsi'] ?: '-', ENT_QUOTES, 'UTF-8') ?></small></td>
                                    <td class="text-center">
                                        <a href="<?= base_url('admin/classes.php?major_id=' . $m['id']) ?>" class="badge bg-light text-dark border text-decoration-none px-2 py-1">
                                            <i class="bi bi-door-open text-primary me-1"></i> <?= (int)$m['total_classes'] ?> Kelas
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border px-2 py-1">
                                            <i class="bi bi-people me-1"></i> <?= (int)$m['total_students'] ?> Siswa
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($m['is_active']): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                            <!-- Edit Button -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Edit Jurusan"
                                                    data-id="<?= $m['id'] ?>"
                                                    data-kode="<?= htmlspecialchars($m['kode_jurusan'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-nama="<?= htmlspecialchars($m['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-deskripsi="<?= htmlspecialchars($m['deskripsi'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-active="<?= (int)$m['is_active'] ?>"
                                                    onclick="openEditMajorModal(this)">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Toggle Status -->
                                            <form action="<?= base_url('admin/majors.php') ?>" method="POST" class="d-inline mb-0">
                                                <?= CsrfHelper::getTokenField() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="major_id" value="<?= $m['id'] ?>">
                                                <?php if ($m['is_active']): ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Nonaktifkan Jurusan">
                                                        <i class="bi bi-slash-circle"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Aktifkan Jurusan">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Delete Button -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Hapus Jurusan"
                                                    data-id="<?= $m['id'] ?>"
                                                    data-kode="<?= htmlspecialchars($m['kode_jurusan'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-nama="<?= htmlspecialchars($m['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-classes="<?= (int)$m['total_classes'] ?>"
                                                    onclick="openDeleteMajorModal(this)">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </main>

        <!-- MODAL TAMBAH / EDIT JURUSAN -->
        <div class="modal fade" id="majorModal" tabindex="-1" aria-labelledby="majorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-primary-subtle text-primary border-0 py-3">
                        <h5 class="modal-title fw-bold" id="majorModalLabel">
                            <i class="bi bi-diagram-3-fill me-2"></i> Tambah Jurusan Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?= base_url('admin/majors.php') ?>" method="POST">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="action" id="majorFormAction" value="create">
                        <input type="hidden" name="major_id" id="majorIdInput" value="">

                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label for="kode_jurusan" class="form-label fw-semibold small text-dark">Kode / Singkatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase fw-bold" id="kode_jurusan" name="kode_jurusan" placeholder="Contoh: RPL" maxlength="20" required>
                                    <div class="form-text text-xs">Digunakan untuk format kelas (misal: X <strong>RPL</strong> 1).</div>
                                </div>
                                <div class="col-md-7">
                                    <label for="is_active_major" class="form-label fw-semibold small text-dark">Status Jurusan</label>
                                    <select class="form-select" id="is_active_major" name="is_active">
                                        <option value="1">Aktif (Digunakan)</option>
                                        <option value="0">Nonaktif</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="nama_jurusan" class="form-label fw-semibold small text-dark">Nama Lengkap Program Keahlian / Jurusan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_jurusan" name="nama_jurusan" placeholder="Contoh: Rekayasa Perangkat Lunak" maxlength="120" required>
                                </div>
                                <div class="col-12">
                                    <label for="deskripsi_jurusan" class="form-label fw-semibold small text-dark">Deskripsi / Bidang Keahlian <small class="text-muted">(Opsional)</small></label>
                                    <textarea class="form-control" id="deskripsi_jurusan" name="deskripsi" rows="2" placeholder="Keterangan singkat program keahlian..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light border-0 py-3 px-4">
                            <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary-custom px-4 fw-bold" id="majorSubmitBtn">
                                <i class="bi bi-save me-1"></i> Simpan Jurusan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL HAPUS JURUSAN -->
        <div class="modal fade" id="deleteMajorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-danger-subtle text-danger border-0 py-3">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Hapus Jurusan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?= base_url('admin/majors.php') ?>" method="POST">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="major_id" id="deleteMajorId" value="">

                        <div class="modal-body p-4">
                            <p class="text-muted mb-3">Apakah Anda yakin ingin menghapus program keahlian / jurusan berikut?</p>
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <span class="badge bg-primary mb-1" id="deleteMajorKode">-</span>
                                <h6 class="fw-bold text-dark mb-0" id="deleteMajorNama">-</h6>
                            </div>
                            <div class="alert alert-warning small mb-0 border-0 bg-warning-subtle text-dark" id="deleteMajorWarning">
                                <i class="bi bi-info-circle-fill text-warning me-1"></i>
                                Pastikan tidak ada kelas yang masih terhubung dengan jurusan ini sebelum menghapus.
                            </div>
                        </div>

                        <div class="modal-footer bg-light border-0 py-3 px-4">
                            <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger px-4 fw-bold">
                                <i class="bi bi-trash3-fill me-1"></i> Ya, Hapus Jurusan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function openCreateMajorModal() {
            document.getElementById('majorModalLabel').innerHTML = '<i class="bi bi-diagram-3-fill me-2"></i> Tambah Jurusan Baru';
            document.getElementById('majorFormAction').value = 'create';
            document.getElementById('majorIdInput').value = '';
            document.getElementById('kode_jurusan').value = '';
            document.getElementById('nama_jurusan').value = '';
            document.getElementById('deskripsi_jurusan').value = '';
            document.getElementById('is_active_major').value = '1';

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('majorModal'));
            modal.show();
        }

        function openEditMajorModal(btn) {
            document.getElementById('majorModalLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Data Jurusan';
            document.getElementById('majorFormAction').value = 'update';
            document.getElementById('majorIdInput').value = btn.getAttribute('data-id');
            document.getElementById('kode_jurusan').value = btn.getAttribute('data-kode');
            document.getElementById('nama_jurusan').value = btn.getAttribute('data-nama');
            document.getElementById('deskripsi_jurusan').value = btn.getAttribute('data-deskripsi');
            document.getElementById('is_active_major').value = btn.getAttribute('data-active');

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('majorModal'));
            modal.show();
        }

        function openDeleteMajorModal(btn) {
            document.getElementById('deleteMajorId').value = btn.getAttribute('data-id');
            document.getElementById('deleteMajorKode').textContent = btn.getAttribute('data-kode');
            document.getElementById('deleteMajorNama').textContent = btn.getAttribute('data-nama');

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteMajorModal'));
            modal.show();
        }
        </script>

        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>

