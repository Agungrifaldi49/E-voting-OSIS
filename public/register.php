<?php
// public/register.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Models/AcademicMaster.php';

$activeMajors = AcademicMaster::getAllMajors('', true);
$activeClasses = AcademicMaster::getAllClasses('', '', 0, true);

if (AuthHelper::check()) {
    $role = AuthHelper::role();
    header("Location: " . base_url($role . '/dashboard.php'));
    exit;
}

$type = isset($_GET['type']) && $_GET['type'] === 'guru' ? 'guru' : 'siswa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionType = $_POST['reg_type'] ?? 'siswa';
    if ($actionType === 'guru') {
        AuthController::handleTeacherRegister();
    } else {
        AuthController::handleStudentRegister();
    }
}

$pageTitle = "Registrasi Akun Pemilih - E-Voting OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
require_once BASE_PATH . '/app/Views/layouts/navbar.php';
require_once BASE_PATH . '/app/Views/layouts/alerts.php';
?>

<div class="container py-5 my-auto">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white text-center py-4 border-0">
                    <img src="<?= app_logo() ?>" alt="Logo" width="50" height="50" class="mb-2 rounded bg-white p-1 object-fit-contain">
                    <h4 class="fw-bold text-white mb-0">Registrasi Akun Pemilih</h4>
                    <p class="text-warning small mb-0"><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <!-- Nav Tabs Switcher -->
                    <ul class="nav nav-pills nav-fill mb-4 p-1 rounded bg-light border" id="registerTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= ($type === 'siswa') ? 'active fw-bold' : '' ?>" href="<?= base_url('register.php?type=siswa') ?>">
                                <i class="bi bi-mortarboard me-1"></i> Registrasi Siswa
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= ($type === 'guru') ? 'active fw-bold' : '' ?>" href="<?= base_url('register.php?type=guru') ?>">
                                <i class="bi bi-person-workspace me-1"></i> Registrasi Dewan Guru
                            </a>
                        </li>
                    </ul>

                    <?php if ($type === 'siswa'): ?>
                        <!-- Form Registrasi Siswa -->
                        <form action="<?= base_url('register.php') ?>" method="POST" id="form-register-siswa">
                            <?= CsrfHelper::getTokenField() ?>
                            <input type="hidden" name="reg_type" value="siswa">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nisn" class="form-label fw-semibold text-dark">
                                        NISN <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="nisn"
                                           name="nisn"
                                           placeholder="10 digit nomor NISN"
                                           maxlength="10"
                                           pattern="[0-9]{10}"
                                           required>
                                    <div class="form-text">Contoh: 0061234567</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="nama_lengkap" class="form-label fw-semibold text-dark">
                                        Nama Lengkap <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="nama_lengkap"
                                           name="nama_lengkap"
                                           placeholder="Nama lengkap sesuai data sekolah"
                                           required>
                                </div>

                                <div class="col-md-6">
                                    <label for="jurusan" class="form-label fw-semibold text-dark">
                                        Program Keahlian / Jurusan <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="jurusan" name="jurusan" required onchange="filterClassesByMajor()">
                                        <option value="">-- Pilih Jurusan --</option>
                                        <?php foreach ($activeMajors as $m): ?>
                                            <option value="<?= htmlspecialchars($m['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?>" data-major-id="<?= (int)$m['id'] ?>">
                                                <?= htmlspecialchars($m['nama_jurusan'] . ' (' . $m['kode_jurusan'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="kelas" class="form-label fw-semibold text-dark">
                                        Tingkat / Kelas <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="kelas" name="kelas" required onchange="syncMajorFromSelectedClass()">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php if (!empty($activeClasses)): ?>
                                            <?php foreach ($activeClasses as $c): ?>
                                                <option value="<?= htmlspecialchars($c['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-major-nama="<?= htmlspecialchars($c['nama_jurusan'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($c['nama_kelas'] . (!empty($c['nama_jurusan']) ? ' — ' . $c['nama_jurusan'] : ''), ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="X">Kelas X (Sepuluh)</option>
                                            <option value="XI">Kelas XI (Sebelas)</option>
                                            <option value="XII">Kelas XII (Dua Belas)</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="email" class="form-label fw-semibold text-dark">
                                        Email Siswa <small class="text-muted">(Opsional)</small>
                                    </label>
                                    <input type="email"
                                           class="form-control"
                                           id="email"
                                           name="email"
                                           placeholder="nama@email.com">
                                </div>

                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-semibold text-dark">
                                        Password <span class="text-danger">*</span>
                                    </label>
                                    <input type="password"
                                           class="form-control"
                                           id="password"
                                           name="password"
                                           placeholder="Minimal 6 karakter"
                                           minlength="6"
                                           required>
                                </div>

                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label fw-semibold text-dark">
                                        Konfirmasi Password <span class="text-danger">*</span>
                                    </label>
                                    <input type="password"
                                           class="form-control"
                                           id="confirm_password"
                                           name="confirm_password"
                                           placeholder="Ketik ulang password"
                                           minlength="6"
                                           required>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-3 border mt-4 small text-muted">
                                <i class="bi bi-shield-lock-fill text-warning me-1"></i>
                                Akun yang baru didaftarkan akan berstatus <strong>Pending</strong> dan diverifikasi terlebih dahulu oleh panitia pemilihan sekolah sebelum dapat digunakan untuk voting.
                            </div>

                            <button type="submit" class="btn btn-primary-custom btn-lg w-100 fw-bold mt-4" id="btn-submit-register-siswa">
                                <i class="bi bi-person-check-fill me-2"></i> Daftarkan Akun Siswa
                            </button>
                        </form>
                        <script>
                        function filterClassesByMajor() {
                            const jurusanSel = document.getElementById('jurusan');
                            const kelasSel = document.getElementById('kelas');
                            if (!jurusanSel || !kelasSel) return;
                            const selectedJurusan = jurusanSel.value;
                            Array.from(kelasSel.options).forEach(opt => {
                                if (!opt.value) return;
                                const optMajor = opt.getAttribute('data-major-nama') || '';
                                if (!selectedJurusan || !optMajor || optMajor === selectedJurusan) {
                                    opt.hidden = false;
                                } else {
                                    opt.hidden = true;
                                }
                            });
                            const currentOpt = kelasSel.options[kelasSel.selectedIndex];
                            if (currentOpt && currentOpt.hidden) {
                                kelasSel.value = '';
                            }
                        }

                        function syncMajorFromSelectedClass() {
                            const jurusanSel = document.getElementById('jurusan');
                            const kelasSel = document.getElementById('kelas');
                            if (!jurusanSel || !kelasSel) return;
                            const currentOpt = kelasSel.options[kelasSel.selectedIndex];
                            if (currentOpt) {
                                const majorNama = currentOpt.getAttribute('data-major-nama');
                                if (majorNama && !jurusanSel.value) {
                                    jurusanSel.value = majorNama;
                                }
                            }
                        }
                        </script>
                    <?php else: ?>
                        <!-- Form Registrasi Guru -->
                        <form action="<?= base_url('register.php') ?>" method="POST" id="form-register-guru">
                            <?= CsrfHelper::getTokenField() ?>
                            <input type="hidden" name="reg_type" value="guru">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="identifier" class="form-label fw-semibold text-dark">
                                        NIP / NUPTK / Kode Guru <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="identifier"
                                           name="identifier"
                                           placeholder="NIP atau identitas resmi guru"
                                           required>
                                </div>

                                <div class="col-md-6">
                                    <label for="nama_lengkap_guru" class="form-label fw-semibold text-dark">
                                        Nama Lengkap & Gelar <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="nama_lengkap_guru"
                                           name="nama_lengkap"
                                           placeholder="Nama lengkap beserta gelar"
                                           required>
                                </div>

                                <div class="col-md-6">
                                    <label for="mata_pelajaran" class="form-label fw-semibold text-dark">
                                        Mata Pelajaran yang Diampu
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="mata_pelajaran"
                                           name="mata_pelajaran"
                                           placeholder="Contoh: Matematika, Bahasa Indonesia">
                                </div>

                                <div class="col-md-6">
                                    <label for="email_guru" class="form-label fw-semibold text-dark">
                                        Alamat Email Aktif <small class="text-muted">(Opsional)</small>
                                    </label>
                                    <input type="email"
                                           class="form-control"
                                           id="email_guru"
                                           name="email"
                                           placeholder="guru@smkmuthiaharapan.sch.id">
                                </div>

                                <div class="col-md-6">
                                    <label for="pw_guru" class="form-label fw-semibold text-dark">
                                        Password <span class="text-danger">*</span>
                                    </label>
                                    <input type="password"
                                           class="form-control"
                                           id="pw_guru"
                                           name="password"
                                           placeholder="Minimal 6 karakter"
                                           minlength="6"
                                           required>
                                </div>

                                <div class="col-md-6">
                                    <label for="cpw_guru" class="form-label fw-semibold text-dark">
                                        Konfirmasi Password <span class="text-danger">*</span>
                                    </label>
                                    <input type="password"
                                           class="form-control"
                                           id="cpw_guru"
                                           name="confirm_password"
                                           placeholder="Ketik ulang password"
                                           minlength="6"
                                           required>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-3 border mt-4 small text-muted">
                                <i class="bi bi-info-circle-fill text-info me-1"></i>
                                Hak suara Dewan Guru ditentukan oleh pengaturan periode pemilihan aktif yang ditetapkan oleh Panitia Pemilihan OSIS.
                            </div>

                            <button type="submit" class="btn btn-primary-custom btn-lg w-100 fw-bold mt-4" id="btn-submit-register-guru">
                                <i class="bi bi-person-check-fill me-2"></i> Daftarkan Akun Guru
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="text-center pt-4 border-top mt-4">
                        <span class="text-muted small">Sudah memiliki akun?</span>
                        <a href="<?= base_url('login.php') ?>" class="fw-bold text-primary small ms-1">
                            Masuk di Sini
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/app/Views/layouts/footer.php'; ?>
