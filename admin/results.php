<?php
// admin/results.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Controllers/ElectionController.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Candidate.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';

AuthHelper::requireRole('admin');

$electionId = (int)($_GET['election_id'] ?? 0);
if (!$electionId) {
    $active = Election::getActive();
    $electionId = $active ? (int)$active['id'] : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'set_visibility') {
        ElectionController::handleSetVisibility();
    }
}

$allElections = Election::getAll();
$currentElection = Election::getById($electionId);
$candidates = $electionId ? Candidate::getByElectionId($electionId) : [];
$results = $electionId ? Vote::getResults($electionId) : null;
$turnout = $electionId ? Vote::getParticipationStats($electionId) : null;

$pageTitle = "Monitoring & Publikasi Hasil - Panel Admin";
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
                <h3 class="fw-bold text-dark mb-1">Monitoring &amp; Pengaturan Publikasi Hasil</h3>
                <p class="text-muted small mb-0">Pantau suara masuk secara realtime dan atur visibilitas hasil kepada pemilih dan publik.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/reports.php?election_id=' . $electionId) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Cetak Laporan
                </a>
            </div>
        </div>

        <!-- Period Selector & Publication Toggle -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small text-dark"><i class="bi bi-filter me-1"></i> Periode Pemilihan:</label>
                    <form action="<?= base_url('admin/results.php') ?>" method="GET" id="form-select-election">
                        <select class="form-select" name="election_id" onchange="this.form.submit()">
                            <?php foreach ($allElections as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= ($e['id'] == $electionId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['nama_periode'], ENT_QUOTES, 'UTF-8') ?> (<?= $e['tahun'] ?>) <?= $e['is_active'] ? ' - [AKTIF]' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="col-md-7">
                    <?php if ($currentElection): ?>
                    <label class="form-label fw-semibold small text-dark"><i class="bi bi-eye me-1"></i> Mode Publikasi Hasil Pemilihan:</label>
                    <form action="<?= base_url('admin/results.php') ?>" method="POST" class="d-flex gap-2 align-items-center">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="action" value="set_visibility">
                        <input type="hidden" name="election_id" value="<?= $electionId ?>">

                        <select class="form-select" name="results_visibility">
                            <option value="hidden" <?= ($currentElection['results_visibility'] === 'hidden') ? 'selected' : '' ?>>
                                🔒 Mode 1: Hasil Disembunyikan (Hanya Admin yang dapat memantau)
                            </option>
                            <option value="realtime" <?= ($currentElection['results_visibility'] === 'realtime') ? 'selected' : '' ?>>
                                📡 Mode 2: Hasil Sementara Realtime (Publik dapat melihat pembaruan live)
                            </option>
                            <option value="final" <?= ($currentElection['results_visibility'] === 'final') ? 'selected' : '' ?>>
                                🏆 Mode 3: Hasil Final Diumumkan (Rekapitulasi resmi akhir pemilu)
                            </option>
                        </select>
                        <button type="submit" class="btn btn-primary-custom text-nowrap">
                            <i class="bi bi-check-lg me-1"></i> Simpan Mode
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($currentElection): ?>
        <!-- Realtime Result Card -->
        <div class="results-card mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-4 border-bottom gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="live-pulse"></span>
                    <h4 class="mb-0 fw-bold">Live Rekapitulasi Suara Pemilihan</h4>
                    <span class="badge bg-primary text-uppercase"><?= $currentElection['results_visibility'] ?></span>
                </div>
                <div class="text-md-end">
                    <small class="text-muted d-block">Terakhir diperbarui:</small>
                    <span class="fw-semibold text-dark small" id="results-last-updated">
                        <?= $results['updated_at_formatted'] ?? 'Baru saja' ?>
                    </span>
                </div>
            </div>

            <!-- Mini stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-3 bg-light border text-center">
                        <small class="text-muted d-block">Total DPT</small>
                        <h4 class="fw-bold text-dark mb-0" id="stat-total-eligible"><?= $turnout['total_eligible'] ?? 0 ?></h4>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-3 bg-light border text-center">
                        <small class="text-muted d-block">Total Suara Masuk</small>
                        <h4 class="fw-bold text-success mb-0" id="stat-total-voted"><?= $turnout['total_voted'] ?? 0 ?></h4>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-3 bg-light border text-center">
                        <small class="text-muted d-block">Belum Memilih</small>
                        <h4 class="fw-bold text-secondary mb-0" id="stat-total-unvoted"><?= $turnout['total_unvoted'] ?? 0 ?></h4>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-3 bg-light border text-center">
                        <small class="text-muted d-block">Tingkat Partisipasi</small>
                        <h4 class="fw-bold text-primary mb-0" id="stat-turnout-pct"><?= $turnout['turnout_pct'] ?? 0 ?>%</h4>
                    </div>
                </div>
            </div>

            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <div style="height: 340px; position: relative;">
                        <canvas id="liveResultsChart"
                                data-election-id="<?= $electionId ?>"
                                data-api-url="<?= base_url('api/results.php?election_id=' . $electionId) ?>"></canvas>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($candidates as $c): 
                            $cVotes = 0;
                            $cPct = 0;
                            if (!empty($results['candidates'])) {
                                foreach ($results['candidates'] as $rc) {
                                    if ($rc['id'] == $c['id']) {
                                        $cVotes = $rc['total_suara'];
                                        $cPct = $rc['persentase'];
                                        break;
                                    }
                                }
                            }
                        ?>
                        <div class="p-3 rounded-3 bg-white border shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark">
                                    No. <?= sprintf('%02d', $c['nomor_urut']) ?> - <?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="fw-bold text-primary" id="cand-votes-<?= $c['id'] ?>"><?= $cVotes ?> Suara</span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                     role="progressbar"
                                     id="cand-bar-<?= $c['id'] ?>"
                                     style="width: <?= $cPct ?>%;"
                                     aria-valuenow="<?= $cPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2 small">
                                <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($c['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($c['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="fw-bold text-dark" id="cand-pct-<?= $c['id'] ?>"><?= $cPct ?>%</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>
    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
