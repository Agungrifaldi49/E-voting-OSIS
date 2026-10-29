<?php
// admin/verification.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Controllers/AdminController.php';
require_once dirname(__DIR__) . '/app/Models/Registry.php';

AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AdminController::verifyUser();
}

$pdo = Database::getConnection();

$statusFilter = trim($_GET['status'] ?? 'pending');
$allowedStatuses = ['pending', 'active', 'rejected', 'inactive', 'all'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'pending';
}

$sql = "
    SELECT u.id, u.username, u.email, u.role, u.account_status, u.rejection_reason, u.created_at,
           COALESCE(sp.nama_lengkap, tp.nama_lengkap, u.username) AS nama_lengkap,
           sp.kelas, sp.jurusan, tp.mata_pelajaran
    FROM users u
    LEFT JOIN student_profiles sp ON u.id = sp.user_id
    LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
    WHERE u.role != 'admin'
";

if ($statusFilter !== 'all') {
    $sql .= " AND u.account_status = '{$statusFilter}'";
}
$sql .= " ORDER BY u.id DESC";

$users = $pdo->query($sql)->fetchAll();

$pageTitle = "Verifikasi Registrasi - Panel Admin";
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
                <h3 class="fw-bold text-dark mb-1">Verifikasi Registrasi Pemilih</h3>
                <p class="text-muted small mb-0">Tinjau, setujui, tolak, atau kelola status akun siswa dan dewan guru.</p>
            </div>
            <!-- Filter Pills -->
            <div class="btn-group shadow-sm">
                <a href="<?= base_url('admin/verification.php?status=pending') ?>" class="btn btn-sm <?= ($statusFilter === 'pending') ? 'btn-warning fw-bold' : 'btn-outline-secondary' ?>">
                    Pending <span class="badge bg-dark ms-1"><?= (int)$pdo->query("SELECT COUNT(*) FROM users WHERE account_status = 'pending'")->fetchColumn() ?></span>
                </a>
                <a href="<?= base_url('admin/verification.php?status=active') ?>" class="btn btn-sm <?= ($statusFilter === 'active') ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>">
                    Aktif
                </a>
                <a href="<?= base_url('admin/verification.php?status=rejected') ?>" class="btn btn-sm <?= ($statusFilter === 'rejected') ? 'btn-danger fw-bold' : 'btn-outline-secondary' ?>">
                    Ditolak
                </a>
                <a href="<?= base_url('admin/verification.php?status=all') ?>" class="btn btn-sm <?= ($statusFilter === 'all') ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
                    Semua Status
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Identitas Akun</th>
                            <th>Nama Lengkap</th>
                            <th>Role &amp; Data Sekolah</th>
                            <th>Validasi Master Data</th>
                            <th>Status Akun</th>
                            <th class="text-center">Tindakan Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                                    Tidak ada data pengguna pada filter status ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1;
                            foreach ($users as $u): 
                                $isOfficial = false;
                                if ($u['role'] === 'siswa') {
                                    $isOfficial = (bool)Registry::checkStudent($u['username']);
                                } else {
                                    $isOfficial = (bool)Registry::checkTeacher($u['username']);
                                }
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td>
                                    <strong class="text-dark"><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if (!empty($u['email'])): ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold text-dark">
                                    <?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="badge <?= $u['role'] === 'siswa' ? 'bg-primary' : 'bg-info' ?> text-uppercase mb-1">
                                        <?= $u['role'] ?>
                                    </span>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars($u['kelas'] ?? ($u['mata_pelajaran'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                        <?= !empty($u['jurusan']) ? ' - ' . htmlspecialchars($u['jurusan'], ENT_QUOTES, 'UTF-8') : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isOfficial): ?>
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25">
                                            <i class="bi bi-patch-check-fill me-1"></i> Cocok Data Master
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25">
                                            <i class="bi bi-question-circle me-1"></i> Belum di Master
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['account_status'] === 'active'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php elseif ($u['account_status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                                    <?php elseif ($u['account_status'] === 'rejected'): ?>
                                        <span class="badge bg-danger" title="<?= htmlspecialchars($u['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                            Ditolak
                                        </span>
                                        <?php if (!empty($u['rejection_reason'])): ?>
                                            <small class="text-danger d-block" style="font-size: 0.72rem;"><?= htmlspecialchars($u['rejection_reason'], ENT_QUOTES, 'UTF-8') ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?php if ($u['account_status'] === 'pending'): ?>
                                            <!-- Approve Form -->
                                            <form action="<?= base_url('admin/verification.php') ?>" method="POST" class="d-inline">
                                                <?= CsrfHelper::getTokenField() ?>
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="verification_action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success fw-semibold" title="Setujui Akun">
                                                    <i class="bi bi-check-lg me-1"></i> Setujui
                                                </button>
                                            </form>

                                            <!-- Reject Trigger Modal -->
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-reject-modal"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectReasonModal"
                                                    onclick="prepareReject(this)"
                                                    data-id="<?= $u['id'] ?>"
                                                    data-name="<?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                                    title="Tolak Registrasi">
                                                <i class="bi bi-x-lg me-1"></i> Tolak
                                            </button>
                                        <?php elseif ($u['account_status'] === 'active'): ?>
                                            <form action="<?= base_url('admin/verification.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Nonaktifkan akun ini? Pengguna tidak akan bisa login.');">
                                                <?= CsrfHelper::getTokenField() ?>
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="verification_action" value="deactivate">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Nonaktifkan Akun">
                                                    <i class="bi bi-pause-circle me-1"></i> Nonaktifkan
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="<?= base_url('admin/verification.php') ?>" method="POST" class="d-inline">
                                                <?= CsrfHelper::getTokenField() ?>
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="verification_action" value="activate">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Aktifkan Kembali">
                                                    <i class="bi bi-arrow-repeat me-1"></i> Aktifkan
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL TOLAK REGISTRASI DENGAN ALASAN -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-labelledby="rejectModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="rejectModalTitle">Tolak Pendaftaran Akun</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/verification.php') ?>" method="POST">
                <?= CsrfHelper::getTokenField() ?>
                <input type="hidden" name="user_id" id="reject-user-id" value="">
                <input type="hidden" name="verification_action" value="reject">

                <div class="modal-body p-4">
                    <p class="mb-3">
                        Anda akan menolak pendaftaran akun: <strong class="text-danger" id="reject-user-name"></strong>.
                    </p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control"
                                  id="rejection_reason"
                                  name="rejection_reason"
                                  rows="3"
                                  placeholder="Contoh: NISN tidak terdaftar di database sekolah / Identitas kelas tidak valid"
                                  required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">Konfirmasi Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function prepareReject(btn) {
    if (!btn) return;
    const uid = btn.getAttribute('data-id');
    const uname = btn.getAttribute('data-name');
    const userIdInput = document.getElementById('reject-user-id');
    const userNameSpan = document.getElementById('reject-user-name');
    if (userIdInput) userIdInput.value = uid;
    if (userNameSpan) userNameSpan.innerText = uname;
}
</script>

        </main>
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
