<?php
// admin/candidates.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Controllers/CandidateController.php';
require_once dirname(__DIR__) . '/app/Models/Candidate.php';
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
    if ($action === 'delete') {
        CandidateController::handleDelete();
    } else {
        CandidateController::handleSave();
    }
}

$allElections = Election::getAll();
$currentElection = Election::getById($electionId);
$candidates = $electionId ? Candidate::getByElectionId($electionId) : [];

// Menghitung statistik ringkasan paslon
$totalCandidates = count($candidates);
$publishedCount = 0;
$draftCount = 0;
foreach ($candidates as $c) {
    if ($c['publication_status'] === 'published') {
        $publishedCount++;
    } else {
        $draftCount++;
    }
}

// Rekapitulasi suara per paslon
$tallyData = $electionId ? Vote::getTally($electionId) : ['total_suara_sah' => 0, 'candidates' => []];
$totalVotesCast = (int)($tallyData['total_suara_sah'] ?? 0);
$votesMap = [];
if (!empty($tallyData['candidates'])) {
    foreach ($tallyData['candidates'] as $tc) {
        $votesMap[$tc['id']] = [
            'total' => (int)$tc['total_suara'],
            'persen' => $tc['persentase']
        ];
    }
}

$pageTitle = "Data Pasangan Calon - Panel Admin";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/admin_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <!-- Page Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                        <i class="bi bi-people-fill me-1"></i> Panel Kandidat
                    </span>
                    <span class="text-muted small">&bull;</span>
                    <span class="text-muted small">Periode: <strong><?= htmlspecialchars($currentElection['nama_periode'] ?? 'Tidak Dipilih', ENT_QUOTES, 'UTF-8') ?></strong></span>
                </div>
                <h3 class="fw-bold text-dark mb-1">Manajemen Pasangan Calon OSIS</h3>
                <p class="text-muted small mb-0">Kelola nomor urut resmi, foto bersama, identitas ketua &amp; wakil, visi-misi, serta visibilitas surat suara.</p>
            </div>
            <?php if ($currentElection): ?>
                <button type="button" class="btn btn-primary-custom px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#candidateModal" onclick="prepareCreateCandidate()">
                    <i class="bi bi-plus-circle-fill me-1"></i> Tambah Pasangan Calon
                </button>
            <?php endif; ?>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.35rem;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block fw-semibold" style="font-size: 0.75rem;">TOTAL PASLON</small>
                            <h4 class="fw-bold text-dark mb-0"><?= $totalCandidates ?> <span class="fs-6 fw-normal text-muted">Pasangan</span></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.35rem;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block fw-semibold" style="font-size: 0.75rem;">STATUS PUBLIKASI</small>
                            <h4 class="fw-bold text-success mb-0"><?= $publishedCount ?> <span class="fs-6 fw-normal text-muted">Tayang (<?= $draftCount ?> Draft)</span></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.35rem;">
                            <i class="bi bi-box2-heart-fill"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block fw-semibold" style="font-size: 0.75rem;">SUARA SAH TERKUMPUL</small>
                            <h4 class="fw-bold text-dark mb-0"><?= $totalVotesCast ?> <span class="fs-6 fw-normal text-muted">Suara</span></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.35rem;">
                            <i class="bi bi-calendar2-check-fill"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block fw-semibold" style="font-size: 0.75rem;">STATUS PEMILIHAN</small>
                            <h5 class="fw-bold text-dark text-uppercase mb-0"><?= $currentElection['computed_status'] ?? 'DRAFT' ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Control & View Switcher Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <!-- Election Period Selector -->
                <form action="<?= base_url('admin/candidates.php') ?>" method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-0">
                    <label class="fw-bold text-dark small mb-0"><i class="bi bi-filter me-1 text-primary"></i> Periode:</label>
                    <div style="min-width: 280px;">
                        <select class="form-select form-select-sm rounded-3" name="election_id" onchange="this.form.submit()">
                            <?php foreach ($allElections as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= ($e['id'] == $electionId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['nama_periode'], ENT_QUOTES, 'UTF-8') ?> (<?= $e['tahun'] ?>) <?= $e['is_active'] ? ' - [AKTIF]' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($currentElection): ?>
                        <a href="<?= base_url('admin/elections.php') ?>" class="btn btn-sm btn-outline-secondary rounded-3" title="Kelola Periode">
                            <i class="bi bi-gear me-1"></i> Atur Periode
                        </a>
                        <a href="<?= base_url('admin/voters.php?election_id=' . $electionId) ?>" class="btn btn-sm btn-outline-info rounded-3" title="Kelola DPT Pemilih">
                            <i class="bi bi-card-checklist me-1"></i> DPT Pemilih
                        </a>
                    <?php endif; ?>
                </form>

                <!-- View Mode Toggle -->
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted fw-semibold">Tampilan:</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="btnViewCards" onclick="switchCandidateView('cards')">
                            <i class="bi bi-grid-fill me-1"></i> Kartu Paslon
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="btnViewTable" onclick="switchCandidateView('table')">
                            <i class="bi bi-table me-1"></i> Tabel Komparasi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($candidates)): ?>
            <!-- Empty State -->
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted mb-4">
                <div class="mb-3">
                    <span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center p-4">
                        <i class="bi bi-people text-muted" style="font-size: 3.5rem;"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-dark mb-1">Belum Ada Pasangan Calon</h4>
                <p class="text-muted small mb-4 mx-auto" style="max-width: 480px;">
                    Belum ada pasangan calon yang didaftarkan untuk periode <strong><?= htmlspecialchars($currentElection['nama_periode'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>.
                    Silakan daftarkan pasangan calon ketua dan wakil ketua OSIS pertama Anda.
                </p>
                <?php if ($currentElection): ?>
                    <div>
                        <button type="button" class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#candidateModal" onclick="prepareCreateCandidate()">
                            <i class="bi bi-plus-circle me-1"></i> Daftarkan Paslon Baru Sekarang
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <!-- 1. TAMPILAN KARTU PASLON (GRID VIEW) -->
            <div id="candidatesCardsView" class="row g-4 mb-4">
                <?php foreach ($candidates as $c): 
                    $photoPasangan = !empty($c['foto_pasangan']) ? asset('uploads/candidates/' . $c['foto_pasangan']) : null;
                    $photoKetua = !empty($c['foto_ketua']) ? asset('uploads/candidates/' . $c['foto_ketua']) : asset('assets/images/default-avatar.svg');
                    $photoWakil = !empty($c['foto_wakil']) ? asset('uploads/candidates/' . $c['foto_wakil']) : asset('assets/images/default-avatar.svg');
                    $hasVotes = Candidate::hasVotes($c['id']);
                    $votesCount = $votesMap[$c['id']]['total'] ?? 0;
                    $votesPct = $votesMap[$c['id']]['persen'] ?? 0.00;
                ?>
                <div class="col-xl-6 col-lg-6">
                    <div class="candidate-card border-0 shadow-sm rounded-4 h-100 overflow-hidden d-flex flex-column bg-white">
                        <!-- Top Header with Dark Navy Gradient -->
                        <div class="candidate-card-header d-flex justify-content-between align-items-center p-3 px-4">
                            <div>
                                <span class="badge bg-warning text-dark fw-bold mb-1 shadow-sm">
                                    <i class="bi bi-award-fill me-1"></i> PASANGAN CALON OSIS
                                </span>
                                <h5 class="text-white fw-bold mb-0">Nomor Urut <?= sprintf('%02d', $c['nomor_urut']) ?></h5>
                                <small class="text-white-50" style="font-size: 0.78rem;">SMK Muthia Harapan Cicalengka</small>
                            </div>
                            <div class="nomor-urut-badge shadow">
                                <?= sprintf('%02d', $c['nomor_urut']) ?>
                            </div>
                        </div>

                        <!-- Photo Presentation Wrapper -->
                        <div class="candidate-photo-wrapper position-relative" style="height: 220px; background: #0f172a;">
                            <?php if ($photoPasangan): ?>
                                <img src="<?= $photoPasangan ?>" alt="Foto Bersama Paslon" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-white text-center py-1 small fw-semibold">
                                    <i class="bi bi-image me-1"></i> Foto Resmi Pasangan Calon
                                </div>
                            <?php else: ?>
                                <div class="dual-avatar-box">
                                    <div class="avatar-half position-relative">
                                        <img src="<?= $photoKetua ?>" alt="Ketua" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                        <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-white text-center py-1 small fw-bold">
                                            <i class="bi bi-person-fill me-1"></i> CALON KETUA
                                        </div>
                                    </div>
                                    <div class="avatar-half position-relative">
                                        <img src="<?= $photoWakil ?>" alt="Wakil" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                        <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-white text-center py-1 small fw-bold">
                                            <i class="bi bi-person-badge-fill me-1"></i> CALON WAKIL
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Body Content -->
                        <div class="candidate-body p-4 d-flex flex-column flex-grow-1">
                            <!-- Two Symmetrical Identity Boxes -->
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6">
                                    <div class="candidate-identity-box h-100">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="badge bg-primary px-2 py-1" style="font-size: 0.68rem;">KETUA OSIS</span>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($c['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="candidate-identity-box h-100">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="badge bg-info text-white px-2 py-1" style="font-size: 0.68rem;">WAKIL KETUA</span>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($c['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($c['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($c['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Visi Ringkas -->
                            <div class="mb-3">
                                <div class="small fw-semibold text-muted text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                    <i class="bi bi-quote text-primary me-1"></i> Visi Utama Pasangan Calon:
                                </div>
                                <div class="p-3 bg-light rounded-3 text-secondary fst-italic small border-start border-3 border-primary" style="min-height: 58px;">
                                    "<?= htmlspecialchars(mb_strimwidth($c['visi'], 0, 140, '...'), ENT_QUOTES, 'UTF-8') ?>"
                                </div>
                            </div>

                            <!-- Indikator Status & Perolehan Suara -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-auto pt-3 border-top">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <?php if ($c['publication_status'] === 'published'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> Tayang di Surat Suara
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                            <i class="bi bi-eye-slash-fill me-1"></i> Draft (Disembunyikan)
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($hasVotes): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" title="Perolehan suara saat ini">
                                            <i class="bi bi-box-seam-fill me-1"></i> <?= $votesCount ?> Suara (<?= $votesPct ?>%)
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Buttons -->
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-info btn-view-detail"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detailCandidateModal"
                                            onclick="showCandidateDetail(this)"
                                            data-nomor="<?= $c['nomor_urut'] ?>"
                                            data-ketua="<?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-kketua="<?= htmlspecialchars($c['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-wakil="<?= htmlspecialchars($c['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-kwakil="<?= htmlspecialchars($c['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-visi="<?= htmlspecialchars($c['visi'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-misi="<?= htmlspecialchars($c['misi'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-proker="<?= htmlspecialchars($c['program_kerja'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-foto-paslon="<?= $photoPasangan ?: '' ?>"
                                            data-foto-ketua="<?= $photoKetua ?: '' ?>"
                                            data-foto-wakil="<?= $photoWakil ?: '' ?>"
                                            title="Lihat Visi, Misi &amp; Program Kerja">
                                        <i class="bi bi-eye me-1"></i> Detail
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-cand"
                                            data-bs-toggle="modal"
                                            data-bs-target="#candidateModal"
                                            onclick="prepareEditCandidate(this)"
                                            data-id="<?= $c['id'] ?>"
                                            data-nomor="<?= $c['nomor_urut'] ?>"
                                            data-ketua="<?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-kketua="<?= htmlspecialchars($c['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-wakil="<?= htmlspecialchars($c['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-kwakil="<?= htmlspecialchars($c['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-visi="<?= htmlspecialchars($c['visi'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-misi="<?= htmlspecialchars($c['misi'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-proker="<?= htmlspecialchars($c['program_kerja'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-status="<?= $c['publication_status'] ?>"
                                            title="Edit Data Pasangan Calon">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </button>

                                    <?php if (!$hasVotes): ?>
                                        <form action="<?= base_url('admin/candidates.php?election_id=' . $electionId) ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pasangan calon Nomor <?= $c['nomor_urut'] ?> ini? Tindakan ini tidak dapat dibatalkan.');">
                                            <?= CsrfHelper::getTokenField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="election_id" value="<?= $electionId ?>">
                                            <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Pasangan Calon">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="Terkunci: Paslon ini telah memiliki suara sah yang masuk di bilik suara">
                                            <i class="bi bi-lock-fill"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 2. TAMPILAN TABEL KOMPARASI (TABLE VIEW) -->
            <div id="candidatesTableView" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 d-none">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;" class="text-center">No. Urut</th>
                                <th style="width: 90px;" class="text-center">Foto</th>
                                <th>Calon Ketua &amp; Kelas</th>
                                <th>Calon Wakil &amp; Kelas</th>
                                <th>Visi Singkat</th>
                                <th style="width: 120px;" class="text-center">Suara Sah</th>
                                <th style="width: 140px;" class="text-center">Status</th>
                                <th style="width: 150px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidates as $c): 
                                $photoPasangan = !empty($c['foto_pasangan']) ? asset('uploads/candidates/' . $c['foto_pasangan']) : null;
                                $photoKetua = !empty($c['foto_ketua']) ? asset('uploads/candidates/' . $c['foto_ketua']) : asset('assets/images/default-avatar.svg');
                                $photoWakil = !empty($c['foto_wakil']) ? asset('uploads/candidates/' . $c['foto_wakil']) : asset('assets/images/default-avatar.svg');
                                $hasVotes = Candidate::hasVotes($c['id']);
                                $votesCount = $votesMap[$c['id']]['total'] ?? 0;
                                $votesPct = $votesMap[$c['id']]['persen'] ?? 0.00;
                            ?>
                            <tr>
                                <td class="text-center">
                                    <span class="badge rounded-circle p-2 bg-warning text-dark fw-bold" style="width: 36px; height: 36px; line-height: 20px; font-size: 1rem;">
                                        <?= sprintf('%02d', $c['nomor_urut']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($photoPasangan): ?>
                                        <img src="<?= $photoPasangan ?>" alt="Paslon" class="rounded-3 border" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                    <?php else: ?>
                                        <img src="<?= $photoKetua ?>" alt="Ketua" class="rounded-circle border" style="width: 44px; height: 44px; object-fit: cover;" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-dark d-block"><?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($c['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <strong class="text-dark d-block"><?= htmlspecialchars($c['nama_wakil'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle"><?= htmlspecialchars($c['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <small class="text-muted fst-italic">"<?= htmlspecialchars(mb_strimwidth($c['visi'], 0, 85, '...'), ENT_QUOTES, 'UTF-8') ?>"</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border fw-bold px-2 py-1">
                                        <?= $votesCount ?> Suara
                                    </span>
                                    <small class="d-block text-muted" style="font-size: 0.72rem;"><?= $votesPct ?>%</small>
                                </td>
                                <td class="text-center">
                                    <?php if ($c['publication_status'] === 'published'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            Tayang
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                            Draft
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-info"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detailCandidateModal"
                                                onclick="showCandidateDetail(this)"
                                                data-nomor="<?= $c['nomor_urut'] ?>"
                                                data-ketua="<?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-kketua="<?= htmlspecialchars($c['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-wakil="<?= htmlspecialchars($c['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-kwakil="<?= htmlspecialchars($c['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-visi="<?= htmlspecialchars($c['visi'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-misi="<?= htmlspecialchars($c['misi'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-proker="<?= htmlspecialchars($c['program_kerja'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-foto-paslon="<?= $photoPasangan ?: '' ?>"
                                                data-foto-ketua="<?= $photoKetua ?: '' ?>"
                                                data-foto-wakil="<?= $photoWakil ?: '' ?>"
                                                title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-edit-cand"
                                                data-bs-toggle="modal"
                                                data-bs-target="#candidateModal"
                                                onclick="prepareEditCandidate(this)"
                                                data-id="<?= $c['id'] ?>"
                                                data-nomor="<?= $c['nomor_urut'] ?>"
                                                data-ketua="<?= htmlspecialchars($c['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-kketua="<?= htmlspecialchars($c['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-wakil="<?= htmlspecialchars($c['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-kwakil="<?= htmlspecialchars($c['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-visi="<?= htmlspecialchars($c['visi'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-misi="<?= htmlspecialchars($c['misi'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-proker="<?= htmlspecialchars($c['program_kerja'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-status="<?= $c['publication_status'] ?>"
                                                title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if (!$hasVotes): ?>
                                            <form action="<?= base_url('admin/candidates.php?election_id=' . $electionId) ?>" method="POST" class="d-inline" onsubmit="return confirm('Hapus pasangan calon No. <?= $c['nomor_urut'] ?>?');">
                                                <?= CsrfHelper::getTokenField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="election_id" value="<?= $electionId ?>">
                                                <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary" disabled title="Terkunci: Paslon sudah memiliki suara masuk">
                                                <i class="bi bi-lock-fill"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- MODAL FORM TAMBAH / EDIT PASANGAN CALON (LEBAR, SCROLLABLE & RAPI) -->
        <!-- ============================================================== -->
        <div class="modal fade" id="candidateModal" tabindex="-1" aria-labelledby="candModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 1200px;">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <!-- Header Modal Premium -->
                    <div class="modal-header modal-header-premium p-3 px-4 position-relative">
                        <div class="d-flex align-items-center gap-3">
                            <div id="modalHeaderBadge" class="nomor-urut-badge shadow-sm" style="width: 46px; height: 46px; font-size: 1.25rem;">
                                <i class="bi bi-person-plus-fill"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-warning text-dark fw-bold px-2 py-0" id="modalHeaderStatusBadge" style="font-size: 0.7rem;">
                                        FORMULIR PENDAFTARAN
                                    </span>
                                    <span class="text-white-50 small">&bull;</span>
                                    <span class="text-white-50 small" style="font-size: 0.75rem;">SMK Muthia Harapan Cicalengka</span>
                                </div>
                                <h4 class="modal-title fw-bold text-white mb-0" id="candModalTitle">Tambah Pasangan Calon Baru</h4>
                                <small class="text-white-50" style="font-size: 0.78rem;">
                                    <i class="bi bi-calendar3 me-1 text-warning"></i> Periode: <strong><?= htmlspecialchars($currentElection['nama_periode'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> (Tahun Ajaran: <?= htmlspecialchars($currentElection['tahun'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                                </small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form action="<?= base_url('admin/candidates.php?election_id=' . $electionId) ?>" method="POST" enctype="multipart/form-data" id="form-candidate">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="election_id" value="<?= $electionId ?>">
                        <input type="hidden" name="candidate_id" id="cand-id" value="0">

                        <div class="modal-body p-4 bg-light modal-scrollable-body">
                            <!-- Bagian 1: Nomor Urut & Status -->
                            <div class="modal-section-card bg-white shadow-sm mb-3">
                                <div class="modal-section-title">
                                    <i class="bi bi-tag-fill text-primary"></i> 1. Nomor Urut &amp; Status Surat Suara
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="cand-nomor" class="form-label fw-semibold small">Nomor Urut Resmi <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light fw-bold text-primary">NO.</span>
                                            <input type="number" class="form-control fw-bold" id="cand-nomor" name="nomor_urut" min="1" max="99" placeholder="Contoh: 1" required>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.72rem;">Nomor urut harus unik per periode pemilihan.</small>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="cand-status" class="form-label fw-semibold small">Status Publikasi</label>
                                        <select class="form-select" id="cand-status" name="publication_status">
                                            <option value="published">Published (Ditampilkan Resmi pada Surat Suara Digital)</option>
                                            <option value="draft">Draft (Disembunyikan Sementara dari Bilik Suara)</option>
                                        </select>
                                        <small class="text-muted" style="font-size: 0.72rem;">Hanya paslon 'Published' yang dapat dicoblos pemilih.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Bagian 2: Calon Ketua & Calon Wakil (Simetris 2 Kolom) -->
                            <div class="modal-section-card bg-white shadow-sm mb-3">
                                <div class="modal-section-title">
                                    <i class="bi bi-people-fill text-primary"></i> 2. Identitas Kandidat Ketua &amp; Wakil
                                </div>
                                <div class="row g-3">
                                    <!-- Kolom Calon Ketua -->
                                    <div class="col-md-6 border-end pe-md-3">
                                        <div class="p-2 px-3 bg-primary bg-opacity-10 rounded-3 text-primary fw-bold small mb-2 d-flex align-items-center gap-2">
                                            <i class="bi bi-person-fill"></i> CALON KETUA OSIS
                                        </div>
                                        <div class="mb-2">
                                            <label for="cand-ketua" class="form-label fw-semibold small mb-1">Nama Lengkap Ketua <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="cand-ketua" name="nama_ketua" placeholder="Nama lengkap calon ketua..." required>
                                        </div>
                                        <div class="mb-2">
                                            <label for="cand-kketua" class="form-label fw-semibold small mb-1">Kelas Ketua <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="cand-kketua" name="kelas_ketua" placeholder="Contoh: XI RPL 1" required>
                                        </div>
                                        <div class="mb-0">
                                            <label for="foto_ketua" class="form-label fw-semibold small mb-1">Foto Calon Ketua</label>
                                            <input type="file" class="form-control form-control-sm mb-1" id="foto_ketua" name="foto_ketua" accept="image/*" onchange="previewUploadImage(this, 'preview-foto-ketua')">
                                            <div id="preview-foto-ketua" class="candidate-photo-thumb-preview mt-2 d-none">
                                                <img src="" alt="Pratinjau Foto Ketua">
                                            </div>
                                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Format: JPG, PNG, WEBP (Maks 2MB). Kosongkan jika tidak diubah.</small>
                                        </div>
                                    </div>

                                    <!-- Kolom Calon Wakil -->
                                    <div class="col-md-6 ps-md-3">
                                        <div class="p-2 px-3 bg-info bg-opacity-10 rounded-3 text-info fw-bold small mb-2 d-flex align-items-center gap-2">
                                            <i class="bi bi-person-badge-fill"></i> CALON WAKIL KETUA OSIS
                                        </div>
                                        <div class="mb-2">
                                            <label for="cand-wakil" class="form-label fw-semibold small mb-1">Nama Lengkap Wakil <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="cand-wakil" name="nama_wakil" placeholder="Nama lengkap calon wakil..." required>
                                        </div>
                                        <div class="mb-2">
                                            <label for="cand-kwakil" class="form-label fw-semibold small mb-1">Kelas Wakil <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="cand-kwakil" name="kelas_wakil" placeholder="Contoh: XI TKJ 2" required>
                                        </div>
                                        <div class="mb-0">
                                            <label for="foto_wakil" class="form-label fw-semibold small mb-1">Foto Calon Wakil</label>
                                            <input type="file" class="form-control form-control-sm mb-1" id="foto_wakil" name="foto_wakil" accept="image/*" onchange="previewUploadImage(this, 'preview-foto-wakil')">
                                            <div id="preview-foto-wakil" class="candidate-photo-thumb-preview mt-2 d-none">
                                                <img src="" alt="Pratinjau Foto Wakil">
                                            </div>
                                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Format: JPG, PNG, WEBP (Maks 2MB). Kosongkan jika tidak diubah.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bagian 3: Foto Bersama (Opsional) -->
                            <div class="modal-section-card bg-white shadow-sm mb-3">
                                <div class="modal-section-title">
                                    <i class="bi bi-camera-fill text-primary"></i> 3. Foto Bersama Pasangan Calon (Opsional)
                                </div>
                                <div class="row align-items-center g-3">
                                    <div class="col-md-8">
                                        <label for="foto_pasangan" class="form-label fw-semibold small mb-1">Unggah Foto Bersama (Studio/Resmi)</label>
                                        <input type="file" class="form-control form-control-sm" id="foto_pasangan" name="foto_pasangan" accept="image/*" onchange="previewUploadImage(this, 'preview-foto-pasangan')">
                                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                            Jika diunggah, foto bersama ini akan ditampilkan secara utuh menggantikan split avatar di bilik suara. Kosongkan jika tidak diubah.
                                        </small>
                                    </div>
                                    <div class="col-md-4">
                                        <div id="preview-foto-pasangan" class="candidate-photo-thumb-preview d-none">
                                            <img src="" alt="Pratinjau Foto Bersama">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bagian 4: Visi, Misi & Program Kerja Unggulan (dengan Format Poin Lengkap: 1, a, *, •, -) -->
                            <div class="modal-section-card bg-white shadow-sm mb-0">
                                <div class="modal-section-title">
                                    <i class="bi bi-journal-text text-primary"></i> 4. Visi, Misi &amp; Program Kerja Unggulan
                                </div>
                                <div class="row g-3">
                                    <!-- Visi Paslon -->
                                    <div class="col-12">
                                        <label for="cand-visi" class="form-label fw-bold text-dark small mb-1">
                                            Visi Pasangan Calon <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="cand-visi" name="visi" rows="3" placeholder="Tuliskan rumusan visi pasangan calon..." required></textarea>
                                    </div>

                                    <!-- Misi Paslon dengan Toolbar Poin -->
                                    <div class="col-12">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                                            <label for="cand-misi" class="form-label fw-bold text-dark small mb-0">
                                                Misi Pasangan Calon <span class="text-danger">*</span>
                                            </label>
                                            <div class="d-flex flex-wrap align-items-center gap-1">
                                                <span class="small text-muted me-1" style="font-size: 0.72rem;">Bentuk Poin:</span>
                                                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-misi', 'number')" title="Format Poin Angka (1., 2., 3.)">
                                                    <strong>1. 2. 3.</strong>
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-info py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-misi', 'alpha')" title="Format Poin Huruf (a., b., c.)">
                                                    <strong>a. b. c.</strong>
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-misi', 'bullet')" title="Format Poin Bulat (•)">
                                                    &bull; Bulat
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-warning text-dark py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-misi', 'star')" title="Format Poin Bintang (*)">
                                                    * Bintang
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-misi', 'dash')" title="Format Poin Strip (-)">
                                                    - Strip
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-misi', 'clear')" title="Hapus Awalan Poin">
                                                    <i class="bi bi-eraser"></i> Hapus
                                                </button>
                                            </div>
                                        </div>
                                        <textarea class="form-control" id="cand-misi" name="misi" rows="5" placeholder="Tuliskan butir-butir misi paslon...&#10;Dapat menggunakan nomor (1., 2.), huruf (a., b.), bintang (*), atau strip (-)..." required onkeydown="handleSmartListKey(event, this)"></textarea>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-info-circle me-1 text-primary"></i> Tekan tombol poin di atas untuk format instan, atau ketik langsung dengan angka, huruf, atau simbol bintang (*). Tekan Enter untuk melanjutkan poin berikutnya otomatis.
                                        </small>
                                    </div>

                                    <!-- Program Kerja Unggulan dengan Toolbar Poin -->
                                    <div class="col-12">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                                            <label for="cand-proker" class="form-label fw-bold text-dark small mb-0">
                                                Program Kerja Unggulan
                                            </label>
                                            <div class="d-flex flex-wrap align-items-center gap-1">
                                                <span class="small text-muted me-1" style="font-size: 0.72rem;">Bentuk Poin:</span>
                                                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-proker', 'number')" title="Format Poin Angka (1., 2., 3.)">
                                                    <strong>1. 2. 3.</strong>
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-info py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-proker', 'alpha')" title="Format Poin Huruf (a., b., c.)">
                                                    <strong>a. b. c.</strong>
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-proker', 'bullet')" title="Format Poin Bulat (•)">
                                                    &bull; Bulat
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-warning text-dark py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-proker', 'star')" title="Format Poin Bintang (*)">
                                                    * Bintang
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-proker', 'dash')" title="Format Poin Strip (-)">
                                                    - Strip
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size: 0.75rem;" onclick="applyPointFormat('cand-proker', 'clear')" title="Hapus Awalan Poin">
                                                    <i class="bi bi-eraser"></i> Hapus
                                                </button>
                                            </div>
                                        </div>
                                        <textarea class="form-control" id="cand-proker" name="program_kerja" rows="5" placeholder="Contoh:&#10;1. Ruang Aspirasi Digital (SMK Voice)&#10;2. Pekan Kreativitas Siswa (Muthia Fest)&#10;3. Muthia Care: Aksi Sosial dan Peduli Lingkungan" onkeydown="handleSmartListKey(event, this)"></textarea>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-info-circle me-1 text-primary"></i> Tuliskan program unggulan dalam bentuk butir poin terstruktur untuk memudahkan pemilih membaca visi kerja paslon.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-white border-top p-3 px-4 d-flex justify-content-between align-items-center">
                            <span class="small text-muted" style="font-size: 0.75rem;">
                                <i class="bi bi-shield-check text-success me-1"></i> Data paslon divalidasi dan dicatat dalam audit log sistem.
                            </span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-1"></i> Batal
                                </button>
                                <button type="submit" class="btn btn-primary-custom px-4 fw-bold shadow-sm">
                                    <i class="bi bi-save me-1"></i> Simpan Pasangan Calon
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL PREVIEW DETAIL LENGKAP VISI, MISI & PROKER (LEBAR & SCROLLABLE) -->
        <!-- ============================================================== -->
        <div class="modal fade" id="detailCandidateModal" tabindex="-1" aria-labelledby="detailModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 1100px;">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header modal-header-premium p-3 px-4 position-relative">
                        <div class="d-flex align-items-center gap-3">
                            <span class="nomor-urut-badge shadow-sm" id="detail-nomor-badge" style="width: 44px; height: 44px; font-size: 1.25rem;">
                                01
                            </span>
                            <div>
                                <div class="badge bg-warning text-dark fw-bold px-2 py-0 mb-1" style="font-size: 0.7rem;">PROFIL RESMI PASLON</div>
                                <h4 class="modal-title fw-bold text-white mb-0" id="detailModalTitle">Profil Lengkap Pasangan Calon</h4>
                                <small class="text-white-50" id="detail-header-names">Nama Paslon</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4 bg-light modal-scrollable-body">
                        <!-- Identitas Kandidat Box -->
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
                            <div class="row align-items-center g-3">
                                <div class="col-md-6 border-end">
                                    <div class="d-flex align-items-center gap-3">
                                        <img id="detail-img-ketua" src="" alt="Ketua" class="rounded-circle border shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                                        <div>
                                            <span class="badge bg-primary px-2 py-1 mb-1" style="font-size: 0.68rem;">CALON KETUA OSIS</span>
                                            <h5 class="fw-bold text-dark mb-0" id="detail-nama-ketua">-</h5>
                                            <span class="badge bg-light text-dark border mt-1">Kelas: <strong id="detail-kelas-ketua">-</strong></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center gap-3">
                                        <img id="detail-img-wakil" src="" alt="Wakil" class="rounded-circle border shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                                        <div>
                                            <span class="badge bg-info text-white px-2 py-1 mb-1" style="font-size: 0.68rem;">CALON WAKIL KETUA OSIS</span>
                                            <h5 class="fw-bold text-dark mb-0" id="detail-nama-wakil">-</h5>
                                            <span class="badge bg-light text-dark border mt-1">Kelas: <strong id="detail-kelas-wakil">-</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visi Card -->
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-3">
                            <h6 class="fw-bold text-dark text-uppercase small mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-quote fs-5 text-primary"></i> Visi Utama
                            </h6>
                            <div class="p-3 bg-light rounded-3 text-secondary fst-italic border-start border-4 border-primary fs-6 lh-base" id="detail-visi-text">
                                -
                            </div>
                        </div>

                        <!-- Misi Card -->
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-3">
                            <h6 class="fw-bold text-dark text-uppercase small mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-list-check fs-5 text-success"></i> Misi &amp; Program Prioritas
                            </h6>
                            <div id="detail-misi-list">
                                -
                            </div>
                        </div>

                        <!-- Proker Card -->
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-0" id="detail-proker-card">
                            <h6 class="fw-bold text-dark text-uppercase small mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-stars fs-5 text-warning"></i> Program Kerja Unggulan
                            </h6>
                            <div id="detail-proker-list">
                                -
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-white border-top p-3 px-4">
                        <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        </main>
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>

<script>
// Helper untuk escape HTML pada render teks
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Toggle View Switcher: Cards vs Table
function switchCandidateView(mode) {
    const cardsView = document.getElementById('candidatesCardsView');
    const tableView = document.getElementById('candidatesTableView');
    const btnCards = document.getElementById('btnViewCards');
    const btnTable = document.getElementById('btnViewTable');

    if (!cardsView || !tableView) return;

    if (mode === 'table') {
        cardsView.classList.add('d-none');
        tableView.classList.remove('d-none');
        if (btnTable) btnTable.classList.add('active');
        if (btnCards) btnCards.classList.remove('active');
        localStorage.setItem('candidate_view_mode', 'table');
    } else {
        tableView.classList.add('d-none');
        cardsView.classList.remove('d-none');
        if (btnCards) btnCards.classList.add('active');
        if (btnTable) btnTable.classList.remove('active');
        localStorage.setItem('candidate_view_mode', 'cards');
    }
}

// Format Point Generator untuk Misi & Proker (1, a, •, *, -)
function applyPointFormat(textareaId, type) {
    const textarea = document.getElementById(textareaId);
    if (!textarea) return;

    const val = textarea.value;
    const lines = val.split('\n');
    const newLines = [];
    let counter = 1;
    let alphaCharCode = 97; // 'a'

    for (let i = 0; i < lines.length; i++) {
        let line = lines[i];
        // Strip awalan poin lama: 1., a., A., *, •, -, +, etc.
        let cleaned = line.replace(/^(\d+[\.\)]|[a-zA-Z][\.\)]|[\*\•\-\+])\s*/, '').trim();

        if (cleaned.length === 0) {
            newLines.push('');
            continue;
        }

        if (type === 'number') {
            newLines.push(counter + '. ' + cleaned);
            counter++;
        } else if (type === 'alpha') {
            const char = String.fromCharCode(alphaCharCode);
            newLines.push(char + '. ' + cleaned);
            alphaCharCode++;
            if (alphaCharCode > 122) alphaCharCode = 97;
        } else if (type === 'bullet') {
            newLines.push('• ' + cleaned);
        } else if (type === 'star') {
            newLines.push('* ' + cleaned);
        } else if (type === 'dash') {
            newLines.push('- ' + cleaned);
        } else if (type === 'clear') {
            newLines.push(cleaned);
        }
    }

    // Jika textarea masih kosong, berikan kerangka otomatis
    if (val.trim() === '' && type !== 'clear') {
        if (type === 'number') {
            textarea.value = '1. \n2. \n3. ';
        } else if (type === 'alpha') {
            textarea.value = 'a. \nb. \nc. ';
        } else if (type === 'bullet') {
            textarea.value = '• \n• \n• ';
        } else if (type === 'star') {
            textarea.value = '* \n* \n* ';
        } else if (type === 'dash') {
            textarea.value = '- \n- \n- ';
        }
    } else {
        textarea.value = newLines.join('\n');
    }
    textarea.focus();
}

// Handler Enter Cerdas: Melanjutkan penomoran / poin berikutnya secara otomatis
function handleSmartListKey(e, textarea) {
    if (e.key !== 'Enter') return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const value = textarea.value;

    // Cari awal baris saat ini
    const lastNewLine = value.lastIndexOf('\n', start - 1);
    const lineStart = lastNewLine === -1 ? 0 : lastNewLine + 1;
    const currentLine = value.substring(lineStart, start);

    // Cocokkan awalan poin
    const numMatch = currentLine.match(/^(\d+)([\.\)])\s*(.*)$/);
    const alphaMatch = currentLine.match(/^([a-zA-Z])([\.\)])\s*(.*)$/);
    const bulletMatch = currentLine.match(/^([\*\•\-\+])\s*(.*)$/);

    let nextPrefix = null;

    if (numMatch) {
        const num = parseInt(numMatch[1], 10);
        const sep = numMatch[2];
        const content = numMatch[3];
        if (content.trim() === '') {
            // Jika baris kosong, hapus awalan poin
            e.preventDefault();
            textarea.value = value.substring(0, lineStart) + value.substring(start);
            textarea.selectionStart = textarea.selectionEnd = lineStart;
            return;
        }
        nextPrefix = (num + 1) + sep + ' ';
    } else if (alphaMatch) {
        const charCode = alphaMatch[1].charCodeAt(0);
        const sep = alphaMatch[2];
        const content = alphaMatch[3];
        if (content.trim() === '') {
            e.preventDefault();
            textarea.value = value.substring(0, lineStart) + value.substring(start);
            textarea.selectionStart = textarea.selectionEnd = lineStart;
            return;
        }
        nextPrefix = String.fromCharCode(charCode + 1) + sep + ' ';
    } else if (bulletMatch) {
        const symbol = bulletMatch[1];
        const content = bulletMatch[2];
        if (content.trim() === '') {
            e.preventDefault();
            textarea.value = value.substring(0, lineStart) + value.substring(start);
            textarea.selectionStart = textarea.selectionEnd = lineStart;
            return;
        }
        nextPrefix = symbol + ' ';
    }

    if (nextPrefix) {
        e.preventDefault();
        const insertion = '\n' + nextPrefix;
        textarea.value = value.substring(0, start) + insertion + value.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + insertion.length;
    }
}

// Reset Form Tambah Paslon
function prepareCreateCandidate() {
    document.getElementById('modalHeaderBadge').innerHTML = '<i class="bi bi-person-plus-fill"></i>';
    document.getElementById('modalHeaderStatusBadge').innerText = 'FORMULIR PENDAFTARAN';
    document.getElementById('modalHeaderStatusBadge').className = 'badge bg-warning text-dark fw-bold px-2 py-0';

    document.getElementById('candModalTitle').innerText = 'Tambah Pasangan Calon Baru';
    document.getElementById('cand-id').value = '0';
    document.getElementById('cand-nomor').value = '';
    document.getElementById('cand-ketua').value = '';
    document.getElementById('cand-kketua').value = '';
    document.getElementById('cand-wakil').value = '';
    document.getElementById('cand-kwakil').value = '';
    document.getElementById('cand-visi').value = '';
    document.getElementById('cand-misi').value = '';
    document.getElementById('cand-proker').value = '';
    document.getElementById('cand-status').value = 'published';

    // Reset file inputs & live previews
    ['foto_ketua', 'foto_wakil', 'foto_pasangan'].forEach(id => {
        const input = document.getElementById(id);
        if (input) input.value = '';
    });
    ['preview-foto-ketua', 'preview-foto-wakil', 'preview-foto-pasangan'].forEach(id => {
        const preview = document.getElementById(id);
        if (preview) {
            preview.classList.add('d-none');
            const img = preview.querySelector('img');
            if (img) img.src = '';
        }
    });
}

// Prepare Edit Paslon
function prepareEditCandidate(btn) {
    if (!btn) return;
    const nomor = btn.getAttribute('data-nomor') || '0';
    const nomorPadded = String(nomor).padStart(2, '0');

    document.getElementById('modalHeaderBadge').innerText = nomorPadded;
    document.getElementById('modalHeaderStatusBadge').innerText = 'MODE EDIT PASLON NO. ' + nomorPadded;
    document.getElementById('modalHeaderStatusBadge').className = 'badge bg-info text-white fw-bold px-2 py-0';

    document.getElementById('candModalTitle').innerText = 'Edit Pasangan Calon No. ' + nomorPadded;
    document.getElementById('cand-id').value = btn.getAttribute('data-id') || '0';
    document.getElementById('cand-nomor').value = nomor;
    document.getElementById('cand-ketua').value = btn.getAttribute('data-ketua') || '';
    document.getElementById('cand-kketua').value = btn.getAttribute('data-kketua') || '';
    document.getElementById('cand-wakil').value = btn.getAttribute('data-wakil') || '';
    document.getElementById('cand-kwakil').value = btn.getAttribute('data-kwakil') || '';
    document.getElementById('cand-visi').value = btn.getAttribute('data-visi') || '';
    document.getElementById('cand-misi').value = btn.getAttribute('data-misi') || '';
    document.getElementById('cand-proker').value = btn.getAttribute('data-proker') || '';
    document.getElementById('cand-status').value = btn.getAttribute('data-status') || 'published';

    // Reset file inputs & live previews
    ['foto_ketua', 'foto_wakil', 'foto_pasangan'].forEach(id => {
        const input = document.getElementById(id);
        if (input) input.value = '';
    });
    ['preview-foto-ketua', 'preview-foto-wakil', 'preview-foto-pasangan'].forEach(id => {
        const preview = document.getElementById(id);
        if (preview) {
            preview.classList.add('d-none');
            const img = preview.querySelector('img');
            if (img) img.src = '';
        }
    });
}

// Preview Upload Image secara Instan
function previewUploadImage(input, previewContainerId) {
    const preview = document.getElementById(previewContainerId);
    if (!preview) return;
    const img = preview.querySelector('img');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (img) img.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.classList.add('d-none');
        if (img) img.src = '';
    }
}

// Render Beragam Format Poin (1, a, *, •, -) menjadi Elemen Visual yang Estetis
function renderFormattedPoints(rawText) {
    if (!rawText || !rawText.trim()) return '<span class="text-muted fst-italic">Belum ada keterangan butir yang dicatat.</span>';

    const lines = rawText.split('\n').map(l => l.trim()).filter(l => l.length > 0);
    if (lines.length === 0) return '<span class="text-muted fst-italic">Belum ada keterangan butir yang dicatat.</span>';

    let html = '<div class="d-flex flex-column gap-2">';
    lines.forEach(line => {
        const numMatch = line.match(/^(\d+)[\.\)]\s*(.*)$/);
        const alphaMatch = line.match(/^([a-zA-Z])[\.\)]\s*(.*)$/);
        const starMatch = line.match(/^[\*]\s*(.*)$/);
        const bulletMatch = line.match(/^[\•]\s*(.*)$/);
        const dashMatch = line.match(/^[\-]\s*(.*)$/);

        if (numMatch) {
            html += `
                <div class="d-flex align-items-start gap-2 p-2 rounded-3 bg-light border">
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.72rem; min-width: 26px; text-align: center;">${numMatch[1]}</span>
                    <span class="text-dark small lh-base pt-1">${escapeHtml(numMatch[2])}</span>
                </div>`;
        } else if (alphaMatch) {
            html += `
                <div class="d-flex align-items-start gap-2 p-2 rounded-3 bg-light border">
                    <span class="badge bg-info text-white rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.72rem; min-width: 26px; text-align: center;">${alphaMatch[1]}</span>
                    <span class="text-dark small lh-base pt-1">${escapeHtml(alphaMatch[2])}</span>
                </div>`;
        } else if (starMatch) {
            html += `
                <div class="d-flex align-items-start gap-2 p-2 rounded-3 bg-light border">
                    <span class="badge bg-warning text-dark rounded-pill px-2 py-1 shadow-sm" style="font-size: 0.72rem;"><i class="bi bi-star-fill text-dark"></i></span>
                    <span class="text-dark small lh-base pt-1">${escapeHtml(starMatch[1])}</span>
                </div>`;
        } else if (bulletMatch) {
            html += `
                <div class="d-flex align-items-start gap-2 p-2 rounded-3 bg-light border">
                    <span class="text-primary fs-5 lh-1 mt-0">&bull;</span>
                    <span class="text-dark small lh-base pt-1">${escapeHtml(bulletMatch[1])}</span>
                </div>`;
        } else if (dashMatch) {
            html += `
                <div class="d-flex align-items-start gap-2 p-2 rounded-3 bg-light border">
                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1 fw-bold" style="font-size: 0.72rem;">&ndash;</span>
                    <span class="text-dark small lh-base pt-1">${escapeHtml(dashMatch[1])}</span>
                </div>`;
        } else {
            html += `
                <div class="d-flex align-items-start gap-2 p-2 rounded-3 bg-light border">
                    <span class="text-success mt-1"><i class="bi bi-check-circle-fill"></i></span>
                    <span class="text-dark small lh-base pt-1">${escapeHtml(line)}</span>
                </div>`;
        }
    });
    html += '</div>';
    return html;
}

// Tampilkan Detail Lengkap Paslon
function showCandidateDetail(btn) {
    if (!btn) return;
    const nomor = btn.getAttribute('data-nomor') || '0';
    const ketua = btn.getAttribute('data-ketua') || '';
    const kketua = btn.getAttribute('data-kketua') || '';
    const wakil = btn.getAttribute('data-wakil') || '';
    const kwakil = btn.getAttribute('data-kwakil') || '';
    const visi = btn.getAttribute('data-visi') || '';
    const misi = btn.getAttribute('data-misi') || '';
    const proker = btn.getAttribute('data-proker') || '';
    const fotoKetua = btn.getAttribute('data-foto-ketua') || '<?= asset('assets/images/default-avatar.svg') ?>';
    const fotoWakil = btn.getAttribute('data-foto-wakil') || '<?= asset('assets/images/default-avatar.svg') ?>';

    const nomorPadded = String(nomor).padStart(2, '0');
    document.getElementById('detail-nomor-badge').innerText = nomorPadded;
    document.getElementById('detailModalTitle').innerText = 'Pasangan Calon Nomor ' + nomorPadded;
    document.getElementById('detail-header-names').innerText = ketua + ' & ' + wakil;

    document.getElementById('detail-nama-ketua').innerText = ketua;
    document.getElementById('detail-kelas-ketua').innerText = kketua;
    document.getElementById('detail-nama-wakil').innerText = wakil;
    document.getElementById('detail-kelas-wakil').innerText = kwakil;

    const imgKetua = document.getElementById('detail-img-ketua');
    if (imgKetua) imgKetua.src = fotoKetua;
    const imgWakil = document.getElementById('detail-img-wakil');
    if (imgWakil) imgWakil.src = fotoWakil;

    document.getElementById('detail-visi-text').innerText = '"' + visi + '"';

    // Format Misi & Proker dengan parser visual cerdas
    const misiContainer = document.getElementById('detail-misi-list');
    if (misiContainer) {
        misiContainer.innerHTML = renderFormattedPoints(misi);
    }

    const prokerCard = document.getElementById('detail-proker-card');
    const prokerContainer = document.getElementById('detail-proker-list');
    if (prokerContainer && prokerCard) {
        if (!proker.trim()) {
            prokerCard.classList.add('d-none');
        } else {
            prokerCard.classList.remove('d-none');
            prokerContainer.innerHTML = renderFormattedPoints(proker);
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Restore Saved View Preference
    const savedView = localStorage.getItem('candidate_view_mode');
    if (savedView === 'table') {
        switchCandidateView('table');
    }
});
</script>
