<?php
// app/Helpers/SettingHelper.php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once __DIR__ . '/LogHelper.php';

class SettingHelper {
    private static ?array $cache = null;

    public static function getAll(): array {
        if (self::$cache === null) {
            try {
                $pdo = Database::getConnection();
                $rows = $pdo->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll();
                self::$cache = [];
                foreach ($rows as $r) {
                    self::$cache[$r['setting_key']] = $r['setting_value'];
                }
            } catch (Exception $e) {
                self::$cache = [];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, ?string $default = null): ?string {
        $all = self::getAll();
        if (isset($all[$key]) && $all[$key] !== '') {
            return $all[$key];
        }
        return $default;
    }

    public static function set(string $key, ?string $value): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("REPLACE INTO app_settings (setting_key, setting_value, updated_at) VALUES (:k, :v, NOW())");
        $stmt->execute([':k' => $key, ':v' => $value]);
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    public static function setMultiple(array $data): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("REPLACE INTO app_settings (setting_key, setting_value, updated_at) VALUES (:k, :v, NOW())");
        foreach ($data as $k => $v) {
            $stmt->execute([':k' => $k, ':v' => (string)$v]);
            if (self::$cache !== null) {
                self::$cache[$k] = (string)$v;
            }
        }
    }

    public static function getLogoUrl(): string {
        $logo = self::get('logo_url');
        if (!empty($logo)) {
            $cleanPath = ltrim($logo, '/');
            // If it's a relative path in public/
            if (file_exists(PUBLIC_PATH . '/' . $cleanPath)) {
                return asset($cleanPath);
            }
        }

        // Check fallback existing files
        if (file_exists(PUBLIC_PATH . '/uploads/settings/logo.png')) {
            return asset('uploads/settings/logo.png');
        }
        if (file_exists(PUBLIC_PATH . '/assets/images/logo.png')) {
            return asset('assets/images/logo.png');
        }

        return asset('assets/images/logo-placeholder.svg');
    }

    public static function uploadLogo(array $file): array {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'File logo tidak valid atau tidak diunggah dengan benar.'];
        }

        $maxSize = 3 * 1024 * 1024; // 3MB
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Ukuran file logo maksimal 3MB.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
        if (!in_array($ext, $allowedExts, true)) {
            return ['success' => false, 'error' => 'Format logo harus berupa PNG, JPG, JPEG, WEBP, atau SVG.'];
        }

        // Additional MIME check for safety
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml', 'image/x-svg', 'text/plain', 'text/html'];
        if (!in_array($mime, $allowedMimes, true) && $ext !== 'svg') {
            return ['success' => false, 'error' => 'Tipe file gambar tidak diizinkan.'];
        }

        $targetDir = PUBLIC_PATH . '/uploads/settings';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = 'school_logo_' . time() . '.' . $ext;
        $dest = $targetDir . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'error' => 'Gagal memindahkan file logo yang diunggah ke server.'];
        }

        $relativePath = 'uploads/settings/' . $fileName;
        self::set('logo_url', $relativePath);
        LogHelper::log('LOGO_UPDATED', "Admin mengunggah logo baru sekolah/aplikasi: {$fileName}");

        return ['success' => true, 'path' => $relativePath, 'url' => asset($relativePath)];
    }

    public static function resetLogo(): bool {
        self::set('logo_url', 'assets/images/logo-placeholder.svg');
        LogHelper::log('LOGO_RESET', "Admin mereset logo ke logo default.");
        return true;
    }
}
