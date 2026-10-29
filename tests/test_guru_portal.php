<?php
// tests/test_guru_portal.php
// Automated Verification for Hak Akses Guru, Mobile Responsiveness & Bottom Navigation

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Election.php';
require_once __DIR__ . '/../app/Models/Vote.php';
require_once __DIR__ . '/../app/Helpers/AuthHelper.php';

echo "=== TESTING GURU ACCESS PORTAL & MOBILE RESPONSIVENESS ===\n\n";

$pdo = Database::getConnection();
$teachers = $pdo->query("SELECT u.id, u.username, u.role, tp.nama_lengkap, tp.employee_identifier, tp.mata_pelajaran FROM users u LEFT JOIN teacher_profiles tp ON u.id = tp.user_id WHERE u.role = 'guru' LIMIT 1")->fetchAll();

assert(!empty($teachers), "At least one teacher must exist in DB");
$t = $teachers[0];
echo "Teacher Account Found: ID {$t['id']} | NIP: {$t['username']} | Nama: {$t['nama_lengkap']} | Mapel: {$t['mata_pelajaran']}\n";

// Mock Guru Session
@session_start();
$_SESSION['user'] = [
    'id' => (int)$t['id'],
    'role' => 'guru',
    'username' => $t['username'],
    'nama_lengkap' => $t['nama_lengkap'],
    'account_status' => 'active',
    'employee_identifier' => $t['employee_identifier'] ?? $t['username'],
    'mata_pelajaran' => $t['mata_pelajaran'] ?? 'Konsentrasi Keahlian'
];

// Test 1: Guru Sidebar Component
echo "\nTEST 1: Render guru_sidebar.php\n";
$_SERVER['SCRIPT_NAME'] = '/guru/dashboard.php';
ob_start();
include BASE_PATH . '/app/Views/layouts/guru_sidebar.php';
$sidebarHtml = ob_get_clean();
assert(strpos($sidebarHtml, 'id="guru-sidebar"') !== false, "Must have guru-sidebar ID");
assert(strpos($sidebarHtml, 'window.closeSidebarMobile(event)') !== false, "Close button must use closeSidebarMobile");
assert(strpos($sidebarHtml, 'Menu Pendidik') !== false, "Sidebar heading must say Menu Pendidik");
assert(strpos($sidebarHtml, 'guru/dashboard.php') !== false, "Sidebar must link to guru/dashboard.php");
assert(strpos($sidebarHtml, 'guru/candidates.php') !== false, "Sidebar must link to guru/candidates.php");
assert(strpos($sidebarHtml, 'guru/status.php') !== false, "Sidebar must link to guru/status.php");
assert(strpos($sidebarHtml, 'guru/profile.php') !== false, "Sidebar must link to guru/profile.php");
echo "  [PASS] guru_sidebar.php rendered with all links and close handlers.\n";

// Test 2: Topbar for Guru
echo "\nTEST 2: Render dashboard_topbar.php for Guru\n";
ob_start();
include BASE_PATH . '/app/Views/layouts/dashboard_topbar.php';
$topbarHtml = ob_get_clean();
assert(strpos($topbarHtml, 'Portal Dewan Guru') !== false, "Topbar must display Portal Dewan Guru");
assert(strpos($topbarHtml, 'NIP/ID:') !== false, "Topbar dropdown must display NIP/ID for guru");
assert(strpos($topbarHtml, 'guru/profile.php') !== false, "Topbar dropdown must link to guru profile");
assert(strpos($topbarHtml, 'guru/status.php') !== false, "Topbar dropdown must link to guru status");
echo "  [PASS] dashboard_topbar.php rendered accurately for Guru.\n";

// Test 3: Footer for Guru with Mobile Bottom Nav
echo "\nTEST 3: Render dashboard_footer.php for Guru\n";
ob_start();
include BASE_PATH . '/app/Views/layouts/dashboard_footer.php';
$footerHtml = ob_get_clean();
assert(strpos($footerHtml, 'mobile-bottom-nav') !== false, "Teacher must receive mobile-bottom-nav bar");
assert(strpos($footerHtml, 'guru/dashboard.php') !== false, "Bottom nav must link to guru/dashboard.php");
assert(strpos($footerHtml, 'guru/candidates.php') !== false, "Bottom nav must link to guru/candidates.php");
assert(strpos($footerHtml, 'voting.php') !== false, "Bottom nav must link to voting.php");
assert(strpos($footerHtml, 'guru/status.php') !== false, "Bottom nav must link to guru/status.php");
assert(strpos($footerHtml, 'guru/profile.php') !== false, "Bottom nav must link to guru/profile.php");
echo "  [PASS] dashboard_footer.php contains mobile-bottom-nav with all teacher URLs.\n";

// Test 4: Guru Profile Page Content
echo "\nTEST 4: Inspect guru/profile.php elements\n";
$profileFile = file_get_contents(BASE_PATH . '/guru/profile.php');
assert(strpos($profileFile, 'Pengaturan Profil &amp; Keamanan Akun') !== false, "Profile must have new modern title");
assert(strpos($profileFile, 'Akun Dewan Guru Terverifikasi') !== false, "Profile must have verified teacher badge");
assert(strpos($profileFile, 'Mata Pelajaran:') !== false, "Profile must display Mata Pelajaran");
assert(strpos($profileFile, 'togglePassVisibility') !== false, "Profile must include eye password toggle");
assert(strpos($profileFile, 'old_password') !== false && strpos($profileFile, 'new_password') !== false, "Profile must have password change inputs");
echo "  [PASS] guru/profile.php code has verified educator identity & password eye toggles.\n";

// Test 5: Guru Dashboard Content
echo "\nTEST 5: Inspect guru/dashboard.php elements\n";
$dashFile = file_get_contents(BASE_PATH . '/guru/dashboard.php');
assert(strpos($dashFile, 'teacher-card-luxury') !== false, "Dashboard must have teacher-card-luxury");
assert(strpos($dashFile, 'E-KARTU DEWAN GURU RESMI') !== false, "Dashboard must have E-Kartu Dewan Guru Resmi");
assert(strpos($dashFile, 'student-quick-grid') !== false, "Dashboard must have mobile touch quick action grid");
assert(strpos($dashFile, 'Kebijakan Hak Suara Guru') !== false, "Dashboard must have election policy status");
assert(strpos($dashFile, 'candidate-photo-wrapper') !== false, "Dashboard must have candidate photos");
echo "  [PASS] guru/dashboard.php code has luxury digital card, quick grid, and responsive candidate cards.\n";

// Test 6: Shared status.php adaptation for Guru
echo "\nTEST 6: Inspect siswa/status.php role-aware logic\n";
$statusFile = file_get_contents(BASE_PATH . '/siswa/status.php');
assert(strpos($statusFile, "user['role'] === 'guru' ? 'Nama Pengajar:' : 'Nama Pemilih:'") !== false, "Status must show Nama Pengajar for guru");
assert(strpos($statusFile, "user['role'] === 'guru' ? 'NIP / Identitas:' : 'NISN / Identitas:'") !== false, "Status must show NIP / Identitas for guru");
assert(strpos($statusFile, "user['role'] === 'guru' ? 'Mata Pelajaran:' : 'Kelas &amp; Jurusan:'") !== false, "Status must show Mata Pelajaran for guru");
echo "  [PASS] siswa/status.php dynamically adapts for teacher role.\n";

echo "\n======================================================\n";
echo "SUCCESS: ALL 6 HAK AKSES GURU AUTOMATED CHECKS PASSED!\n";
echo "======================================================\n";
