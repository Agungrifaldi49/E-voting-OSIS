<?php
// app/Helpers/AuthHelper.php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once __DIR__ . '/LogHelper.php';

class AuthHelper {
    public static function check(): bool {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    public static function isLoggedIn(): bool {
        return self::check();
    }

    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int {
        return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
    }

    public static function role(): ?string {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function nama(): string {
        return $_SESSION['user']['nama_lengkap'] ?? ($_SESSION['user']['username'] ?? 'Pengguna');
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            set_flash('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
            header("Location: " . base_url('login.php'));
            exit;
        }

        // Verify that account is still active in DB
        $status = self::user()['account_status'] ?? '';
        if ($status !== 'active') {
            self::logout();
            set_flash('error', 'Akun Anda belum aktif atau sedang dalam peninjauan verifikasi admin.');
            header("Location: " . base_url('login.php'));
            exit;
        }
    }

    public static function requireRole(array|string $roles): void {
        self::requireLogin();
        $allowedRoles = is_array($roles) ? $roles : [$roles];
        $currentRole = self::role();

        if (!in_array($currentRole, $allowedRoles, true)) {
            http_response_code(403);
            die("Error 403: Akses ditolak. Anda tidak memiliki izin untuk membuka halaman ini.");
        }
    }

    public static function login(array $userData): void {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'             => (int)$userData['id'],
            'username'       => $userData['username'],
            'email'          => $userData['email'] ?? '',
            'role'           => $userData['role'],
            'account_status' => $userData['account_status'],
            'nama_lengkap'   => $userData['nama_lengkap'] ?? $userData['username'],
            'kelas'          => $userData['kelas'] ?? '',
            'jurusan'        => $userData['jurusan'] ?? '',
            'identifier'     => $userData['employee_identifier'] ?? $userData['nisn'] ?? $userData['username'],
            'avatar'         => $userData['avatar'] ?? null
        ];

        LogHelper::log('AUTH_LOGIN', "Pengguna {$userData['username']} ({$userData['role']}) berhasil login.", (int)$userData['id']);
    }

    public static function logout(): void {
        if (self::check()) {
            $user = self::user();
            LogHelper::log('AUTH_LOGOUT', "Pengguna {$user['username']} telah logout.", $user['id']);
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }

    // Rate Limiting untuk Proteksi Brute Force Login
    public static function isRateLimited(string $identifier, int $maxAttempts = 5, int $decaySeconds = 300): bool {
        try {
            $pdo = Database::getConnection();
            $now = time();
            $cutoff = $now - $decaySeconds;

            // Clean up old limits
            $cleanStmt = $pdo->prepare("DELETE FROM login_rate_limits WHERE attempt_time < :cutoff");
            $cleanStmt->execute([':cutoff' => $cutoff]);

            // Count attempts
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as attempts FROM login_rate_limits
                WHERE identifier = :identifier AND attempt_time >= :cutoff
            ");
            $stmt->execute([':identifier' => $identifier, ':cutoff' => $cutoff]);
            $row = $stmt->fetch();

            return ($row && (int)$row['attempts'] >= $maxAttempts);
        } catch (Exception $e) {
            error_log("Rate limiting error: " . $e->getMessage());
            return false;
        }
    }

    public static function recordFailedAttempt(string $identifier): void {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("INSERT INTO login_rate_limits (identifier, attempt_time) VALUES (:identifier, :time)");
            $stmt->execute([':identifier' => $identifier, ':time' => time()]);
        } catch (Exception $e) {
            error_log("Failed to record login attempt: " . $e->getMessage());
        }
    }

    public static function clearLoginAttempts(string $identifier): void {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM login_rate_limits WHERE identifier = :identifier");
            $stmt->execute([':identifier' => $identifier]);
        } catch (Exception $e) {
            error_log("Failed to clear login attempts: " . $e->getMessage());
        }
    }
}
