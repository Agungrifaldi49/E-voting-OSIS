<?php
// app/Controllers/AdminController.php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once __DIR__ . '/../Helpers/AuthHelper.php';
require_once __DIR__ . '/../Helpers/CsrfHelper.php';
require_once __DIR__ . '/../Helpers/LogHelper.php';
require_once __DIR__ . '/../Helpers/ValidationHelper.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Election.php';
require_once __DIR__ . '/../Models/Candidate.php';
require_once __DIR__ . '/../Models/Vote.php';
require_once __DIR__ . '/../Models/Registry.php';

class AdminController {
    public static function getDashboardStats(): array {
        $pdo = Database::getConnection();

        // 1. Total counts
        $totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();
        $totalTeachers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guru'")->fetchColumn();
        $pendingUsers  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE account_status = 'pending'")->fetchColumn();
        $activeUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE account_status = 'active'")->fetchColumn();

        // 2. Active election info
        $activeElection = Election::getActive();
        $candidateCount = 0;
        $turnout = [
            'total_eligible' => 0,
            'total_voted'    => 0,
            'total_unvoted'  => 0,
            'turnout_pct'    => 0
        ];
        $results = [];

        if ($activeElection) {
            $candStmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE election_id = :eid");
            $candStmt->execute([':eid' => $activeElection['id']]);
            $candidateCount = (int)$candStmt->fetchColumn();

            $turnout = Vote::getParticipationStats($activeElection['id']);
            $results = Vote::getResults($activeElection['id']);
        }

        // 3. Recent activity logs
        $logsStmt = $pdo->query("
            SELECT al.*, u.username, u.role
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.id DESC
            LIMIT 8
        ");
        $recentLogs = $logsStmt->fetchAll();

        // 4. Pending verifications list preview
        $pendingList = User::getPendingRegistrations();

        return [
            'total_students'   => $totalStudents,
            'total_teachers'   => $totalTeachers,
            'pending_users'    => $pendingUsers,
            'active_users'     => $activeUsers,
            'active_election'  => $activeElection,
            'candidate_count'  => $candidateCount,
            'turnout'          => $turnout,
            'results'          => $results,
            'recent_logs'      => $recentLogs,
            'pending_list'     => $pendingList
        ];
    }

    public static function verifyUser(): void {
        AuthHelper::requireRole('admin');
        CsrfHelper::validateOrAbort();

        $userId = (int)($_POST['user_id'] ?? 0);
        $action = trim($_POST['verification_action'] ?? '');
        $reason = trim($_POST['rejection_reason'] ?? '');

        $redirectTo = trim($_POST['redirect_to'] ?? '');
        $defaultRedirect = base_url('admin/verification.php');
        $finalRedirect = (!empty($redirectTo) && strpos($redirectTo, base_url()) === 0) ? $redirectTo : $defaultRedirect;

        if (!$userId || !in_array($action, ['approve', 'reject', 'activate', 'deactivate', 'delete'], true)) {
            set_flash('error', 'Aksi tidak valid.');
            header("Location: " . $finalRedirect);
            exit;
        }

        $targetUser = User::findById($userId);
        if (!$targetUser) {
            set_flash('error', 'Pengguna tidak ditemukan.');
            header("Location: " . $finalRedirect);
            exit;
        }

        switch ($action) {
            case 'approve':
                User::updateStatus($userId, 'active');
                LogHelper::log('USER_VERIFIED', "Admin menyetujui akun {$targetUser['username']} ({$targetUser['role']}).");
                set_flash('success', "Akun {$targetUser['username']} ({$targetUser['nama_lengkap']}) berhasil diverifikasi dan diaktifkan.");
                break;

            case 'reject':
                User::updateStatus($userId, 'rejected', $reason ?: 'Data pendaftaran tidak valid.');
                LogHelper::log('USER_REJECTED', "Admin menolak pendaftaran akun {$targetUser['username']}. Alasan: {$reason}");
                set_flash('warning', "Pendaftaran akun {$targetUser['username']} telah ditolak.");
                break;

            case 'deactivate':
                User::updateStatus($userId, 'inactive');
                LogHelper::log('USER_DEACTIVATED', "Admin menonaktifkan akun {$targetUser['username']}.");
                set_flash('info', "Akun {$targetUser['username']} telah dinonaktifkan.");
                break;

            case 'activate':
                User::updateStatus($userId, 'active');
                LogHelper::log('USER_ACTIVATED', "Admin mengaktifkan kembali akun {$targetUser['username']}.");
                set_flash('success', "Akun {$targetUser['username']} telah diaktifkan kembali.");
                break;

            case 'delete':
                $res = User::deleteUser($userId);
                if ($res['success']) {
                    $roleLabel = ($targetUser['role'] === 'siswa') ? 'siswa' : 'dewan guru';
                    set_flash('success', "Data {$roleLabel} <strong>{$targetUser['nama_lengkap']}</strong> ({$targetUser['username']}) berhasil dihapus dari sistem.");
                } else {
                    set_flash('error', $res['error'] ?? 'Gagal menghapus data.');
                }
                break;
        }

        header("Location: " . $finalRedirect);
        exit;
    }

    public static function importStudents(): void {
        AuthHelper::requireRole('admin');
        CsrfHelper::validateOrAbort();

        if (empty($_FILES['csv_file']['tmp_name'])) {
            set_flash('error', 'Silakan pilih file CSV daftar siswa terlebih dahulu.');
            header("Location: " . base_url('admin/import-students.php'));
            exit;
        }

        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            set_flash('error', 'File harus berekstensi .csv');
            header("Location: " . base_url('admin/import-students.php'));
            exit;
        }

        $res = Registry::importStudentsCsv($file['tmp_name']);
        if (!$res['success']) {
            set_flash('error', $res['error']);
        } else {
            LogHelper::log('CSV_IMPORT_STUDENTS', "Import CSV siswa selesai. Berhasil: {$res['imported']}, Dilewati: {$res['skipped']}");
            $msg = "Import data siswa selesai! Berhasil: <strong>{$res['imported']}</strong> data, Dilewati: <strong>{$res['skipped']}</strong>.";
            if (!empty($res['errors'])) {
                $msg .= "<br><small class='text-muted'>Catatan kendala: " . implode('; ', array_slice($res['errors'], 0, 5)) . "</small>";
            }
            set_flash('success', $msg);
        }

        header("Location: " . base_url('admin/import-students.php'));
        exit;
    }

    public static function importTeachers(): void {
        AuthHelper::requireRole('admin');
        CsrfHelper::validateOrAbort();

        if (empty($_FILES['csv_file']['tmp_name'])) {
            set_flash('error', 'Silakan pilih file CSV daftar guru terlebih dahulu.');
            header("Location: " . base_url('admin/import-teachers.php'));
            exit;
        }

        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            set_flash('error', 'File harus berekstensi .csv');
            header("Location: " . base_url('admin/import-teachers.php'));
            exit;
        }

        $res = Registry::importTeachersCsv($file['tmp_name']);
        if (!$res['success']) {
            set_flash('error', $res['error']);
        } else {
            LogHelper::log('CSV_IMPORT_TEACHERS', "Import CSV guru selesai. Berhasil: {$res['imported']}, Dilewati: {$res['skipped']}");
            $msg = "Import data guru selesai! Berhasil: <strong>{$res['imported']}</strong> data, Dilewati: <strong>{$res['skipped']}</strong>.";
            if (!empty($res['errors'])) {
                $msg .= "<br><small class='text-muted'>Catatan: " . implode('; ', array_slice($res['errors'], 0, 5)) . "</small>";
            }
            set_flash('success', $msg);
        }

        header("Location: " . base_url('admin/import-teachers.php'));
        exit;
    }
}
