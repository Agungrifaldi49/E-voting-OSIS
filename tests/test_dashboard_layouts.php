<?php
// tests/test_dashboard_layouts.php
require_once __DIR__ . '/../config/app.php';

function testRoleAccess($username, $password, $pagesToCheck, $roleName) {
    echo "\n=== Menguji Portal {$roleName} ({$username}) ===\n";
    $cookieJar = tempnam(sys_get_temp_dir(), 'vote_cookie_');

    // 1. Get Login Page & CSRF Token
    $ch = curl_init('http://localhost/E-vooting%20OSIS/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    $html = curl_exec($ch);
    curl_close($ch);

    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $matches);
    $csrf = $matches[1] ?? '';
    if (!$csrf) {
        echo "[FAIL] Gagal mengekstrak CSRF token dari login.php\n";
        return false;
    }

    // 2. Submit Login
    $ch = curl_init('http://localhost/E-vooting%20OSIS/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'csrf_token' => $csrf,
        'username' => $username,
        'password' => $password,
    ]));
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    echo "Login HTTP Code: {$httpCode}, Final URL: {$effectiveUrl}\n";

    // 3. Check Each Portal Page
    foreach ($pagesToCheck as $path) {
        $url = 'http://localhost/E-vooting%20OSIS/' . $path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $pageHtml = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $hasTopbar = strpos($pageHtml, 'dashboard-topbar') !== false;
        $hasDashboardFooter = strpos($pageHtml, 'dashboard-footer') !== false;
        $hasPublicNavbar = strpos($pageHtml, 'navbar-custom') !== false;
        $hasPublicFooter = strpos($pageHtml, 'site-footer') !== false;

        $statusStr = ($code === 200 && $hasTopbar && $hasDashboardFooter && !$hasPublicNavbar && !$hasPublicFooter)
            ? "[PASS]" : "[FAIL]";

        echo "{$statusStr} {$path} (HTTP {$code}) | Topbar: " . ($hasTopbar ? 'YES' : 'NO') 
             . " | DashFooter: " . ($hasDashboardFooter ? 'YES' : 'NO') 
             . " | NoPublicNav: " . (!$hasPublicNavbar ? 'YES' : 'NO') 
             . " | NoPublicFooter: " . (!$hasPublicFooter ? 'YES' : 'NO') . "\n";
    }

    @unlink($cookieJar);
}

// 1. Admin Test
testRoleAccess('admin', 'admin123', [
    'admin/dashboard.php',
    'admin/verification.php',
    'admin/students.php',
    'admin/teachers.php',
    'admin/elections.php',
    'admin/candidates.php',
    'admin/voters.php',
    'admin/results.php',
    'admin/reports.php',
    'admin/logs.php',
    'admin/settings.php',
    'admin/profile.php',
], 'ADMINISTRATOR');

// 2. Guru Test
testRoleAccess('198501152010011001', 'guru123', [
    'guru/dashboard.php',
    'guru/candidates.php',
    'guru/info.php',
    'guru/status.php',
    'guru/profile.php',
], 'DEWAN GURU');

// 3. Siswa Test
testRoleAccess('0061234567', 'siswa123', [
    'siswa/dashboard.php',
    'siswa/candidates.php',
    'siswa/info.php',
    'siswa/status.php',
    'siswa/profile.php',
], 'SISWA');
