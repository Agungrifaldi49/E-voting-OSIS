<?php
// database/reset_for_production.php
// Skrip Pembersihan & Reset Data Pengujian ke Status Siap Pakai (Production Go-Live)
// E-Voting OSIS SMK Muthia Harapan Cicalengka

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/LogHelper.php';

class SystemResetHelper {
    /**
     * Menjalankan pembersihan data uji coba dengan aman
     * Mempertahankan:
     * - Akun Administrator (users role = 'admin')
     * - Seluruh Konfigurasi Website & Identitas Sekolah (app_settings)
     * - Logo resmi website
     */
    public static function executeReset(array $options = []): array {
        $clearRegistry   = $options['clear_registry'] ?? true;   // Kosongkan master DPT siswa & guru
        $clearCandidates = $options['clear_candidates'] ?? true; // Kosongkan kandidat & foto paslon
        $clearElections  = $options['clear_elections'] ?? true;  // Kosongkan periode pemilihan
        $clearLogs       = $options['clear_logs'] ?? true;       // Bersihkan riwayat log aktivitas

        $pdo = Database::getConnection();

        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            // 1. Bersihkan Suara & Partisipasi Pemilihan
            $pdo->exec("TRUNCATE TABLE `votes`;");
            $pdo->exec("TRUNCATE TABLE `voting_participation`;");
            $pdo->exec("TRUNCATE TABLE `voting_eligibility`;");

            // 2. Bersihkan Akun Pemilih Siswa & Guru (Non-Admin)
            $pdo->exec("TRUNCATE TABLE `student_profiles`;");
            $pdo->exec("TRUNCATE TABLE `teacher_profiles`;");
            $pdo->exec("DELETE FROM `users` WHERE `role` != 'admin';");
            $pdo->exec("ALTER TABLE `users` AUTO_INCREMENT = 2;");

            // 3. Bersihkan Master Registry DPT jika diminta
            if ($clearRegistry) {
                $pdo->exec("TRUNCATE TABLE `student_registry`;");
                $pdo->exec("TRUNCATE TABLE `teacher_registry`;");
            } else {
                // Jika tidak dikosongkan, reset status registered menjadi unregistered
                $pdo->exec("UPDATE `student_registry` SET `status` = 'unregistered';");
                $pdo->exec("UPDATE `teacher_registry` SET `status` = 'unregistered';");
            }

            // 4. Bersihkan Kandidat & File Foto jika diminta
            if ($clearCandidates) {
                $pdo->exec("TRUNCATE TABLE `candidates`;");
                
                // Hapus file foto kandidat di public/uploads/candidates/ (kecuali folder itu sendiri)
                $candidatesDir = dirname(__DIR__) . '/public/uploads/candidates';
                if (is_dir($candidatesDir)) {
                    $files = glob($candidatesDir . '/*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                        }
                    }
                }
            }

            // 5. Bersihkan Periode Pemilihan jika diminta
            if ($clearElections) {
                $pdo->exec("TRUNCATE TABLE `elections`;");
            }

            // 6. Bersihkan Rate Limits & Log Aktivitas
            $pdo->exec("TRUNCATE TABLE `login_rate_limits`;");
            if ($clearLogs) {
                $pdo->exec("TRUNCATE TABLE `activity_logs`;");
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            // 7. Catat audit log inisialisasi produksi
            LogHelper::log('SYSTEM_READY_FOR_PRODUCTION', 'Sistem E-Voting OSIS telah dibersihkan dari data pengujian dan siap digunakan untuk pemilihan resmi.');

            return [
                'success' => true,
                'message' => 'Sistem berhasil dibersihkan dari seluruh data pengujian. Akun Administrator dan Pengaturan Sekolah tetap aman.'
            ];
        } catch (Exception $e) {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            return [
                'success' => false,
                'error'   => 'Gagal mereset sistem: ' . $e->getMessage()
            ];
        }
    }
}

// Jika dijalankan langsung lewat CLI
if (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    echo "========================================================\n";
    echo " RESET SISTEM E-VOTING OSIS KE STATUS SIAP PAKAI (GO-LIVE)\n";
    echo "========================================================\n";
    echo "PERINGATAN: Tindakan ini akan menghapus data siswa, guru, suara,\n";
    echo "dan pengujian. Akun admin & pengaturan sekolah tetap dipertahankan.\n\n";

    $result = SystemResetHelper::executeReset();
    if ($result['success']) {
        echo "[SUKSES] " . $result['message'] . "\n";
    } else {
        echo "[GAGAL] " . $result['error'] . "\n";
    }
}
