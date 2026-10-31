<?php
// admin/dashboard.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Controllers/AdminController.php';

AuthHelper::requireRole('admin');
$stats = AdminController::getDashboardStats();
$activeElection = $stats['active_election'];
$turnout = $stats['turnout'];
$results = $stats['results'];

$pageTitle = "Dashboard Administrator - E-Voting OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/admin_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <!-- Top Greeting Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold text-dark mb-0">Dashboard Administrator</h3>
                <p class="text-muted small mb-0">Sistem E-Voting Pemilihan Ketua &amp; Wakil Ketua OSIS <?= SCHOOL_NAME ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/verification.php') ?>" class="btn btn-outline-warning btn-sm fw-semibold position-relative">
                    <i class="bi bi-person-check me-1"></i> Verifikasi Akun
                    <?php if ($stats['pending_users'] > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $stats['pending_users'] ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="<?= base_url('admin/reports.php') ?>" class="btn btn-primary-custom btn-sm">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Laporan Pemilihan
                </a>
            </div>
        </div>

        <!-- 8 Core Metrics Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Siswa -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Total Siswa</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?= $stats['total_students'] ?></h3>
                            <small class="text-primary"><a href="<?= base_url('admin/students.php') ?>" class="text-decoration-none">Kelola data &rarr;</a></small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Guru -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Total Guru</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?= $stats['total_teachers'] ?></h3>
                            <small class="text-primary"><a href="<?= base_url('admin/teachers.php') ?>" class="text-decoration-none">Kelola data &rarr;</a></small>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menunggu Verifikasi -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Menunggu Verifikasi</span>
                            <h3 class="fw-bold text-warning mb-0 mt-1"><?= $stats['pending_users'] ?></h3>
                            <small class="text-warning"><a href="<?= base_url('admin/verification.php') ?>" class="text-decoration-none text-warning fw-semibold">Tinjau antrean &rarr;</a></small>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Akun Aktif -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Akun Aktif</span>
                            <h3 class="fw-bold text-success mb-0 mt-1"><?= $stats['active_users'] ?></h3>
                            <small class="text-muted">Siap memberikan suara</small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pasangan Calon -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Pasangan Calon</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?= $stats['candidate_count'] ?> Paslon</h3>
                            <small class="text-primary"><a href="<?= base_url('admin/candidates.php') ?>" class="text-decoration-none">Kelola paslon &rarr;</a></small>
                        </div>
                        <div class="stat-icon bg-purple bg-opacity-10 text-purple" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sudah Memilih -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Sudah Memilih</span>
                            <h3 class="fw-bold text-success mb-0 mt-1"><?= $turnout['total_voted'] ?> Suara</h3>
                            <small class="text-muted">Dari total <?= $turnout['total_eligible'] ?> DPT</small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-envelope-check-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Belum Memilih -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Belum Memilih</span>
                            <h3 class="fw-bold text-secondary mb-0 mt-1"><?= $turnout['total_unvoted'] ?> Pemilih</h3>
                            <small class="text-muted">Pemilih belum memilih</small>
                        </div>
                        <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="bi bi-envelope-dash"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Persentase Partisipasi -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Partisipasi Pemilih</span>
                            <h3 class="fw-bold text-primary mb-0 mt-1"><?= $turnout['turnout_pct'] ?>%</h3>
                            <div class="progress mt-1" style="height: 6px; width: 120px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $turnout['turnout_pct'] ?>%"></div>
                            </div>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Election Status Banner -->
        <?php if ($activeElection): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase"><?= $activeElection['computed_status'] ?></span>
                            <span class="badge bg-secondary-subtle text-secondary">Tahun: <?= htmlspecialchars($activeElection['tahun'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-warning-subtle text-dark">Publikasi: <?= strtoupper($activeElection['results_visibility']) ?></span>
                        </div>
                        <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($activeElection['nama_periode'], ENT_QUOTES, 'UTF-8') ?></h4>
                        <small class="text-muted">
                            Waktu: <strong><?= date('d M Y, H:i', strtotime($activeElection['start_at'])) ?></strong> s.d. 
                            <strong><?= date('d M Y, H:i', strtotime($activeElection['end_at'])) ?> WIB</strong>
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('admin/elections.php') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-pencil-square me-1"></i> Edit Periode
                        </a>
                        <a href="<?= base_url('admin/results.php') ?>" class="btn btn-primary-custom btn-sm">
                            <i class="bi bi-sliders me-1"></i> Pengaturan Publikasi
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Realtime Results & Recent Logs Row -->
        <div class="row g-4 mb-4">
            <!-- Realtime Chart -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="live-pulse"></span>
                            <h5 class="fw-bold text-dark mb-0">Perolehan Suara Realtime</h5>
                        </div>
                        <small class="text-muted" id="results-last-updated"><?= $results['updated_at_formatted'] ?? 'Baru saja' ?></small>
                    </div>

                    <?php if ($activeElection): ?>
                        <div style="height: 280px; position: relative;">
                            <canvas id="liveResultsChart"
                                    data-election-id="<?= $activeElection['id'] ?>"
                                    data-api-url="<?= base_url('api/results.php?election_id=' . $activeElection['id']) ?>"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">Belum ada pemilihan aktif</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity Logs -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-journal-text me-2 text-primary"></i> Aktivitas Terbaru</h5>
                        <a href="<?= base_url('admin/logs.php') ?>" class="small text-decoration-none">Semua Log &rarr;</a>
                    </div>

                    <div class="list-group list-group-flush small">
                        <?php if (empty($stats['recent_logs'])): ?>
                            <div class="text-muted text-center py-4">Belum ada catatan aktivitas.</div>
                        <?php else: ?>
                            <?php foreach (array_slice($stats['recent_logs'], 0, 5) as $log): ?>
                                <div class="list-group-item px-0 py-2 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <small class="text-muted"><?= date('H:i, d M', strtotime($log['created_at'])) ?></small>
                                    </div>
                                    <div class="text-dark mt-1"><?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Verifications Preview Table -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-check text-warning me-2"></i> Pendaftaran Menunggu Verifikasi</h5>
                    <small class="text-muted">Daftar calon pemilih baru yang memerlukan persetujuan panitia</small>
                </div>
                <a href="<?= base_url('admin/verification.php') ?>" class="btn btn-outline-primary btn-sm">Lihat Seluruh Pendaftar &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Identitas / NISN</th>
                            <th>Nama Lengkap</th>
                            <th>Tipe / Role</th>
                            <th>Kelas / Mapel</th>
                            <th>Waktu Daftar</th>
                            <th class="text-center">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats['pending_list'])): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-check2-all text-success fs-4 d-block mb-1"></i>
                                    Tidak ada antrean registrasi yang menunggu verifikasi saat ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (array_slice($stats['pending_list'], 0, 5) as $p): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($p['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($p['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge <?= $p['role'] === 'siswa' ? 'bg-primary' : 'bg-info' ?>">
                                            <?= strtoupper($p['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($p['kelas'] ?? ($p['mata_pelajaran'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><small class="text-muted"><?= date('d M Y H:i', strtotime($p['created_at'])) ?></small></td>
                                    <td class="text-center">
                                        <form action="<?= base_url('admin/verification.php') ?>" method="POST" class="d-inline">
                                            <?= CsrfHelper::getTokenField() ?>
                                            <input type="hidden" name="user_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="verification_action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold">
                                                <i class="bi bi-check-lg me-1"></i> Setujui
                                            </button>
                                        </form>
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
