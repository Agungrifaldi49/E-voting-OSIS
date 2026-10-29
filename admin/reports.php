<?php
// admin/reports.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Controllers/ResultController.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Candidate.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';

AuthHelper::requireRole('admin');

$electionId = (int)($_GET['election_id'] ?? 0);
if (!$electionId) {
    $active = Election::getActive();
    $electionId = $active ? (int)$active['id'] : 0;
}

// Export CSV handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    ResultController::exportCsv($electionId);
}

$allElections = Election::getAll();
$currentElection = Election::getById($electionId);
$turnout = $electionId ? Vote::getParticipationStats($electionId) : null;
$results = $electionId ? Vote::getResults($electionId) : null;

$pageTitle = "Laporan Resmi Pemilihan - Panel Admin";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<style>
@media print {
    .sidebar, .no-print, .dashboard-topbar, .dashboard-footer {
        display: none !important;
    }
    .dashboard-wrapper {
        display: block !important;
    }
    .dashboard-main-area {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .dashboard-content {
        padding: 0 !important;
        background: white !important;
    }
    .report-paper {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
}
</style>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/admin_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <!-- Action Bar (No Print) -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 no-print">
            <div>
                <h3 class="fw-bold text-dark mb-1">Laporan Resmi Hasil Pemilihan OSIS</h3>
                <p class="text-muted small mb-0">Cetak berita acara atau unduh berkas rekapitulasi pemilihan dalam format CSV.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Cetak / Simpan PDF
                </button>
                <a href="<?= base_url('admin/reports.php?election_id=' . $electionId . '&export=csv') ?>" class="btn btn-success btn-sm fw-semibold">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Unduh Laporan CSV
                </a>
            </div>
        </div>

        <!-- Period Selector (No Print) -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 no-print">
            <form action="<?= base_url('admin/reports.php') ?>" method="GET" class="row align-items-center g-2">
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

        <!-- Printable Formal Report Paper -->
        <div class="card border-0 shadow-sm rounded-4 p-5 report-paper bg-white">
            <!-- Kop Surat Sekolah -->
            <div class="text-center pb-4 mb-4 border-bottom border-dark border-2">
                <div class="d-flex justify-content-center align-items-center gap-3 mb-2">
                    <img src="<?= app_logo() ?>" alt="Logo" width="70" height="70" class="object-fit-contain">
                    <div>
                        <h4 class="fw-bold text-dark text-uppercase mb-0"><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></h4>
                        <h6 class="fw-bold text-secondary text-uppercase mb-1">ORGANISASI SISWA INTRA SEKOLAH (OSIS)</h6>
                        <small class="text-muted"><?= htmlspecialchars(app_setting('school_address', 'Jl. Babakan Peuteuy No.300, Babakan Peuteuy, Kec. Cicalengka, Kabupaten Bandung, Jawa Barat 40395'), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                </div>
                <div class="mt-2 text-uppercase fw-bold text-decoration-underline" style="letter-spacing: 1px;">
                    BERITA ACARA REKAPITULASI HASIL PEMUNGUTAN SUARA
                </div>
                <small class="text-muted">Nomor: 001/PAN-OSIS/E-VOTING/<?= date('Y') ?></small>
            </div>

            <?php if ($currentElection): ?>
            <!-- Election Meta -->
            <div class="mb-4">
                <table class="table table-sm table-borderless small mb-0">
                    <tr>
                        <td style="width: 200px;"><strong>Nama Pemilihan</strong></td>
                        <td>: <?= htmlspecialchars($currentElection['nama_periode'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tahun Ajaran</strong></td>
                        <td>: <?= htmlspecialchars($currentElection['tahun'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Waktu Pemungutan Suara</strong></td>
                        <td>: <?= date('d F Y, H:i', strtotime($currentElection['start_at'])) ?> s.d. <?= date('d F Y, H:i', strtotime($currentElection['end_at'])) ?> WIB</td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal Rekapitulasi Laporan</strong></td>
                        <td>: <?= date('d F Y, H:i:s') ?> WIB</td>
                    </tr>
                </table>
            </div>

            <!-- Turnout Summary -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark text-uppercase mb-2">A. RINGKASAN TINGKAT PARTISIPASI PEMILIH</h6>
                <table class="table table-bordered table-sm align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Kategori Data Pemilih</th>
                            <th class="text-center" style="width: 160px;">Jumlah</th>
                            <th class="text-center" style="width: 160px;">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Pemilih Berhak Terdaftar (DPT)</td>
                            <td class="text-center fw-bold"><?= $turnout['total_eligible'] ?></td>
                            <td class="text-center">100.00%</td>
                        </tr>
                        <tr>
                            <td>Total Pemilih Menggunakan Hak Suara</td>
                            <td class="text-center fw-bold text-success"><?= $turnout['total_voted'] ?></td>
                            <td class="text-center fw-bold text-success"><?= $turnout['turnout_pct'] ?>%</td>
                        </tr>
                        <tr>
                            <td>Total Pemilih Belum Memilih / Golput</td>
                            <td class="text-center fw-bold text-secondary"><?= $turnout['total_unvoted'] ?></td>
                            <td class="text-center"><?= $turnout['total_eligible'] > 0 ? round(($turnout['total_unvoted'] / $turnout['total_eligible']) * 100, 2) : 0 ?>%</td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-bold">Total Surat Suara Sah Masuk</td>
                            <td class="text-center fw-bold"><?= $results['total_suara_sah'] ?></td>
                            <td class="text-center">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Candidates Vote Table -->
            <div class="mb-5">
                <h6 class="fw-bold text-dark text-uppercase mb-2">B. REKAPITULASI PEROLEHAN SUARA PASANGAN CALON</h6>
                <table class="table table-bordered table-sm align-middle small">
                    <thead class="table-light text-center">
                        <tr>
                            <th style="width: 80px;">No. Urut</th>
                            <th>Calon Ketua OSIS</th>
                            <th>Calon Wakil Ketua OSIS</th>
                            <th style="width: 140px;">Perolehan Suara</th>
                            <th style="width: 140px;">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['candidates'] as $rc): ?>
                        <tr>
                            <td class="text-center fw-bold fs-6"><?= sprintf('%02d', $rc['nomor_urut']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($rc['nama_ketua'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small class="text-muted d-block">Kelas: <?= htmlspecialchars($rc['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($rc['nama_wakil'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small class="text-muted d-block">Kelas: <?= htmlspecialchars($rc['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td class="text-center fw-bold fs-6 text-primary"><?= $rc['total_suara'] ?> Suara</td>
                            <td class="text-center fw-bold fs-6"><?= $rc['persentase'] ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Signatures Section -->
            <div class="row text-center mt-5 pt-4">
                <div class="col-6">
                    <small class="text-muted">Mengetahui,</small>
                    <div class="fw-bold text-dark mt-1">Ketua Panitia Pemilihan OSIS</div>
                    <div style="height: 70px;"></div>
                    <div class="fw-bold text-decoration-underline"><?= htmlspecialchars(app_setting('ketua_panitia_nama', 'Drs. H. Ahmad Fauzi, M.Pd.'), ENT_QUOTES, 'UTF-8') ?></div>
                    <small class="text-muted">NIP. <?= htmlspecialchars(app_setting('ketua_panitia_nip', '198501152010011001'), ENT_QUOTES, 'UTF-8') ?></small>
                </div>
                <div class="col-6">
                    <small class="text-muted">Cicalengka, <?= date('d F Y') ?></small>
                    <div class="fw-bold text-dark mt-1">Kepala Sekolah <?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="height: 70px;"></div>
                    <div class="fw-bold text-decoration-underline"><?= htmlspecialchars(app_setting('kepala_sekolah_nama', 'H. Dadang Sulaeman, S.Pd., M.M.'), ENT_QUOTES, 'UTF-8') ?></div>
                    <small class="text-muted">NIP. <?= htmlspecialchars(app_setting('kepala_sekolah_nip', '197508102003121002'), ENT_QUOTES, 'UTF-8') ?></small>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
