<?php
// tests/test_auth_flow.php
$cookieJar = __DIR__ . '/cookie.txt';
if (file_exists($cookieJar)) unlink($cookieJar);

// 1. Ambil halaman login dan extract CSRF token
$ch = curl_init('http://localhost/E-vooting%20OSIS/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $matches);
$csrfToken = $matches[1] ?? '';
echo "CSRF Token didapatkan: " . substr($csrfToken, 0, 10) . "...\n";

// 2. Kirim POST login sebagai Admin
$ch = curl_init('http://localhost/E-vooting%20OSIS/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrfToken,
    'username'   => 'admin',
    'password'   => 'admin123'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "URL setelah login admin: {$finalUrl}\n";
if (strpos($finalUrl, 'admin/dashboard.php') !== false) {
    echo "SUCCESS: Admin berhasil diarahkan ke admin/dashboard.php!\n";
} else {
    echo "FAILED: Login tidak diarahkan ke dashboard.\n";
}

// 3. Tes akses halaman admin/dashboard.php dengan cookie sesi
$ch = curl_init('http://localhost/E-vooting%20OSIS/admin/dashboard.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$dashHtml = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status akses admin/dashboard.php: HTTP {$httpCode}\n";
if (strpos($dashHtml, 'Dashboard Administrator') !== false) {
    echo "SUCCESS: Konten dashboard admin berhasil dimuat dengan benar!\n";
} else {
    echo "FAILED: Konten dashboard admin tidak ditemukan.\n";
}

if (file_exists($cookieJar)) unlink($cookieJar);
