<?php
// tests/test_results_photos.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Models/Election.php';
require_once __DIR__ . '/../app/Models/Candidate.php';
require_once __DIR__ . '/../app/Models/Vote.php';
require_once __DIR__ . '/../app/Helpers/AuthHelper.php';
require_once __DIR__ . '/../app/Helpers/SettingHelper.php';

ob_start();
$_SESSION = [];
require __DIR__ . '/../public/index.php';
$html = ob_get_clean();

$checks = [
    'results-candidates-grid'    => 'Grid kartu foto paslon hasil pemilihan',
    'live-result-candidate-card' => 'Kartu paslon hasil pemilihan interaktif',
    'live-result-photo-box'      => 'Kotak foto paslon (tunggal / dual avatar)',
    'live-tally-number'          => 'Angka besar perolehan suara paslon',
    'btn-manual-refresh'         => 'Tombol manual refresh realtime',
    'liveResultsChart'           => 'Kanvas Chart.js komparasi suara'
];

echo "=== VERIFIKASI SEKSI PEROLEHAN SUARA & FOTO PASLON ===\n";
foreach ($checks as $pattern => $name) {
    if (strpos($html, $pattern) !== false) {
        echo "  [PASS] {$name} ('{$pattern}') ditemukan.\n";
    } else {
        echo "  [FAIL] {$name} ('{$pattern}') TIDAK ditemukan.\n";
        exit(1);
    }
}

echo "=== SELURUH FITUR HASIL SUARA & FOTO PASLON BERHASIL DIVERIFIKASI! ===\n";
