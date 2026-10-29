<?php
// admin/voters.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Controllers/ElectionController.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';

AuthHelper::requireRole('admin');

$electionId = (int)($_GET['election_id'] ?? 0);
if (!$electionId) {
    $active = Election::getActive();
    $electionId = $active ? (int)$active['id'] : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'sync_dpt') {
        ElectionController::handleSyncEligibility();
    }
}

$allElections = Election::getAll();
$currentElection = Election::getById($electionId);
$turnout = $electionId ? Vote::getParticipationStats($electionId) : null;

// Get Voters
$pdo = Database::getConnection();
$voters = [];
if ($electionId) {
    $stmt = $pdo->prepare("
        SELECT ve.id as eligibility_id, ve.eligibility_status, ve.created_at as dpt_date,
               u.id as user_id, u.username, u.role,
               COALESCE(sp.nama_lengkap, tp.nama_lengkap, u.username) AS nama_lengkap,
               sp.kelas, sp.jurusan, tp.mata_pelajaran,
               vp.voted_at
        FROM voting_eligibility ve
        JOIN users u ON ve.user_id = u.id
        LEFT JOIN student_profiles sp ON u.id = sp.user_id
        LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
        LEFT JOIN voting_participation vp ON ve.election_id = vp.election_id AND ve.user_id = vp.user_id
        WHERE ve.election_id = :eid
        ORDER BY ve.id DESC
    ");
    $stmt->execute([':eid' => $electionId]);
    $voters = $stmt->fetchAll();
}

$pageTitle = "Daftar Pemilih Tetap (DPT) - Panel Admin";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/admin_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold text-dark mb-1">Pengaturan Hak Pilih &amp; DPT</h3>
                <p class="text-muted small mb-0">Daftar Pemilih Tetap (DPT) dan audit partisipasi hak suara per periode pemilihan.</p>
            </div>
            <?php if ($currentElection): ?>
                <form action="<?= base_url('admin/voters.php?election_id=' . $electionId) ?>" method="POST" class="d-inline" onsubmit="return confirm('Sinkronkan seluruh akun siswa dan guru aktif ke dalam DPT periode ini?');">
                    <?= CsrfHelper::getTokenField() ?>
                    <input type="hidden" name="action" value="sync_dpt">
                    <input type="hidden" name="election_id" value="<?= $electionId ?>">
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-arrow-repeat me-1"></i> Sinkronkan DPT dari Akun Aktif
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Period Selector -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <form action="<?= base_url('admin/voters.php') ?>" method="GET" class="row align-items-center g-2">
                <div class="col-auto">
                    <label class="fw-semibold text-dark small"><i class="bi bi-filter me-1"></i> Pilih Periode Pemilihan:</label>
                </div>
                <div class="col-md-5">
                    <select class="form-select form-select-sm" name="election_id" onchange="this.form.submit()">
                        <?php foreach ($allElections as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= ($e['id'] == $electionId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nama_periode'], ENT_QUOTES, 'UTF-8') ?> (<?= $e['tahun'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- Turnout Mini-Cards -->
        <?php if ($turnout): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white rounded-3 border shadow-sm text-center">
                    <small class="text-muted d-block">Total DPT Terdaftar</small>
                    <h4 class="fw-bold text-dark mb-0"><?= $turnout['total_eligible'] ?></h4>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white rounded-3 border shadow-sm text-center">
                    <small class="text-muted d-block">Sudah Menggunakan Hak Pilih</small>
                    <h4 class="fw-bold text-success mb-0"><?= $turnout['total_voted'] ?></h4>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white rounded-3 border shadow-sm text-center">
                    <small class="text-muted d-block">Belum Memilih</small>
                    <h4 class="fw-bold text-secondary mb-0"><?= $turnout['total_unvoted'] ?></h4>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white rounded-3 border shadow-sm text-center">
                    <small class="text-muted d-block">Partisipasi Suara</small>
                    <h4 class="fw-bold text-primary mb-0"><?= $turnout['turnout_pct'] ?>%</h4>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Voters Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>NISN / NIP</th>
                            <th>Nama Lengkap</th>
                            <th>Role &amp; Data Sekolah</th>
                            <th>Status DPT</th>
                            <th>Status Penggunaan Hak Suara</th>
                            <th>Waktu Memilih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($voters)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-2 d-block mb-1"></i>
                                    Belum ada pemilih yang dimasukkan ke dalam DPT untuk periode ini.<br>
                                    <small>Klik tombol <strong>"Sinkronkan DPT dari Akun Aktif"</strong> di atas untuk memasukkan pemilih.</small>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1;
                            foreach ($voters as $v): 
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($v['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($v['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge <?= $v['role'] === 'siswa' ? 'bg-primary' : 'bg-info' ?> text-uppercase mb-1">
                                        <?= $v['role'] ?>
                                    </span>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars($v['kelas'] ?? ($v['mata_pelajaran'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-check-circle me-1"></i> Terdaftar (Eligible)
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($v['voted_at'])): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-all me-1"></i> Sudah Memilih
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-clock me-1"></i> Belum Memilih
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= !empty($v['voted_at']) ? date('d M Y, H:i', strtotime($v['voted_at'])) . ' WIB' : '-' ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
