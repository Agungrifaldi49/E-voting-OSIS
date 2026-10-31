<?php
// app/Helpers/LogHelper.php
require_once dirname(__DIR__, 2) . '/config/database.php';

class LogHelper {
    public static function log(string $action, string $description, ?int $userId = null): void {
        try {
            $pdo = Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250);

            if ($userId === null && isset($_SESSION['user']['id'])) {
                $userId = (int)$_SESSION['user']['id'];
            }

            // Ensure no sensitive keywords like password/tokens in description
            $safeDescription = preg_replace('/(password|token|hash)\s*=\s*[^\s,]+/i', '$1=***REDACTED***', $description);

            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at)
                VALUES (:user_id, :action, :description, :ip, :ua, NOW())
            ");
            $stmt->execute([
                ':user_id'     => $userId,
                ':action'      => substr($action, 0, 60),
                ':description' => $safeDescription,
                ':ip'          => $ip,
                ':ua'          => $userAgent
            ]);
        } catch (Exception $e) {
            error_log("Failed to write activity log: " . $e->getMessage());
        }
    }

    /**
     * Mengambil statistik ringkas tabel activity_logs (jumlah, tanggal, perkiraan ukuran)
     */
    public static function getStats(): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_count,
                    MIN(created_at) as oldest_date,
                    MAX(created_at) as newest_date
                FROM activity_logs
            ");
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            $dbName = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
            $sizeStmt = $pdo->prepare("
                SELECT (data_length + index_length) as table_size
                FROM information_schema.TABLES
                WHERE table_schema = :db AND table_name = 'activity_logs'
            ");
            $sizeStmt->execute([':db' => $dbName]);
            $sizeBytes = (int)$sizeStmt->fetchColumn();

            if ($sizeBytes >= 1048576) {
                $formattedSize = round($sizeBytes / 1048576, 2) . ' MB';
            } elseif ($sizeBytes >= 1024) {
                $formattedSize = round($sizeBytes / 1024, 2) . ' KB';
            } else {
                $formattedSize = $sizeBytes . ' B';
            }

            return [
                'total_count'    => (int)($data['total_count'] ?? 0),
                'oldest_date'    => $data['oldest_date'],
                'newest_date'    => $data['newest_date'],
                'size_bytes'     => $sizeBytes,
                'formatted_size' => $formattedSize
            ];
        } catch (Exception $e) {
            return [
                'total_count'    => 0,
                'oldest_date'    => null,
                'newest_date'    => null,
                'size_bytes'     => 0,
                'formatted_size' => '0 KB'
            ];
        }
    }

    /**
     * Membersihkan log lama berdasarkan kriteria untuk menjaga performa dan kapasitas penyimpanan
     */
    public static function purgeLogs(string $mode): array {
        try {
            $pdo = Database::getConnection();
            $deletedCount = 0;
            $label = '';

            switch ($mode) {
                case 'older_7_days':
                    $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
                    $stmt->execute();
                    $deletedCount = $stmt->rowCount();
                    $label = 'lebih lama dari 7 hari';
                    break;

                case 'older_30_days':
                    $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    $stmt->execute();
                    $deletedCount = $stmt->rowCount();
                    $label = 'lebih lama dari 30 hari';
                    break;

                case 'older_90_days':
                    $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
                    $stmt->execute();
                    $deletedCount = $stmt->rowCount();
                    $label = 'lebih lama dari 90 hari';
                    break;

                case 'keep_latest_100':
                    $total = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
                    if ($total > 100) {
                        $stmt = $pdo->prepare("
                            DELETE FROM activity_logs 
                            WHERE id NOT IN (
                                SELECT id FROM (
                                    SELECT id FROM activity_logs ORDER BY id DESC LIMIT 100
                                ) AS keep_rows
                            )
                        ");
                        $stmt->execute();
                        $deletedCount = $stmt->rowCount();
                    }
                    $label = 'selain 100 entri terbaru';
                    break;

                case 'all':
                    $deletedCount = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
                    $pdo->exec("DELETE FROM activity_logs");
                    $label = 'seluruh riwayat log';
                    break;

                default:
                    return ['success' => false, 'error' => 'Kriteria pembersihan log tidak valid.'];
            }

            // Catat log pembersihan itu sendiri
            if (isset($_SESSION['user']['id'])) {
                self::log('LOGS_PURGED', "Admin membersihkan riwayat log ({$label}). Sebanyak {$deletedCount} entri dihapus.");
            }

            return [
                'success' => true,
                'count'   => $deletedCount,
                'message' => "Pembersihan selesai! Sebanyak {$deletedCount} entri log ({$label}) berhasil dibersihkan dari database."
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Gagal membersihkan log: ' . $e->getMessage()];
        }
    }
}
