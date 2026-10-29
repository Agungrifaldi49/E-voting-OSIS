<?php
// admin/logs.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';

AuthHelper::requireRole('admin');

$pdo = Database::getConnection();

$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$totalLogs = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

$stmt = $pdo->prepare("
    SELECT al.*, u.username, u.role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.id DESC
    LIMIT {$limit} OFFSET {$offset}
");
$stmt->execute();
$logs = $stmt->fetchAll();

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

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1">Riwayat Aktivitas &amp; Audit Log Sistem</h3>
            <p class="text-muted small mb-0">Catatan aktivitas administratif dan keamanan sistem tanpa menyimpan kata sandi atau data rahasia pemilih.</p>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>Waktu (WIB)</th>
                            <th>Aktor / Pengguna</th>
                            <th>Aksi Sistem</th>
                            <th>Keterangan Aktivitas</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Belum ada riwayat aktivitas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                            <tr>
                                <td class="text-muted small">#<?= $l['id'] ?></td>
                                <td class="small text-nowrap"><?= date('d M Y, H:i:s', strtotime($l['created_at'])) ?></td>
                                <td>
                                    <?php if ($l['username']): ?>
                                        <strong class="text-dark"><?= htmlspecialchars($l['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="badge bg-light text-dark border small ms-1"><?= $l['role'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Sistem / Tamu</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary font-monospace">
                                        <?= htmlspecialchars($l['action'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($l['description'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="font-monospace small text-muted"><?= htmlspecialchars($l['ip_address'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?></small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= base_url("admin/logs.php?p={$i}") ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
