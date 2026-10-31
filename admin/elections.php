<?php
// admin/elections.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Controllers/ElectionController.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';

AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'set_active') {
        ElectionController::handleSetActive();
    } else {
        ElectionController::handleSave();
    }
}

$elections = Election::getAll();

$pageTitle = "Periode Pemilihan - Panel Admin";
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
                <h3 class="fw-bold text-dark mb-1">Periode Pemilihan OSIS</h3>
                <p class="text-muted small mb-0">Kelola tahun ajaran, jadwal pelaksanaan, hak suara guru, dan status pemilihan.</p>
            </div>
            <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#electionModal" onclick="prepareCreateElection()">
                <i class="bi bi-plus-circle me-1"></i> Buat Periode Baru
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Status Utama</th>
                            <th>Nama Periode &amp; Tahun</th>
                            <th>Jadwal Pelaksanaan (WIB)</th>
                            <th>Status Pemilihan</th>
                            <th>Hak Suara Guru</th>
                            <th>Visibilitas Hasil</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($elections)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-2 d-block mb-1"></i> Belum ada periode pemilihan yang dibuat.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($elections as $el): ?>
                            <tr>
                                <td>
                                    <?php if ($el['is_active']): ?>
                                        <span class="badge bg-success px-2 py-1"><i class="bi bi-star-fill me-1"></i> AKTIF</span>
                                    <?php else: ?>
                                        <form action="<?= base_url('admin/elections.php') ?>" method="POST" class="d-inline">
                                            <?= CsrfHelper::getTokenField() ?>
                                            <input type="hidden" name="action" value="set_active">
                                            <input type="hidden" name="election_id" value="<?= $el['id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-outline-secondary" style="font-size: 0.75rem;" title="Jadikan pemilihan utama aktif">
                                                Jadikan Aktif
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-dark d-block"><?= htmlspecialchars($el['nama_periode'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="badge bg-light text-dark border">Tahun: <?= htmlspecialchars($el['tahun'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <small class="text-muted d-block">Mulai: <?= date('d M Y, H:i', strtotime($el['start_at'])) ?></small>
                                    <small class="text-muted d-block">Selesai: <?= date('d M Y, H:i', strtotime($el['end_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-primary text-uppercase"><?= $el['computed_status'] ?></span>
                                </td>
                                <td>
                                    <?php if ($el['teacher_can_vote']): ?>
                                        <span class="badge bg-success-subtle text-success">Guru Boleh Memilih</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">Hanya Siswa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-warning-subtle text-dark text-uppercase"><?= $el['results_visibility'] ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="<?= base_url('admin/candidates.php?election_id=' . $el['id']) ?>" class="btn btn-sm btn-outline-primary" title="Kelola Kandidat">
                                            <i class="bi bi-people"></i>
                                        </a>
                                        <a href="<?= base_url('admin/voters.php?election_id=' . $el['id']) ?>" class="btn btn-sm btn-outline-info" title="Kelola Pemilih (DPT)">
                                            <i class="bi bi-card-checklist"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-election"
                                                data-bs-toggle="modal"
                                                data-bs-target="#electionModal"
                                                onclick="prepareEditElection(this)"
                                                data-id="<?= $el['id'] ?>"
                                                data-nama="<?= htmlspecialchars($el['nama_periode'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-tahun="<?= htmlspecialchars($el['tahun'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-deskripsi="<?= htmlspecialchars($el['deskripsi'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-start="<?= date('Y-m-d\TH:i', strtotime($el['start_at'])) ?>"
                                                data-end="<?= date('Y-m-d\TH:i', strtotime($el['end_at'])) ?>"
                                                data-status="<?= $el['status'] ?>"
                                                data-active="<?= $el['is_active'] ?>"
                                                data-teacher="<?= $el['teacher_can_vote'] ?>"
                                                data-vis="<?= $el['results_visibility'] ?>"
                                                title="Edit Periode">
                                            <i class="bi bi-pencil"></i>
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

        <!-- MODAL FORM PERIODE PEMILIHAN -->
<div class="modal fade" id="electionModal" tabindex="-1" aria-labelledby="modalElectionTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header modal-header-premium p-3 px-4">
                <h5 class="modal-title fw-bold text-white mb-0" id="modalElectionTitle">Buat Periode Pemilihan Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/elections.php') ?>" method="POST" id="form-election">
                <?= CsrfHelper::getTokenField() ?>
                <input type="hidden" name="election_id" id="el-id" value="0">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="el-nama" class="form-label fw-semibold">Nama Periode Pemilihan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="el-nama" name="nama_periode" placeholder="Contoh: Pemilihan Ketua & Wakil Ketua OSIS 2026/2027" required>
                        </div>
                        <div class="col-md-4">
                            <label for="el-tahun" class="form-label fw-semibold">Tahun Ajaran <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="el-tahun" name="tahun" placeholder="2026/2027" required>
                        </div>

                        <div class="col-12">
                            <label for="el-deskripsi" class="form-label fw-semibold">Deskripsi Singkat</label>
                            <textarea class="form-control" id="el-deskripsi" name="deskripsi" rows="2" placeholder="Keterangan pemilihan..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="el-start" class="form-label fw-semibold">Waktu Mulai Pemilihan (WIB) <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="el-start" name="start_at" required>
                        </div>
                        <div class="col-md-6">
                            <label for="el-end" class="form-label fw-semibold">Waktu Selesai Pemilihan (WIB) <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="el-end" name="end_at" required>
                        </div>

                        <div class="col-md-4">
                            <label for="el-status" class="form-label fw-semibold">Status Awal</label>
                            <select class="form-select" id="el-status" name="status">
                                <option value="draft">Draft (Belum Dibuka)</option>
                                <option value="scheduled">Scheduled (Terjadwal)</option>
                                <option value="ongoing">Ongoing (Sedang Berlangsung)</option>
                                <option value="closed">Closed (Ditutup)</option>
                                <option value="results_published">Results Published (Diumumkan)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="el-vis" class="form-label fw-semibold">Publikasi Hasil</label>
                            <select class="form-select" id="el-vis" name="results_visibility">
                                <option value="hidden">Disembunyikan (Hidden)</option>
                                <option value="realtime">Realtime (Live Suara)</option>
                                <option value="final">Final (Pengumuman Akhir)</option>
                            </select>
                        </div>

                        <div class="col-md-4 d-flex align-items-center pt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="el-teacher" name="teacher_can_vote" value="1">
                                <label class="form-check-label fw-semibold" for="el-teacher">Dewan Guru Boleh Memilih</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="el-active" name="is_active" value="1" checked>
                                <label class="form-check-label fw-semibold" for="el-active">Jadikan sebagai Periode Pemilihan Aktif Utama Saat Ini</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom px-4 fw-bold">Simpan Periode</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function prepareCreateElection() {
    document.getElementById('modalElectionTitle').innerText = 'Buat Periode Pemilihan Baru';
    document.getElementById('el-id').value = '0';
    document.getElementById('el-nama').value = '';
    document.getElementById('el-tahun').value = '<?= date('Y') ?>/<?= date('Y') + 1 ?>';
    document.getElementById('el-deskripsi').value = '';
    document.getElementById('el-status').value = 'draft';
    document.getElementById('el-vis').value = 'hidden';
    document.getElementById('el-teacher').checked = false;
    document.getElementById('el-active').checked = true;
}

function prepareEditElection(btn) {
    if (!btn) return;
    document.getElementById('modalElectionTitle').innerText = 'Edit Periode Pemilihan';
    document.getElementById('el-id').value = btn.getAttribute('data-id') || '0';
    document.getElementById('el-nama').value = btn.getAttribute('data-nama') || '';
    document.getElementById('el-tahun').value = btn.getAttribute('data-tahun') || '';
    document.getElementById('el-deskripsi').value = btn.getAttribute('data-deskripsi') || '';
    document.getElementById('el-start').value = btn.getAttribute('data-start') || '';
    document.getElementById('el-end').value = btn.getAttribute('data-end') || '';
    document.getElementById('el-status').value = btn.getAttribute('data-status') || 'draft';
    document.getElementById('el-vis').value = btn.getAttribute('data-vis') || 'hidden';
    document.getElementById('el-teacher').checked = (btn.getAttribute('data-teacher') === '1');
    document.getElementById('el-active').checked = (btn.getAttribute('data-active') === '1');
}
</script>

        </main>
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
