<?php
// tests/test_modals_and_edits.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Candidate.php';

echo "========================================================\n";
echo "   PENGUJIAN FITUR MODAL & EDIT ADMIN (ELECTIONS & CANDIDATES)\n";
echo "========================================================\n\n";

$pdo = Database::getConnection();

// --- 1. PENGUJIAN INTEGRITAS ELECTIONS.PHP ---
echo "--- 1. PENGUJIAN ELECTIONS.PHP ---\n";
$electionsContent = file_get_contents(BASE_PATH . '/admin/elections.php');

// Check modal ID
assert(strpos($electionsContent, 'id="electionModal"') !== false, "electionModal ID harus ada");
assert(strpos($electionsContent, 'data-bs-target="#electionModal"') !== false, "data-bs-target harus mengarah ke #electionModal");
assert(strpos($electionsContent, 'prepareEditElection(this)') !== false, "onclick prepareEditElection harus ada");
assert(strpos($electionsContent, 'function prepareEditElection(btn)') !== false, "fungsi prepareEditElection harus didefinisikan");
assert(strpos($electionsContent, 'function prepareCreateElection()') !== false, "fungsi prepareCreateElection harus didefinisikan");

// Check all form input IDs
$expectedElectionIds = ['el-id', 'el-nama', 'el-tahun', 'el-deskripsi', 'el-start', 'el-end', 'el-status', 'el-vis', 'el-teacher', 'el-active'];
foreach ($expectedElectionIds as $eid) {
    assert(strpos($electionsContent, "id=\"$eid\"") !== false, "Input id '$eid' harus ada di elections.php modal");
}
echo "[PASS] Struktur Modal & Trigger Edit di admin/elections.php Valid dan Lengkap\n";

// --- 2. PENGUJIAN INTEGRITAS CANDIDATES.PHP ---
echo "\n--- 2. PENGUJIAN CANDIDATES.PHP ---\n";
$candidatesContent = file_get_contents(BASE_PATH . '/admin/candidates.php');

assert(strpos($candidatesContent, 'id="candidateModal"') !== false, "candidateModal ID harus ada");
assert(strpos($candidatesContent, 'data-bs-target="#candidateModal"') !== false, "data-bs-target harus mengarah ke #candidateModal");
assert(strpos($candidatesContent, 'prepareEditCandidate(this)') !== false, "onclick prepareEditCandidate harus ada");
assert(strpos($candidatesContent, 'function prepareEditCandidate(btn)') !== false, "fungsi prepareEditCandidate harus didefinisikan");
assert(strpos($candidatesContent, 'function prepareCreateCandidate()') !== false, "fungsi prepareCreateCandidate harus didefinisikan");

$expectedCandIds = ['cand-id', 'cand-nomor', 'cand-ketua', 'cand-kketua', 'cand-wakil', 'cand-kwakil', 'cand-visi', 'cand-misi', 'cand-proker', 'cand-status'];
foreach ($expectedCandIds as $cid) {
    assert(strpos($candidatesContent, "id=\"$cid\"") !== false, "Input id '$cid' harus ada di candidates.php modal");
}
echo "[PASS] Struktur Modal & Trigger Edit di admin/candidates.php Valid dan Lengkap\n";

// --- 3. PENGUJIAN BACKEND UPDATE MODEL ELECTION ---
echo "\n--- 3. PENGUJIAN UPDATE DATABASE ELECTIONS ---\n";
$activeElection = Election::getActive();
if ($activeElection) {
    $origName = $activeElection['nama_periode'];
    $testName = $origName . ' (TEST_EDIT)';
    
    $updateData = [
        'nama_periode'       => $testName,
        'tahun'              => $activeElection['tahun'],
        'deskripsi'          => 'Deskripsi uji coba edit',
        'start_at'           => $activeElection['start_at'],
        'end_at'             => $activeElection['end_at'],
        'status'             => $activeElection['status'],
        'is_active'          => 1,
        'teacher_can_vote'   => $activeElection['teacher_can_vote'],
        'results_visibility' => $activeElection['results_visibility']
    ];
    $ok = Election::update((int)$activeElection['id'], $updateData);
    assert($ok === true, "Election::update harus berhasil");

    $reloaded = Election::getById((int)$activeElection['id']);
    assert($reloaded['nama_periode'] === $testName, "Nama periode harus terupdate");
    echo "[PASS] Election::update berhasil memperbarui data di MySQL\n";

    // Restore original
    $updateData['nama_periode'] = $origName;
    Election::update((int)$activeElection['id'], $updateData);
    echo "[PASS] Data periode berhasil dinormalkan kembali\n";
} else {
    echo "[SKIP] Tidak ada periode aktif untuk dites\n";
}

// --- 4. PENGUJIAN BACKEND UPDATE MODEL CANDIDATE ---
echo "\n--- 4. PENGUJIAN UPDATE DATABASE CANDIDATES ---\n";
if ($activeElection) {
    $cands = Candidate::getByElectionId((int)$activeElection['id']);
    if (!empty($cands)) {
        $c = $cands[0];
        $origVisi = $c['visi'];
        $testVisi = "Visi baru hasil edit: Membawa OSIS lebih maju dan kreatif";

        $candData = [
            'nomor_urut'         => $c['nomor_urut'],
            'nama_ketua'         => $c['nama_ketua'],
            'kelas_ketua'        => $c['kelas_ketua'],
            'nama_wakil'         => $c['nama_wakil'],
            'kelas_wakil'        => $c['kelas_wakil'],
            'visi'               => $testVisi,
            'misi'               => $c['misi'],
            'program_kerja'      => $c['program_kerja'],
            'publication_status' => $c['publication_status']
        ];
        $ok = Candidate::update((int)$c['id'], $candData);
        assert($ok === true, "Candidate::update harus berhasil");

        $reloadedCand = Candidate::getById((int)$c['id']);
        assert($reloadedCand['visi'] === $testVisi, "Visi kandidat harus terupdate");
        echo "[PASS] Candidate::update berhasil memperbarui data di MySQL\n";

        // Restore original
        $candData['visi'] = $origVisi;
        Candidate::update((int)$c['id'], $candData);
        echo "[PASS] Data kandidat berhasil dinormalkan kembali\n";
    }
}

// --- 5. PENGUJIAN HTTP GET ACCESS ---
echo "\n--- 5. PENGUJIAN HTTP LIVE ACCESS KE HALAMAN ADMIN ---\n";
// Login as admin and fetch pages
$cookieFile = sys_get_temp_dir() . '/test_cookie_modals.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$ch = curl_init('http://localhost/E-vooting%20OSIS/login.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
]);
$html = curl_exec($ch);
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m);
$csrf = $m[1] ?? '';
curl_close($ch);

$ch = curl_init('http://localhost/E-vooting%20OSIS/login.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf_token' => $csrf,
        'username' => 'admin',
        'password' => 'admin123'
    ]),
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_FOLLOWLOCATION => true
]);
curl_exec($ch);
curl_close($ch);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_FOLLOWLOCATION => true
]);

// Test admin/elections.php
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/E-vooting%20OSIS/admin/elections.php',
    CURLOPT_POST => false,
    CURLOPT_HTTPGET => true
]);
$elHtml = curl_exec($ch);
$elCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($elCode !== 200) {
    echo "FAILED: HTTP Code = $elCode, curl error: " . curl_error($ch) . "\n";
    echo "Response snippet: " . substr($elHtml, 0, 300) . "\n";
}
assert($elCode === 200, "elections.php HTTP code harus 200");
assert(strpos($elHtml, 'Periode Pemilihan OSIS') !== false, "Harus memuat konten Periode Pemilihan");
assert(strpos($elHtml, 'btn-edit-election') !== false, "Harus memuat tombol edit periode");
echo "[PASS] admin/elections.php berhasil diakses (HTTP 200) dan memuat tombol edit modal\n";

// Test admin/candidates.php
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/E-vooting%20OSIS/admin/candidates.php',
    CURLOPT_POST => false,
    CURLOPT_HTTPGET => true
]);
$candHtml = curl_exec($ch);
$candCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($candCode !== 200) {
    echo "FAILED: candidates.php HTTP Code = $candCode, curl error: " . curl_error($ch) . "\n";
    echo "Response snippet: " . substr($candHtml, 0, 500) . "\n";
}
assert($candCode === 200, "candidates.php HTTP code harus 200");
assert(strpos($candHtml, 'Manajemen Pasangan Calon OSIS') !== false, "Harus memuat konten Pasangan Calon");
assert(strpos($candHtml, 'btn-edit-cand') !== false, "Harus memuat tombol edit kandidat");
echo "[PASS] admin/candidates.php berhasil diakses (HTTP 200) dan memuat tombol edit modal\n";

curl_close($ch);
if (file_exists($cookieFile)) unlink($cookieFile);

echo "\n========================================================\n";
echo "   SEMUA PENGUJIAN MODAL EDIT LULUS DENGAN SEMPURNA!   \n";
echo "========================================================\n";
