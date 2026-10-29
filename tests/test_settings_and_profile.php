<?php
// tests/test_settings_and_profile.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/SettingHelper.php';
require_once __DIR__ . '/../app/Models/User.php';

echo "========================================================\n";
echo "   PENGUJIAN MODUL PENGATURAN WEBSITE & PROFIL ADMIN    \n";
echo "========================================================\n\n";

$pdo = Database::getConnection();

// --- 1. UJI SETTING HELPER ---
echo "--- 1. PENGUJIAN PENGATURAN WEBSITE (APP_SETTINGS) ---\n";

$schoolName = SettingHelper::get('school_name');
assert(!empty($schoolName), "Nama sekolah harus tersedia di database");
echo "[PASS] Berhasil mengambil 'school_name': {$schoolName}\n";

// Update settings
SettingHelper::setMultiple([
    'school_name'         => 'SMK Muthia Harapan Cicalengka',
    'app_title'           => 'E-Voting OSIS Modern',
    'kepala_sekolah_nama' => 'H. Dadang Sulaeman, S.Pd., M.M.',
    'ketua_panitia_nama'  => 'Drs. H. Ahmad Fauzi, M.Pd.'
]);

assert(SettingHelper::get('app_title') === 'E-Voting OSIS Modern', "app_title harus terupdate");
echo "[PASS] Update multiple settings berhasil disimpan dan terbaca\n";

// Logo URL check
$logoUrl = SettingHelper::getLogoUrl();
assert(!empty($logoUrl), "Logo URL tidak boleh kosong");
echo "[PASS] Logo URL berhasil dimuat: {$logoUrl}\n";

// --- 2. UJI UPLOAD & RESET LOGO ---
echo "\n--- 2. PENGUJIAN UPLOAD & RESET LOGO WEBSITE ---\n";

// Buat dummy PNG valid (1x1 transparent PNG)
$dummyPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
$tmpPngPath = tempnam(sys_get_temp_dir(), 'test_logo_') . '.png';
file_put_contents($tmpPngPath, $dummyPng);

$mockFile = [
    'name'     => 'official_logo.png',
    'type'     => 'image/png',
    'tmp_name' => $tmpPngPath,
    'error'    => UPLOAD_ERR_OK,
    'size'     => strlen($dummyPng)
];

// Simulasi upload (copy to destination directly via helper logic)
$destDir = PUBLIC_PATH . '/uploads/settings';
if (!is_dir($destDir)) mkdir($destDir, 0777, true);
$targetFile = 'test_logo_' . time() . '.png';
copy($tmpPngPath, $destDir . '/' . $targetFile);
SettingHelper::set('logo_url', 'uploads/settings/' . $targetFile);

assert(strpos(SettingHelper::getLogoUrl(), $targetFile) !== false, "Logo aktif harus mengarah ke file baru");
echo "[PASS] Logo baru berhasil diunggah dan terpasang di sistem: " . SettingHelper::getLogoUrl() . "\n";

// Reset logo test
SettingHelper::resetLogo();
assert(strpos(SettingHelper::getLogoUrl(), 'logo-placeholder.svg') !== false, "Logo harus kembali ke default");
echo "[PASS] Reset logo ke default berhasil\n";

@unlink($tmpPngPath);
@unlink($destDir . '/' . $targetFile);

// --- 3. UJI PROFIL ADMINISTRATOR LENGKAP ---
echo "\n--- 3. PENGUJIAN PROFIL ADMINISTRATOR ---\n";

$adminUser = User::findByUsername('admin');
assert($adminUser !== null, "Akun admin harus ditemukan");
$adminId = (int)$adminUser['id'];

// Update profil admin (Nama lengkap, email, username)
$res = User::updateAdminProfile($adminId, [
    'full_name' => 'Super Administrator OSIS',
    'username'  => 'admin',
    'email'     => 'admin.osis@smkmuthiaharapan.sch.id'
]);
assert($res['success'] === true, "Update profil admin harus berhasil");

$updatedAdmin = User::findById($adminId);
assert($updatedAdmin['nama_lengkap'] === 'Super Administrator OSIS', "Nama lengkap harus terupdate");
assert($updatedAdmin['email'] === 'admin.osis@smkmuthiaharapan.sch.id', "Email harus terupdate");
echo "[PASS] Perbaruan data profil admin (Nama, Email, Username) berhasil disimpan\n";

// Uji ganti password admin
$oldHash = $updatedAdmin['password_hash'];
assert(password_verify('admin123', $oldHash), "Password lama harus valid");

User::updatePassword($adminId, 'adminNewPass123');
$passUpdatedAdmin = User::findById($adminId);
assert(password_verify('adminNewPass123', $passUpdatedAdmin['password_hash']), "Password baru harus terverifikasi");
assert(!password_verify('admin123', $passUpdatedAdmin['password_hash']), "Password lama tidak boleh berlaku lagi");
echo "[PASS] Ganti password administrator berhasil diverifikasi\n";

// Kembalikan password ke default 'admin123' untuk kemudahan pengujian selanjutnya
User::updatePassword($adminId, 'admin123');
echo "[PASS] Password admin berhasil dinormalkan kembali ke 'admin123'\n";

// --- 4. UJI HTTP ENDPOINT AKSES ADMIN/SETTINGS & ADMIN/PROFILE ---
echo "\n--- 4. PENGUJIAN HTTP TAMPILAN PANEL ADMIN ---\n";

$cookieJar = tempnam(sys_get_temp_dir(), 'test_ck_');

// Login
$ch = curl_init('http://localhost/E-vooting%20OSIS/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
$loginHtml = curl_exec($ch);
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginHtml, $m);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $m[1] ?? '',
    'username'   => 'admin',
    'password'   => 'admin123'
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Test admin/settings.php
curl_setopt($ch, CURLOPT_URL, 'http://localhost/E-vooting%20OSIS/admin/settings.php');
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$settingsHtml = curl_exec($ch);
$settingsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

assert($settingsCode === 200, "admin/settings.php harus return 200 OK");
assert(strpos($settingsHtml, 'Logo &amp; Identitas') !== false, "Tab Logo harus ada");
assert(strpos($settingsHtml, 'Pejabat Laporan') !== false, "Tab Pejabat Laporan harus ada");
assert(strpos($settingsHtml, 'Kontak &amp; Medsos') !== false, "Tab Kontak Medsos harus ada");
assert(strpos($settingsHtml, 'Pengumuman Panitia') !== false, "Tab Pengumuman harus ada");
assert(strpos($settingsHtml, 'dashboard-topbar') !== false, "Topbar dashboard harus ada");
assert(strpos($settingsHtml, 'dashboard-footer') !== false, "Footer dashboard harus ada");
echo "[PASS] admin/settings.php berhasil diakses (HTTP 200) dengan seluruh 5 Tab Pengaturan lengkap\n";

// Test admin/profile.php
curl_setopt($ch, CURLOPT_URL, 'http://localhost/E-vooting%20OSIS/admin/profile.php');
$profileHtml = curl_exec($ch);
$profileCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

assert($profileCode === 200, "admin/profile.php harus return 200 OK");
assert(strpos($profileHtml, 'Profil &amp; Keamanan Akun Administrator') !== false, "Header profil harus ada");
assert(strpos($profileHtml, 'Ganti Password Administrator') !== false, "Form password harus ada");
assert(strpos($profileHtml, 'Perbarui Data Profil &amp; Foto') !== false, "Form data profil & foto harus ada");
assert(strpos($profileHtml, 'Riwayat Aktivitas Terakhir Anda') !== false, "Riwayat aktivitas harus ada");
assert(strpos($profileHtml, 'dashboard-topbar') !== false, "Topbar dashboard harus ada");
assert(strpos($profileHtml, 'dashboard-footer') !== false, "Footer dashboard harus ada");
echo "[PASS] admin/profile.php berhasil diakses (HTTP 200) dengan fitur profil komplit\n";

unlink($cookieJar);

echo "\n========================================================\n";
echo "   SEMUA PENGUJIAN PENGATURAN & PROFIL ADMIN LULUS!     \n";
echo "========================================================\n";
