<?php
// tests/test_header_clickability.php
// Automated test for Header clickability, mobile responsiveness, and dropdown reliability

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/AuthHelper.php';
require_once __DIR__ . '/../app/Models/Election.php';
require_once __DIR__ . '/../app/Models/Vote.php';

echo "=== RUNNING HEADER CLICKABILITY & RESPONSIVENESS TESTS ===\n\n";

// 1. Verify Local Bootstrap Assets Exist
echo "TEST 1: Local Bootstrap CSS & JS Assets\n";
$cssPath = BASE_PATH . '/public/assets/css/bootstrap.min.css';
$jsPath = BASE_PATH . '/public/assets/js/bootstrap.bundle.min.js';
assert(file_exists($cssPath), "Local bootstrap.min.css must exist");
assert(filesize($cssPath) > 50000, "bootstrap.min.css must have valid size");
assert(file_exists($jsPath), "Local bootstrap.bundle.min.js must exist");
assert(filesize($jsPath) > 50000, "bootstrap.bundle.min.js must have valid size");
echo "  [PASS] Local Bootstrap assets verified (CSS: " . filesize($cssPath) . " bytes, JS: " . filesize($jsPath) . " bytes)\n\n";

// 2. Mock Student Session
@session_start();
$_SESSION['user'] = [
    'id'             => 2,
    'username'       => '5224020552',
    'role'           => 'siswa',
    'account_status' => 'active',
    'nama_lengkap'   => 'Siswa Test Automation',
    'kelas'          => 'XII',
    'jurusan'        => 'Rekayasa Perangkat Lunak'
];
$_SERVER['SCRIPT_NAME'] = '/E-vooting OSIS/siswa/dashboard.php';

// 3. Render dashboard_topbar.php
echo "TEST 2: Render dashboard_topbar.php for Student\n";
ob_start();
include BASE_PATH . '/app/Views/layouts/dashboard_topbar.php';
$topbarHtml = ob_get_clean();

assert(strpos($topbarHtml, 'id="mainDashboardTopbar"') !== false, "Topbar must have id mainDashboardTopbar");
assert(strpos($topbarHtml, 'id="dashboardSidebarToggle"') !== false, "Topbar must have sidebar toggle");
assert(strpos($topbarHtml, 'onclick="toggleSidebarMobile(event)"') !== false, "Sidebar toggle must have inline onclick fallback");
assert(strpos($topbarHtml, 'id="dashboardUserDropdown"') !== false, "Topbar must have user dropdown button");
assert(strpos($topbarHtml, 'onclick="toggleUserDropdown(event)"') !== false, "Dropdown button must have inline onclick fallback");
assert(strpos($topbarHtml, 'id="dashboardUserDropdownMenu"') !== false, "Dropdown menu must exist");
assert(strpos($topbarHtml, 'title="Ke Dashboard Utama"') !== false, "Brand logo and title must link to dashboard");
echo "  [PASS] dashboard_topbar.php contains all required IDs, onclick handlers, and brand navigation links\n\n";

// 4. Render dashboard_footer.php
echo "TEST 3: Render dashboard_footer.php for Student\n";
ob_start();
include BASE_PATH . '/app/Views/layouts/dashboard_footer.php';
$footerHtml = ob_get_clean();

assert(strpos($footerHtml, 'bootstrap.bundle.min.js') !== false, "Footer must load bootstrap.bundle.min.js");
assert(strpos($footerHtml, 'window.toggleSidebarMobile') !== false, "Footer must define toggleSidebarMobile");
assert(strpos($footerHtml, 'window.toggleUserDropdown') !== false, "Footer must define toggleUserDropdown");
assert(strpos($footerHtml, 'window.closeSidebarMobile') !== false, "Footer must define closeSidebarMobile");
assert(strpos($footerHtml, 'mobile-bottom-nav') !== false, "Student mobile bottom nav must be included");
echo "  [PASS] dashboard_footer.php contains local bootstrap, window handlers, and student bottom nav\n\n";

// 5. Test Full Page Rendering of Student Pages
$pages = [
    'siswa/dashboard.php',
    'siswa/candidates.php',
    'siswa/status.php',
    'siswa/profile.php',
    'siswa/info.php'
];

echo "TEST 4: Rendering All Student Pages (Independent Request Simulation)\n";
$runnerCode = <<<'CODE'
<?php
require 'config/app.php';
@session_start();
$_SESSION['user'] = [
    'id'             => 2,
    'username'       => '5224020552',
    'role'           => 'siswa',
    'account_status' => 'active',
    'nama_lengkap'   => 'Siswa Test Automation',
    'kelas'          => 'XII',
    'jurusan'        => 'Rekayasa Perangkat Lunak'
];
$targetPage = $argv[1] ?? 'siswa/dashboard.php';
$_SERVER['SCRIPT_NAME'] = '/E-vooting OSIS/' . $targetPage;
include $targetPage;
CODE;
file_put_contents(BASE_PATH . '/tests/page_runner.php', $runnerCode);

foreach ($pages as $page) {
    $output = shell_exec('php ' . escapeshellarg(BASE_PATH . '/tests/page_runner.php') . ' ' . escapeshellarg($page));

    assert(strlen($output) > 500, "Page $page must render non-empty HTML");
    assert(strpos($output, 'mainDashboardTopbar') !== false, "Page $page must include mainDashboardTopbar");
    assert(strpos($output, 'dashboardUserDropdown') !== false, "Page $page must include dashboardUserDropdown");
    assert(strpos($output, 'mobile-bottom-nav') !== false, "Page $page must include mobile-bottom-nav");
    assert(strpos($output, 'Fatal error') === false, "Page $page must have no Fatal error");
    assert(strpos($output, 'Parse error') === false, "Page $page must have no Parse error");
    echo "  [PASS] Page $page rendered successfully (" . strlen($output) . " bytes)\n";
}
@unlink(BASE_PATH . '/tests/page_runner.php');
echo "\n";

// 6. Verify CSS Styling for Topbar and Dropdown
echo "TEST 5: Verify CSS rules in style.css\n";
$cssContent = file_get_contents(BASE_PATH . '/public/assets/css/style.css');
assert(strpos($cssContent, '.dashboard-topbar .dropdown-menu') !== false, "style.css must style .dashboard-topbar .dropdown-menu");
assert(strpos($cssContent, 'z-index: 1060') !== false, "style.css must set high z-index for dropdown");
assert(strpos($cssContent, '.mobile-nav-toggler') !== false, "style.css must have .mobile-nav-toggler");
echo "  [PASS] style.css verified with high z-index and mobile-nav-toggler styles\n\n";

echo "=== ALL HEADER CLICKABILITY TESTS PASSED (100%) ===\n";
