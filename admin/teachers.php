<?php
// admin/teachers.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/LogHelper.php';
require_once dirname(__DIR__) . '/app/Models/User.php';

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
    if (!empty($_POST['return_p'])) {
        $returnParams['p'] = (int)$_POST['return_p'];
    }
    $returnUrl = base_url('admin/teachers.php') . (!empty($returnParams) ? '?' . http_build_query($returnParams) : '');

    if ($action === 'delete') {
        $res = User::deleteUser($userId);
        if ($res['success']) {
            $deleted = $res['user'];
            $nama = $deleted['nama_lengkap'] ?? $deleted['username'];
            $nip = $deleted['employee_identifier'] ?? $deleted['username'];
            set_flash('success', "Data guru <strong>" . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . "</strong> (NIP/Identitas: " . htmlspecialchars($nip, ENT_QUOTES, 'UTF-8') . ") berhasil dihapus secara permanen.");
        } else {
            set_flash('error', $res['error'] ?? 'Gagal menghapus data guru.');
        }
    } elseif ($action === 'toggle_status') {
        $newStatus = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';
        $user = User::findById($userId);
        if ($user && $user['role'] === 'guru') {
            User::updateStatus($userId, $newStatus);
            $nama = $user['nama_lengkap'] ?? $user['username'];
            $statusLabel = ($newStatus === 'active') ? 'diaktifkan kembali' : 'dinonaktifkan';
            set_flash('success', "Akun guru <strong>" . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . "</strong> berhasil {$statusLabel}.");
        } else {
            set_flash('error', 'Guru tidak ditemukan.');
        }
    } elseif ($action === 'reset_password') {
        $newPassword = trim($_POST['new_password'] ?? '');
        $user = User::findById($userId);
        if (!$user || $user['role'] !== 'guru') {
            set_flash('error', 'Data akun guru tidak ditemukan.');
        } elseif (strlen($newPassword) < 6) {
            set_flash('error', 'Kata sandi baru minimal harus terdiri dari 6 karakter.');
        } else {
            if (User::updatePassword($userId, $newPassword)) {
                $nama = $user['nama_lengkap'] ?? $user['username'];
                $nip = $user['employee_identifier'] ?? $user['username'];
                LogHelper::log('PASSWORD_RESET_TEACHER', "Admin mereset kata sandi akun guru: {$nama} (NIP/ID: {$nip}).");
                set_flash('success', "Kata sandi untuk guru <strong>" . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . "</strong> (NIP/Identitas: <strong>" . htmlspecialchars($nip, ENT_QUOTES, 'UTF-8') . "</strong>) berhasil diperbarui.");
            } else {
                set_flash('error', 'Gagal memperbarui kata sandi guru.');
            }
        }
    }

    header("Location: " . $returnUrl);
    exit;
}

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$totalTeachers = User::countAllTeachers($search);
$totalPages = ceil($totalTeachers / $limit);
$teachers = User::getAllTeachers($search, $limit, $offset);

$pageTitle = "Data Dewan Guru - Panel Admin";
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
                <h3 class="fw-bold text-dark mb-1">Data Dewan Guru</h3>
                <p class="text-muted small mb-0">Total: <strong><?= $totalTeachers ?></strong> guru terdaftar dalam sistem.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/import-teachers.php') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-person me-1"></i> Import CSV Guru
                </a>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <form action="<?= base_url('admin/teachers.php') ?>" method="GET" class="row g-2">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari nama guru atau NIP...">
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-custom px-4">Cari</button>
                    <?php if ($search): ?>
                        <a href="<?= base_url('admin/teachers.php') ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Teachers Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>NIP / Identitas</th>
                            <th>Nama Lengkap &amp; Gelar</th>
                            <th>Mata Pelajaran</th>
                            <th>Status Akun</th>
                            <th>Tgl Terdaftar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-2 text-muted d-block mb-1"></i>
                                    Data guru tidak ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = $offset + 1;
                            foreach ($teachers as $t): 
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($t['employee_identifier'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($t['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($t['mata_pelajaran'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ($t['account_status'] === 'active'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php elseif ($t['account_status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($t['account_status'] === 'rejected'): ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= date('d M Y', strtotime($t['created_at'])) ?></small></td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                        <!-- Toggle Status Form -->
                                        <form action="<?= base_url('admin/teachers.php') ?>" method="POST" class="d-inline mb-0">
                                            <?= CsrfHelper::getTokenField() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="return_q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="return_p" value="<?= $page ?>">
                                            <?php if ($t['account_status'] === 'active'): ?>
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

                                        <!-- Tombol Reset / Ubah Kata Sandi Guru -->
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Reset / Ubah Kata Sandi"
                                                data-bs-toggle="tooltip"
                                                data-id="<?= $t['id'] ?>"
                                                data-nama="<?= htmlspecialchars($t['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-nip="<?= htmlspecialchars($t['employee_identifier'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-mapel="<?= htmlspecialchars($t['mata_pelajaran'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="openResetPasswordTeacherModal(this)">
                                            <i class="bi bi-key-fill"></i>
                                        </button>

                                        <!-- Tombol Hapus Guru -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                title="Hapus Data Guru" 
                                                data-bs-toggle="tooltip"
                                                data-id="<?= $t['id'] ?>"
                                                data-nama="<?= htmlspecialchars($t['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-nip="<?= htmlspecialchars($t['employee_identifier'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-mapel="<?= htmlspecialchars($t['mata_pelajaran'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="openDeleteTeacherModal(this)">
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

            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?></small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= base_url("admin/teachers.php?q=" . urlencode($search) . "&p={$i}") ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- MODAL RESET / UBAH KATA SANDI DEWAN GURU -->
    <div class="modal fade" id="resetPasswordTeacherModal" tabindex="-1" aria-labelledby="resetPasswordTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-primary-subtle text-primary border-0 py-3">
                    <h5 class="modal-title fw-bold" id="resetPasswordTeacherModalLabel">
                        <i class="bi bi-key-fill me-2 text-primary"></i> Reset Kata Sandi Akun Dewan Guru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/teachers.php') ?>" method="POST" id="formResetPasswordTeacher">
                    <?= CsrfHelper::getTokenField() ?>
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="resetTeacherUserId" value="">
                    <input type="hidden" name="return_q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_p" value="<?= $page ?>">

                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">
                            Gunakan fitur ini apabila guru lupa kata sandi saat akan masuk ke bilik suara.
                        </p>

                        <!-- Teacher Highlight Box -->
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-circle-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; font-size: 1.1rem;">
                                    <i class="bi bi-person-badge-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-0" id="resetTeacherNama">-</h6>
                                    <div class="small text-muted mt-1">
                                        NIP / ID: <span class="fw-semibold text-dark" id="resetTeacherNip">-</span>
                                        <span class="mx-1">•</span>
                                        Mapel: <span class="badge bg-white border text-dark" id="resetTeacherMapel">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="newTeacherPassword" class="form-label fw-semibold small text-dark mb-0">Kata Sandi Baru <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" onclick="fillTeacherDefaultPassword()">
                                    <i class="bi bi-magic me-1"></i>Samakan dengan NIP/ID
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-lock-fill text-muted"></i></span>
                                <input type="text" class="form-control fw-semibold" id="newTeacherPassword" name="new_password" minlength="6" required placeholder="Minimal 6 karakter...">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleTeacherPassVisibility()" title="Tampilkan/Sembunyikan">
                                    <i class="bi bi-eye-slash" id="iconToggleTeacherPass"></i>
                                </button>
                                <button class="btn btn-outline-primary" type="button" onclick="copyTeacherPassword()" title="Salin Kata Sandi">
                                    <i class="bi bi-clipboard" id="iconCopyTeacherPass"></i> Salin
                                </button>
                            </div>
                            <div class="form-text text-xs">Minimal 6 karakter. Klik <strong>Samakan dengan NIP/ID</strong> untuk mengisi otomatis dengan NIP/Identitas guru.</div>
                        </div>

                        <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3 rounded-3 small mb-0 border-0 bg-info-subtle text-dark">
                            <i class="bi bi-info-circle-fill text-info flex-shrink-0 mt-1"></i>
                            <div>
                                Setelah disimpan, berikan kata sandi baru ini kepada guru yang bersangkutan melalui posko panitia atau helpdesk WhatsApp.
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

    <!-- MODAL KONFIRMASI HAPUS DEWAN GURU -->
    <div class="modal fade" id="deleteTeacherModal" tabindex="-1" aria-labelledby="deleteTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-danger-subtle text-danger border-0 py-3">
                    <h5 class="modal-title fw-bold" id="deleteTeacherModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i> Konfirmasi Hapus Data Dewan Guru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/teachers.php') ?>" method="POST" id="formDeleteTeacher">
                    <?= CsrfHelper::getTokenField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="modalTeacherUserId" value="">
                    <input type="hidden" name="return_q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_p" value="<?= $page ?>">

                    <div class="modal-body p-4">
                        <p class="text-muted mb-3">
                            Apakah Anda yakin ingin menghapus data dewan guru berikut secara permanen dari sistem?
                        </p>

                        <!-- Teacher Highlight Box -->
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-circle-sm bg-danger text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; font-size: 1.1rem;">
                                    <i class="bi bi-person-badge-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-0" id="modalTeacherNama">-</h6>
                                    <div class="small text-muted mt-1">
                                        NIP / ID: <span class="fw-semibold text-dark" id="modalTeacherNip">-</span>
                                        <span class="mx-1">•</span>
                                        Mapel: <span class="badge bg-white border text-dark" id="modalTeacherMapel">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start gap-2 py-2 px-3 rounded-3 small mb-0 border-0 bg-warning-subtle text-dark">
                            <i class="bi bi-info-circle-fill text-warning flex-shrink-0 mt-1"></i>
                            <div>
                                <strong>Perhatian:</strong> Menghapus akun guru ini akan mencabut status hak pilih, menghapus profil pengguna, dan mengembalikan status master data di pendaftaran menjadi belum terdaftar.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-0 py-3 px-4">
                        <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger px-4 fw-bold shadow-sm" id="btnSubmitDeleteTeacher">
                            <i class="bi bi-trash3-fill me-1"></i> Ya, Hapus Guru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    let currentResetTeacherNip = '';

    function openResetPasswordTeacherModal(button) {
        const id = button.getAttribute('data-id');
        const nama = button.getAttribute('data-nama');
        const nip = button.getAttribute('data-nip') || '';
        const mapel = button.getAttribute('data-mapel');

        currentResetTeacherNip = nip;
        document.getElementById('resetTeacherUserId').value = id;
        document.getElementById('resetTeacherNama').textContent = nama;
        document.getElementById('resetTeacherNip').textContent = nip;
        document.getElementById('resetTeacherMapel').textContent = mapel;

        const passInput = document.getElementById('newTeacherPassword');
        passInput.type = 'text';
        passInput.value = nip.length >= 6 ? nip : (nip + '123456').slice(0, 6);
        document.getElementById('iconToggleTeacherPass').className = 'bi bi-eye-slash';

        const modalEl = document.getElementById('resetPasswordTeacherModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    function fillTeacherDefaultPassword() {
        const passInput = document.getElementById('newTeacherPassword');
        if (passInput && currentResetTeacherNip) {
            passInput.value = currentResetTeacherNip.length >= 6 ? currentResetTeacherNip : (currentResetTeacherNip + '123456').slice(0, 6);
            passInput.focus();
        }
    }

    function toggleTeacherPassVisibility() {
        const passInput = document.getElementById('newTeacherPassword');
        const icon = document.getElementById('iconToggleTeacherPass');
        if (passInput.type === 'password') {
            passInput.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            passInput.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    function copyTeacherPassword() {
        const passInput = document.getElementById('newTeacherPassword');
        if (!passInput || !passInput.value) return;
        navigator.clipboard.writeText(passInput.value).then(() => {
            const icon = document.getElementById('iconCopyTeacherPass');
            icon.className = 'bi bi-check2 text-success';
            setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 1800);
        });
    }

    function openDeleteTeacherModal(button) {
        const id = button.getAttribute('data-id');
        const nama = button.getAttribute('data-nama');
        const nip = button.getAttribute('data-nip');
        const mapel = button.getAttribute('data-mapel');

        document.getElementById('modalTeacherUserId').value = id;
        document.getElementById('modalTeacherNama').textContent = nama;
        document.getElementById('modalTeacherNip').textContent = nip;
        document.getElementById('modalTeacherMapel').textContent = mapel;

        const modalEl = document.getElementById('deleteTeacherModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }
    </script>

    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
