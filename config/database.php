<?php
// config/database.php
// Konfigurasi Koneksi Database MySQL / MariaDB via PDO

class Database {
    private static ?PDO $instance = null;

    // =========================================================================
    // PENGATURAN KONEKSI DATABASE
    // =========================================================================
    // Catatan untuk Hosting cPanel (Jagoan Hosting / Niagahoster / dll):
    // 1. DB_HOST: Wajib gunakan 'localhost' (bukan 127.0.0.1) karena cPanel menggunakan UNIX socket.
    // 2. DB_NAME: Wajib sertakan prefix cPanel Anda (contoh: 'username_db_evoting').
    // 3. DB_USER: Wajib sertakan prefix cPanel Anda (contoh: 'username_dbuser').
    // 4. DB_PASS: Password user database yang dibuat di menu 'MySQL Databases' cPanel.
    // 5. PENTING: Di cPanel, pastikan Anda sudah melakukan langkah "Add User To Database"
    //    dan mencentang "ALL PRIVILEGES" agar user memiliki izin mengakses database!
    // =========================================================================
    private const DB_HOST = 'localhost';
    private const DB_PORT = '3306';
    private const DB_NAME = 'db_evoting_osis';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_CHARSET = 'utf8mb4';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // Support .env file if present in project root
            self::loadEnv();

            $host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : self::DB_HOST);
            $port = getenv('DB_PORT') ?: (defined('DB_PORT') ? DB_PORT : self::DB_PORT);
            $name = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : self::DB_NAME);
            $user = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : self::DB_USER);
            $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (defined('DB_PASS') ? DB_PASS : self::DB_PASS);
            $charset = self::DB_CHARSET;

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}, time_zone = '+07:00'"
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // Log error internally
                error_log("Database connection error: " . $e->getMessage());
                http_response_code(500);

                // Render error diagnostik yang jelas dan solutif
                self::renderError($e->getMessage(), $host, $port, $name, $user);
                exit;
            }
        }
        return self::$instance;
    }

    private static function loadEnv(): void {
        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile) && is_readable($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                if (strpos($line, '=') !== false) {
                    [$k, $v] = explode('=', $line, 2);
                    $k = trim($k);
                    $v = trim($v);
                    if ((str_starts_with($v, '"') && str_ends_with($v, '"')) ||
                        (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
                        $v = substr($v, 1, -1);
                    }
                    putenv("{$k}={$v}");
                    $_ENV[$k] = $v;
                }
            }
        }
    }

    private static function renderError(string $errorMsg, string $host, string $port, string $dbname, string $dbuser): void {
        $isApi = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                 || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false);
        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Gagal terhubung ke database MySQL.',
                'message' => $errorMsg,
                'target'  => ['host' => $host, 'database' => $dbname, 'user' => $dbuser]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return;
        }

        echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koneksi Database Gagal - E-Voting OSIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card-diagnostic { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); max-width: 720px; width: 100%; overflow: hidden; }
        .diagnostic-header { background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); padding: 24px 30px; }
        .param-badge { background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 8px 14px; font-family: monospace; font-size: 0.85rem; color: #38bdf8; }
        .step-num { width: 28px; height: 28px; border-radius: 50%; background: #3b82f6; color: white; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; margin-right: 10px; flex-shrink: 0; }
    </style>
</head>
<body>
    <div class="card-diagnostic">
        <div class="diagnostic-header text-white">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                <div>
                    <h4 class="mb-1 fw-bold">Gagal Terhubung ke Database MySQL</h4>
                    <p class="mb-0 small text-white-50">Sistem E-Voting OSIS tidak dapat terhubung dengan parameter database yang aktif.</p>
                </div>
            </div>
        </div>
        <div class="p-4 p-md-5">
            <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger-emphasis mb-4">
                <div class="fw-bold mb-1"><i class="bi bi-bug me-1"></i> Respon Error dari MySQL Server:</div>
                <code class="text-danger fw-semibold d-block p-2 bg-dark bg-opacity-25 rounded mt-2" style="word-break: break-all;">' . htmlspecialchars($errorMsg) . '</code>
            </div>

            <h6 class="fw-bold mb-3 text-uppercase small" style="color: #94a3b8;">Parameter yang Sedang Digunakan:</h6>
            <div class="row g-2 mb-4">
                <div class="col-sm-6"><div class="param-badge"><span class="text-secondary">Host:</span> ' . htmlspecialchars($host) . '</div></div>
                <div class="col-sm-6"><div class="param-badge"><span class="text-secondary">Port:</span> ' . htmlspecialchars($port) . '</div></div>
                <div class="col-sm-6"><div class="param-badge"><span class="text-secondary">Database:</span> ' . htmlspecialchars($dbname) . '</div></div>
                <div class="col-sm-6"><div class="param-badge"><span class="text-secondary">User:</span> ' . htmlspecialchars($dbuser) . '</div></div>
            </div>

            <div class="p-3 rounded-3 mb-4" style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2);">
                <h6 class="fw-bold text-info mb-3"><i class="bi bi-lightbulb me-1"></i> Langkah Perbaikan untuk Hosting (cPanel / Jagoan Hosting):</h6>
                <div class="d-flex align-items-start mb-2">
                    <span class="step-num">1</span>
                    <div><strong>Gunakan DB_HOST = \'localhost\'</strong><br><small class="text-muted">Di cPanel Linux, MySQL menggunakan UNIX socket sehingga host wajib <code>localhost</code> (bukan <code>127.0.0.1</code>).</small></div>
                </div>
                <div class="d-flex align-items-start mb-2">
                    <span class="step-num">2</span>
                    <div><strong>Gunakan Prefix Akun cPanel</strong><br><small class="text-muted">Nama database dan user di cPanel wajib menyertakan prefix cPanel Anda, contoh: <code>jagoan_db_evoting</code> &amp; <code>jagoan_admin</code>.</small></div>
                </div>
                <div class="d-flex align-items-start mb-2">
                    <span class="step-num">3</span>
                    <div><strong>Wajib "Add User To Database" dengan All Privileges</strong><br><small class="text-muted">Buka menu <strong>MySQL Databases</strong> di cPanel, scroll ke <em>Add User To Database</em>, pilih User dan Database, klik <strong>Add</strong>, lalu centang <strong>ALL PRIVILEGES</strong> &amp; <strong>Make Changes</strong>.</small></div>
                </div>
                <div class="d-flex align-items-start">
                    <span class="step-num">4</span>
                    <div><strong>Buka file config/database.php di File Manager</strong><br><small class="text-muted">Sesuaikan nilai <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, dan <code>DB_PASS</code>.</small></div>
                </div>
            </div>

            <div class="text-center">
                <button onclick="window.location.reload();" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Coba Muat Ulang
                </button>
            </div>
        </div>
    </div>
</body>
</html>';
    }
}
