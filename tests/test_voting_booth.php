<?php
// tests/test_voting_booth.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Vote.php';
require_once __DIR__ . '/../app/Models/Election.php';

$pdo = Database::getConnection();

// Create/get student
$testNisn = '9990001112';
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u");
$stmt->execute([':u' => $testNisn]);
$existing = $stmt->fetch();

if ($existing) {
    $uid = $existing['id'];
    $pdo->prepare("UPDATE users SET account_status = 'active' WHERE id = :id")->execute([':id' => $uid]);
} else {
    $res = User::createStudent([
        'username' => $testNisn,
        'password' => 'siswa123',
        'nama_lengkap' => 'Tester Siswa Bilik Suara',
        'nisn' => $testNisn,
        'kelas' => 'XII RPL 1',
        'jurusan' => 'RPL'
    ]);
    $uid = $res['user_id'];
    $pdo->prepare("UPDATE users SET account_status = 'active' WHERE id = :id")->execute([':id' => $uid]);
}

$active = Election::getActive();
if ($active) {
    // Ensure in DPT
    $stmt = $pdo->prepare("INSERT IGNORE INTO voting_eligibility (election_id, user_id) VALUES (:eid, :uid)");
    $stmt->execute([':eid' => $active['id'], ':uid' => $uid]);

    // Ensure has NOT voted
    $stmt = $pdo->prepare("DELETE FROM voting_participation WHERE election_id = :eid AND user_id = :uid");
    $stmt->execute([':eid' => $active['id'], ':uid' => $uid]);
}

$cookieJar = tempnam(sys_get_temp_dir(), 'vote_booth_cookie_');

// 1. Get Login CSRF
$ch = curl_init('http://localhost/E-vooting%20OSIS/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $matches);
$csrf = $matches[1] ?? '';

// 2. Login as the fresh student
$ch = curl_init('http://localhost/E-vooting%20OSIS/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrf,
    'username' => $testNisn,
    'password' => 'siswa123',
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);
curl_close($ch);

// 3. Request voting.php
$ch = curl_init('http://localhost/E-vooting%20OSIS/voting.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$votingHtml = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$hasBoothHeader = strpos($votingHtml, 'Bilik Suara Elektronik') !== false;
$hasBoothFooter = strpos($votingHtml, 'Dijamin Rahasia') !== false;
$hasPublicNavbar = strpos($votingHtml, 'navbar-custom') !== false;
$hasPublicFooter = strpos($votingHtml, 'site-footer') !== false;

echo "=== Pengujian Bilik Suara Elektronik (voting.php) ===\n";
echo "HTTP Code: {$httpCode}\n";
echo "Booth Header: " . ($hasBoothHeader ? 'YES' : 'NO') . "\n";
echo "Booth Footer: " . ($hasBoothFooter ? 'YES' : 'NO') . "\n";
echo "No Public Nav: " . (!$hasPublicNavbar ? 'YES' : 'NO') . "\n";
echo "No Public Footer: " . (!$hasPublicFooter ? 'YES' : 'NO') . "\n";

if ($httpCode === 200 && $hasBoothHeader && $hasBoothFooter && !$hasPublicNavbar && !$hasPublicFooter) {
    echo "[PASS] Bilik Suara menggunakan tata letak eksklusif, bebas dari navbar/footer index!\n";
} else {
    echo "[FAIL] Bilik Suara belum sesuai spesifikasi.\n";
    if ($httpCode !== 200) {
        echo "Response excerpt: " . substr($votingHtml, 0, 300) . "\n";
    }
}

@unlink($cookieJar);

// Clean up test user so it doesn't pollute the live database
if (!empty($uid)) {
    User::deleteUser($uid);
}
