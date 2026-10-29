<?php
// app/Helpers/ValidationHelper.php

class ValidationHelper {
    public static function validateNISN(string $nisn): bool {
        // NISN must be 10 digits
        return (bool)preg_match('/^[0-9]{10}$/', trim($nisn));
    }

    public static function validateIdentifier(string $id): bool {
        // Teacher identifier: 5-32 alphanumeric
        return (bool)preg_match('/^[A-Za-z0-9\-_.]{5,32}$/', trim($id));
    }

    public static function validateEmail(string $email): bool {
        return (bool)filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }

    public static function validatePassword(string $password, string $confirmPassword): ?string {
        if (strlen($password) < 6) {
            return "Password minimal harus 6 karakter.";
        }
        if ($password !== $confirmPassword) {
            return "Konfirmasi password tidak cocok dengan password yang dimasukkan.";
        }
        return null;
    }

    /**
     * Upload foto kandidat secara aman
     */
    public static function handlePhotoUpload(array $file, string $targetDir): array {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'filename' => null];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Terjadi kesalahan saat mengunggah file.'];
        }

        // Limit size: 2MB max
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Ukuran file foto maksimal adalah 2 MB.'];
        }

        // Validate MIME type with finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (!array_key_exists($mimeType, $allowedMimes)) {
            return ['success' => false, 'error' => 'Format file tidak diizinkan. Hanya format JPG, JPEG, PNG, dan WEBP yang diterima.'];
        }

        // Check file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return ['success' => false, 'error' => 'Ekstensi file tidak valid.'];
        }

        // Ensure upload directory exists
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        // Generate cryptographically random filename
        $safeName = 'cand_' . bin2hex(random_bytes(12)) . '.' . $allowedMimes[$mimeType];
        $destPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'error' => 'Gagal memindahkan file foto ke direktori tujuan.'];
        }

        return ['success' => true, 'filename' => $safeName];
    }

    /**
     * Defense against CSV formula injection
     */
    public static function sanitizeCsvValue(mixed $value): string {
        $str = (string)$value;
        if ($str === '') {
            return '';
        }

        $firstChar = $str[0];
        // If value starts with =, +, -, @, tab, or carriage return, prefix with single quote
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $str;
        }

        return $str;
    }
}
