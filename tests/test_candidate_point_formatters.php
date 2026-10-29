<?php
// tests/test_candidate_point_formatters.php
require_once dirname(__DIR__) . '/config/app.php';

echo "========================================================\n";
echo "   PENGUJIAN FITUR POIN MISI & PROKER CANDIDATE MODAL   \n";
echo "========================================================\n\n";

$candidatesContent = file_get_contents(BASE_PATH . '/admin/candidates.php');

// 1. Verifikasi Ukuran Modal XL & Scrollable Body
assert(strpos($candidatesContent, 'modal-xl') !== false, "Modal harus menggunakan ukuran modal-xl");
assert(strpos($candidatesContent, 'modal-scrollable-body') !== false, "Modal harus memiliki kelas modal-scrollable-body");
assert(strpos($candidatesContent, 'modal-header-premium') !== false, "Modal harus memiliki kelas modal-header-premium");
echo "[PASS] Modal Form & Modal Detail diperlebar (modal-xl) dengan scrollable body yang mulus\n";

// 2. Verifikasi Header Modal Dinamis
assert(strpos($candidatesContent, 'id="modalHeaderBadge"') !== false, "modalHeaderBadge harus ada di header modal");
assert(strpos($candidatesContent, 'id="modalHeaderStatusBadge"') !== false, "modalHeaderStatusBadge harus ada di header modal");
echo "[PASS] Header modal memiliki lencana nomor urut emas dan badge status dinamis\n";

// 3. Verifikasi Toolbar Format Poin (1, a, *, •, -)
$expectedFormats = ['applyPointFormat(\'cand-misi\', \'number\')', 'applyPointFormat(\'cand-misi\', \'alpha\')', 'applyPointFormat(\'cand-misi\', \'bullet\')', 'applyPointFormat(\'cand-misi\', \'star\')', 'applyPointFormat(\'cand-misi\', \'dash\')'];
foreach ($expectedFormats as $fmt) {
    assert(strpos($candidatesContent, $fmt) !== false, "Toolbar Misi harus memiliki opsi: $fmt");
}

$expectedProkerFormats = ['applyPointFormat(\'cand-proker\', \'number\')', 'applyPointFormat(\'cand-proker\', \'alpha\')', 'applyPointFormat(\'cand-proker\', \'bullet\')', 'applyPointFormat(\'cand-proker\', \'star\')', 'applyPointFormat(\'cand-proker\', \'dash\')'];
foreach ($expectedProkerFormats as $fmt) {
    assert(strpos($candidatesContent, $fmt) !== false, "Toolbar Proker harus memiliki opsi: $fmt");
}
echo "[PASS] Toolbar Format Poin (1., a., •, *, -) tersedia lengkap pada textarea Misi dan Proker\n";

// 4. Verifikasi Smart Enter-Key Handler & Point Renderer
assert(strpos($candidatesContent, 'handleSmartListKey(event, this)') !== false, "Handler tombol Enter otomatis harus terpasang");
assert(strpos($candidatesContent, 'function renderFormattedPoints(rawText)') !== false, "Fungsi renderFormattedPoints harus ada");
echo "[PASS] Fungsi penomoran cerdas (Auto-indent Enter) dan visual point renderer terverifikasi aktif\n";

echo "\n========================================================\n";
echo "   SEMUA VERIFIKASI FITUR MODAL & POIN BERHASIL!       \n";
echo "========================================================\n";
