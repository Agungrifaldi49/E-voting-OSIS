<?php
// tests/test_sweetalert_flash.php
require_once __DIR__ . '/../config/app.php';

// Test 1: Success message
set_flash('success', 'Data berhasil disimpan dengan sempurna!');
ob_start();
require __DIR__ . '/../app/Views/layouts/alerts.php';
$outputSuccess = ob_get_clean();

assert(strpos($outputSuccess, 'flashData') !== false, 'Must contain flashData variable');
assert(strpos($outputSuccess, 'Swal.fire') !== false, 'Must contain Swal.fire invocation');
assert(strpos($outputSuccess, 'Data berhasil disimpan dengan sempurna!') !== false, 'Must contain success message');
assert(strpos($outputSuccess, 'swalTitle = \'Berhasil!\'') !== false, 'Must have Berhasil! title');
assert(strpos($outputSuccess, 'timerDuration = 4500') !== false, 'Must have timer for success');

echo "[PASS] Test 1: Flash message 'success' correctly generates SweetAlert2 payload.\n";

// Test 2: Error message
set_flash('error', 'Username atau password tidak cocok.');
ob_start();
require __DIR__ . '/../app/Views/layouts/alerts.php';
$outputError = ob_get_clean();

assert(strpos($outputError, 'Swal.fire') !== false, 'Must contain Swal.fire invocation');
assert(strpos($outputError, 'Username atau password tidak cocok.') !== false, 'Must contain error message');
assert(strpos($outputError, 'swalTitle = \'Terjadi Kesalahan!\'') !== false, 'Must have Terjadi Kesalahan! title');
assert(strpos($outputError, 'timerDuration = null') !== false, 'Error message must not auto-dismiss without reading');

echo "[PASS] Test 2: Flash message 'error' correctly generates SweetAlert2 payload.\n";

// Test 3: Warning message
set_flash('warning', 'Peringatan penting untuk pengguna!');
ob_start();
require __DIR__ . '/../app/Views/layouts/alerts.php';
$outputWarning = ob_get_clean();

assert(strpos($outputWarning, 'swalTitle = \'Peringatan Sistem\'') !== false, 'Must have Peringatan title');
echo "[PASS] Test 3: Flash message 'warning' correctly generates SweetAlert2 payload.\n";

// Test 4: Verify header.php includes sweetalert2 assets
ob_start();
require __DIR__ . '/../app/Views/layouts/header.php';
$outputHeader = ob_get_clean();

assert(strpos($outputHeader, 'sweetalert2.min.css') !== false, 'header.php must include sweetalert2.min.css');
assert(strpos($outputHeader, 'sweetalert2.all.min.js') !== false, 'header.php must include sweetalert2.all.min.js');
assert(strpos($outputHeader, 'typeof Swal === \'undefined\'') !== false, 'header.php must have CDN fallback for Swal');

echo "[PASS] Test 4: header.php properly includes SweetAlert2 CSS & JS with CDN fallback.\n";

// Test 5: Verify main.js contains initSweetAlertBridges and helper functions
$mainJsContent = file_get_contents(__DIR__ . '/../public/assets/js/main.js');
assert(strpos($mainJsContent, 'initSweetAlertBridges') !== false, 'main.js must call initSweetAlertBridges');
assert(strpos($mainJsContent, 'window.showToast') !== false, 'main.js must define window.showToast');
assert(strpos($mainJsContent, 'window.showSuccessAlert') !== false, 'main.js must define window.showSuccessAlert');
assert(strpos($mainJsContent, 'window.showErrorAlert') !== false, 'main.js must define window.showErrorAlert');
assert(strpos($mainJsContent, 'window.showConfirmDialog') !== false, 'main.js must define window.showConfirmDialog');

echo "[PASS] Test 5: main.js includes global SweetAlert2 utilities and bridge interceptors.\n";

echo "\nAll SweetAlert2 integration tests passed successfully!\n";
