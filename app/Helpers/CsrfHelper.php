<?php
// app/Helpers/CsrfHelper.php

class CsrfHelper {
    public static function generateToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function getToken(): string {
        return self::generateToken();
    }

    public static function getTokenField(): string {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verifyToken(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function validateOrAbort(): void {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::verifyToken($token)) {
            http_response_code(403);
            die("Error 403: Invalid CSRF Token. Permintaan tidak sah atau sesi Anda telah kedaluwarsa. Silakan muat ulang halaman.");
        }
    }
}
