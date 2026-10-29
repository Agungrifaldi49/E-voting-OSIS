<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();
$row = $pdo->query("SELECT username, password_hash, role FROM users WHERE username = '3204252301040001'")->fetch();
$candidates = ['123456', 'guru123', 'admin123', 'password', '3204252301040001'];
foreach ($candidates as $cand) {
    if (password_verify($cand, $row['password_hash'])) {
        echo "MATCH: $cand\n";
        exit(0);
    }
}
echo "NO COMMON MATCH, setting known test password 'guru123'...\n";
$hash = password_hash('guru123', PASSWORD_BCRYPT);
$pdo->prepare("UPDATE users SET password_hash = ? WHERE username = '3204252301040001'")->execute([$hash]);
echo "PASSWORD SET TO: guru123\n";
