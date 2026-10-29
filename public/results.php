<?php
// public/results.php
// Halaman Hasil Pemilihan OSIS
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Candidate.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';

$activeElection = Election::getActive();
$candidates = [];
$turnout = null;
$results = null;

if ($activeElection) {
    $candidates = Candidate::getByElectionId($activeElection['id'], true);
    $turnout = Vote::getParticipationStats($activeElection['id']);
    $results = Vote::getResults($activeElection['id']);
}

$isAdmin = (AuthHelper::check() && AuthHelper::role() === 'admin');
$pageTitle = "Hasil Pemilihan - E-Voting OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
require_once BASE_PATH . '/app/Views/layouts/navbar.php';
require_once BASE_PATH . '/app/Views/layouts/alerts.php';
?>

<div class="container py-5">
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8 text-center">
            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill text-uppercase mb-2">
                <i class="bi bi-bar-chart-fill me-1"></i> Rekapitulasi Suara
            </span>
            <h2 class="display-6 fw-bold text-dark">Hasil Pemilihan Ketua & Wakil Ketua OSIS</h2>
            <p class="text-muted">
                <?= $activeElection ? htmlspecialchars($activeElection['nama_periode'], ENT_QUOTES, 'UTF-8') : 'SMK Muthia Harapan Cicalengka' ?>
            </p>
        </div>
    </div>

    <?php if (!$activeElection || ($activeElection['results_visibility'] === 'hidden' && !$isAdmin)): ?>
        <div class="results-card text-center py-5">
            <div class="p-4 rounded-circle bg-secondary bg-opacity-10 text-secondary d-inline-flex mb-3">
                <i class="bi bi-shield-lock-fill display-3"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">Hasil Perolehan Suara Belum Dipublikasikan</h4>
            <p class="text-muted max-w-xl mx-auto mb-4">
                Panitia Pemilihan OSIS SMK Muthia Harapan Cicalengka mengatur hasil pemilihan ini dalam mode tertutup selama proses pemungutan suara berlangsung. Rekapitulasi suara akan diumumkan secara resmi setelah seluruh tahapan pemungutan suara berakhir.
            </p>
            <a href="<?= base_url('index.php') ?>" class="btn btn-outline-primary px-4">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
            </a>
        </div>
    <?php else: ?>
        <div class="results-card">
            <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-4 border-bottom gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="live-pulse"></span>
                    <h4 class="mb-0 fw-bold">Live Rekapitulasi Suara</h4>
                    <span class="badge bg-primary text-uppercase"><?= $activeElection['results_visibility'] ?></span>
                </div>
                <div class="text-md-end">
                    <small class="text-muted d-block">Terakhir diperbarui:</small>
                    <span class="fw-semibold text-dark small" id="results-last-updated">
                        <?= $results['updated_at_formatted'] ?? 'Baru saja' ?>
                    </span>
                </div>
            </div>

            <!-- Participation Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-3 bg-light border text-center">
                        <small class="text-muted d-block">Total DPT (Pemilih)</small>
                        <h4 class="fw-bold text-dark mb-0" id="stat-total-eligible"><?= $turnout['total_eligible'] ?? 0 ?></h4>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-3 bg-light border text-center">
                        <small class="text-muted d-block">Suara Masuk</small>
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
                <!-- Canvas Chart.js -->
                <div class="col-lg-7">
                    <div style="height: 350px; position: relative;">
                        <canvas id="liveResultsChart"
                                data-election-id="<?= $activeElection['id'] ?>"
                                data-api-url="<?= base_url('api/results.php?election_id=' . $activeElection['id']) ?>"></canvas>
                    </div>
                </div>

                <!-- Per Candidate Breakdown -->
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
                                    No. <?= sprintf('%02d', $c['nomor_urut']) ?> - <?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?> &amp; <?= htmlspecialchars($c['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>
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
</div>

<?php require_once BASE_PATH . '/app/Views/layouts/footer.php'; ?>
