<?php
// admin/teachers.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
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
