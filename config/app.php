<?php
// config/app.php
// Konfigurasi Utama Aplikasi E-Voting OSIS SMK Muthia Harapan Cicalengka

// 1. Timezone Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// 2. Error Reporting
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$logDir = dirname(__DIR__) . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
ini_set('error_log', $logDir . '/app.log');

// 3. Konstanta Aplikasi
define('APP_NAME', 'E-Voting OSIS');
define('SCHOOL_NAME', 'SMK Muthia Harapan Cicalengka');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_DIR', PUBLIC_PATH . '/uploads/candidates');

// 4. Session Protection
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie
    $cookieParams = [
        'lifetime' => 86400, // 24 hours
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}

// 5. URL Helpers
function base_url(string $path = ''): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Calculate script root directory relative to DOCUMENT_ROOT
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    
    // If the path contains /E-vooting OSIS
    if (stripos($scriptName, '/E-vooting OSIS') !== false) {
        $root = '/E-vooting%20OSIS';
    } else {
        $root = '';
    }
    
    $cleanPath = ltrim($path, '/');
    return $protocol . $host . $root . ($cleanPath ? '/' . $cleanPath : '');
}

function asset(string $path = ''): string {
    $cleanPath = ltrim($path, '/');
    return base_url('public/' . $cleanPath);
}

// 6. Flash Message Helpers
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type] = $message;
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function has_flash(string $type): bool {
    return isset($_SESSION['flash'][$type]);
}

// 7. Sanitization Helper
function sanitize(?string $data): string {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// 8. Application Settings & Logo Helper
require_once dirname(__DIR__) . '/app/Helpers/SettingHelper.php';

function app_setting(string $key, ?string $default = null): ?string {
    return SettingHelper::get($key, $default);
}

function app_logo(): string {
    return SettingHelper::getLogoUrl();
}

