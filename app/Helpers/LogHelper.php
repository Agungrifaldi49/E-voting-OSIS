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
}
