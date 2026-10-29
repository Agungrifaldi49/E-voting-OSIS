<?php
// admin/profile.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/ValidationHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/LogHelper.php';
require_once dirname(__DIR__) . '/app/Models/User.php';

AuthHelper::requireRole('admin');
$currentUser = AuthHelper::user();
$userId = (int)$currentUser['id'];
$pdo = Database::getConnection();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfHelper::validateOrAbort();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $avatarFile = !empty($_FILES['avatar']['name']) ? $_FILES['avatar'] : null;

        $result = User::updateAdminProfile($userId, [
            'full_name' => $fullName,
            'username'  => $username,
            'email'     => $email
        ], $avatarFile);

        if ($result['success']) {
            set_flash('success', 'Data profil dan foto administrator berhasil diperbarui.');
        } else {
            set_flash('error', $result['error'] ?? 'Gagal memperbarui profil.');
        }
    } elseif ($action === 'remove_avatar') {
        $pdo->prepare("UPDATE users SET avatar = NULL, updated_at = NOW() WHERE id = :id")->execute([':id' => $userId]);
        $_SESSION['user']['avatar'] = null;
        LogHelper::log('ADMIN_AVATAR_REMOVED', "Administrator menghapus foto profil kustom.", $userId);
        set_flash('info', 'Foto profil berhasil dihapus dan dikembalikan ke avatar bawaan.');
    } elseif ($action === 'change_password') {
        $oldPass = $_POST['old_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $adminData = User::findById($userId);
        if (!password_verify($oldPass, $adminData['password_hash'])) {
            set_flash('error', 'Password lama administrator yang Anda masukkan salah.');
        } else {
            $pwErr = ValidationHelper::validatePassword($newPass, $confirmPass);
            if ($pwErr) {
                set_flash('error', $pwErr);
            } else {
                User::updatePassword($userId, $newPass);
                LogHelper::log('CHANGE_PASSWORD', "Administrator berhasil memperbarui password akunnya.", $userId);
                set_flash('success', 'Password administrator berhasil diperbarui. Silakan gunakan password baru ini pada login berikutnya.');
            }
        }
    }

    header("Location: " . base_url('admin/profile.php'));
    exit;
}

// Reload fresh user profile
$fullProfile = User::findById($userId);

// Fetch Admin Activity Stats
$logCountStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE user_id = :uid");
$logCountStmt->execute([':uid' => $userId]);
$totalAdminLogs = (int)$logCountStmt->fetchColumn();

// Fetch 5 Recent logs of this admin
$recentLogsStmt = $pdo->prepare("SELECT action, description, created_at FROM activity_logs WHERE user_id = :uid ORDER BY id DESC LIMIT 5");
$recentLogsStmt->execute([':uid' => $userId]);
$recentAdminLogs = $recentLogsStmt->fetchAll();

// Avatar resolution
$avatarUrl = !empty($fullProfile['avatar']) && file_exists(PUBLIC_PATH . '/' . ltrim($fullProfile['avatar'], '/'))
    ? asset(ltrim($fullProfile['avatar'], '/'))
    : asset('assets/images/default-avatar.svg');

$pageTitle = "Profil Administrator Lengkap - Panel Admin";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/admin_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <!-- Page Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold text-dark mb-1">Profil &amp; Keamanan Akun Administrator</h3>
                <p class="text-muted small mb-0">Kelola informasi nama lengkap, foto avatar, username, email, dan kata sandi akun super admin.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill border border-primary border-opacity-25">
                <i class="bi bi-shield-check me-1"></i> Sesi Aman Terverifikasi
            </span>
        </div>

        <div class="row g-4">
            
            <!-- Left Column: Admin Identity & Security Card -->
            <div class="col-lg-4">
                <!-- Profile Identity Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center mb-4">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <img src="<?= $avatarUrl ?>" alt="Avatar Admin" id="profileAvatarPreview" class="rounded-circle shadow-sm border border-3 border-primary border-opacity-25" style="width: 120px; height: 120px; object-fit: cover;">
                        <span class="position-absolute bottom-0 end-0 p-2 bg-success border border-2 border-white rounded-circle" title="Status: Online"></span>
                    </div>

                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($fullProfile['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small mb-2">@<?= htmlspecialchars($fullProfile['username'], ENT_QUOTES, 'UTF-8') ?></p>
                    
                    <div class="mb-3">
                        <span class="badge bg-primary text-uppercase px-3 py-1 rounded-pill fw-semibold">
                            <i class="bi bi-key-fill me-1"></i> Super Administrator
                        </span>
                    </div>

                    <?php if (!empty($fullProfile['avatar'])): ?>
                    <form action="<?= base_url('admin/profile.php') ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus foto profil kustom?')">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="action" value="remove_avatar">
                        <button type="submit" class="btn btn-outline-danger btn-xs">
                            <i class="bi bi-trash me-1"></i> Hapus Foto Kustom
                        </button>
                    </form>
                    <?php endif; ?>

                    <div class="border-top mt-4 pt-3 text-start small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Email:</span>
                            <span class="fw-semibold text-dark text-truncate" style="max-width: 180px;"><?= htmlspecialchars($fullProfile['email'] ?: 'admin@smkmuthiaharapan.sch.id', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Status Akun:</span>
                            <span class="badge bg-success">Aktif / Terverifikasi</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Terdaftar Sejak:</span>
                            <span class="text-dark"><?= date('d M Y', strtotime($fullProfile['created_at'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total Aktivitas:</span>
                            <span class="badge bg-dark"><?= $totalAdminLogs ?> Tindakan</span>
                        </div>
                    </div>
                </div>

                <!-- Session & Security Details -->
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock-fill text-warning me-2"></i>Informasi Keamanan Sesi</h6>
                    <div class="small text-muted">
                        <div class="mb-2">
                            <span class="d-block text-dark fw-semibold">IP Address Saat Ini:</span>
                            <code><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?></code>
                        </div>
                        <div class="mb-2">
                            <span class="d-block text-dark fw-semibold">Zona Waktu Sistem:</span>
                            Asia/Jakarta (WIB)
                        </div>
                        <div>
                            <span class="d-block text-dark fw-semibold">Browser Agent:</span>
                            <div class="text-xs text-truncate" title="<?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(substr($_SERVER['HTTP_USER_AGENT'] ?? 'Web Browser', 0, 40) . '...', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Edit Forms -->
            <div class="col-lg-8">
                
                <!-- 1. Edit Data Profil Form -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-gear text-primary me-2"></i>Perbarui Data Profil &amp; Foto</h5>
                    
                    <form action="<?= base_url('admin/profile.php') ?>" method="POST" enctype="multipart/form-data">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label fw-semibold small text-dark">Nama Lengkap Administrator</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($fullProfile['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>" required>
                                <div class="form-text text-xs">Nama ini tampil pada salam dashboard dan menu topbar.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="username" class="form-label fw-semibold small text-dark">Username Login</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($fullProfile['username'], ENT_QUOTES, 'UTF-8') ?>" required>
                                <div class="form-text text-xs">Digunakan untuk autentikasi masuk ke panel admin.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold small text-dark">Alamat Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($fullProfile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="admin@smkmuthiaharapan.sch.id">
                            </div>

                            <div class="col-md-6">
                                <label for="avatar" class="form-label fw-semibold small text-dark">Ganti Foto Profil / Avatar</label>
                                <input type="file" class="form-control" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp" onchange="previewAdminAvatar(this)">
                                <div class="form-text text-xs">Format: JPG, PNG, atau WEBP (Maks. 2MB).</div>
                            </div>
                        </div>

                        <div class="mt-4 pt-2 border-top">
                            <button type="submit" class="btn btn-primary-custom px-4 fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Simpan Perubahan Profil
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 2. Ganti Password Form -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-key-fill text-warning me-2"></i>Ganti Password Administrator</h5>
                    <p class="text-muted small mb-3">Gunakan kombinasi password yang kuat untuk menjaga integritas dan keamanan sistem pemilihan.</p>
                    
                    <form action="<?= base_url('admin/profile.php') ?>" method="POST">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label for="old_password" class="form-label fw-semibold small text-dark">Password Saat Ini</label>
                            <input type="password" class="form-control" id="old_password" name="old_password" required placeholder="Masukkan password lama Anda">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label fw-semibold small text-dark">Password Baru</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required placeholder="Minimal 6 karakter kombinasi">
                            </div>

                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold small text-dark">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required placeholder="Ulangi password baru">
                            </div>
                        </div>

                        <div class="mt-4 pt-2 border-top">
                            <button type="submit" class="btn btn-gold-custom px-4 fw-bold">
                                <i class="bi bi-lock-fill me-1"></i> Perbarui Password Admin
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 3. Riwayat Aktivitas Terakhir Saya -->
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-secondary me-2"></i>Riwayat Aktivitas Terakhir Anda</h6>
                        <a href="<?= base_url('admin/logs.php') ?>" class="small text-decoration-none">Lihat Semua Audit Log &rarr;</a>
                    </div>
                    
                    <?php if (empty($recentAdminLogs)): ?>
                        <p class="text-muted small mb-0">Belum ada riwayat aktivitas tercatat.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush small">
                            <?php foreach ($recentAdminLogs as $log): ?>
                            <div class="list-group-item px-0 py-2 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-secondary-subtle text-dark fw-semibold me-1"><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-dark"><?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <span class="text-muted text-xs ms-2"><?= date('d M, H:i', strtotime($log['created_at'])) ?> WIB</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

        </main>
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>

<script>
function previewAdminAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const preview = document.getElementById('profileAvatarPreview');
            if (preview) {
                preview.src = e.target.result;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
