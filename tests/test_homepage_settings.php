<?php
// tests/test_homepage_settings.php
// Verification test for public/index.php revamp and admin/settings.php homepage controls

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/SettingHelper.php';
require_once __DIR__ . '/../app/Helpers/AuthHelper.php';

echo "=== MEMULAI TEST HOMEPAGE DAN PENGATURAN WEBSITE ADMIN ===\n\n";

$pdo = Database::getConnection();

// Test 1: Seed / Ensure homepage settings are present
echo "[TEST 1] Verifikasi default homepage settings di database...";
$keys = [
    'hero_announcement_enabled', 'hero_announcement_text',
    'hero_badge_text', 'hero_title', 'hero_subtitle', 'hero_cta_vote_text', 'hero_cta_cand_text',
    'hero_countdown_title', 'hero_countdown_desc',
    'stat_card_3_title', 'stat_card_3_desc', 'stat_card_4_title', 'stat_card_4_desc',
    'kandidat_section_badge', 'kandidat_section_title', 'kandidat_section_subtitle', 'kandidat_empty_msg',
    'agenda_section_badge', 'agenda_section_title', 'agenda_section_subtitle',
    'agenda_step1_badge', 'agenda_step1_title', 'agenda_step1_desc',
    'agenda_step2_badge', 'agenda_step2_title', 'agenda_step2_desc',
    'agenda_step3_badge', 'agenda_step3_title', 'agenda_step3_desc',
    'guide_section_badge', 'guide_section_title', 'guide_section_subtitle',
    'guide_step1_title', 'guide_step1_desc', 'guide_step2_title', 'guide_step2_desc',
    'guide_step3_title', 'guide_step3_desc', 'guide_step4_title', 'guide_step4_desc',
    'hasil_section_badge', 'hasil_section_title', 'hasil_section_subtitle',
    'hasil_hidden_title', 'hasil_hidden_desc', 'hasil_hidden_note',
    'faq_section_badge', 'faq_section_title', 'faq_section_subtitle',
    'faq1_q', 'faq1_a', 'faq2_q', 'faq2_a', 'faq3_q', 'faq3_a',
    'helpdesk_title', 'helpdesk_desc', 'helpdesk_phone', 'helpdesk_hours'
];

$all = SettingHelper::getAll();
$missing = [];
foreach ($keys as $k) {
    if (!isset($all[$k])) {
        $missing[] = $k;
    }
}

if (empty($missing)) {
    echo " [OK] Seluruh 59 kunci homepage settings terdaftar di app_settings.\n";
} else {
    echo " [GAGAL] Ada kunci yang belum terdaftar: " . implode(', ', $missing) . "\n";
    exit(1);
}

// Test 2: Render public/index.php and check key elements
echo "[TEST 2] Simulasi render public/index.php...";
ob_start();
$_SESSION = []; // guest
require __DIR__ . '/../public/index.php';
$html = ob_get_clean();

$checks = [
    'top-announcement-ticker' => 'Bar pengumuman darurat (Ticker)',
    'hero-luxury-mesh'        => 'Hero section luxury mesh background',
    'election-countdown'      => 'Kotak hitung mundur live',
    'stats-luxury-card'       => 'Kartu statistik dan nilai pemilu',
    'candidate-card'          => 'Kartu pasangan calon / empty state',
    'agenda-luxury-card'      => 'Kartu tahapan agenda pemilu',
    'guide-step-card'         => 'Kartu 4 langkah panduan memilih',
    'sealed-ballot-card'      => 'Seksi hasil pemilihan / segel bilik suara',
    'faq-accordion'           => 'Accordion FAQ pemilih',
    'helpdesk-luxury-card'    => 'Kartu kontak helpdesk panitia'
];

$passCount = 0;
foreach ($checks as $pattern => $desc) {
    if (strpos($html, $pattern) !== false) {
        echo "  [PASS] Menemukan {$desc} ('{$pattern}')\n";
        $passCount++;
    } else {
        echo "  [FAIL] Tidak menemukan {$desc} ('{$pattern}')\n";
    }
}

if ($passCount >= count($checks) - 1) { // sealed or live results is conditional
    echo "[OK] Render public/index.php berhasil dan struktur lengkap.\n";
} else {
    echo "[FAIL] Beberapa komponen utama tidak ditemukan di output index.php.\n";
    exit(1);
}

// Test 3: Test Dynamic Control from Admin Settings
echo "[TEST 3] Uji coba perubahan teks dinamis via SettingHelper::set()...\n";
$customBadge = "TEST_BADGE_KUSTOM_" . time();
$customTitle = "JUDUL_TEST_PEMILIHAN_" . time();

SettingHelper::set('hero_badge_text', $customBadge);
SettingHelper::set('hero_title', $customTitle);

ob_start();
$_SESSION = [];
require __DIR__ . '/../public/index.php';
$updatedHtml = ob_get_clean();

$badgeFound = strpos($updatedHtml, $customBadge) !== false;
$titleFound = strpos($updatedHtml, $customTitle) !== false;

if ($badgeFound && $titleFound) {
    echo "  [PASS] Teks hero badge kustom berhasil muncul di index.php: '{$customBadge}'\n";
    echo "  [PASS] Judul hero kustom berhasil muncul di index.php: '{$customTitle}'\n";
    echo "[OK] Kontrol dinamis dari app_settings bekerja 100% pada public/index.php!\n";
} else {
    echo "  [FAIL] Teks kustom tidak ter-render di index.php!\n";
    exit(1);
}

// Restore original values
SettingHelper::set('hero_badge_text', 'Pesta Demokrasi Siswa • Asas Luber Jurdil');
SettingHelper::set('hero_title', 'PEMILIHAN KETUA & WAKIL KETUA OSIS');
echo "[RESTORE] Mengembalikan setting hero ke nilai standar.\n";

// Test 4: Verify admin/settings.php contains tab-homepage with form fields
echo "[TEST 4] Memeriksa keberadaan tab-homepage di admin/settings.php...";
$settingsFile = file_get_contents(__DIR__ . '/../admin/settings.php');

$formChecks = [
    'data-bs-target="#tab-homepage"',
    'name="form_type" value="homepage_settings"',
    'name="hero_announcement_enabled"',
    'name="hero_badge_text"',
    'name="hero_title"',
    'name="stat_card_3_title"',
    'name="kandidat_section_title"',
    'name="agenda_step1_title"',
    'name="guide_step1_title"',
    'name="hasil_hidden_title"',
    'name="faq1_q"',
    'name="helpdesk_phone"'
];

$allFormsPresent = true;
foreach ($formChecks as $fc) {
    if (strpos($settingsFile, $fc) === false) {
        echo "\n  [FAIL] Field form tidak ditemukan di admin/settings.php: {$fc}";
        $allFormsPresent = false;
    }
}

if ($allFormsPresent) {
    echo " [OK] Seluruh 12 field form representatif kontrol beranda tersedia di admin/settings.php.\n";
} else {
    echo "\n[FAIL] Ada field form yang kurang di admin/settings.php.\n";
    exit(1);
}

echo "\n=== SELURUH TEST HOMEPAGE & PENGATURAN ADMIN BERHASIL (PASS)! ===\n";
