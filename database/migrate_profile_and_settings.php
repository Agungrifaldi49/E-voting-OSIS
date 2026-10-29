<?php
// database/migrate_profile_and_settings.php
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();

// 1. Add avatar & full_name columns to users if not exist
$cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
echo "Existing columns in users: " . implode(', ', $cols) . "\n";

if (!in_array('full_name', $cols)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(128) NULL AFTER username");
    echo "[OK] Added 'full_name' column to users.\n";
} else {
    echo "[SKIP] 'full_name' column already exists.\n";
}

if (!in_array('avatar', $cols)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER email");
    echo "[OK] Added 'avatar' column to users.\n";
} else {
    echo "[SKIP] 'avatar' column already exists.\n";
}

// 2. Set default full_name for existing admin if empty
$pdo->exec("UPDATE users SET full_name = 'Administrator Sistem' WHERE role = 'admin' AND (full_name IS NULL OR full_name = '')");

// 3. Ensure comprehensive settings are seeded
$defaultSettings = [
    'school_name'         => 'SMK Muthia Harapan Cicalengka',
    'school_npsn'         => '69900000',
    'app_title'           => 'E-Voting OSIS',
    'app_tagline'         => 'Sistem Pemilihan Ketua & Wakil Ketua OSIS Terpercaya, Jujur & Transparan',
    'academic_year'       => '2026/2027',
    'logo_url'            => 'assets/images/logo-placeholder.svg',
    'school_address'      => 'Jl. Babakan Peuteuy No.300, Babakan Peuteuy, Kec. Cicalengka, Kabupaten Bandung, Jawa Barat 40395',
    'school_phone'        => '(022) 7949000',
    'school_email'        => 'info@smkmuthiaharapan.sch.id',
    'contact_person'      => 'Panitia Pemilu OSIS (Muthia Election Commission)',
    'kepala_sekolah_nama' => 'H. Dadang Sulaeman, S.Pd., M.M.',
    'kepala_sekolah_nip'  => '197508102003121002',
    'ketua_panitia_nama'  => 'Drs. H. Ahmad Fauzi, M.Pd.',
    'ketua_panitia_nip'   => '198501152010011001',
    'pembina_osis_nama'   => 'Siti Rahmawati, S.Pd.',
    'social_instagram'    => '@smkmuthiaharapan',
    'social_youtube'      => 'SMK Muthia Harapan Official',
    'website_url'         => 'https://smkmuthiaharapan.sch.id',
    'voting_guide'        => 'Gunakan hak suara Anda dengan cermat. Pilihan Anda sepenuhnya rahasia dan dilindungi enkripsi sistem.'
];

$stmt = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = COALESCE(setting_value, VALUES(setting_value))");
foreach ($defaultSettings as $k => $v) {
    $stmt->execute([':k' => $k, ':v' => $v]);
}

// 4. Ensure upload directories exist
$uploadSettingsDir = dirname(__DIR__) . '/public/uploads/settings';
$uploadAvatarsDir = dirname(__DIR__) . '/public/uploads/avatars';
if (!is_dir($uploadSettingsDir)) {
    mkdir($uploadSettingsDir, 0777, true);
}
if (!is_dir($uploadAvatarsDir)) {
    mkdir($uploadAvatarsDir, 0777, true);
}

echo "[SUCCESS] Migration and seeding completed successfully!\n";
