<?php
// admin/logs.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/LogHelper.php';

AuthHelper::requireRole('admin');

$pdo = Database::getConnection();

// 1. Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportStmt = $pdo->query("
        SELECT al.id, al.created_at, u.username, u.role, al.action, al.description, al.ip_address, al.user_agent
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.id DESC
    ");
    $exportLogs = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['ID Log', 'Waktu (WIB)', 'Username / Aktor', 'Peran Akun', 'Kode Aksi', 'Keterangan Aktivitas', 'Alamat IP', 'Perangkat (User Agent)']);

    foreach ($exportLogs as $row) {
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $row['username'] ?? 'Sistem / Tamu',
            $row['role'] ?? '-',
            $row['action'],
            $row['description'],
            $row['ip_address'] ?? '-',
            $row['user_agent'] ?? '-'
        ]);
    }
    fclose($output);
    exit;
}

// 2. Handle POST Purge Logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'purge_logs') {
    CsrfHelper::validateOrAbort();
    $mode = trim($_POST['purge_mode'] ?? '');
    $res = LogHelper::purgeLogs($mode);
    if ($res['success']) {
        set_flash('success', $res['message']);
    } else {
        set_flash('error', $res['error']);
    }
    header("Location: " . base_url('admin/logs.php'));
    exit;
}

// 3. Filter & Pagination Parameters
$q = trim($_GET['q'] ?? '');
$actionFilter = trim($_GET['action_filter'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Build Query Conditions
$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(al.action LIKE :q OR al.description LIKE :q OR al.ip_address LIKE :q OR u.username LIKE :q)";
    $params[':q'] = "%{$q}%";
}

if ($actionFilter !== '') {
    $where[] = "al.action = :action_filter";
    $params[':action_filter'] = $actionFilter;
}

if ($dateFrom !== '') {
    $where[] = "DATE(al.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $where[] = "DATE(al.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count Filtered
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    {$whereSql}
");
$countStmt->execute($params);
$totalFilteredLogs = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalFilteredLogs / $limit);

// Fetch Paginated Records
$sql = "
    SELECT al.*, u.username, u.role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    {$whereSql}
    ORDER BY al.id DESC
    LIMIT {$limit} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct Actions for Filter Dropdown
$actionListStmt = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
$actionList = $actionListStmt->fetchAll(PDO::FETCH_COLUMN);

// 4. Overall Table Stats
$stats = LogHelper::getStats();

$pageTitle = "Riwayat Aktivitas & Audit Log - Panel Admin";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/admin_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <!-- Page Header & Action Buttons -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="bi bi-shield-check text-primary me-2"></i>Riwayat Aktivitas &amp; Audit Log Sistem
                </h3>
                <p class="text-muted small mb-0">
                    Pencatatan rekam jejak administratif, keamanan, dan integritas pemilu tanpa merekam pilihan rahasia suara.
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="<?= base_url('admin/logs.php?export=csv') ?>" class="btn btn-outline-success btn-sm px-3 shadow-sm d-flex align-items-center gap-1" title="Unduh seluruh rekam jejak sebagai file CSV">
                    <i class="bi bi-file-earmark-spreadsheet-fill"></i> Ekspor CSV Cadangan
                </a>
                <button type="button" class="btn btn-danger btn-sm px-3 shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalPurgeLogs">
                    <i class="bi bi-trash3-fill"></i> Bersihkan Log Lama
                </button>
            </div>
        </div>

        <!-- 3 Stat Cards Ringkasan Penyimpanan Log -->
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <div>
                            <div class="text-muted text-xs text-uppercase fw-semibold">Total Rekaman Log</div>
                            <h4 class="fw-bold text-dark mb-0"><?= number_format($stats['total_count'], 0, ',', '.') ?> <span class="fs-6 fw-normal text-muted">Entri</span></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 fs-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="bi bi-hdd-network"></i>
                        </div>
                        <div>
                            <div class="text-muted text-xs text-uppercase fw-semibold">Estimasi Ruang Penyimpanan</div>
                            <h4 class="fw-bold text-dark mb-0"><?= $stats['formatted_size'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="bi bi-calendar2-range"></i>
                        </div>
                        <div>
                            <div class="text-muted text-xs text-uppercase fw-semibold">Rentang Arsip Log</div>
                            <div class="small fw-bold text-dark mb-0">
                                <?php if ($stats['oldest_date']): ?>
                                    <?= date('d M Y', strtotime($stats['oldest_date'])) ?> &ndash; <?= date('d M Y', strtotime($stats['newest_date'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <form action="<?= base_url('admin/logs.php') ?>" method="GET" class="row g-2 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label for="q" class="form-label small fw-semibold text-muted mb-1">Cari Kata Kunci / IP / Aktor</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="q" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari keterangan, username, IP...">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="action_filter" class="form-label small fw-semibold text-muted mb-1">Filter Aksi</label>
                    <select class="form-select form-select-sm" id="action_filter" name="action_filter">
                        <option value="">-- Semua Aksi Sistem --</option>
                        <?php foreach ($actionList as $act): ?>
                            <option value="<?= htmlspecialchars($act, ENT_QUOTES, 'UTF-8') ?>" <?= ($actionFilter === $act) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($act, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-sm-6">
                    <label for="date_from" class="form-label small fw-semibold text-muted mb-1">Dari Tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-lg-2 col-sm-6">
                    <label for="date_to" class="form-label small fw-semibold text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-lg-1 col-12 d-flex gap-1">
                    <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Terapkan Filter">
                        <i class="bi bi-filter"></i>
                    </button>
                    <?php if ($q !== '' || $actionFilter !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
                        <a href="<?= base_url('admin/logs.php') ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabel Log Data -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark">
                    <i class="bi bi-list-columns-reverse text-primary me-2"></i>Daftar Aktivitas Terfilter
                </div>
                <div class="text-muted small">
                    Menampilkan <strong><?= count($logs) ?></strong> dari <strong><?= number_format($totalFilteredLogs, 0, ',', '.') ?></strong> entri
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th style="width: 175px;">Waktu (WIB)</th>
                            <th style="width: 180px;">Aktor / Pengguna</th>
                            <th style="width: 170px;">Aksi Sistem</th>
                            <th>Keterangan Aktivitas</th>
                            <th style="width: 140px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <i class="bi bi-inbox fs-1 text-secondary opacity-50 mb-2"></i>
                                        <span>Tidak ada riwayat aktivitas yang sesuai kriteria pencarian.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): 
                                // Color coding for actions
                                $badgeClass = 'bg-secondary-subtle text-secondary';
                                if (str_starts_with($l['action'], 'AUTH_LOGIN')) {
                                    $badgeClass = 'bg-primary-subtle text-primary';
                                } elseif (str_starts_with($l['action'], 'VOTE')) {
                                    $badgeClass = 'bg-warning-subtle text-warning-emphasis';
                                } elseif (str_contains($l['action'], 'DELETED') || str_contains($l['action'], 'PURGED') || str_contains($l['action'], 'DEACTIVATED')) {
                                    $badgeClass = 'bg-danger-subtle text-danger';
                                } elseif (str_contains($l['action'], 'CREATED') || str_contains($l['action'], 'VERIFIED') || str_contains($l['action'], 'SYNCED')) {
                                    $badgeClass = 'bg-success-subtle text-success';
                                }
                            ?>
                            <tr>
                                <td class="text-muted small">#<?= $l['id'] ?></td>
                                <td class="small text-nowrap">
                                    <i class="bi bi-clock me-1 text-muted"></i><?= date('d M Y, H:i:s', strtotime($l['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($l['username']): ?>
                                        <strong class="text-dark"><?= htmlspecialchars($l['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="badge bg-light text-dark border small ms-1"><?= $l['role'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">Sistem / Tamu</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?> font-monospace small px-2 py-1">
                                        <?= htmlspecialchars($l['action'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="small text-dark"><?= htmlspecialchars($l['description'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="font-monospace small text-muted">
                                    <i class="bi bi-globe2 me-1"></i><?= htmlspecialchars($l['ip_address'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): 
                $queryParams = $_GET;
            ?>
            <div class="card-footer bg-white p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?></small>
                <nav aria-label="Navigasi Halaman Log">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): 
                            $queryParams['p'] = $page - 1;
                        ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= base_url('admin/logs.php?' . http_build_query($queryParams)) ?>">&laquo;</a>
                            </li>
                        <?php endif; ?>

                        <?php 
                        $startP = max(1, $page - 2);
                        $endP = min($totalPages, $page + 2);
                        for ($i = $startP; $i <= $endP; $i++): 
                            $queryParams['p'] = $i;
                        ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= base_url('admin/logs.php?' . http_build_query($queryParams)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): 
                            $queryParams['p'] = $page + 1;
                        ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= base_url('admin/logs.php?' . http_build_query($queryParams)) ?>">&raquo;</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>

<!-- =========================================================================
     MODAL BERSIHKAN / HAPUS LOG LAMA (DENGAN SWEETALERT2 INTEGRASI)
     ========================================================================= -->
<div class="modal fade" id="modalPurgeLogs" tabindex="-1" aria-labelledby="modalPurgeLogsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-xl rounded-4 overflow-hidden">
            <!-- Header Modal Premium High-Contrast -->
            <div class="modal-header modal-header-premium p-3 px-4 position-relative">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 46px; height: 46px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35);">
                        <i class="bi bi-trash3-fill fs-5" style="color: #f87171 !important;"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-danger text-white fw-bold px-2 py-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                OPTIMASI DATABASE
                            </span>
                            <span class="text-white-50 small">&bull;</span>
                            <span class="text-warning small fw-semibold" style="font-size: 0.75rem;">
                                <i class="bi bi-shield-check me-1"></i>Audit Log &amp; Rekam Jejak
                            </span>
                        </div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="modalPurgeLogsTitle" style="color: #ffffff !important;">
                            Pembersihan &amp; Manajemen Arsip Log
                        </h5>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <form action="<?= base_url('admin/logs.php') ?>" method="POST" id="formPurgeLogs">
                <?= CsrfHelper::getTokenField() ?>
                <input type="hidden" name="action" value="purge_logs">

                <div class="modal-body p-4 bg-light">
                    <!-- Snapshot Kondisi Penyimpanan Saat Ini -->
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
                        <div class="row g-2 align-items-center text-center text-md-start">
                            <div class="col-md-4 border-end-md">
                                <div class="text-muted text-xs text-uppercase fw-semibold">Jumlah Rekaman Saat Ini</div>
                                <div class="fw-bold text-dark fs-5"><?= number_format($stats['total_count'], 0, ',', '.') ?> <span class="fs-6 fw-normal text-muted">Entri</span></div>
                            </div>
                            <div class="col-md-4 border-end-md">
                                <div class="text-muted text-xs text-uppercase fw-semibold">Ukuran Ruang Tabel</div>
                                <div class="fw-bold text-primary fs-5"><?= $stats['formatted_size'] ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted text-xs text-uppercase fw-semibold">Rentang Arsip</div>
                                <div class="fw-bold text-dark small">
                                    <?= $stats['oldest_date'] ? date('d M Y', strtotime($stats['oldest_date'])) : '-' ?> &ndash; <?= $stats['newest_date'] ? date('d M Y', strtotime($stats['newest_date'])) : '-' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Peringatan Keamanan & Integritas -->
                    <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-start gap-2 mb-3 py-2 px-3">
                        <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0 mt-1"></i>
                        <div class="small text-dark">
                            <strong>Perhatian:</strong> Menghapus log lama akan mengosongkan riwayat rekaman yang terpilih secara permanen untuk membebaskan ruang penyimpanan dan mempercepat kueri audit. Tindakan ini <strong>tidak dapat dibatalkan</strong>.
                        </div>
                    </div>

                    <p class="small fw-bold text-dark mb-2">Pilih Strategi &amp; Kriteria Pembersihan Log:</p>

                    <!-- 2-Column Grid untuk Opsi Pembersihan -->
                    <div class="row g-3 mb-3">
                        <!-- Kolom Kiri: Retensi Berdasarkan Umur -->
                        <div class="col-md-6">
                            <div class="text-xs text-uppercase fw-bold text-muted mb-2 tracking-wide">
                                <i class="bi bi-clock-history me-1 text-primary"></i> Berdasarkan Umur Rekaman
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <!-- Opsi 30 Hari (Default & Rekomendasi) -->
                                <label class="modal-purge-option form-check p-2 px-3 rounded-4 border bg-white d-flex align-items-start gap-3 shadow-sm m-0">
                                    <input class="form-check-input flex-shrink-0 mt-1" type="radio" name="purge_mode" value="older_30_days" checked>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                            <span class="fw-bold text-dark small">Lebih lama dari 30 hari</span>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 0.68rem;">Rekomendasi</span>
                                        </div>
                                        <div class="text-muted text-xs">Menjaga riwayat 1 bulan terakhir tetap utuh untuk audit aktif.</div>
                                    </div>
                                </label>

                                <!-- Opsi 7 Hari -->
                                <label class="modal-purge-option form-check p-2 px-3 rounded-4 border bg-white d-flex align-items-start gap-3 shadow-sm m-0">
                                    <input class="form-check-input flex-shrink-0 mt-1" type="radio" name="purge_mode" value="older_7_days">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                            <span class="fw-bold text-dark small">Lebih lama dari 7 hari</span>
                                            <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">1 Minggu</span>
                                        </div>
                                        <div class="text-muted text-xs">Untuk penyimpanan berkapasitas sangat terbatas atau pasca-pemilu.</div>
                                    </div>
                                </label>

                                <!-- Opsi 90 Hari -->
                                <label class="modal-purge-option form-check p-2 px-3 rounded-4 border bg-white d-flex align-items-start gap-3 shadow-sm m-0">
                                    <input class="form-check-input flex-shrink-0 mt-1" type="radio" name="purge_mode" value="older_90_days">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                            <span class="fw-bold text-dark small">Lebih lama dari 90 hari</span>
                                            <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">Kuartalan (3 Bln)</span>
                                        </div>
                                        <div class="text-muted text-xs">Membersihkan arsip kuartal lampau yang telah mengendap lama.</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Kuota & Total Purge -->
                        <div class="col-md-6">
                            <div class="text-xs text-uppercase fw-bold text-muted mb-2 tracking-wide">
                                <i class="bi bi-sliders me-1 text-primary"></i> Berdasarkan Kuota &amp; Reset
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <!-- Opsi 100 Terkini -->
                                <label class="modal-purge-option form-check p-2 px-3 rounded-4 border bg-white d-flex align-items-start gap-3 shadow-sm m-0">
                                    <input class="form-check-input flex-shrink-0 mt-1" type="radio" name="purge_mode" value="keep_latest_100">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                            <span class="fw-bold text-dark small">Sisakan 100 log terbaru</span>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" style="font-size: 0.68rem;">Batas Entri</span>
                                        </div>
                                        <div class="text-muted text-xs">Hapus seluruh data lama dan pertahankan hanya 100 aktivitas terkini.</div>
                                    </div>
                                </label>

                                <!-- Opsi Pembersihan Total -->
                                <label class="modal-purge-option danger-option form-check p-2 px-3 rounded-4 border border-danger border-opacity-50 bg-white d-flex align-items-start gap-3 shadow-sm m-0">
                                    <input class="form-check-input flex-shrink-0 mt-1" type="radio" name="purge_mode" value="all">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                            <span class="fw-bold text-danger small"><i class="bi bi-trash3-fill me-1"></i>Pembersihan Total</span>
                                            <span class="badge bg-danger text-white" style="font-size: 0.68rem;">Kritis / Reset</span>
                                        </div>
                                        <div class="text-danger-emphasis text-xs">Kosongkan seluruh rekam jejak dan reset tabel riwayat dari awal.</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Bar Unduh Cadangan CSV -->
                    <div class="p-3 bg-white rounded-4 border shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 bg-success bg-opacity-10 text-success rounded-circle">
                                <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark small">Amankan Rekam Jejak Terlebih Dahulu</div>
                                <div class="text-muted text-xs">Unduh cadangan data dalam format CSV sebelum memulai proses pembersihan.</div>
                            </div>
                        </div>
                        <a href="<?= base_url('admin/logs.php?export=csv') ?>" class="btn btn-outline-success btn-sm px-3 fw-semibold d-flex align-items-center gap-1 shadow-sm">
                            <i class="bi bi-download"></i> Ekspor CSV Cadangan
                        </a>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-light border px-4 fw-semibold text-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" id="btnConfirmPurge">
                        <i class="bi bi-trash3-fill"></i> Ya, Lanjutkan Pembersihan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnPurge = document.getElementById('btnConfirmPurge');
    const formPurge = document.getElementById('formPurgeLogs');
    if (!btnPurge || !formPurge) return;

    btnPurge.addEventListener('click', function() {
        const selectedRadio = formPurge.querySelector('input[name="purge_mode"]:checked');
        const mode = selectedRadio ? selectedRadio.value : 'older_30_days';
        const isAll = (mode === 'all');

        let textPrompt = isAll 
            ? 'PERINGATAN KRUSIAL: Anda memilih untuk mengosongkan SELURUH riwayat log audit sistem. Tindakan ini tidak dapat dibatalkan.' 
            : 'Apakah Anda yakin ingin membersihkan data log lama sesuai kriteria yang dipilih? Tindakan ini akan mengosongkan rekaman yang sudah kedaluwarsa.';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: isAll ? 'Konfirmasi Pembersihan Total!' : 'Konfirmasi Pembersihan Log',
                html: `<div class="text-dark small">${textPrompt}</div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: isAll ? '<i class="bi bi-trash3-fill me-1"></i> Ya, Kosongkan Semua' : '<i class="bi bi-check-lg me-1"></i> Ya, Bersihkan Log',
                cancelButtonText: '<i class="bi bi-x-lg me-1"></i> Batal',
                reverseButtons: true,
                focusCancel: isAll,
                customClass: {
                    popup: 'swal2-app-popup shadow-xl',
                    confirmButton: 'btn btn-danger px-4 py-2 rounded-3 me-2 fw-semibold',
                    cancelButton: 'btn btn-outline-secondary px-4 py-2 rounded-3 fw-semibold'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    formPurge.submit();
                }
            });
        } else {
            if (confirm(textPrompt)) {
                formPurge.submit();
            }
        }
    });
});
</script>
