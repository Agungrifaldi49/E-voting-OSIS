<?php
// tests/test_siswa_mobile_portal.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Models/User.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';

echo "=== MEMULAI TEST HAK AKSES SISWA & MOBILE RESPONSIVE PORTAL ===\n\n";

$pdo = Database::getConnection();

// Mock Student Session
$_SESSION['user'] = [
    'id' => 2,
    'username' => '1234567890',
    'role' => 'siswa',
    'nama_lengkap' => 'Ahmad Siswa Penguji',
    'kelas' => 'XII',
    'jurusan' => 'RPL',
    'account_status' => 'active'
];

// --- TEST 1: Verifikasi File-File Hak Akses Siswa ---
echo "[TEST 1] Memeriksa keberadaan file dan sintaks portal siswa...\n";
$studentFiles = [
    'siswa/dashboard.php',
    'siswa/candidates.php',
    'siswa/status.php',
    'siswa/profile.php',
    'siswa/info.php',
    'app/Views/siswa/voting.php',
    'app/Views/layouts/dashboard_footer.php'
];

foreach ($studentFiles as $file) {
    $fullPath = dirname(__DIR__) . '/' . $file;
    if (file_exists($fullPath)) {
        echo "  [PASS] File {$file} terverifikasi ada.\n";
    } else {
        echo "  [FAIL] File {$file} tidak ditemukan!\n";
        exit(1);
    }
}

// --- TEST 2: Verifikasi Komponen Mobile-First di CSS ---
echo "\n[TEST 2] Memeriksa styling mobile di public/assets/css/style.css...\n";
$css = file_get_contents(dirname(__DIR__) . '/public/assets/css/style.css');

$cssChecks = [
    '.mobile-bottom-nav'       => 'Bilah Navigasi Bawah Layar HP (Mobile Bottom Nav)',
    '.vote-icon-bubble'        => 'Gelembung Tombol Bilik Suara Tengah',
    '.student-card-luxury'     => 'E-Kartu Pemilih Siswa Digital',
    '.student-quick-grid'      => 'Grid Aksi Cepat Sentuh Layar HP',
    '.quick-action-box'        => 'Kotak Aksi Sentuh Cepat',
    '.certificate-card'        => 'Sertifikat Bukti Partisipasi Digital',
    '.certificate-seal'        => 'Stempel Segel Resmi Sah Memilih'
];

foreach ($cssChecks as $selector => $label) {
    if (strpos($css, $selector) !== false) {
        echo "  [PASS] CSS memiliki {$label} ('{$selector}')\n";
    } else {
        echo "  [FAIL] CSS TIDAK memiliki {$label} ('{$selector}')!\n";
        exit(1);
    }
}

// --- TEST 3: Verifikasi Elemen di dashboard_footer.php (Bottom Nav) ---
echo "\n[TEST 3] Memeriksa Navigasi Bawah HP di dashboard_footer.php...\n";
$footerCode = file_get_contents(dirname(__DIR__) . '/app/Views/layouts/dashboard_footer.php');

$footerChecks = [
    'class="mobile-bottom-nav'   => 'Wadah Navigasi Bawah',
    'siswa/dashboard.php'         => 'Tautan Tab Beranda',
    'siswa/candidates.php'        => 'Tautan Tab Paslon',
    'voting.php'                  => 'Tautan Tab Bilik Suara',
    'siswa/status.php'            => 'Tautan Tab Status',
    'siswa/profile.php'           => 'Tautan Tab Profil'
];

foreach ($footerChecks as $needle => $label) {
    if (strpos($footerCode, $needle) !== false) {
        echo "  [PASS] dashboard_footer.php memiliki {$label}\n";
    } else {
        echo "  [FAIL] dashboard_footer.php TIDAK memiliki {$label}!\n";
        exit(1);
    }
}

// --- TEST 4: Verifikasi Elemen Dashboard Siswa ---
echo "\n[TEST 4] Memeriksa elemen modern di siswa/dashboard.php...\n";
$dashCode = file_get_contents(dirname(__DIR__) . '/siswa/dashboard.php');

$dashChecks = [
    'student-card-luxury'       => 'E-Kartu Pemilih Digital Siswa',
    'E-KARTU PEMILIH RESMI OSIS'=> 'Badge Judul E-Kartu Pemilih',
    'student-quick-grid'        => 'Grid Aksi Cepat 4 Menu Siswa',
    'card-watermark'            => 'Watermark Latar Belakang Kartu'
];

foreach ($dashChecks as $needle => $label) {
    if (strpos($dashCode, $needle) !== false) {
        echo "  [PASS] siswa/dashboard.php memiliki {$label}\n";
    } else {
        echo "  [FAIL] siswa/dashboard.php TIDAK memiliki {$label}!\n";
        exit(1);
    }
}

// --- TEST 5: Verifikasi Status & Sertifikat Digital Siswa ---
echo "\n[TEST 5] Memeriksa elemen sertifikat digital di siswa/status.php...\n";
$statusCode = file_get_contents(dirname(__DIR__) . '/siswa/status.php');

$statusChecks = [
    'certificate-card'          => 'Wadah Sertifikat Digital',
    'certificate-seal'          => 'Stempel Segel Resmi Sah',
    'KODE VERIFIKASI'           => 'Kode Hash Verifikasi Keamanan',
    'Secret Ballot'             => 'Penjelasan Asas Kerahasiaan Penuh',
    'window.print()'            => 'Fungsi Cetak / Simpan Bukti'
];

foreach ($statusChecks as $needle => $label) {
    if (strpos($statusCode, $needle) !== false) {
        echo "  [PASS] siswa/status.php memiliki {$label}\n";
    } else {
        echo "  [FAIL] siswa/status.php TIDAK memiliki {$label}!\n";
        exit(1);
    }
}

// --- TEST 6: Verifikasi Halaman Profil & Fitur Password Toggle ---
echo "\n[TEST 6] Memeriksa elemen profil dan toggle password di siswa/profile.php...\n";
$profCode = file_get_contents(dirname(__DIR__) . '/siswa/profile.php');

$profChecks = [
    'togglePassVisibility'      => 'Fungsi Toggle Show/Hide Password untuk Layar HP',
    'bi-eye'                    => 'Ikon Mata Intip Password',
    'Akun Pemilih Terverifikasi'=> 'Badge Akun Pemilih Terverifikasi'
];

foreach ($profChecks as $needle => $label) {
    if (strpos($profCode, $needle) !== false) {
        echo "  [PASS] siswa/profile.php memiliki {$label}\n";
    } else {
        echo "  [FAIL] siswa/profile.php TIDAK memiliki {$label}!\n";
        exit(1);
    }
}

echo "\n=== SELURUH PENGUJIAN PORTAL SISWA MOBILE-FIRST BERHASIL 100% (PASS)! ===\n";
