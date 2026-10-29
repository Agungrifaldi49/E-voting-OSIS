<?php
// tests/test_endpoints.php
$urls = [
    'http://localhost/E-vooting%20OSIS/',
    'http://localhost/E-vooting%20OSIS/login.php',
    'http://localhost/E-vooting%20OSIS/register.php',
    'http://localhost/E-vooting%20OSIS/results.php',
    'http://localhost/E-vooting%20OSIS/api/results.php',
    'http://localhost/E-vooting%20OSIS/api/participation.php'
];

foreach ($urls as $u) {
    $ch = curl_init($u);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "{$u} -> HTTP {$httpCode}\n";
}
