<?php
// admin/students.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/LogHelper.php';
require_once dirname(__DIR__) . '/app/Models/User.php';
require_once dirname(__DIR__) . '/app/Models/AcademicMaster.php';

$masterMajors = AcademicMaster::getAllMajors('', true);
$masterClasses = AcademicMaster::getAllClasses('', '', 0, true);

AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfHelper::validateOrAbort();

    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    // Build return redirect URL with filter and page
    $returnParams = [];
    if (!empty($_POST['return_q'])) {
        $returnParams['q'] = $_POST['return_q'];
    }
    if (!empty($_POST['return_kelas'])) {
        $returnParams['kelas'] = $_POST['return_kelas'];
    }
    if (!empty($_POST['return_p'])) {
        $returnParams['p'] = (int)$_POST['return_p'];
    }
    $returnUrl = base_url('admin/students.php') . (!empty($returnParams) ? '?' . http_build_query($returnParams) : '');

    if ($action === 'delete') {
        $res = User::deleteUser($userId);
        if ($res['success']) {
            $deleted = $res['user'];
            $nama = $deleted['nama_lengkap'] ?? $deleted['username'];
            $nisn = $deleted['nisn'] ?? $deleted['username'];
            set_flash('success', "Data siswa <strong>" . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . "</strong> (NISN: " . htmlspecialchars($nisn, ENT_QUOTES, 'UTF-8') . ") berhasil dihapus secara permanen.");
        } else {
            set_flash('error', $res['error'] ?? 'Gagal menghapus data siswa.');
        }
    } elseif ($action === 'toggle_status') {
        $newStatus = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';
        $user = User::findById($userId);
        if ($user && $user['role'] === 'siswa') {
            User::updateStatus($userId, $newStatus);
            $nama = $user['nama_lengkap'] ?? $user['username'];
            $statusLabel = ($newStatus === 'active') ? 'diaktifkan kembali' : 'dinonaktifkan';
            set_flash('success', "Akun siswa <strong>" . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . "</strong> berhasil {$statusLabel}.");
        } else {
            set_flash('error', 'Siswa tidak ditemukan.');
        }
    } elseif ($action === 'reset_password') {
        $newPassword = trim($_POST['new_password'] ?? '');
        $user = User::findById($userId);
        if (!$user || $user['role'] !== 'siswa') {
            set_flash('error', 'Data akun siswa tidak ditemukan.');
        } elseif (strlen($newPassword) < 6) {
            set_flash('error', 'Kata sandi baru minimal harus terdiri dari 6 karakter.');
        } else {
            if (User::updatePassword($userId, $newPassword)) {
                $nama = $user['nama_lengkap'] ?? $user['username'];
                $nisn = $user['nisn'] ?? $user['username'];
                LogHelper::log('PASSWORD_RESET_STUDENT', "Admin mereset kata sandi akun siswa: {$nama} (NISN: {$nisn}).");
                set_flash('success', "Kata sandi untuk siswa <strong>" . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . "</strong> (NISN: <strong>" . htmlspecialchars($nisn, ENT_QUOTES, 'UTF-8') . "</strong>) berhasil diperbarui.");
            } else {
                set_flash('error', 'Gagal memperbarui kata sandi siswa.');
            }
        }
    } elseif ($action === 'update_academic') {
        $newKelas = trim($_POST['kelas'] ?? '');
        $newJurusan = trim($_POST['jurusan'] ?? '');
        $user = User::findById($userId);
        if (!$user || $user['role'] !== 'siswa') {
            set_flash('error', 'Data akun siswa tidak ditemukan.');
        } elseif ($newKelas === '' || $newJurusan === '') {
            set_flash('error', 'Kelas dan jurusan siswa wajib dipilih.');
        } else {
            if (User::updateStudentAcademic($userId, $newKelas, $newJurusan)) {
                $nama = $user['nama_lengkap'] ?? $user['username'];
                LogHelper::log('STUDENT_CLASS_UPDATED', "Admin memperbarui kelas/jurusan siswa {$nama} menjadi {$newKelas} ({$newJurusan}).");
                set_flash('success', "Kelas dan jurusan siswa <strong>" . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . "</strong> berhasil diperbarui menjadi <strong>" . htmlspecialchars($newKelas . ' — ' . $newJurusan, ENT_QUOTES, 'UTF-8') . "</strong>.");
            } else {
                set_flash('error', 'Gagal memperbarui kelas dan jurusan siswa.');
            }
        }
    }

    header("Location: " . $returnUrl);
    exit;
}

$search = trim($_GET['q'] ?? '');
$kelasFilter = trim($_GET['kelas'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$totalStudents = User::countAllStudents($search, $kelasFilter);
$totalPages = ceil($totalStudents / $limit);
$students = User::getAllStudents($search, $kelasFilter, $limit, $offset);

$pageTitle = "Data Siswa - Panel Admin";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/admin_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold text-dark mb-1">Data Siswa Pemilih</h3>
                <p class="text-muted small mb-0">Total: <strong><?= $totalStudents ?></strong> siswa terdaftar dalam sistem.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/classes.php') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-door-open me-1"></i> Manajemen Kelas
                </a>
                <a href="<?= base_url('admin/majors.php') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-diagram-3 me-1"></i> Manajemen Jurusan
                </a>
                <a href="<?= base_url('admin/import-students.php') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV Siswa
                </a>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <form action="<?= base_url('admin/students.php') ?>" method="GET" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari nama, NISN, kelas, atau jurusan...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="kelas">
                        <option value="">Semua Tingkat &amp; Kelas</option>
                        <optgroup label="Berdasarkan Tingkat">
                            <option value="X" <?= ($kelasFilter === 'X') ? 'selected' : '' ?>>Tingkat X (Semua Jurusan)</option>
                            <option value="XI" <?= ($kelasFilter === 'XI') ? 'selected' : '' ?>>Tingkat XI (Semua Jurusan)</option>
                            <option value="XII" <?= ($kelasFilter === 'XII') ? 'selected' : '' ?>>Tingkat XII (Semua Jurusan)</option>
                        </optgroup>
                        <?php if (!empty($masterClasses)): ?>
                            <optgroup label="Berdasarkan Rombel Kelas">
                                <?php foreach ($masterClasses as $mc): ?>
                                    <option value="<?= htmlspecialchars($mc['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>" <?= ($kelasFilter === $mc['nama_kelas']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mc['nama_kelas'] . ' (' . ($mc['nama_jurusan'] ?? '') . ')', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-custom px-4">Filter</button>
                    <?php if ($search || $kelasFilter): ?>
                        <a href="<?= base_url('admin/students.php') ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Student Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>NISN</th>
                            <th>Nama Lengkap</th>
                            <th>Kelas</th>
                            <th>Jurusan</th>
                            <th>Status Akun</th>
                            <th>Tgl Terdaftar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-2 text-muted d-block mb-1"></i>
                                    Data siswa tidak ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = $offset + 1;
                            foreach ($students as $s): 
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($s['nisn'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($s['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['kelas'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><small class="text-muted"><?= htmlspecialchars($s['jurusan'], ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td>
                                    <?php if ($s['account_status'] === 'active'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php elseif ($s['account_status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($s['account_status'] === 'rejected'): ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= date('d M Y', strtotime($s['created_at'])) ?></small></td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                        <!-- Toggle Status Form -->
                                        <form action="<?= base_url('admin/students.php') ?>" method="POST" class="d-inline mb-0">
                                            <?= CsrfHelper::getTokenField() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                                            <input type="hidden" name="return_q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="return_kelas" value="<?= htmlspecialchars($kelasFilter, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="return_p" value="<?= $page ?>">
                                            <?php if ($s['account_status'] === 'active'): ?>
                                                <input type="hidden" name="status" value="inactive">
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Nonaktifkan Akun" data-bs-toggle="tooltip">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Aktifkan Akun" data-bs-toggle="tooltip">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <!-- Tombol Edit Kelas & Jurusan Siswa -->
                                        <button type="button"
                                                class="btn btn-sm btn-outline-info"
                                                title="Ubah Kelas &amp; Jurusan"
                                                data-bs-toggle="tooltip"
                                                data-id="<?= $s['id'] ?>"
                                                data-nama="<?= htmlspecialchars($s['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-nisn="<?= htmlspecialchars($s['nisn'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-kelas-val="<?= htmlspecialchars($s['kelas'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-jurusan-val="<?= htmlspecialchars($s['jurusan'], ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="openEditAcademicStudentModal(this)">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Tombol Reset / Ubah Kata Sandi Siswa -->
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Reset / Ubah Kata Sandi"
                                                data-bs-toggle="tooltip"
                                                data-id="<?= $s['id'] ?>"
                                                data-nama="<?= htmlspecialchars($s['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-nisn="<?= htmlspecialchars($s['nisn'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-kelas="<?= htmlspecialchars($s['kelas'] . ' ' . $s['jurusan'], ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="openResetPasswordStudentModal(this)">
                                            <i class="bi bi-key-fill"></i>
                                        </button>

                                        <!-- Tombol Hapus Siswa -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                title="Hapus Data Siswa" 
                                                data-bs-toggle="tooltip"
                                                data-id="<?= $s['id'] ?>"
                                                data-nama="<?= htmlspecialchars($s['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-nisn="<?= htmlspecialchars($s['nisn'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-kelas="<?= htmlspecialchars($s['kelas'] . ' ' . $s['jurusan'], ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="openDeleteStudentModal(this)">
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

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?></small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= base_url("admin/students.php?q=" . urlencode($search) . "&kelas=" . urlencode($kelasFilter) . "&p={$i}") ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- MODAL UBAH KELAS & JURUSAN SISWA -->
    <div class="modal fade" id="editAcademicStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-info-subtle text-info border-0 py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-door-open-fill me-2 text-info"></i> Ubah Kelas &amp; Jurusan Siswa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/students.php') ?>" method="POST">
                    <?= CsrfHelper::getTokenField() ?>
                    <input type="hidden" name="action" value="update_academic">
                    <input type="hidden" name="user_id" id="editAcademicUserId" value="">
                    <input type="hidden" name="return_q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_kelas" value="<?= htmlspecialchars($kelasFilter, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_p" value="<?= $page ?>">

                    <div class="modal-body p-4">
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <h6 class="fw-bold text-dark mb-0" id="editAcademicNama">-</h6>
                            <small class="text-muted">NISN: <strong id="editAcademicNisn">-</strong></small>
                        </div>

                        <div class="mb-3">
                            <label for="editAcademicJurusan" class="form-label fw-semibold small text-dark">Program Keahlian / Jurusan <span class="text-danger">*</span></label>
                            <select class="form-select" id="editAcademicJurusan" name="jurusan" required>
                                <?php foreach ($masterMajors as $mm): ?>
                                    <option value="<?= htmlspecialchars($mm['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($mm['nama_jurusan'] . ' (' . $mm['kode_jurusan'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-0">
                            <label for="editAcademicKelas" class="form-label fw-semibold small text-dark">Kelas / Rombel <span class="text-danger">*</span></label>
                            <select class="form-select fw-bold" id="editAcademicKelas" name="kelas" required onchange="syncAcademicModalMajor()">
                                <?php foreach ($masterClasses as $mc): ?>
                                    <option value="<?= htmlspecialchars($mc['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-major-nama="<?= htmlspecialchars($mc['nama_jurusan'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($mc['nama_kelas'] . ' — ' . ($mc['nama_jurusan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="X">X</option>
                                <option value="XI">XI</option>
                                <option value="XII">XII</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-0 py-3 px-4">
                        <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary-custom px-4 fw-bold">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL RESET / UBAH KATA SANDI SISWA -->
    <div class="modal fade" id="resetPasswordStudentModal" tabindex="-1" aria-labelledby="resetPasswordStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-primary-subtle text-primary border-0 py-3">
                    <h5 class="modal-title fw-bold" id="resetPasswordStudentModalLabel">
                        <i class="bi bi-key-fill me-2 text-primary"></i> Reset Kata Sandi Akun Siswa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/students.php') ?>" method="POST" id="formResetPasswordStudent">
                    <?= CsrfHelper::getTokenField() ?>
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="resetStudentUserId" value="">
                    <input type="hidden" name="return_q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_kelas" value="<?= htmlspecialchars($kelasFilter, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_p" value="<?= $page ?>">

                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">
                            Gunakan fitur ini apabila siswa lupa kata sandi saat akan masuk ke bilik suara.
                        </p>

                        <!-- Student Highlight Box -->
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-circle-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; font-size: 1.1rem;">
                                    <i class="bi bi-mortarboard-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-0" id="resetStudentNama">-</h6>
                                    <div class="small text-muted mt-1">
                                        NISN: <span class="fw-semibold text-dark" id="resetStudentNisn">-</span>
                                        <span class="mx-1">•</span>
                                        Kelas: <span class="badge bg-white border text-dark" id="resetStudentKelas">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="newStudentPassword" class="form-label fw-semibold small text-dark mb-0">Kata Sandi Baru <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" onclick="fillStudentDefaultPassword()">
                                    <i class="bi bi-magic me-1"></i>Samakan dengan NISN
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-lock-fill text-muted"></i></span>
                                <input type="text" class="form-control fw-semibold" id="newStudentPassword" name="new_password" minlength="6" required placeholder="Minimal 6 karakter...">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleStudentPassVisibility()" title="Tampilkan/Sembunyikan">
                                    <i class="bi bi-eye-slash" id="iconToggleStudentPass"></i>
                                </button>
                                <button class="btn btn-outline-primary" type="button" onclick="copyStudentPassword()" title="Salin Kata Sandi">
                                    <i class="bi bi-clipboard" id="iconCopyStudentPass"></i> Salin
                                </button>
                            </div>
                            <div class="form-text text-xs">Minimal 6 karakter. Klik <strong>Samakan dengan NISN</strong> untuk mengisi otomatis dengan NISN siswa.</div>
                        </div>

                        <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3 rounded-3 small mb-0 border-0 bg-info-subtle text-dark">
                            <i class="bi bi-info-circle-fill text-info flex-shrink-0 mt-1"></i>
                            <div>
                                Setelah disimpan, berikan kata sandi baru ini kepada siswa yang bersangkutan melalui posko panitia atau helpdesk WhatsApp.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-0 py-3 px-4">
                        <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary-custom px-4 fw-bold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Kata Sandi Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS SISWA -->
    <div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-danger-subtle text-danger border-0 py-3">
                    <h5 class="modal-title fw-bold" id="deleteStudentModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i> Konfirmasi Hapus Data Siswa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/students.php') ?>" method="POST" id="formDeleteStudent">
                    <?= CsrfHelper::getTokenField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="modalStudentUserId" value="">
                    <input type="hidden" name="return_q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_kelas" value="<?= htmlspecialchars($kelasFilter, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_p" value="<?= $page ?>">

                    <div class="modal-body p-4">
                        <p class="text-muted mb-3">
                            Apakah Anda yakin ingin menghapus data siswa pemilih berikut secara permanen dari sistem?
                        </p>

                        <!-- Student Highlight Box -->
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-circle-sm bg-danger text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; font-size: 1.1rem;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-0" id="modalStudentNama">-</h6>
                                    <div class="small text-muted mt-1">
                                        NISN: <span class="fw-semibold text-dark" id="modalStudentNisn">-</span>
                                        <span class="mx-1">•</span>
                                        Kelas: <span class="badge bg-white border text-dark" id="modalStudentKelas">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start gap-2 py-2 px-3 rounded-3 small mb-0 border-0 bg-warning-subtle text-dark">
                            <i class="bi bi-info-circle-fill text-warning flex-shrink-0 mt-1"></i>
                            <div>
                                <strong>Perhatian:</strong> Menghapus akun siswa ini akan mencabut status hak pilih, menghapus profil pengguna, dan mengembalikan status master data di pendaftaran menjadi belum terdaftar.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-0 py-3 px-4">
                        <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger px-4 fw-bold shadow-sm" id="btnSubmitDeleteStudent">
                            <i class="bi bi-trash3-fill me-1"></i> Ya, Hapus Siswa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function openEditAcademicStudentModal(btn) {
        document.getElementById('editAcademicUserId').value = btn.getAttribute('data-id');
        document.getElementById('editAcademicNama').textContent = btn.getAttribute('data-nama');
        document.getElementById('editAcademicNisn').textContent = btn.getAttribute('data-nisn');

        const curJurusan = btn.getAttribute('data-jurusan-val') || '';
        const curKelas = btn.getAttribute('data-kelas-val') || '';

        const jurSel = document.getElementById('editAcademicJurusan');
        const kelSel = document.getElementById('editAcademicKelas');
        if (jurSel && curJurusan) {
            jurSel.value = curJurusan;
        }
        if (kelSel && curKelas) {
            kelSel.value = curKelas;
        }

        const modalEl = document.getElementById('editAcademicStudentModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function syncAcademicModalMajor() {
        const kelSel = document.getElementById('editAcademicKelas');
        const jurSel = document.getElementById('editAcademicJurusan');
        if (!kelSel || !jurSel) return;
        const opt = kelSel.options[kelSel.selectedIndex];
        if (opt) {
            const majorNama = opt.getAttribute('data-major-nama');
            if (majorNama) {
                jurSel.value = majorNama;
            }
        }
    }

    let currentResetStudentNisn = '';

    function openResetPasswordStudentModal(button) {
        const id = button.getAttribute('data-id');
        const nama = button.getAttribute('data-nama');
        const nisn = button.getAttribute('data-nisn') || '';
        const kelas = button.getAttribute('data-kelas');

        currentResetStudentNisn = nisn;
        document.getElementById('resetStudentUserId').value = id;
        document.getElementById('resetStudentNama').textContent = nama;
        document.getElementById('resetStudentNisn').textContent = nisn;
        document.getElementById('resetStudentKelas').textContent = kelas;

        const passInput = document.getElementById('newStudentPassword');
        passInput.type = 'text';
        passInput.value = nisn.length >= 6 ? nisn : (nisn + '123456').slice(0, 6);
        document.getElementById('iconToggleStudentPass').className = 'bi bi-eye-slash';

        const modalEl = document.getElementById('resetPasswordStudentModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    function fillStudentDefaultPassword() {
        const passInput = document.getElementById('newStudentPassword');
        if (passInput && currentResetStudentNisn) {
            passInput.value = currentResetStudentNisn.length >= 6 ? currentResetStudentNisn : (currentResetStudentNisn + '123456').slice(0, 6);
            passInput.focus();
        }
    }

    function toggleStudentPassVisibility() {
        const passInput = document.getElementById('newStudentPassword');
        const icon = document.getElementById('iconToggleStudentPass');
        if (passInput.type === 'password') {
            passInput.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            passInput.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    function copyStudentPassword() {
        const passInput = document.getElementById('newStudentPassword');
        if (!passInput || !passInput.value) return;
        navigator.clipboard.writeText(passInput.value).then(() => {
            const icon = document.getElementById('iconCopyStudentPass');
            icon.className = 'bi bi-check2 text-success';
            setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 1800);
        });
    }

    function openDeleteStudentModal(button) {
        const id = button.getAttribute('data-id');
        const nama = button.getAttribute('data-nama');
        const nisn = button.getAttribute('data-nisn');
        const kelas = button.getAttribute('data-kelas');

        document.getElementById('modalStudentUserId').value = id;
        document.getElementById('modalStudentNama').textContent = nama;
        document.getElementById('modalStudentNisn').textContent = nisn;
        document.getElementById('modalStudentKelas').textContent = kelas;

        const modalEl = document.getElementById('deleteStudentModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }
    </script>

    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
