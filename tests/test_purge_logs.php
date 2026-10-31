<?php
// tests/test_purge_logs.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/LogHelper.php';

echo "=== MENGUJI FITUR PEMBERSIHAN LOG & AUDIT LOG STATS ===\n";

$pdo = Database::getConnection();

// 1. Test getStats()
$stats = LogHelper::getStats();
assert(isset($stats['total_count']), 'Stats must contain total_count');
assert(isset($stats['formatted_size']), 'Stats must contain formatted_size');
echo "[PASS] 1. LogHelper::getStats() berhasil mengambil statistik: Total={$stats['total_count']}, Ukuran={$stats['formatted_size']}\n";

// 2. Insert dummy old logs to test purging
$pdo->exec("
    INSERT INTO activity_logs (action, description, created_at)
    VALUES 
    ('TEST_OLD_1', 'Log simulasi 45 hari yang lalu', DATE_SUB(NOW(), INTERVAL 45 DAY)),
    ('TEST_OLD_2', 'Log simulasi 15 hari yang lalu', DATE_SUB(NOW(), INTERVAL 15 DAY)),
    ('TEST_OLD_3', 'Log simulasi 3 hari yang lalu', DATE_SUB(NOW(), INTERVAL 3 DAY))
");

$countOld45 = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action = 'TEST_OLD_1'")->fetchColumn();
assert($countOld45 === 1, 'Dummy old log must exist');

// 3. Test purgeLogs('older_30_days')
$res = LogHelper::purgeLogs('older_30_days');
assert($res['success'] === true, 'purgeLogs must succeed');
assert($res['count'] >= 1, 'At least 1 log (TEST_OLD_1) should be purged');

$checkOld45 = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action = 'TEST_OLD_1'")->fetchColumn();
assert($checkOld45 === 0, 'Log older than 30 days must be deleted');

$checkOld15 = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action = 'TEST_OLD_2'")->fetchColumn();
assert($checkOld15 === 1, 'Log from 15 days ago must NOT be deleted by older_30_days');
echo "[PASS] 2. LogHelper::purgeLogs('older_30_days') berhasil menghapus log berumur >30 hari dan mempertahankan log baru.\n";

// 4. Test purgeLogs('older_7_days')
$res7 = LogHelper::purgeLogs('older_7_days');
assert($res7['success'] === true, 'purgeLogs older_7_days must succeed');
$checkOld15After = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action = 'TEST_OLD_2'")->fetchColumn();
assert($checkOld15After === 0, 'Log from 15 days ago must now be deleted by older_7_days');

$checkOld3 = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action = 'TEST_OLD_3'")->fetchColumn();
assert($checkOld3 === 1, 'Log from 3 days ago must NOT be deleted by older_7_days');
echo "[PASS] 3. LogHelper::purgeLogs('older_7_days') berhasil menghapus log berumur >7 hari.\n";

// Cleanup dummy record
$pdo->exec("DELETE FROM activity_logs WHERE action IN ('TEST_OLD_1', 'TEST_OLD_2', 'TEST_OLD_3', 'LOGS_PURGED')");
echo "[PASS] 4. Cleanup dummy test logs selesai.\n";

// 5. Test Web Endpoints (CSV Export & Purge POST)
$cookieJar = tempnam(sys_get_temp_dir(), 'vote_cookie_');
$ch = curl_init('http://localhost/E-vooting%20OSIS/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $matches);
$csrf = $matches[1] ?? '';

$ch = curl_init('http://localhost/E-vooting%20OSIS/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrf,
    'username' => 'admin',
    'password' => 'admin123',
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);
curl_close($ch);

// Test CSV Export
$ch = curl_init('http://localhost/E-vooting%20OSIS/admin/logs.php?export=csv');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$csv = curl_exec($ch);
$csvCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$csvType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

assert($csvCode === 200, "CSV export must return HTTP 200, got {$csvCode}");
assert(strpos($csv, 'ID Log') !== false, "CSV must contain 'ID Log' header");
echo "[PASS] 5. Endpoint admin/logs.php?export=csv berhasil mengekspor CSV (HTTP 200, Header valid).\n";

// Test HTML page has stats and modal
$ch = curl_init('http://localhost/E-vooting%20OSIS/admin/logs.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$pageHtml = curl_exec($ch);
$htmlCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

assert($htmlCode === 200, 'admin/logs.php must return HTTP 200');
assert(strpos($pageHtml, 'modalPurgeLogs') !== false, 'Page must contain modalPurgeLogs');
assert(strpos($pageHtml, 'Bersihkan Log Lama') !== false, 'Page must contain Bersihkan Log Lama button');
assert(strpos($pageHtml, 'Ekspor CSV Cadangan') !== false, 'Page must contain Ekspor CSV button');
echo "[PASS] 6. Halaman admin/logs.php memuat kartu statistik, modal pembersihan log, dan tombol ekspor CSV.\n";

@unlink($cookieJar);
echo "\nSELURUH PENGUJIAN PEMBERSIHAN LOG BERHASIL DENGAN SEMPURNA!\n";
