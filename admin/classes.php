<?php
// admin/classes.php
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
        $res = AcademicMaster::createClass($_POST);
        if ($res['success']) {
            set_flash('success', "Kelas <strong>" . htmlspecialchars($res['nama_kelas'], ENT_QUOTES, 'UTF-8') . "</strong> berhasil ditambahkan.");
        } else {
            set_flash('error', $res['error'] ?? 'Gagal menambahkan kelas.');
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['class_id'] ?? 0);
        $res = AcademicMaster::updateClass($id, $_POST);
        if ($res['success']) {
            set_flash('success', "Data kelas <strong>" . htmlspecialchars($res['nama_kelas'], ENT_QUOTES, 'UTF-8') . "</strong> berhasil diperbarui.");
        } else {
            set_flash('error', $res['error'] ?? 'Gagal memperbarui kelas.');
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['class_id'] ?? 0);
        $res = AcademicMaster::toggleClassStatus($id);
        if ($res['success']) {
            $statusText = $res['new_status'] ? 'diaktifkan' : 'dinonaktifkan';
            set_flash('success', "Kelas <strong>" . htmlspecialchars($res['class']['nama_kelas'], ENT_QUOTES, 'UTF-8') . "</strong> berhasil {$statusText}.");
        } else {
            set_flash('error', $res['error'] ?? 'Gagal mengubah status kelas.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['class_id'] ?? 0);
        $res = AcademicMaster::deleteClass($id);
        if ($res['success']) {
            set_flash('success', "Kelas <strong>" . htmlspecialchars($res['class']['nama_kelas'], ENT_QUOTES, 'UTF-8') . "</strong> berhasil dihapus.");
        } else {
            set_flash('error', $res['error'] ?? 'Gagal menghapus kelas.');
        }
    }

    header("Location: " . base_url('admin/classes.php'));
    exit;
}

$search = trim($_GET['q'] ?? '');
$tingkatFilter = trim($_GET['tingkat'] ?? '');
$majorFilter = (int)($_GET['major_id'] ?? 0);

$allMajors = AcademicMaster::getAllMajors('', false);
$classes = AcademicMaster::getAllClasses($search, $tingkatFilter, $majorFilter, false);

// Count summary per tingkat
$allClassesRaw = AcademicMaster::getAllClasses('', '', 0, false);
$countTotal = count($allClassesRaw);
$countX = 0;
$countXI = 0;
$countXII = 0;
foreach ($allClassesRaw as $c) {
    if ($c['tingkat'] === 'X') $countX++;
    elseif ($c['tingkat'] === 'XI') $countXI++;
    elseif ($c['tingkat'] === 'XII') $countXII++;
}

$pageTitle = "Manajemen Kelas - Panel Admin";
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
                <h3 class="fw-bold text-dark mb-1">Manajemen Kelas (Rombongan Belajar)</h3>
                <p class="text-muted small mb-0">Kelola tingkat kelas (X, XI, XII) berdasarkan jurusan dengan format standar (misal: <strong>X RPL 1</strong>, <strong>XI TKJ 2</strong>).</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/majors.php') ?>" class="btn btn-outline-primary btn-sm fw-semibold">
                    <i class="bi bi-diagram-3 me-1"></i> Manajemen Jurusan
                </a>
                <button type="button" class="btn btn-primary-custom btn-sm fw-bold" onclick="openCreateClassModal()">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Kelas Baru
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Total Rombel</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?= $countTotal ?> Kelas</h3>
                            <small class="text-muted">Seluruh tingkat &amp; jurusan</small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-door-open-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Tingkat X</span>
                            <h3 class="fw-bold text-info mb-0 mt-1"><?= $countX ?> Kelas</h3>
                            <small class="text-muted">Kelas X (Sepuluh)</small>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-1-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Tingkat XI</span>
                            <h3 class="fw-bold text-success mb-0 mt-1"><?= $countXI ?> Kelas</h3>
                            <small class="text-muted">Kelas XI (Sebelas)</small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-2-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Tingkat XII</span>
                            <h3 class="fw-bold text-warning mb-0 mt-1"><?= $countXII ?> Kelas</h3>
                            <small class="text-muted">Kelas XII (Dua Belas)</small>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-3-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <form action="<?= base_url('admin/classes.php') ?>" method="GET" class="row g-2">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari nama kelas (misal: X RPL 1) atau wali kelas...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="tingkat">
                        <option value="">Semua Tingkat</option>
                        <option value="X" <?= ($tingkatFilter === 'X') ? 'selected' : '' ?>>Tingkat X</option>
                        <option value="XI" <?= ($tingkatFilter === 'XI') ? 'selected' : '' ?>>Tingkat XI</option>
                        <option value="XII" <?= ($tingkatFilter === 'XII') ? 'selected' : '' ?>>Tingkat XII</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="major_id">
                        <option value="0">Semua Jurusan</option>
                        <?php foreach ($allMajors as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= ($majorFilter === (int)$m['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['kode_jurusan'] . ' - ' . $m['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-custom px-4">Filter</button>
                    <?php if ($search || $tingkatFilter || $majorFilter): ?>
                        <a href="<?= base_url('admin/classes.php') ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Classes Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="55">No</th>
                            <th>Nama Kelas</th>
                            <th>Tingkat</th>
                            <th>Program Keahlian / Jurusan</th>
                            <th class="text-center">Rombel</th>
                            <th>Wali Kelas</th>
                            <th class="text-center">Jumlah Siswa</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($classes)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-door-closed fs-2 d-block mb-2"></i>
                                    Belum ada data kelas yang ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($classes as $idx => $c): ?>
                                <tr>
                                    <td class="text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge bg-dark text-white fw-bold px-3 py-2 fs-6">
                                            <?= htmlspecialchars($c['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-semibold">
                                            Kelas <?= htmlspecialchars($c['tingkat'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($c['nama_jurusan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                                        <small class="text-primary fw-bold"><?= htmlspecialchars($c['kode_jurusan'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary fw-bold">
                                            <?= htmlspecialchars($c['nomor_rombel'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($c['wali_kelas'] ?: 'Belum ditentukan', ENT_QUOTES, 'UTF-8') ?></small></td>
                                    <td class="text-center">
                                        <a href="<?= base_url('admin/students.php?kelas=' . urlencode($c['nama_kelas'])) ?>" class="badge bg-light text-dark border text-decoration-none px-2 py-1">
                                            <i class="bi bi-people text-primary me-1"></i> <?= (int)$c['total_students'] ?> Siswa
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($c['is_active']): ?>
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
                                                    title="Edit Kelas"
                                                    data-id="<?= $c['id'] ?>"
                                                    data-tingkat="<?= htmlspecialchars($c['tingkat'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-major="<?= (int)$c['major_id'] ?>"
                                                    data-rombel="<?= htmlspecialchars($c['nomor_rombel'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-nama="<?= htmlspecialchars($c['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-wali="<?= htmlspecialchars($c['wali_kelas'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-active="<?= (int)$c['is_active'] ?>"
                                                    onclick="openEditClassModal(this)">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Toggle Status -->
                                            <form action="<?= base_url('admin/classes.php') ?>" method="POST" class="d-inline mb-0">
                                                <?= CsrfHelper::getTokenField() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="class_id" value="<?= $c['id'] ?>">
                                                <?php if ($c['is_active']): ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Nonaktifkan Kelas">
                                                        <i class="bi bi-slash-circle"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Aktifkan Kelas">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Delete Button -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Hapus Kelas"
                                                    data-id="<?= $c['id'] ?>"
                                                    data-nama="<?= htmlspecialchars($c['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-jurusan="<?= htmlspecialchars($c['nama_jurusan'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                                    onclick="openDeleteClassModal(this)">
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

        <!-- MODAL TAMBAH / EDIT KELAS -->
        <div class="modal fade" id="classModal" tabindex="-1" aria-labelledby="classModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-primary-subtle text-primary border-0 py-3">
                        <h5 class="modal-title fw-bold" id="classModalLabel">
                            <i class="bi bi-door-open-fill me-2"></i> Tambah Kelas Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?= base_url('admin/classes.php') ?>" method="POST">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="action" id="classFormAction" value="create">
                        <input type="hidden" name="class_id" id="classIdInput" value="">

                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label for="tingkat_kelas" class="form-label fw-semibold small text-dark">Tingkat Kelas <span class="text-danger">*</span></label>
                                    <select class="form-select fw-semibold" id="tingkat_kelas" name="tingkat" required onchange="autoGenerateClassName()">
                                        <option value="X">Kelas X (Sepuluh)</option>
                                        <option value="XI">Kelas XI (Sebelas)</option>
                                        <option value="XII">Kelas XII (Dua Belas)</option>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <label for="nomor_rombel" class="form-label fw-semibold small text-dark">Nomor / Kode Rombel</label>
                                    <input type="text" class="form-control" id="nomor_rombel" name="nomor_rombel" value="1" placeholder="Contoh: 1, 2, 3, A, B (Opsional)" maxlength="15" oninput="autoGenerateClassName()">
                                </div>
                                <div class="col-12">
                                    <label for="major_id_select" class="form-label fw-semibold small text-dark">Program Keahlian / Jurusan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="major_id_select" name="major_id" required onchange="autoGenerateClassName()">
                                        <option value="" data-kode="">-- Pilih Jurusan --</option>
                                        <?php foreach ($allMajors as $m): ?>
                                            <option value="<?= $m['id'] ?>" data-kode="<?= htmlspecialchars($m['kode_jurusan'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($m['nama_jurusan'] . ' (' . $m['kode_jurusan'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label for="nama_kelas_input" class="form-label fw-semibold small text-dark mb-0">Format Nama Kelas <span class="text-danger">*</span></label>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" onclick="autoGenerateClassName(true)">
                                            <i class="bi bi-magic me-1"></i>Susun Otomatis
                                        </button>
                                    </div>
                                    <input type="text" class="form-control fw-bold text-primary fs-6" id="nama_kelas_input" name="nama_kelas" placeholder="Contoh: X RPL 1" required>
                                    <div class="form-text text-xs">Terbentuk otomatis dari <strong>Tingkat + Kode Jurusan + Nomor Rombel</strong> (misal: <code>X RPL 1</code>), atau dapat Anda sesuaikan.</div>
                                </div>
                                <div class="col-md-7">
                                    <label for="wali_kelas_input" class="form-label fw-semibold small text-dark">Nama Wali Kelas <small class="text-muted">(Opsional)</small></label>
                                    <input type="text" class="form-control" id="wali_kelas_input" name="wali_kelas" placeholder="Contoh: Budi Santoso, S.Kom">
                                </div>
                                <div class="col-md-5">
                                    <label for="is_active_class" class="form-label fw-semibold small text-dark">Status Kelas</label>
                                    <select class="form-select" id="is_active_class" name="is_active">
                                        <option value="1">Aktif</option>
                                        <option value="0">Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light border-0 py-3 px-4">
                            <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary-custom px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Simpan Kelas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL HAPUS KELAS -->
        <div class="modal fade" id="deleteClassModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-danger-subtle text-danger border-0 py-3">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Hapus Kelas
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?= base_url('admin/classes.php') ?>" method="POST">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="class_id" id="deleteClassId" value="">

                        <div class="modal-body p-4">
                            <p class="text-muted mb-3">Apakah Anda yakin ingin menghapus kelas berikut dari daftar master kelas?</p>
                            <div class="p-3 bg-light rounded-3 border mb-0">
                                <h5 class="fw-bold text-dark mb-1" id="deleteClassNama">-</h5>
                                <small class="text-muted">Jurusan: <strong id="deleteClassJurusan">-</strong></small>
                            </div>
                        </div>

                        <div class="modal-footer bg-light border-0 py-3 px-4">
                            <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger px-4 fw-bold">
                                <i class="bi bi-trash3-fill me-1"></i> Ya, Hapus Kelas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function autoGenerateClassName() {
            const tingkat = document.getElementById('tingkat_kelas').value.trim();
            const majorSelect = document.getElementById('major_id_select');
            const selectedOption = majorSelect.options[majorSelect.selectedIndex];
            const kodeJurusan = selectedOption ? (selectedOption.getAttribute('data-kode') || '') : '';
            const rombel = document.getElementById('nomor_rombel').value.trim();

            if (!tingkat || !kodeJurusan) return;

            let generated = tingkat + ' ' + kodeJurusan;
            if (rombel !== '') {
                generated += ' ' + rombel;
            }
            document.getElementById('nama_kelas_input').value = generated;
        }

        function openCreateClassModal() {
            document.getElementById('classModalLabel').innerHTML = '<i class="bi bi-door-open-fill me-2"></i> Tambah Kelas Baru';
            document.getElementById('classFormAction').value = 'create';
            document.getElementById('classIdInput').value = '';
            document.getElementById('tingkat_kelas').value = 'X';
            document.getElementById('nomor_rombel').value = '1';
            const majorSel = document.getElementById('major_id_select');
            if (majorSel.options.length > 1) {
                majorSel.selectedIndex = 1;
            } else {
                majorSel.selectedIndex = 0;
            }
            document.getElementById('wali_kelas_input').value = '';
            document.getElementById('is_active_class').value = '1';
            autoGenerateClassName();

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('classModal'));
            modal.show();
        }

        function openEditClassModal(btn) {
            document.getElementById('classModalLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Data Kelas';
            document.getElementById('classFormAction').value = 'update';
            document.getElementById('classIdInput').value = btn.getAttribute('data-id');
            document.getElementById('tingkat_kelas').value = btn.getAttribute('data-tingkat');
            document.getElementById('major_id_select').value = btn.getAttribute('data-major');
            document.getElementById('nomor_rombel').value = btn.getAttribute('data-rombel');
            document.getElementById('nama_kelas_input').value = btn.getAttribute('data-nama');
            document.getElementById('wali_kelas_input').value = btn.getAttribute('data-wali');
            document.getElementById('is_active_class').value = btn.getAttribute('data-active');

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('classModal'));
            modal.show();
        }

        function openDeleteClassModal(btn) {
            document.getElementById('deleteClassId').value = btn.getAttribute('data-id');
            document.getElementById('deleteClassNama').textContent = btn.getAttribute('data-nama');
            document.getElementById('deleteClassJurusan').textContent = btn.getAttribute('data-jurusan');

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteClassModal'));
            modal.show();
        }
        </script>

        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>

